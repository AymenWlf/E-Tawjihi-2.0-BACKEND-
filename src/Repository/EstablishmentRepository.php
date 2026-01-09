<?php

namespace App\Repository;

use App\Entity\Establishment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Establishment>
 *
 * @method Establishment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Establishment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Establishment[]    findAll()
 * @method Establishment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstablishmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Establishment::class);
    }

    /**
     * Recherche d'établissements avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.nom', 'ASC');

        // Filtre par recherche (nom, sigle, ville, email)
        if (!empty($filters['search'])) {
            $qb->andWhere('e.nom LIKE :search OR e.sigle LIKE :search OR e.ville LIKE :search OR e.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('e.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre par ville
        if (!empty($filters['ville'])) {
            $qb->andWhere('e.ville = :ville OR e.villes LIKE :villeJson')
                ->setParameter('ville', $filters['ville'])
                ->setParameter('villeJson', '%"' . $filters['ville'] . '"%');
        }

        // Filtre par université
        if (!empty($filters['universite'])) {
            $qb->andWhere('e.universite LIKE :universite')
                ->setParameter('universite', '%' . $filters['universite'] . '%');
        }

        // Filtre par statut (actif/inactif)
        if (isset($filters['isActive'])) {
            $qb->andWhere('e.isActive = :isActive')
                ->setParameter('isActive', $filters['isActive']);
        }

        // Filtre par status (Publié/Brouillon)
        if (!empty($filters['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre recommandé
        if (isset($filters['isRecommended']) && $filters['isRecommended']) {
            $qb->andWhere('e.isRecommended = true');
        }

        // Filtre sponsorisé
        if (isset($filters['isSponsored']) && $filters['isSponsored']) {
            $qb->andWhere('e.isSponsored = true');
        }

        // Filtre à la une
        if (isset($filters['isFeatured']) && $filters['isFeatured']) {
            $qb->andWhere('e.isFeatured = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre d'établissements avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        return count($this->findWithFilters($filters));
    }

    /**
     * Trouve un établissement par slug (case-insensitive)
     */
    public function findOneBySlug(string $slug): ?Establishment
    {
        if (empty($slug)) {
            return null;
        }
        
        // Nettoyer le slug (trim)
        $slug = trim($slug);
        
        // Utiliser directement DQL avec case-insensitive pour être sûr
        $qb = $this->createQueryBuilder('e');
        $establishment = $qb
            ->where('LOWER(e.slug) = LOWER(:slug)')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
        
        return $establishment;
    }

    /**
     * Trouve les établissements actifs et publiés pour le front
     */
    public function findActiveForFront(array $filters = []): array
    {
        $filters['isActive'] = true;
        $filters['status'] = 'Publié';
        return $this->findWithFilters($filters);
    }

    /**
     * Statistiques des établissements
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e');
        
        return [
            'total' => $qb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(e.id)')->where('e.isActive = true')->getQuery()->getSingleScalarResult(),
            'publies' => $qb->select('COUNT(e.id)')->where('e.status = :status')->setParameter('status', 'Publié')->getQuery()->getSingleScalarResult(),
            'brouillons' => $qb->select('COUNT(e.id)')->where('e.status = :status')->setParameter('status', 'Brouillon')->getQuery()->getSingleScalarResult(),
            'recommandes' => $qb->select('COUNT(e.id)')->where('e.isRecommended = true')->getQuery()->getSingleScalarResult(),
            'sponsorises' => $qb->select('COUNT(e.id)')->where('e.isSponsored = true')->getQuery()->getSingleScalarResult(),
        ];
    }
}
