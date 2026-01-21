<?php

namespace App\Repository;

use App\Entity\Secteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Secteur>
 *
 * @method Secteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Secteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Secteur[]    findAll()
 * @method Secteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Secteur::class);
    }

    /**
     * Recherche de secteurs avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.titre', 'ASC');

        // Filtre par recherche (titre, code, description)
        if (!empty($filters['search'])) {
            $qb->andWhere('s.titre LIKE :search OR s.code LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par statut
        if (!empty($filters['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre par activation
        if (isset($filters['isActivate'])) {
            $qb->andWhere('s.isActivate = :isActivate')
                ->setParameter('isActivate', $filters['isActivate']);
        }

        // Filtre par complet
        if (isset($filters['isComplet'])) {
            $qb->andWhere('s.isComplet = :isComplet')
                ->setParameter('isComplet', $filters['isComplet']);
        }

        // Filtre par afficherDansTest
        if (isset($filters['afficherDansTest'])) {
            $qb->andWhere('s.afficherDansTest = :afficherDansTest')
                ->setParameter('afficherDansTest', $filters['afficherDansTest']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de secteurs avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        return count($this->findWithFilters($filters));
    }

    /**
     * Trouve les secteurs actifs pour le front
     */
    public function findActiveForFront(array $filters = []): array
    {
        $filters['isActivate'] = true;
        $filters['status'] = 'Actif';
        return $this->findWithFilters($filters);
    }

    /**
     * Trouve les IDs des secteurs qui correspondent à un terme de recherche
     * Recherche optimisée dans : titre, keywords JSON, métiers JSON, et métiers via relation
     * 
     * @param string $searchTerm Terme de recherche
     * @return int[] Tableau d'IDs de secteurs
     */
    public function findIdsMatchingSearch(string $searchTerm): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $searchPattern = '%' . strtolower($searchTerm) . '%';
        
        // Pour la recherche JSON, créer un pattern qui correspond à un élément de tableau
        // JSON array: ["keyword1", "keyword2"] -> on cherche "keyword1" qui devient "\"keyword1\""
        $searchJsonPattern = '%"' . strtolower($searchTerm) . '"%';
        $searchJsonPatternAlt = '%"' . strtolower($searchTerm) . ',%'; // Pour les éléments au milieu
        $searchJsonPatternAlt2 = '%", "' . strtolower($searchTerm) . '"%'; // Pour les éléments suivants
        $searchJsonPatternAlt3 = '%", "' . strtolower($searchTerm) . ',%'; // Autre variante

        // Requête optimisée utilisant une seule requête SQL
        // Recherche dans titre, keywords JSON, métiers JSON, et métiers via relation
        $sql = "
            SELECT DISTINCT s.id as secteur_id
            FROM secteurs s
            WHERE (
                -- Recherche dans le titre du secteur
                LOWER(s.titre) LIKE :search
                
                -- Recherche dans keywords JSON (recherche dans la chaîne JSON comme texte)
                OR (s.keywords IS NOT NULL AND (
                    LOWER(s.keywords) LIKE :searchJson1
                    OR LOWER(s.keywords) LIKE :searchJson2
                    OR LOWER(s.keywords) LIKE :searchJson3
                    OR LOWER(s.keywords) LIKE :searchJson4
                    OR LOWER(s.keywords) LIKE :searchPattern
                ))
                
                -- Recherche dans métiers JSON (recherche dans la chaîne JSON comme texte)
                OR (s.metiers IS NOT NULL AND (
                    LOWER(s.metiers) LIKE :searchJson1
                    OR LOWER(s.metiers) LIKE :searchJson2
                    OR LOWER(s.metiers) LIKE :searchJson3
                    OR LOWER(s.metiers) LIKE :searchJson4
                    OR LOWER(s.metiers) LIKE :searchPattern
                ))
                
                -- Recherche dans métiers via relation Doctrine
                OR EXISTS (
                    SELECT 1 
                    FROM metiers m 
                    WHERE m.secteur_id = s.id 
                    AND m.is_activate = 1
                    AND (LOWER(m.nom) LIKE :search OR LOWER(m.nom_arabe) LIKE :search)
                    LIMIT 1
                )
            )
            AND s.is_activate = 1
        ";

        $result = $conn->executeQuery($sql, [
            'search' => $searchPattern,
            'searchPattern' => $searchPattern,
            'searchJson1' => $searchJsonPattern,
            'searchJson2' => $searchJsonPatternAlt,
            'searchJson3' => $searchJsonPatternAlt2,
            'searchJson4' => $searchJsonPatternAlt3,
        ])->fetchAllAssociative();

        return array_column($result, 'secteur_id');
    }
}
