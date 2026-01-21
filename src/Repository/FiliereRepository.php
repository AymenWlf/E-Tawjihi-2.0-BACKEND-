<?php

namespace App\Repository;

use App\Entity\Filiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Filiere>
 *
 * @method Filiere|null find($id, $lockMode = null, $lockVersion = null)
 * @method Filiere|null findOneBy(array $criteria, array $orderBy = null)
 * @method Filiere[]    findAll()
 * @method Filiere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FiliereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filiere::class);
    }

    /**
     * Trouve une filière par son slug
     */
    public function findOneBySlug(string $slug): ?Filiere
    {
        if (empty($slug)) {
            return null;
        }

        $slug = trim($slug);

        $qb = $this->createQueryBuilder('f')
            ->innerJoin('f.establishment', 'e');
        $filiere = $qb
            ->where('LOWER(f.slug) = LOWER(:slug)')
            ->andWhere('f.isActive = :isActive')
            ->andWhere('e.isActive = :establishmentIsActive')
            ->setParameter('slug', $slug)
            ->setParameter('isActive', true)
            ->setParameter('establishmentIsActive', true)
            ->getQuery()
            ->getOneOrNullResult();

        return $filiere;
    }

    /**
     * Trouve les filières d'un établissement
     */
    public function findByEstablishment(int $establishmentId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.establishment', 'e')
            ->where('f.establishment = :establishmentId')
            ->andWhere('f.isActive = :isActive')
            ->andWhere('e.isActive = :establishmentIsActive')
            ->setParameter('establishmentId', $establishmentId)
            ->setParameter('isActive', true)
            ->setParameter('establishmentIsActive', true)
            ->orderBy('f.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de filières avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('f')
            ->innerJoin('f.establishment', 'e')
            ->where('f.isActive = :isActive')
            ->andWhere('e.isActive = :establishmentIsActive')
            ->setParameter('isActive', true)
            ->setParameter('establishmentIsActive', true);

        if (isset($filters['establishmentId'])) {
            $qb->andWhere('f.establishment = :establishmentId')
               ->setParameter('establishmentId', $filters['establishmentId']);
        }

        if (isset($filters['diplome'])) {
            $qb->andWhere('f.diplome = :diplome')
               ->setParameter('diplome', $filters['diplome']);
        }

        if (isset($filters['langueEtudes'])) {
            $qb->andWhere('f.langueEtudes = :langueEtudes')
               ->setParameter('langueEtudes', $filters['langueEtudes']);
        }

        if (isset($filters['typeEcole'])) {
            $qb->andWhere('f.typeEcole = :typeEcole')
               ->setParameter('typeEcole', $filters['typeEcole']);
        }

        if (isset($filters['recommandee'])) {
            $qb->andWhere('f.recommandee = :recommandee')
               ->setParameter('recommandee', $filters['recommandee']);
        }

        if (isset($filters['search'])) {
            // Recherche insensible aux accents (la collation utf8mb4_unicode_ci le gère déjà)
            $qb->andWhere('LOWER(f.nom) LIKE LOWER(:search) OR LOWER(f.description) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('f.nom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte le nombre de filières directement associées à un secteur
     * Une filière est associée si son champ secteursIds contient l'ID du secteur
     */
    public function countBySecteur(int $secteurId): int
    {
        // Utiliser une requête SQL brute pour une meilleure performance avec JSON
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();
        
        if ($platform === 'mysql' || $platform === 'mariadb') {
            // MySQL/MariaDB : utiliser JSON_CONTAINS avec la valeur JSON encodée
            // JSON_CONTAINS cherche une valeur JSON dans un document JSON
            // On encode le secteurId comme un nombre JSON
            try {
                // Utiliser JSON_CONTAINS avec la valeur JSON du secteurId
                $secteurIdJson = json_encode($secteurId);
                
                $sql = "SELECT COUNT(*) as count 
                        FROM filieres f
                        INNER JOIN establishments e ON f.establishment_id = e.id
                        WHERE f.is_active = 1 
                        AND e.is_active = 1
                        AND f.secteurs_ids IS NOT NULL
                        AND JSON_CONTAINS(f.secteurs_ids, :secteurIdJson)";
                
                $result = $conn->executeQuery($sql, [
                    'secteurIdJson' => $secteurIdJson
                ])->fetchAssociative();
                
                $count = (int) ($result['count'] ?? 0);
                
                // Si le résultat est 0, essayer avec JSON_SEARCH comme fallback
                // JSON_SEARCH cherche une chaîne, donc on cherche le nombre comme chaîne
                if ($count === 0) {
                    $sql2 = "SELECT COUNT(*) as count 
                            FROM filieres f
                            INNER JOIN establishments e ON f.establishment_id = e.id
                            WHERE f.is_active = 1 
                            AND e.is_active = 1
                            AND f.secteurs_ids IS NOT NULL
                            AND (JSON_SEARCH(f.secteurs_ids, 'one', :secteurIdStr) IS NOT NULL
                                 OR JSON_CONTAINS(f.secteurs_ids, CAST(:secteurIdStr AS JSON)))";
                    
                    try {
                        $result2 = $conn->executeQuery($sql2, [
                            'secteurIdStr' => (string)$secteurId
                        ])->fetchAssociative();
                        
                        $count2 = (int) ($result2['count'] ?? 0);
                        if ($count2 > 0) {
                            return $count2;
                        }
                    } catch (\Exception $e2) {
                        // Si les deux méthodes SQL échouent, utiliser PHP
                    }
                } else {
                    return $count;
                }
            } catch (\Exception $e) {
                // En cas d'erreur SQL, utiliser la méthode PHP (fallback)
            }
        }
        
        // Pour les autres bases de données ou en cas d'erreur, utiliser la méthode PHP
        // Cette méthode est plus lente mais toujours fiable
        $filieres = $this->createQueryBuilder('f')
            ->innerJoin('f.establishment', 'e')
            ->where('f.isActive = true')
            ->andWhere('e.isActive = true')
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($filieres as $filiere) {
            $secteursIds = $filiere->getSecteursIds();
            if ($secteursIds && is_array($secteursIds) && in_array($secteurId, $secteursIds, true)) {
                $count++;
            }
        }

        return $count;
    }
}
