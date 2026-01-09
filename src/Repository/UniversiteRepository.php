<?php

namespace App\Repository;

use App\Entity\Universite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Universite>
 *
 * @method Universite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Universite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Universite[]    findAll()
 * @method Universite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UniversiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Universite::class);
    }

    /**
     * Recherche d'universités avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('u.nom', 'ASC');

        if (isset($filters['search'])) {
            $qb->andWhere('u.nom LIKE :search OR u.sigle LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['ville'])) {
            $qb->andWhere('u.ville = :ville')
               ->setParameter('ville', $filters['ville']);
        }

        if (isset($filters['pays'])) {
            $qb->andWhere('u.pays = :pays')
               ->setParameter('pays', $filters['pays']);
        }

        return $qb->getQuery()->getResult();
    }
}
