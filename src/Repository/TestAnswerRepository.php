<?php

namespace App\Repository;

use App\Entity\TestAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestAnswer>
 */
class TestAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestAnswer::class);
    }

    public function findBySession(int $sessionId, ?int $stepNumber = null): array
    {
        $qb = $this->createQueryBuilder('ta')
            ->where('ta.testSession = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('ta.answeredAt', 'ASC');

        if ($stepNumber !== null) {
            $qb->andWhere('ta.stepNumber = :stepNumber')
                ->setParameter('stepNumber', $stepNumber);
        }

        return $qb->getQuery()->getResult();
    }
}
