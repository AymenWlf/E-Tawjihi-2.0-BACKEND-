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

        $qb = $this->createQueryBuilder('f');
        $filiere = $qb
            ->where('LOWER(f.slug) = LOWER(:slug)')
            ->setParameter('slug', $slug)
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
            ->where('f.establishment = :establishmentId')
            ->andWhere('f.isActive = :isActive')
            ->setParameter('establishmentId', $establishmentId)
            ->setParameter('isActive', true)
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
            ->where('f.isActive = :isActive')
            ->setParameter('isActive', true);

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
            $qb->andWhere('f.nom LIKE :search OR f.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('f.nom', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
