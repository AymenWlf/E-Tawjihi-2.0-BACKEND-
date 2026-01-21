<?php

namespace App\Repository;

use App\Entity\Metier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Metier>
 */
class MetierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Metier::class);
    }

    /**
     * Trouver les métiers par secteur
     */
    public function findBySecteur(int $secteurId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.secteur = :secteurId')
            ->setParameter('secteurId', $secteurId)
            ->andWhere('m.isActivate = :isActivate')
            ->setParameter('isActivate', true)
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher les métiers par nom
     */
    public function searchByNom(string $searchTerm): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nom LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->andWhere('m.isActivate = :isActivate')
            ->setParameter('isActivate', true)
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver un métier par slug
     */
    public function findBySlug(string $slug): ?Metier
    {
        return $this->createQueryBuilder('m')
            ->where('m.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
