<?php

namespace App\Repository;

use App\Entity\OrientationTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrientationTest>
 */
class OrientationTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrientationTest::class);
    }

    public function findByUser(int $userId): ?OrientationTest
    {
        return $this->createQueryBuilder('ot')
            ->andWhere('ot.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
