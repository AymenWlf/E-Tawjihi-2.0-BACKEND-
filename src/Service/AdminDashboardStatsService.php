<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\UserProfileRepository;
use App\Repository\TestSessionRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\FiliereRepository;
use App\Repository\ArticleRepository;
use App\Repository\FavorisRepository;
use Doctrine\DBAL\Connection;

/**
 * Agrège les statistiques pour le dashboard admin.
 * Périodes "année scolaire" : octobre à septembre (ex. 2024-2025 = oct. 2024 → sept. 2025).
 */
class AdminDashboardStatsService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserProfileRepository $profileRepository,
        private TestSessionRepository $testSessionRepository,
        private EstablishmentRepository $establishmentRepository,
        private FiliereRepository $filiereRepository,
        private ArticleRepository $articleRepository,
        private FavorisRepository $favorisRepository,
        private Connection $connection,
    ) {
    }

    /**
     * Retourne les bornes (début, fin) d'une année scolaire.
     * Année scolaire Y-(Y+1) = 1er oct. Y → 30 sept. Y+1.
     */
    public function schoolYearBounds(string $schoolYear): ?array
    {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $schoolYear, $m)) {
            return null;
        }
        $y1 = (int) $m[1];
        $y2 = (int) $m[2];
        if ($y2 !== $y1 + 1) {
            return null;
        }
        return [
            'start' => new \DateTimeImmutable($y1 . '-10-01 00:00:00'),
            'end'   => new \DateTimeImmutable($y2 . '-09-30 23:59:59'),
        ];
    }

    /**
     * Année scolaire courante (oct → sept). Ex. en janv. 2025 → "2024-2025".
     */
    public function currentSchoolYear(): string
    {
        $now = new \DateTimeImmutable();
        $y = (int) $now->format('Y');
        $m = (int) $now->format('n');
        if ($m >= 10) {
            return $y . '-' . ($y + 1);
        }
        return ($y - 1) . '-' . $y;
    }

    /**
     * Génère les libellés années scolaires (courante, dernière, etc.).
     */
    public function schoolYearOptions(int $count = 5): array
    {
        $current = $this->currentSchoolYear();
        [$y1] = explode('-', $current);
        $y1 = (int) $y1;
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $yy = $y1 - $i;
            $out[] = ['value' => $yy . '-' . ($yy + 1), 'label' => $yy . '–' . ($yy + 1)];
        }
        return $out;
    }

    /**
     * Statistiques globales du dashboard.
     * Query params : schoolYear (ex. 2024-2025), period=current|last (optionnel).
     */
    public function getStats(?string $schoolYear = null): array
    {
        $period = $schoolYear ?? $this->currentSchoolYear();
        $bounds = $this->schoolYearBounds($period);
        if (!$bounds) {
            $period = $this->currentSchoolYear();
            $bounds = $this->schoolYearBounds($period);
        }

        $start = $bounds['start'];
        $end = $bounds['end'];

        $totalUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $totalProfiles = (int) $this->profileRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $totalOrientationTests = (int) $this->testSessionRepository->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->where('ts.testType = :type')->andWhere('ts.isCompleted = true')
            ->setParameter('type', 'orientation')
            ->getQuery()->getSingleScalarResult();

        $usersInPeriod = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :start')->andWhere('u.createdAt <= :end')
            ->setParameter('start', $start)->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();

        $profilesInPeriod = (int) $this->profileRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt >= :start')->andWhere('p.createdAt <= :end')
            ->setParameter('start', $start)->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();

        $evolutionByMonth = $this->evolutionByMonth($start, $end);
        $byFiliere = $this->aggregateByField('filiere', $start, $end);
        $byNiveau = $this->aggregateByField('niveau', $start, $end);
        $byBacType = $this->aggregateByField('bacType', $start, $end);
        $byVille = $this->aggregateByVille($start, $end);
        $topEstablishmentsByViews = $this->topEstablishmentsByViews(20);
        $topFilieresByViews = $this->topFilieresByViews(20);
        $topArticlesByVues = $this->topArticlesByVues(20);
        $topSecteursByFavoris = $this->topSecteursByFavoris(20);
        $topEstablishmentsByFavoris = $this->topEstablishmentsByFavoris(20);
        $topFilieresByFavoris = $this->topFilieresByFavoris(20);

        return [
            'period' => $period,
            'schoolYearOptions' => $this->schoolYearOptions(),
            'totals' => [
                'users' => $totalUsers,
                'profiles' => $totalProfiles,
                'orientationTests' => $totalOrientationTests,
            ],
            'inPeriod' => [
                'users' => $usersInPeriod,
                'profiles' => $profilesInPeriod,
            ],
            'evolutionByMonth' => $evolutionByMonth,
            'byFiliere' => $byFiliere,
            'byNiveau' => $byNiveau,
            'byBacType' => $byBacType,
            'byVille' => $byVille,
            'topEstablishmentsByViews' => $topEstablishmentsByViews,
            'topFilieresByViews' => $topFilieresByViews,
            'topArticlesByVues' => $topArticlesByVues,
            'topSecteursByFavoris' => $topSecteursByFavoris,
            'topEstablishmentsByFavoris' => $topEstablishmentsByFavoris,
            'topFilieresByFavoris' => $topFilieresByFavoris,
        ];
    }

    private function evolutionByMonth(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $params = ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')];
        $usersSql = "
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(id) AS cnt
            FROM `user`
            WHERE created_at >= :start AND created_at <= :end
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY mois
        ";
        $profilesSql = "
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(id) AS cnt
            FROM user_profile
            WHERE created_at >= :start AND created_at <= :end
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY mois
        ";
        $users = [];
        foreach ($this->connection->executeQuery($usersSql, $params)->fetchAllAssociative() as $r) {
            $users[$r['mois']] = (int) $r['cnt'];
        }
        $profiles = [];
        foreach ($this->connection->executeQuery($profilesSql, $params)->fetchAllAssociative() as $r) {
            $profiles[$r['mois']] = (int) $r['cnt'];
        }
        $months = array_keys($users + $profiles);
        sort($months);
        $byMonth = [];
        foreach ($months as $m) {
            $byMonth[$m] = ['users' => $users[$m] ?? 0, 'profiles' => $profiles[$m] ?? 0];
        }
        return $byMonth;
    }

    private function aggregateByField(string $field, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $conn = $this->connection;
        $allowed = ['filiere' => 'filiere', 'niveau' => 'niveau', 'bacType' => 'bac_type'];
        if (!isset($allowed[$field])) {
            return [];
        }
        $col = $allowed[$field];
        $sql = "
            SELECT p.{$col} AS val, COUNT(p.id) AS cnt
            FROM user_profile p
            WHERE p.created_at >= :start AND p.created_at <= :end AND p.{$col} IS NOT NULL AND p.{$col} != ''
            GROUP BY p.{$col}
            ORDER BY cnt DESC
        ";
        $stmt = $conn->executeQuery($sql, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')]);
        $rows = $stmt->fetchAllAssociative();
        return array_map(fn ($r) => ['label' => $r['val'], 'count' => (int) $r['cnt']], $rows);
    }

    private function aggregateByVille(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = "
            SELECT c.titre AS label, COUNT(p.id) AS cnt
            FROM user_profile p
            JOIN city c ON c.id = p.ville_id
            WHERE p.created_at >= :start AND p.created_at <= :end
            GROUP BY p.ville_id, c.titre
            ORDER BY cnt DESC
        ";
        $stmt = $this->connection->executeQuery($sql, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')]);
        $rows = $stmt->fetchAllAssociative();
        return array_map(fn ($r) => ['label' => $r['label'] ?? '—', 'count' => (int) $r['cnt']], $rows);
    }

    private function topEstablishmentsByViews(int $limit): array
    {
        $qb = $this->establishmentRepository->createQueryBuilder('e')
            ->select('e.id', 'e.nom', 'e.slug', 'e.viewCount')
            ->where('e.isActive = true')
            ->orderBy('e.viewCount', 'DESC')
            ->setMaxResults($limit);
        $rows = $qb->getQuery()->getArrayResult();
        return array_map(fn ($e) => [
            'id' => (int) $e['id'],
            'nom' => $e['nom'] ?? '',
            'slug' => $e['slug'] ?? null,
            'viewCount' => (int) ($e['viewCount'] ?? 0),
        ], $rows);
    }

    private function topFilieresByViews(int $limit): array
    {
        $qb = $this->filiereRepository->createQueryBuilder('f')
            ->select('f.id', 'f.nom', 'f.viewCount')
            ->orderBy('f.viewCount', 'DESC')
            ->setMaxResults($limit);
        $rows = $qb->getQuery()->getArrayResult();
        return array_map(fn ($f) => [
            'id' => (int) $f['id'],
            'nom' => $f['nom'] ?? '',
            'viewCount' => (int) ($f['viewCount'] ?? 0),
        ], $rows);
    }

    private function topArticlesByVues(int $limit): array
    {
        $qb = $this->articleRepository->createQueryBuilder('a')
            ->select('a.id', 'a.titre', 'a.vues', 'a.slug')
            ->where('a.isActivate = true')
            ->orderBy('a.vues', 'DESC')
            ->setMaxResults($limit);
        $rows = $qb->getQuery()->getArrayResult();
        return array_map(fn ($a) => [
            'id' => (int) $a['id'],
            'titre' => $a['titre'] ?? '',
            'vues' => (int) ($a['vues'] ?? 0),
            'slug' => $a['slug'] ?? null,
        ], $rows);
    }

    private function topSecteursByFavoris(int $limit): array
    {
        $sql = "
            SELECT s.id, s.titre, COUNT(f.id) AS cnt
            FROM favoris f
            JOIN secteurs s ON s.id = f.secteur_id
            WHERE f.secteur_id IS NOT NULL
            GROUP BY f.secteur_id, s.id, s.titre
            ORDER BY cnt DESC
            LIMIT " . (int) $limit;
        $stmt = $this->connection->executeQuery($sql);
        return array_map(fn ($r) => ['id' => (int) $r['id'], 'titre' => $r['titre'], 'count' => (int) $r['cnt']], $stmt->fetchAllAssociative());
    }

    private function topEstablishmentsByFavoris(int $limit): array
    {
        $sql = "
            SELECT e.id, e.nom, e.slug, COUNT(f.id) AS cnt
            FROM favoris f
            JOIN establishments e ON e.id = f.establishment_id
            WHERE f.establishment_id IS NOT NULL
            GROUP BY f.establishment_id, e.id, e.nom, e.slug
            ORDER BY cnt DESC
            LIMIT " . (int) $limit;
        $stmt = $this->connection->executeQuery($sql);
        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'nom' => $r['nom'],
            'slug' => $r['slug'] ?? null,
            'count' => (int) $r['cnt'],
        ], $stmt->fetchAllAssociative());
    }

    private function topFilieresByFavoris(int $limit): array
    {
        $sql = "
            SELECT f.id, f.nom, COUNT(fav.id) AS cnt
            FROM favoris fav
            JOIN filieres f ON f.id = fav.filiere_id
            WHERE fav.filiere_id IS NOT NULL
            GROUP BY fav.filiere_id, f.id, f.nom
            ORDER BY cnt DESC
            LIMIT " . (int) $limit;
        $stmt = $this->connection->executeQuery($sql);
        return array_map(fn ($r) => ['id' => (int) $r['id'], 'nom' => $r['nom'] ?? '', 'count' => (int) $r['cnt']], $stmt->fetchAllAssociative());
    }
}
