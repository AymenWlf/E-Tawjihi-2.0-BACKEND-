<?php

namespace App\Repository;

use App\Entity\Favoris;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favoris>
 */
class FavorisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favoris::class);
    }

    /**
     * Trouve un favori par utilisateur et secteur
     */
    public function findByUserAndSecteur(User $user, int $secteurId): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.secteur = :secteurId')
            ->setParameter('user', $user)
            ->setParameter('secteurId', $secteurId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un favori par utilisateur et établissement
     */
    public function findByUserAndEstablishment(User $user, int $establishmentId): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.establishment = :establishmentId')
            ->setParameter('user', $user)
            ->setParameter('establishmentId', $establishmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un favori par utilisateur et filière
     */
    public function findByUserAndFiliere(User $user, int $filiereId): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.filiere = :filiereId')
            ->setParameter('user', $user)
            ->setParameter('filiereId', $filiereId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les favoris d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les IDs des secteurs favoris d'un utilisateur
     */
    public function findSecteurIdsByUser(User $user): array
    {
        $favoris = $this->createQueryBuilder('f')
            ->select('s.id')
            ->join('f.secteur', 's')
            ->where('f.user = :user')
            ->andWhere('f.secteur IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_map('intval', array_column($favoris, 'id'));
    }

    /**
     * Récupère les IDs des établissements favoris d'un utilisateur
     */
    public function findEstablishmentIdsByUser(User $user): array
    {
        $favoris = $this->createQueryBuilder('f')
            ->select('e.id')
            ->join('f.establishment', 'e')
            ->where('f.user = :user')
            ->andWhere('f.establishment IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_map('intval', array_column($favoris, 'id'));
    }

    /**
     * Récupère les IDs des filières favoris d'un utilisateur
     */
    public function findFiliereIdsByUser(User $user): array
    {
        $favoris = $this->createQueryBuilder('f')
            ->select('fil.id')
            ->join('f.filiere', 'fil')
            ->where('f.user = :user')
            ->andWhere('f.filiere IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_map('intval', array_column($favoris, 'id'));
    }
}
