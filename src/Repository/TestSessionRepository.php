<?php

namespace App\Repository;

use App\Entity\TestSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestSession>
 */
class TestSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestSession::class);
    }

    public function findByUser(int $userId, ?string $testType = null): ?TestSession
    {
        $qb = $this->createQueryBuilder('ts')
            ->where('ts.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ts.createdAt', 'DESC')
            ->setMaxResults(1);

        if ($testType !== null) {
            $qb->andWhere('ts.testType = :testType')
                ->setParameter('testType', $testType);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByUser(int $userId, ?string $testType = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->where('ts.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ts.createdAt', 'DESC');

        if ($testType !== null) {
            $qb->andWhere('ts.testType = :testType')
                ->setParameter('testType', $testType);
        }

        return $qb->getQuery()->getResult();
    }
}
