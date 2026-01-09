<?php

namespace App\Repository;

use App\Entity\Campus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campus>
 *
 * @method Campus|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campus|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campus[]    findAll()
 * @method Campus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campus::class);
    }

    /**
     * Trouve les campus d'un établissement triés par ordre
     */
    public function findByEstablishmentOrdered(int $establishmentId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.establishment = :establishmentId')
            ->setParameter('establishmentId', $establishmentId)
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
