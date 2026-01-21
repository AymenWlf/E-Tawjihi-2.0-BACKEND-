<?php

namespace App\Repository;

use App\Entity\EstablishmentQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EstablishmentQuestion>
 */
class EstablishmentQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstablishmentQuestion::class);
    }

    /**
     * Récupère toutes les questions actives d'un établissement
     */
    public function findByEstablishment(int $establishmentId): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.establishment = :establishmentId')
            ->andWhere('q.isActive = :active')
            ->setParameter('establishmentId', $establishmentId)
            ->setParameter('active', true)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
