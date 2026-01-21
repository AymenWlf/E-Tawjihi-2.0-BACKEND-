<?php

namespace App\Repository;

use App\Entity\Establishment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\SecteurRepository;

/**
 * @extends ServiceEntityRepository<Establishment>
 *
 * @method Establishment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Establishment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Establishment[]    findAll()
 * @method Establishment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstablishmentRepository extends ServiceEntityRepository
{
    private ?SecteurRepository $secteurRepository = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Establishment::class);
    }

    /**
     * Recherche d'établissements avec filtres
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.universite', 'u')
            ->addSelect('u')
            ->leftJoin('e.campus', 'c')
            ->addSelect('c')
            ->orderBy('e.nom', 'ASC');

        // Filtre par recherche (nom, sigle, nomArabe, ville, email, diplômes, secteurs)
        // Note: La recherche dans les champs JSON (diplomesDelivres, villes) sera faite après la requête
        // car Doctrine DQL ne supporte pas JSON_SEARCH nativement
        // La recherche utilise COLLATE utf8mb4_unicode_ci qui est insensible aux accents
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            
            // Recherche de base dans les champs textuels
            // Note: La collation utf8mb4_unicode_ci est déjà insensible aux accents par défaut
            // mais on fait aussi une normalisation dans le filtrage post-requête pour être sûr
            $searchConditions = [
                'LOWER(e.nom) LIKE LOWER(:search)',
                'LOWER(e.sigle) LIKE LOWER(:search)',
                'e.nomArabe LIKE :search',
                'LOWER(e.ville) LIKE LOWER(:search)',
                'LOWER(e.email) LIKE LOWER(:search)'
            ];
            
            $qb->andWhere(implode(' OR ', $searchConditions))
                ->setParameter('search', $searchTerm);
        }

        // Filtre par type
        if (!empty($filters['type'])) {
            $qb->andWhere('e.type = :type')
                ->setParameter('type', $filters['type']);
        }

        // Filtre par ville
        if (!empty($filters['ville'])) {
            $qb->andWhere('e.ville = :ville OR e.villes LIKE :villeJson')
                ->setParameter('ville', $filters['ville'])
                ->setParameter('villeJson', '%"' . $filters['ville'] . '"%');
        }

        // Filtre par université (par ID ou par nom)
        // Note: le join avec 'u' est déjà fait au début de la méthode
        if (!empty($filters['universite'])) {
            // Si c'est un ID numérique, filtrer par relation
            if (is_numeric($filters['universite'])) {
                $qb->andWhere('u.id = :universiteId')
                   ->setParameter('universiteId', (int)$filters['universite']);
            } else {
                // Sinon, filtrer par nom de l'université (via la relation)
                $qb->andWhere('u.nom LIKE :universite')
                ->setParameter('universite', '%' . $filters['universite'] . '%');
            }
        }
        
        // Support pour universite_id explicite
        if (!empty($filters['universite_id'])) {
            $qb->andWhere('u.id = :universiteIdExplicit')
               ->setParameter('universiteIdExplicit', (int)$filters['universite_id']);
        }

        // Filtre par statut (actif/inactif)
        if (isset($filters['isActive'])) {
            $qb->andWhere('e.isActive = :isActive')
                ->setParameter('isActive', $filters['isActive']);
        }

        // Filtre par status (Publié/Brouillon)
        if (!empty($filters['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre recommandé
        if (isset($filters['isRecommended']) && $filters['isRecommended']) {
            $qb->andWhere('e.isRecommended = true');
        }

        // Filtre sponsorisé
        if (isset($filters['isSponsored']) && $filters['isSponsored']) {
            $qb->andWhere('e.isSponsored = true');
        }

        // Filtre à la une
        if (isset($filters['isFeatured']) && $filters['isFeatured']) {
            $qb->andWhere('e.isFeatured = true');
        }

        // Filtre échange international
        if (isset($filters['echangeInternational'])) {
            $qb->andWhere('e.echangeInternational = :echangeInternational')
                ->setParameter('echangeInternational', $filters['echangeInternational']);
        }

        // Filtre accréditation/reconnaissance par l'État
        if (isset($filters['accreditationEtat'])) {
            $qb->andWhere('e.accreditationEtat = :accreditationEtat')
                ->setParameter('accreditationEtat', $filters['accreditationEtat']);
        }

        $results = $qb->getQuery()->getResult();
        
        // Fonction pour normaliser les accents (insensible aux accents)
        $normalizeAccents = function($str) {
            return mb_strtolower(
                iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str),
                'UTF-8'
            );
        };
        
        // Filtrage post-requête pour les champs JSON (diplomesDelivres, villes, secteurs)
        // car Doctrine DQL ne supporte pas JSON_SEARCH nativement
        if (!empty($filters['search'])) {
            $searchValue = $normalizeAccents($filters['search']);
            $filteredResults = [];
            
            // Récupérer le SecteurRepository si nécessaire
            if (!$this->secteurRepository) {
                $repo = $this->getEntityManager()->getRepository(\App\Entity\Secteur::class);
                if ($repo instanceof SecteurRepository) {
                    $this->secteurRepository = $repo;
                }
            }
            
            // Trouver les IDs des secteurs qui correspondent au terme de recherche (une seule fois pour tous)
            $matchingSecteurIds = [];
            if ($this->secteurRepository) {
                try {
                    $matchingSecteurIds = $this->secteurRepository->findIdsMatchingSearch($searchValue);
                } catch (\Exception $e) {
                    // En cas d'erreur, continuer sans recherche par secteurs
                    $matchingSecteurIds = [];
                }
            }
            
            foreach ($results as $establishment) {
                $matches = false;
                
                // Vérifier si déjà matché par les conditions DQL
                $nom = $normalizeAccents($establishment->getNom() ?? '');
                $sigle = $normalizeAccents($establishment->getSigle() ?? '');
                $nomArabe = $establishment->getNomArabe() ?? ''; // Garder l'arabe tel quel
                $ville = $normalizeAccents($establishment->getVille() ?? '');
                $email = $normalizeAccents($establishment->getEmail() ?? '');
                
                if (stripos($nom, $searchValue) !== false ||
                    stripos($sigle, $searchValue) !== false ||
                    stripos($nomArabe, $filters['search']) !== false || // Recherche exacte pour l'arabe
                    stripos($ville, $searchValue) !== false ||
                    stripos($email, $searchValue) !== false) {
                    $matches = true;
                }
                
                // Recherche dans diplomesDelivres (JSON array)
                if (!$matches) {
                    $diplomes = $establishment->getDiplomesDelivres();
                    if (is_array($diplomes)) {
                        foreach ($diplomes as $diplome) {
                            if (stripos($normalizeAccents((string)$diplome), $searchValue) !== false) {
                                $matches = true;
                                break;
                            }
                        }
                    }
                }
                
                // Recherche dans villes (JSON array)
                if (!$matches) {
                    $villes = $establishment->getVilles();
                    if (is_array($villes)) {
                        foreach ($villes as $villeItem) {
                            if (stripos($normalizeAccents((string)$villeItem), $searchValue) !== false) {
                                $matches = true;
                                break;
                            }
                        }
                    }
                }
                
                // Recherche dans les secteurs (keywords et métiers)
                // Vérifier les secteurs directement associés et via les filières
                if (!$matches) {
                    // Collecter tous les secteurs IDs (directs + via filières)
                    $allSecteursIds = [];
                    
                    // 1. Secteurs directs de l'établissement
                    $establishmentSecteursIds = $establishment->getSecteursIds();
                    if (is_array($establishmentSecteursIds)) {
                        $allSecteursIds = array_merge($allSecteursIds, $establishmentSecteursIds);
                    }
                    
                    // 2. Secteurs via les filières
                    foreach ($establishment->getFilieres() as $filiere) {
                        $filiereSecteursIds = $filiere->getSecteursIds();
                        if (is_array($filiereSecteursIds)) {
                            $allSecteursIds = array_merge($allSecteursIds, $filiereSecteursIds);
                        }
                    }
                    
                    $allSecteursIds = array_unique($allSecteursIds);
                    
                    // Vérifier si un secteur correspondant est dans la liste des secteurs de l'établissement
                    // (utiliser les secteurs correspondants calculés une seule fois au début)
                    if (!empty($allSecteursIds) && !empty($matchingSecteurIds)) {
                        foreach ($matchingSecteurIds as $matchingSecteurId) {
                            if (in_array($matchingSecteurId, $allSecteursIds, true)) {
                                $matches = true;
                                break;
                            }
                        }
                    }
                }
                
                if ($matches) {
                    $filteredResults[] = $establishment;
                }
            }
            
            return $filteredResults;
        }

        return $results;
    }

    /**
     * Compte le nombre d'établissements avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        return count($this->findWithFilters($filters));
    }

    /**
     * Trouve un établissement par slug (case-insensitive)
     */
    public function findOneBySlug(string $slug): ?Establishment
    {
        if (empty($slug)) {
            return null;
        }
        
        // Nettoyer le slug (trim)
        $slug = trim($slug);
        
        // Utiliser directement DQL avec case-insensitive pour être sûr
        $qb = $this->createQueryBuilder('e');
        $establishment = $qb
            ->where('LOWER(e.slug) = LOWER(:slug)')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
        
        return $establishment;
    }

    /**
     * Trouve les établissements actifs et publiés pour le front
     */
    public function findActiveForFront(array $filters = []): array
    {
        $filters['isActive'] = true;
        $filters['status'] = 'Publié';
        return $this->findWithFilters($filters);
    }

    /**
     * Statistiques des établissements
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e');
        
        return [
            'total' => $qb->select('COUNT(e.id)')->getQuery()->getSingleScalarResult(),
            'active' => $qb->select('COUNT(e.id)')->where('e.isActive = true')->getQuery()->getSingleScalarResult(),
            'publies' => $qb->select('COUNT(e.id)')->where('e.status = :status')->setParameter('status', 'Publié')->getQuery()->getSingleScalarResult(),
            'brouillons' => $qb->select('COUNT(e.id)')->where('e.status = :status')->setParameter('status', 'Brouillon')->getQuery()->getSingleScalarResult(),
            'recommandes' => $qb->select('COUNT(e.id)')->where('e.isRecommended = true')->getQuery()->getSingleScalarResult(),
            'sponsorises' => $qb->select('COUNT(e.id)')->where('e.isSponsored = true')->getQuery()->getSingleScalarResult(),
        ];
    }

    /**
     * Compte le nombre d'établissements associés à un secteur
     * Un établissement est associé si son champ secteursIds contient l'ID du secteur
     */
    public function countBySecteur(int $secteurId): int
    {
        $establishments = $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'Publié')
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($establishments as $establishment) {
            $secteursIds = $establishment->getSecteursIds();
            if ($secteursIds && is_array($secteursIds) && in_array($secteurId, $secteursIds, true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Compte le nombre total de filières associées à un secteur
     * Un établissement est associé si son champ secteursIds contient l'ID du secteur
     * On compte ensuite toutes les filières dans le champ filieresIds de ces établissements
     */
    public function countFilieresBySecteur(int $secteurId): int
    {
        $establishments = $this->createQueryBuilder('e')
            ->where('e.isActive = true')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'Publié')
            ->getQuery()
            ->getResult();

        $filieresIds = [];
        foreach ($establishments as $establishment) {
            $secteursIds = $establishment->getSecteursIds();
            if ($secteursIds && is_array($secteursIds) && in_array($secteurId, $secteursIds, true)) {
                $etablissementFilieresIds = $establishment->getFilieresIds();
                if ($etablissementFilieresIds && is_array($etablissementFilieresIds)) {
                    // Ajouter les IDs de filières à la liste globale (sans doublons)
                    foreach ($etablissementFilieresIds as $filiereId) {
                        if (!in_array($filiereId, $filieresIds, true)) {
                            $filieresIds[] = $filiereId;
                        }
                    }
                }
            }
        }

        return count($filieresIds);
    }
}
