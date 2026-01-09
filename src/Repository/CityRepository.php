<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    /**
     * Find cities by search term
     * 
     * @param string $search
     * @param int $limit
     * @return City[]
     */
    public function findBySearch(string $search = '', int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.titre', 'ASC');

        if (!empty($search)) {
            $qb->where('c.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
    
    /**
     * Find all cities with limit
     * 
     * @param int $limit
     * @return City[]
     */
    public function findAllWithLimit(int $limit = 500): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.titre', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
