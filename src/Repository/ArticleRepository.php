<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Trouve les articles avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($filters['search'])) {
            $qb->andWhere('a.titre LIKE :search OR a.description LIKE :search OR a.contenu LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['isActivate']) && $filters['isActivate'] !== null) {
            $qb->andWhere('a.isActivate = :isActivate')
                ->setParameter('isActivate', $filters['isActivate']);
        }

        if (isset($filters['isComplet']) && $filters['isComplet'] !== null) {
            $qb->andWhere('a.isComplet = :isComplet')
                ->setParameter('isComplet', $filters['isComplet']);
        }

        if (!empty($filters['categorie'])) {
            $qb->andWhere('a.categorie = :categorie')
                ->setParameter('categorie', $filters['categorie']);
        }

        if (!empty($filters['featured'])) {
            $qb->andWhere('a.featured = :featured')
                ->setParameter('featured', $filters['featured']);
        }

        if (!empty($filters['tag'])) {
            $qb->andWhere('JSON_CONTAINS(a.tags, :tag) = 1')
                ->setParameter('tag', json_encode($filters['tag']));
        }

        $qb->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les articles avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)');

        if (!empty($filters['search'])) {
            $qb->andWhere('a.titre LIKE :search OR a.description LIKE :search OR a.contenu LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (isset($filters['isActivate']) && $filters['isActivate'] !== null) {
            $qb->andWhere('a.isActivate = :isActivate')
                ->setParameter('isActivate', $filters['isActivate']);
        }

        if (isset($filters['isComplet']) && $filters['isComplet'] !== null) {
            $qb->andWhere('a.isComplet = :isComplet')
                ->setParameter('isComplet', $filters['isComplet']);
        }

        if (!empty($filters['categorie'])) {
            $qb->andWhere('a.categorie = :categorie')
                ->setParameter('categorie', $filters['categorie']);
        }

        if (!empty($filters['featured'])) {
            $qb->andWhere('a.featured = :featured')
                ->setParameter('featured', $filters['featured']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les articles actifs pour le frontend
     */
    public function findActiveForFront(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isActivate = :isActivate')
            ->andWhere('a.status = :status')
            ->setParameter('isActivate', true)
            ->setParameter('status', 'Publié')
            ->orderBy('a.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un article par slug
     */
    public function findOneBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->where('a.slug = :slug')
            ->andWhere('a.isActivate = :isActivate')
            ->setParameter('slug', $slug)
            ->setParameter('isActivate', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
