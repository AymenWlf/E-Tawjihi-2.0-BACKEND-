<?php

namespace App\Controller\Api;

use App\Entity\Establishment;
use App\Entity\Campus;
use App\Entity\City;
use App\Repository\EstablishmentRepository;
use App\Repository\CampusRepository;
use App\Repository\CityRepository;
use App\Repository\SecteurRepository;
use App\Repository\UniversiteRepository;
use App\Entity\Universite;
use App\Entity\User;
use App\Repository\TestSessionRepository;
use App\Repository\UserRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api/establishments', name: 'api_establishments_')]
class EstablishmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EstablishmentRepository $establishmentRepository,
        private CampusRepository $campusRepository,
        private CityRepository $cityRepository,
        private SecteurRepository $secteurRepository,
        private UniversiteRepository $universiteRepository,
        private TestSessionRepository $testSessionRepository,
        private UserRepository $userRepository,
        private RecommendationService $recommendationService,
        private SerializerInterface $serializer,
        private SluggerInterface $slugger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Récupère un établissement par ID et slug
     * IMPORTANT: Cette route doit être définie AVANT toutes les autres routes
     * pour éviter les conflits de routing
     */
    #[Route('/{id}/{slug}', name: 'get_by_id_slug', methods: ['GET'], priority: 10, requirements: ['id' => '\d+', 'slug' => '.+'])]
    public function getByIdAndSlug(int $id, string $slug): JsonResponse
    {
        $this->logger->info("Recherche d'établissement par ID et slug", [
            'id' => $id,
            'slug' => $slug
        ]);

        // Rechercher par ID d'abord
        $establishment = $this->establishmentRepository->find($id);

        // Vérifier que l'établissement existe et que le slug correspond
        if (!$establishment) {
            $this->logger->warning("Établissement non trouvé par ID", ['id' => $id]);
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé',
                'id' => $id,
                'slug' => $slug
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le slug correspond (case-insensitive)
        if (strtolower($establishment->getSlug() ?? '') !== strtolower($slug)) {
            $this->logger->warning("Slug ne correspond pas", [
                'id' => $id,
                'expected_slug' => $establishment->getSlug(),
                'provided_slug' => $slug
            ]);
            return $this->json([
                'success' => false,
                'message' => 'Slug ne correspond pas à l\'établissement',
                'id' => $id,
                'slug' => $slug,
                'expected_slug' => $establishment->getSlug()
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logger->info("Établissement trouvé", [
            'id' => $establishment->getId(),
            'slug' => $establishment->getSlug(),
            'nom' => $establishment->getNom()
        ]);

        // Incrémenter le compteur de vues
        $establishment->incrementViewCount();
        $this->entityManager->flush();

        // Sérialiser l'établissement avec tous les groupes nécessaires
        $data = $this->serializer->normalize($establishment, null, [
            'groups' => ['establishment:read']
        ]);

        // Enrichir les données avec des informations calculées
        $enrichedData = $this->enrichEstablishmentData($data, $establishment);

        return $this->json([
            'success' => true,
            'data' => $enrichedData
        ]);
    }

    /**
     * Liste tous les établissements (avec filtres)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query->get('search'),
            'type' => $request->query->get('type'),
            'ville' => $request->query->get('ville'),
            'universite' => $request->query->get('universite'),
            'status' => $request->query->get('status'),
        ];

        // Filtres booléens
        // Par défaut, ne retourner que les établissements actifs pour les requêtes publiques
        // Sauf si le paramètre isActive est explicitement fourni (y compris pour false)
        if ($request->query->has('isActive')) {
            $isActiveValue = $request->query->get('isActive');
            // Convertir correctement la valeur (gère "true", "false", "1", "0", true, false, 1, 0)
            if ($isActiveValue === 'false' || $isActiveValue === '0' || $isActiveValue === false || $isActiveValue === 0) {
                $filters['isActive'] = false;
            } else {
                $filters['isActive'] = filter_var($isActiveValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            }
        } else {
            // Par défaut, ne retourner que les établissements actifs
            $filters['isActive'] = true;
        }
        if ($request->query->get('isRecommended') === 'true') {
            $filters['isRecommended'] = true;
        }
        if ($request->query->get('isSponsored') === 'true') {
            $filters['isSponsored'] = true;
        }
        if ($request->query->get('isFeatured') === 'true') {
            $filters['isFeatured'] = true;
        }

        // Filtre échange international
        if ($request->query->has('echangeInternational')) {
            $echangeValue = $request->query->get('echangeInternational');
            if ($echangeValue === 'true' || $echangeValue === '1' || $echangeValue === true || $echangeValue === 1) {
                $filters['echangeInternational'] = true;
            } elseif ($echangeValue === 'false' || $echangeValue === '0' || $echangeValue === false || $echangeValue === 0) {
                $filters['echangeInternational'] = false;
            }
        }

        // Filtre accréditation/reconnaissance par l'État
        if ($request->query->has('accreditationEtat')) {
            $accredValue = $request->query->get('accreditationEtat');
            if ($accredValue === 'true' || $accredValue === '1' || $accredValue === true || $accredValue === 1) {
                $filters['accreditationEtat'] = true;
            } elseif ($accredValue === 'false' || $accredValue === '0' || $accredValue === false || $accredValue === 0) {
                $filters['accreditationEtat'] = false;
            }
        }
        // Support pour reconnaissanceEtat (alias de accreditationEtat)
        if ($request->query->has('reconnaissanceEtat')) {
            $reconValue = $request->query->get('reconnaissanceEtat');
            if ($reconValue === 'true' || $reconValue === '1' || $reconValue === true || $reconValue === 1) {
                $filters['accreditationEtat'] = true;
            } elseif ($reconValue === 'false' || $reconValue === '0' || $reconValue === false || $reconValue === 0) {
                $filters['accreditationEtat'] = false;
            }
        }

        // Retirer les filtres vides
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        // Injecter le SecteurRepository dans EstablishmentRepository pour permettre la recherche par secteurs
        if (method_exists($this->establishmentRepository, 'setSecteurRepository')) {
            $this->establishmentRepository->setSecteurRepository($this->secteurRepository);
        }

        $establishments = $this->establishmentRepository->findWithFilters($filters);
        
        // Filtrage supplémentaire pour les secteurs si recherche active
        // Les établissements qui correspondent déjà aux critères de base (nom, sigle, etc.) sont déjà inclus
        // On ajoute ceux qui correspondent uniquement aux secteurs, keywords ou métiers
        if (!empty($filters['search'])) {
            $searchTerm = strtolower($filters['search']);
            $existingIds = array_map(fn($e) => $e->getId(), $establishments);
            
            // Utiliser la méthode optimisée du repository pour trouver les IDs des secteurs correspondants
            // Cette méthode utilise une seule requête SQL optimisée
            $matchingSecteurIds = $this->secteurRepository->findIdsMatchingSearch($searchTerm);
            
            if (!empty($matchingSecteurIds)) {
                // Récupérer les secteurs en une seule requête Doctrine
                $matchingSecteurs = $this->secteurRepository->createQueryBuilder('s')
                    ->where('s.id IN (:ids)')
                    ->setParameter('ids', $matchingSecteurIds)
                    ->getQuery()
                    ->getResult();
            } else {
                $matchingSecteurs = [];
            }
            
            // Pour chaque secteur correspondant, trouver les établissements associés
            // Utiliser les mêmes filtres que la requête initiale (sans le filtre search pour éviter la récursion)
            $sectorFilters = $filters;
            unset($sectorFilters['search']); // Retirer le filtre search pour éviter la récursion
            
            // Récupérer tous les établissements qui respectent les filtres
            $allFilteredEstablishments = $this->establishmentRepository->findWithFilters($sectorFilters);
                    
            foreach ($allFilteredEstablishments as $establishment) {
                $hasMatchingSecteur = false;
                
                // 1. Vérifier les secteurs directement associés à l'établissement
                $establishmentSecteursIds = $establishment->getSecteursIds();
                if ($establishmentSecteursIds && is_array($establishmentSecteursIds)) {
                    foreach ($matchingSecteurs as $secteur) {
                        $secteurId = $secteur->getId();
                        if (in_array($secteurId, $establishmentSecteursIds, true)) {
                            $hasMatchingSecteur = true;
                            break;
                        }
                    }
                }
                
                // 2. Si pas trouvé, vérifier les secteurs via les filières de l'établissement
                if (!$hasMatchingSecteur) {
                    foreach ($establishment->getFilieres() as $filiere) {
                        $filiereSecteursIds = $filiere->getSecteursIds();
                        if ($filiereSecteursIds && is_array($filiereSecteursIds)) {
                            foreach ($matchingSecteurs as $secteur) {
                                $secteurId = $secteur->getId();
                                if (in_array($secteurId, $filiereSecteursIds, true)) {
                                    $hasMatchingSecteur = true;
                                    break 2; // Sortir des deux boucles
                                }
                            }
                        }
                    }
                }
                
                // Ajouter l'établissement s'il a un secteur correspondant
                if ($hasMatchingSecteur && !in_array($establishment->getId(), $existingIds)) {
                    $establishments[] = $establishment;
                    $existingIds[] = $establishment->getId();
                }
            }
        }
        
        $total = count($establishments);

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 18)));
        $offset = ($page - 1) * $limit;

        $paginatedEstablishments = array_slice($establishments, $offset, $limit);

        $data = $this->serializer->normalize($paginatedEstablishments, null, [
            'groups' => ['establishment:list', 'universite:list'],
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ]);

        // Enrichir les données avec enrichEstablishmentData (qui inclut les secteurs et l'université)
        $enrichedData = [];
        foreach ($paginatedEstablishments as $index => $establishment) {
            $establishmentData = $data[$index] ?? [];
            // S'assurer que l'université est bien chargée
            if ($establishment->getUniversite()) {
                $this->entityManager->initializeObject($establishment->getUniversite());
            }
            $enrichedData[] = $this->enrichEstablishmentData($establishmentData, $establishment);
        }
        $data = $enrichedData;

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Crée un nouvel établissement
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $establishment = new Establishment();
            $this->hydrateEstablishment($establishment, $data);

            // Générer le slug si non fourni
            if (empty($establishment->getSlug())) {
                $slug = $this->slugger->slug(strtolower($establishment->getNom()))->toString();
                $establishment->setSlug($slug);
            }

            $this->entityManager->persist($establishment);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($establishment, null, [
                'groups' => ['establishment:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Établissement créé avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour un établissement
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $establishment = $this->establishmentRepository->find($id);

            if (!$establishment) {
                return $this->json([
                    'success' => false,
                    'message' => 'Établissement non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->hydrateEstablishment($establishment, $data);

            // Générer le slug si non fourni et qu'il est vide
            if (empty($establishment->getSlug())) {
                $slug = $this->slugger->slug(strtolower($establishment->getNom()))->toString();
                $establishment->setSlug($slug);
            }

            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($establishment, null, [
                'groups' => ['establishment:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Établissement mis à jour avec succès',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime un établissement
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $establishment = $this->establishmentRepository->find($id);

            if (!$establishment) {
                return $this->json([
                    'success' => false,
                    'message' => 'Établissement non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($establishment);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Établissement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actions en masse (bulk actions)
     */
    #[Route('/bulk', name: 'bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $action = $data['action'] ?? null;
            $ids = $data['ids'] ?? [];

            if (!$action || empty($ids)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Action et IDs requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $establishments = $this->establishmentRepository->findBy(['id' => $ids]);

            foreach ($establishments as $establishment) {
                switch ($action) {
                    case 'publish':
                        $establishment->setStatus('Publié');
                        $establishment->setIsActive(true);
                        break;
                    case 'draft':
                        $establishment->setStatus('Brouillon');
                        break;
                    case 'activate':
                        $establishment->setIsActive(true);
                        break;
                    case 'deactivate':
                        $establishment->setIsActive(false);
                        break;
                    case 'delete':
                        $this->entityManager->remove($establishment);
                        break;
                    default:
                        return $this->json([
                            'success' => false,
                            'message' => 'Action non reconnue'
                        ], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => count($establishments) . ' établissement(s) ' . $action . ' avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action en masse: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Statistiques des établissements
     */
    #[Route('/stats/overview', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $stats = $this->establishmentRepository->getStatistics();

        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Récupère un établissement par ID ou slug (ancienne route pour compatibilité)
     * IMPORTANT: Cette route doit être définie APRÈS toutes les routes spécifiques
     * pour éviter les conflits de routing
     */
    #[Route('/{identifier}', name: 'get', methods: ['GET'], requirements: ['identifier' => '.+'], priority: -10)]
    public function get(string $identifier): JsonResponse
    {
        $establishment = null;
        $searchType = 'unknown';
        
        // Essayer de trouver par ID d'abord si c'est numérique
        if (is_numeric($identifier)) {
            $searchType = 'ID';
            $establishment = $this->establishmentRepository->find((int) $identifier);
        } else {
            // Rechercher par slug (priorité à la recherche par slug pour les identifiants non-numériques)
            $searchType = 'slug';
            $this->logger->info("Recherche d'établissement par slug", ['slug' => $identifier]);
            
            $establishment = $this->establishmentRepository->findOneBySlug($identifier);
            
            if (!$establishment) {
                $this->logger->warning("Établissement non trouvé par slug", ['slug' => $identifier]);
            }
        }

        if (!$establishment) {
            // Récupérer quelques slugs pour debug (limité à 10 pour éviter les problèmes de performance)
            $sampleEstablishments = $this->establishmentRepository->findBy([], ['id' => 'ASC'], 10);
            $sampleSlugs = array_filter(array_map(function($e) {
                return $e->getSlug();
            }, $sampleEstablishments));
            
            // Récupérer aussi tous les slugs pour debug complet
            $allEstablishments = $this->establishmentRepository->findAll();
            $allSlugs = array_filter(array_map(function($e) {
                return $e->getSlug();
            }, $allEstablishments));
            
            $this->logger->error("Établissement non trouvé", [
                'identifier' => $identifier,
                'type' => $searchType,
                'total_establishments' => count($allEstablishments),
                'slugs_count' => count($allSlugs)
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé',
                'identifier' => $identifier,
                'type' => $searchType,
                'debug' => [
                    'sample_slugs' => array_values($sampleSlugs),
                    'total_slugs' => count($allSlugs),
                    'hint' => 'Vérifiez que le slug existe en base de données. Utilisez la commande: php bin/console app:generate-slugs'
                ]
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logger->info("Établissement trouvé", [
            'id' => $establishment->getId(),
            'slug' => $establishment->getSlug(),
            'nom' => $establishment->getNom()
        ]);

        // Incrémenter le compteur de vues
        $establishment->incrementViewCount();
        $this->entityManager->flush();

        // Sérialiser l'établissement avec tous les groupes nécessaires
        $data = $this->serializer->normalize($establishment, null, [
            'groups' => ['establishment:read']
        ]);

        // Enrichir les données avec des informations calculées
        $enrichedData = $this->enrichEstablishmentData($data, $establishment);

        return $this->json([
            'success' => true,
            'data' => $enrichedData
        ]);
    }

    /**
     * Hydrate l'entité Establishment avec les données
     */
    private function hydrateEstablishment(Establishment $establishment, array $data): void
    {
        // Informations de base
        if (isset($data['nom'])) $establishment->setNom($data['nom']);
        if (isset($data['sigle'])) $establishment->setSigle($data['sigle']);
        if (isset($data['nomArabe'])) $establishment->setNomArabe($data['nomArabe']);
        if (isset($data['type'])) $establishment->setType($data['type']);
        if (isset($data['ville'])) $establishment->setVille($data['ville']);
        if (isset($data['villes'])) $establishment->setVilles($data['villes']);
        if (isset($data['pays'])) $establishment->setPays($data['pays']);
        // Gérer la relation universite
        if (isset($data['universite'])) {
            if (is_numeric($data['universite'])) {
                // Si c'est un ID numérique, chercher l'entité Universite
                $universite = $this->universiteRepository->find((int)$data['universite']);
                if ($universite) {
                    $establishment->setUniversite($universite);
                } else {
                    $establishment->setUniversite(null);
                }
            } elseif (is_array($data['universite']) && isset($data['universite']['id'])) {
                // Si c'est un objet avec un ID
                $universite = $this->universiteRepository->find((int)$data['universite']['id']);
                if ($universite) {
                    $establishment->setUniversite($universite);
                } else {
                    $establishment->setUniversite(null);
                }
            } elseif ($data['universite'] === '' || $data['universite'] === null) {
                // Si c'est vide ou null, supprimer la relation
                $establishment->setUniversite(null);
            }
            // Si c'est une string (ancien format), on l'ignore car on utilise maintenant la relation
        } elseif (isset($data['universite_id'])) {
            // Gérer universite_id directement
            if ($data['universite_id'] === '' || $data['universite_id'] === null) {
                $establishment->setUniversite(null);
            } else {
                $universite = $this->universiteRepository->find((int)$data['universite_id']);
                if ($universite) {
                    $establishment->setUniversite($universite);
                } else {
                    $establishment->setUniversite(null);
                }
            }
        }
        if (isset($data['description'])) $establishment->setDescription($data['description']);
        if (isset($data['logo']) && $data['logo'] !== '') {
            $establishment->setLogo($data['logo']);
        } elseif (array_key_exists('logo', $data) && $data['logo'] === '') {
            // Si logo est explicitement vide, set it to null (delete it)
            $establishment->setLogo(null);
        }
        if (isset($data['imageCouverture']) && $data['imageCouverture'] !== '') {
            $establishment->setImageCouverture($data['imageCouverture']);
        } elseif (array_key_exists('imageCouverture', $data) && $data['imageCouverture'] === '') {
            // Si imageCouverture est explicitement vide, set it to null (delete it)
            $establishment->setImageCouverture(null);
        }

        // Contact
        if (isset($data['email'])) $establishment->setEmail($data['email']);
        if (isset($data['telephone'])) $establishment->setTelephone($data['telephone']);
        if (isset($data['siteWeb'])) $establishment->setSiteWeb($data['siteWeb']);
        if (isset($data['adresse'])) $establishment->setAdresse($data['adresse']);
        if (isset($data['codePostal'])) $establishment->setCodePostal($data['codePostal']);

        // Réseaux sociaux
        if (isset($data['facebook'])) $establishment->setFacebook($data['facebook']);
        if (isset($data['instagram'])) $establishment->setInstagram($data['instagram']);
        if (isset($data['twitter'])) $establishment->setTwitter($data['twitter']);
        if (isset($data['linkedin'])) $establishment->setLinkedin($data['linkedin']);
        if (isset($data['youtube'])) $establishment->setYoutube($data['youtube']);

        // Informations académiques
        if (isset($data['nbEtudiants'])) $establishment->setNbEtudiants($data['nbEtudiants']);
        if (isset($data['nbFilieres'])) $establishment->setNbFilieres($data['nbFilieres']);
        if (isset($data['anneeCreation'])) $establishment->setAnneeCreation($data['anneeCreation']);
        if (isset($data['accreditationEtat'])) $establishment->setAccreditationEtat($data['accreditationEtat']);
        if (isset($data['concours'])) $establishment->setConcours($data['concours']);
        if (isset($data['echangeInternational'])) $establishment->setEchangeInternational($data['echangeInternational']);
        if (isset($data['anneesEtudes'])) $establishment->setAnneesEtudes($data['anneesEtudes']);
        if (isset($data['dureeEtudesMin'])) $establishment->setDureeEtudesMin($data['dureeEtudesMin'] === '' ? null : (int)$data['dureeEtudesMin']);
        if (isset($data['dureeEtudesMax'])) $establishment->setDureeEtudesMax($data['dureeEtudesMax'] === '' ? null : (int)$data['dureeEtudesMax']);
        if (isset($data['fraisScolariteMin'])) $establishment->setFraisScolariteMin($data['fraisScolariteMin'] === '' ? null : (string)$data['fraisScolariteMin']);
        if (isset($data['fraisScolariteMax'])) $establishment->setFraisScolariteMax($data['fraisScolariteMax'] === '' ? null : (string)$data['fraisScolariteMax']);
        if (isset($data['fraisInscriptionMin'])) $establishment->setFraisInscriptionMin($data['fraisInscriptionMin'] === '' ? null : (string)$data['fraisInscriptionMin']);
        if (isset($data['fraisInscriptionMax'])) $establishment->setFraisInscriptionMax($data['fraisInscriptionMax'] === '' ? null : (string)$data['fraisInscriptionMax']);
        
        // Gratuit
        if (isset($data['gratuit'])) $establishment->setGratuit((bool)$data['gratuit']);
        
        // Bourses
        if (isset($data['boursesDisponibles'])) $establishment->setBoursesDisponibles((bool)$data['boursesDisponibles']);
        if (isset($data['bourseMin'])) $establishment->setBourseMin($data['bourseMin'] === '' ? null : (string)$data['bourseMin']);
        if (isset($data['bourseMax'])) $establishment->setBourseMax($data['bourseMax'] === '' ? null : (string)$data['bourseMax']);
        if (isset($data['typesBourse'])) $establishment->setTypesBourse(is_array($data['typesBourse']) ? $data['typesBourse'] : null);
        
        if (isset($data['diplomesDelivres'])) $establishment->setDiplomesDelivres(is_array($data['diplomesDelivres']) ? $data['diplomesDelivres'] : null);
        if (isset($data['bacObligatoire'])) $establishment->setBacObligatoire($data['bacObligatoire']);

        // SEO
        if (isset($data['slug'])) $establishment->setSlug($data['slug']);
        if (isset($data['metaTitle'])) $establishment->setMetaTitle($data['metaTitle']);
        if (isset($data['metaDescription'])) $establishment->setMetaDescription($data['metaDescription']);
        if (isset($data['metaKeywords'])) $establishment->setMetaKeywords($data['metaKeywords']);
        if (isset($data['ogImage'])) $establishment->setOgImage($data['ogImage']);
        if (isset($data['canonicalUrl'])) $establishment->setCanonicalUrl($data['canonicalUrl']);
        if (isset($data['schemaType'])) $establishment->setSchemaType($data['schemaType']);
        if (isset($data['noIndex'])) $establishment->setNoIndex($data['noIndex']);

        // Statuts
        if (isset($data['isActive'])) $establishment->setIsActive($data['isActive']);
        if (isset($data['isRecommended'])) $establishment->setIsRecommended($data['isRecommended']);
        if (isset($data['isSponsored'])) $establishment->setIsSponsored($data['isSponsored']);
        if (isset($data['isFeatured'])) $establishment->setIsFeatured($data['isFeatured']);
        if (isset($data['status'])) $establishment->setStatus($data['status']);
        if (isset($data['isComplet'])) $establishment->setIsComplet($data['isComplet']);
        if (isset($data['hasDetailPage'])) $establishment->setHasDetailPage($data['hasDetailPage']);
        if (isset($data['eTawjihiInscription'])) $establishment->setETawjihiInscription($data['eTawjihiInscription']);
        if (isset($data['bacType'])) $establishment->setBacType($data['bacType']);
        if (isset($data['filieresAcceptees'])) $establishment->setFilieresAcceptees(is_array($data['filieresAcceptees']) ? $data['filieresAcceptees'] : null);
        if (isset($data['combinaisonsBacMission'])) $establishment->setCombinaisonsBacMission(is_array($data['combinaisonsBacMission']) ? $data['combinaisonsBacMission'] : null);
        if (isset($data['secteursIds'])) {
            // Convertir les IDs en entiers si ce sont des strings
            $secteursIds = is_array($data['secteursIds']) ? array_map('intval', array_filter($data['secteursIds'], fn($id) => $id !== '' && $id !== null)) : null;
            $establishment->setSecteursIds(!empty($secteursIds) ? $secteursIds : null);
        }
        if (isset($data['filieresIds'])) {
            // Convertir les IDs en entiers si ce sont des strings
            $filieresIds = is_array($data['filieresIds']) ? array_map('intval', array_filter($data['filieresIds'], fn($id) => $id !== '' && $id !== null)) : null;
            $establishment->setFilieresIds(!empty($filieresIds) ? $filieresIds : null);
        }

        // Médias
        if (isset($data['videoUrl'])) $establishment->setVideoUrl($data['videoUrl']);
        if (isset($data['documents'])) $establishment->setDocuments($data['documents']);

        // Gérer les campus comme des entités
        if (isset($data['campus']) && is_array($data['campus'])) {
            // Supprimer les campus existants qui ne sont plus dans la liste
            $existingCampusIds = [];
            foreach ($data['campus'] as $campusData) {
                if (isset($campusData['id']) && $campusData['id']) {
                    $existingCampusIds[] = (int)$campusData['id'];
                }
            }
            
            // Supprimer les campus qui ne sont plus dans la liste
            foreach ($establishment->getCampus() as $existingCampus) {
                if (!in_array($existingCampus->getId(), $existingCampusIds)) {
                    $establishment->removeCampus($existingCampus);
                    $this->entityManager->remove($existingCampus);
                }
            }
            
            // Ajouter ou mettre à jour les campus
            foreach ($data['campus'] as $campusData) {
                $campus = null;
                
                // Si un ID est fourni, chercher le campus existant
                if (isset($campusData['id']) && $campusData['id']) {
                    $campus = $this->campusRepository->find((int)$campusData['id']);
                    // Vérifier que le campus appartient bien à cet établissement
                    if ($campus && $campus->getEstablishment() !== $establishment) {
                        $campus = null; // Ignorer si le campus appartient à un autre établissement
                    }
                }
                
                // Créer un nouveau campus si nécessaire
                if (!$campus) {
                    $campus = new Campus();
                    $campus->setEstablishment($establishment);
                }
                
                // Mettre à jour les propriétés du campus
                if (isset($campusData['nom'])) $campus->setNom($campusData['nom']);
                
                // Gérer la relation avec City
                if (isset($campusData['cityId']) && $campusData['cityId']) {
                    $city = $this->cityRepository->find((int)$campusData['cityId']);
                    if ($city) {
                        $campus->setCity($city);
                    }
                } elseif (isset($campusData['ville'])) {
                    // Compatibilité : si ville est fourni comme string, chercher la City correspondante
                    $city = $this->cityRepository->findOneBy(['titre' => $campusData['ville']]);
                    if ($city) {
                        $campus->setCity($city);
                    }
                }
                
                if (isset($campusData['quartier'])) $campus->setQuartier($campusData['quartier']);
                if (isset($campusData['adresse'])) $campus->setAdresse($campusData['adresse']);
                if (isset($campusData['codePostal'])) $campus->setCodePostal($campusData['codePostal']);
                if (isset($campusData['telephone'])) $campus->setTelephone($campusData['telephone']);
                if (isset($campusData['email'])) $campus->setEmail($campusData['email']);
                if (isset($campusData['mapUrl'])) $campus->setMapUrl($campusData['mapUrl']);
                if (isset($campusData['ordre'])) $campus->setOrdre((int)$campusData['ordre']);
                
                // Ajouter le campus à l'établissement si ce n'est pas déjà fait
                if (!$establishment->getCampus()->contains($campus)) {
                    $establishment->addCampus($campus);
                }
                
                $this->entityManager->persist($campus);
            }
        }
    }

    /**
     * Enrichit les données de l'établissement avec des informations calculées
     */
    private function enrichEstablishmentData(array $data, Establishment $establishment): array
    {
        // Ajouter des informations calculées
        $data['url'] = '/etablissements/' . $establishment->getSlug();
        $data['canonicalUrl'] = $establishment->getCanonicalUrl() ?? $data['url'];
        
        // Formater les dates
        if (isset($data['createdAt'])) {
            $data['createdAtFormatted'] = $establishment->getCreatedAt()?->format('Y-m-d H:i:s');
        }
        if (isset($data['updatedAt'])) {
            $data['updatedAtFormatted'] = $establishment->getUpdatedAt()?->format('Y-m-d H:i:s');
        }

        // Structurer les réseaux sociaux
        $data['socialLinks'] = [
            'facebook' => $establishment->getFacebook(),
            'instagram' => $establishment->getInstagram(),
            'twitter' => $establishment->getTwitter(),
            'linkedin' => $establishment->getLinkedin(),
            'youtube' => $establishment->getYoutube(),
        ];

        // Structurer les informations de contact
        $data['contact'] = [
            'email' => $establishment->getEmail(),
            'telephone' => $establishment->getTelephone(),
            'siteWeb' => $establishment->getSiteWeb(),
            'adresse' => $establishment->getAdresse(),
            'codePostal' => $establishment->getCodePostal(),
        ];

        // Calculer les diplômes délivrés et la durée d'études depuis les filières associées
        $diplomesDelivres = [];
        $nbFilieres = 0;
        $dureesAnnees = [];
        
        foreach ($establishment->getFilieres() as $filiere) {
            $nbFilieres++;
            
            // Collecter les diplômes
            if ($filiere->getDiplome() && !in_array($filiere->getDiplome(), $diplomesDelivres)) {
                $diplomesDelivres[] = $filiere->getDiplome();
            }
            
            // Extraire le nombre d'années depuis nombreAnnees (format: "2 ans", "3 ans", etc.)
            $nombreAnnees = $filiere->getNombreAnnees();
            if ($nombreAnnees) {
                // Extraire le nombre depuis la chaîne (ex: "2 ans" -> 2)
                if (preg_match('/(\d+)/', $nombreAnnees, $matches)) {
                    $annees = (int)$matches[1];
                    if ($annees > 0) {
                        $dureesAnnees[] = $annees;
                    }
                }
            }
        }
        
        // Calculer la durée min et max
        $dureeEtudes = null;
        if (!empty($dureesAnnees)) {
            $minAnnee = min($dureesAnnees);
            $maxAnnee = max($dureesAnnees);
            
            if ($minAnnee === $maxAnnee) {
                $dureeEtudes = $minAnnee . ' ans';
            } else {
                $dureeEtudes = $minAnnee . '-' . $maxAnnee . ' ans';
            }
        }

        // Structurer les informations académiques
        $data['academicInfo'] = [
            'nbEtudiants' => $establishment->getNbEtudiants(),
            'nbFilieres' => $nbFilieres > 0 ? $nbFilieres : $establishment->getNbFilieres(),
            'diplomesDelivres' => $diplomesDelivres,
            'anneeCreation' => $establishment->getAnneeCreation(),
            'anneesEtudes' => $dureeEtudes ?? $establishment->getAnneesEtudes(),
            'accreditationEtat' => $establishment->isAccreditationEtat(),
            'concours' => $establishment->isConcours(),
            'echangeInternational' => $establishment->isEchangeInternational(),
            'bacObligatoire' => $establishment->isBacObligatoire(),
        ];
        
        // Ajouter directement les diplômes, le nombre de filières et la durée d'études pour faciliter l'accès
        $data['diplomes'] = $diplomesDelivres;
        $data['nbFilieres'] = $nbFilieres > 0 ? $nbFilieres : $establishment->getNbFilieres();
        $data['dureeEtudes'] = $dureeEtudes ?? $establishment->getAnneesEtudes();

        // Structurer les médias
        $data['media'] = [
            'logo' => $establishment->getLogo(),
            'imageCouverture' => $establishment->getImageCouverture(),
            'videoUrl' => $establishment->getVideoUrl(),
            'photos' => $establishment->getPhotos() ?? [],
            'documents' => $establishment->getDocuments() ?? [],
        ];

        // Structurer les campus
        $campusData = [];
        foreach ($establishment->getCampus() as $campus) {
            $campusData[] = [
                'id' => $campus->getId(),
                'nom' => $campus->getNom(),
                'ville' => $campus->getVille(), // Retourne le titre de la City pour compatibilité
                'cityId' => $campus->getCity()?->getId(),
                'city' => $campus->getCity() ? [
                    'id' => $campus->getCity()->getId(),
                    'titre' => $campus->getCity()->getTitre(),
                    'longitude' => $campus->getCity()->getLongitude(),
                    'latitude' => $campus->getCity()->getLatitude(),
                ] : null,
                'quartier' => $campus->getQuartier(),
                'adresse' => $campus->getAdresse(),
                'codePostal' => $campus->getCodePostal(),
                'telephone' => $campus->getTelephone(),
                'email' => $campus->getEmail(),
                'mapUrl' => $campus->getMapUrl(),
                'ordre' => $campus->getOrdre(),
            ];
        }
        $data['campus'] = $campusData;

        // Structurer les informations SEO
        $data['seo'] = [
            'metaTitle' => $establishment->getMetaTitle(),
            'metaDescription' => $establishment->getMetaDescription(),
            'metaKeywords' => $establishment->getMetaKeywords(),
            'ogImage' => $establishment->getOgImage(),
            'canonicalUrl' => $establishment->getCanonicalUrl(),
            'schemaType' => $establishment->getSchemaType(),
            'noIndex' => $establishment->isNoIndex(),
        ];

        // Informations de localisation structurées
        $universite = $establishment->getUniversite();
        $universiteData = null;
        if ($universite) {
            $universiteData = [
                'id' => $universite->getId(),
                'nom' => $universite->getNom(),
                'sigle' => $universite->getSigle(),
                'nomArabe' => $universite->getNomArabe(),
                'ville' => $universite->getVille(),
                'region' => $universite->getRegion(),
                'pays' => $universite->getPays(),
                'type' => $universite->getType(),
                'logo' => $universite->getLogo(),
                'siteWeb' => $universite->getSiteWeb(),
            ];
        }
        $data['location'] = [
            'ville' => $establishment->getVille(),
            'villes' => $establishment->getVilles() ?? [],
            'pays' => $establishment->getPays(),
            'universite' => $universiteData,
            'universiteId' => $universite ? $universite->getId() : null,
        ];

        // Statuts et flags
        $data['flags'] = [
            'isActive' => $establishment->isActive(),
            'isRecommended' => $establishment->isRecommended(),
            'isSponsored' => $establishment->isSponsored(),
            'isFeatured' => $establishment->isFeatured(),
            'isComplet' => $establishment->isComplet(),
            'hasDetailPage' => $establishment->isHasDetailPage(),
            'status' => $establishment->getStatus(),
        ];

        // Calculer les secteurs associés avec logique de merge
        // 1. Récupérer les secteurs directement associés à l'établissement
        $establishmentSecteursIds = $establishment->getSecteursIds() ?? [];
        
        // 2. Récupérer les secteurs depuis les filières de l'établissement
        $filieresSecteursIds = [];
        foreach ($establishment->getFilieres() as $filiere) {
            $filiereSecteursIds = $filiere->getSecteursIds() ?? [];
            if (!empty($filiereSecteursIds)) {
                $filieresSecteursIds = array_merge($filieresSecteursIds, $filiereSecteursIds);
            }
        }
        
        // 3. Fusionner les secteurs (union unique)
        $allSecteursIds = array_unique(array_merge($establishmentSecteursIds, $filieresSecteursIds));
        
        // 4. Si l'établissement n'a pas de secteurs directement associés, utiliser ceux des filières
        if (empty($establishmentSecteursIds) && !empty($filieresSecteursIds)) {
            $allSecteursIds = array_unique($filieresSecteursIds);
        }
        
        // 5. Récupérer les informations complètes des secteurs (incluant keywords et métiers pour la recherche)
        $secteursData = [];
        foreach ($allSecteursIds as $secteurId) {
            $secteur = $this->secteurRepository->find($secteurId);
            if ($secteur) {
                $secteursData[] = [
                    'id' => $secteur->getId(),
                    'titre' => $secteur->getTitre(),
                    'code' => $secteur->getCode(),
                    'icon' => $secteur->getIcon(),
                    'image' => $secteur->getImage(),
                    'keywords' => $secteur->getKeywords(), // Pour la recherche par keywords
                    'metiers' => $secteur->getMetiers(), // Pour la recherche par métiers
                ];
            }
        }
        
        // 6. Ajouter les secteurs dans les données enrichies
        $data['secteurs'] = $secteursData;
        $data['secteursIds'] = array_values($allSecteursIds); // Array values pour réindexer

        // Debug: logger les secteurs pour vérifier
        if (!empty($allSecteursIds)) {
            $this->logger->info('Secteurs calculés pour l\'établissement', [
                'establishment_id' => $establishment->getId(),
                'establishment_nom' => $establishment->getNom(),
                'establishment_secteurs_ids' => $establishmentSecteursIds,
                'filieres_secteurs_ids' => $filieresSecteursIds,
                'merged_secteurs_ids' => $allSecteursIds,
                'secteurs_count' => count($secteursData)
            ]);
        }

        return $data;
    }

    /**
     * Récupère les recommandations d'établissements basées sur le test d'orientation
     */
    #[Route('/recommendations', name: 'recommendations', methods: ['GET'])]
    public function getRecommendations(
        Request $request,
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        try {
            // Si #[CurrentUser] n'a pas fonctionné, essayer plusieurs méthodes pour récupérer l'utilisateur
            if (!$user) {
                // Méthode 1: Essayer depuis les paramètres de requête (userId ou phone)
                $userId = $request->query->get('userId');
                $userPhone = $request->query->get('phone');
                
                if ($userId) {
                    $user = $this->userRepository->find((int)$userId);
                    $this->logger->info('✅ [EstablishmentController] Utilisateur récupéré depuis userId param', [
                        'userId' => $userId,
                        'user_id' => $user ? $user->getId() : null
                    ]);
                } elseif ($userPhone) {
                    $user = $this->userRepository->findOneBy(['phone' => $userPhone]);
                    $this->logger->info('✅ [EstablishmentController] Utilisateur récupéré depuis phone param', [
                        'phone' => $userPhone,
                        'user_id' => $user ? $user->getId() : null
                    ]);
                }
                
                // Méthode 2: Si toujours pas trouvé, essayer depuis le token JWT
                if (!$user) {
                    $authHeader = $request->headers->get('Authorization');
                    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                        $token = $matches[1];
                        try {
                            // Décoder le token JWT manuellement (format: header.payload.signature)
                            $parts = explode('.', $token);
                            if (count($parts) === 3) {
                                // Décoder le payload (partie 2)
                                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                                
                                if ($payload && isset($payload['username'])) {
                                    $username = $payload['username'];
                                    
                                    // Récupérer l'utilisateur par téléphone (le username est généralement le phone)
                                    $user = $this->userRepository->findOneBy(['phone' => $username]);
                                    
                                    // Si pas trouvé par téléphone, essayer par ID si username est numérique
                                    if (!$user && is_numeric($username)) {
                                        $user = $this->userRepository->find((int)$username);
                                    }
                                    
                                    $this->logger->info('✅ [EstablishmentController] Utilisateur récupéré depuis le token JWT', [
                                        'username' => $username,
                                        'user_id' => $user ? $user->getId() : null,
                                        'user_phone' => $user ? $user->getPhone() : null
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning('⚠️ [EstablishmentController] Erreur lors du décodage du token JWT', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            }
            
            $this->logger->info('📊 [EstablishmentController] Demande de recommandations d\'établissements', [
                'user' => $user ? $user->getId() : null,
                'user_phone' => $user ? $user->getPhone() : null,
                'headers' => $request->headers->all()
            ]);
            
            if (!$user) {
                $this->logger->info('ℹ️ [EstablishmentController] Utilisateur non authentifié - Retour de scores vides', [
                    'headers' => $request->headers->all()
                ]);
                return $this->json([
                    'success' => true,
                    'hasTest' => false,
                    'scores' => [],
                    'message' => 'Non authentifié'
                ]);
            }
            
            $this->logger->info('✅ [EstablishmentController] Utilisateur authentifié', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'user_phone' => $user->getPhone()
            ]);

            // Récupérer le test de diagnostic de l'utilisateur depuis TestSession
            // Essayer d'abord 'diagnostic', puis 'orientation' comme fallback
            $testSession = $this->testSessionRepository->findByUser($user->getId(), 'diagnostic');
            
            // Si pas de test diagnostic, essayer orientation
            if (!$testSession) {
                $testSession = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            }
            
            if (!$testSession || !$testSession->isIsCompleted()) {
                $this->logger->info('⚠️ [EstablishmentController] TestSession non trouvée ou non complétée', [
                    'testSession' => $testSession ? 'trouvée' : 'non trouvée',
                    'testType' => $testSession ? $testSession->getTestType() : null,
                    'isCompleted' => $testSession ? $testSession->isIsCompleted() : false
                ]);
                return $this->json([
                    'success' => true,
                    'hasTest' => false,
                    'scores' => [],
                    'message' => 'Test d\'orientation non complété'
                ]);
            }

            $this->logger->info('✅ [EstablishmentController] TestSession trouvée et complétée, calcul des scores...', [
                'testSessionId' => $testSession->getId(),
                'completedAt' => $testSession->getCompletedAt()?->format('Y-m-d H:i:s')
            ]);
            
            // Récupérer tous les établissements actifs
            $establishments = $this->establishmentRepository->findBy(['isActive' => true]);
            
            // Calculer les scores de recommandation pour chaque établissement
            // Retourner TOUS les scores calculés, pas seulement ceux >= 60 (seuil de recommandation)
            $scores = [];
            foreach ($establishments as $establishment) {
                $result = $this->recommendationService->calculateEstablishmentScore($establishment, $user);
                // Retourner le score même s'il est < 60, tant qu'il est > 0
                if (isset($result['score']) && $result['score'] > 0) {
                    $scores[$establishment->getId()] = (int) round($result['score']);
                }
            }
            
            // Calculer la complétude des données de test
            $currentStep = $testSession->getCurrentStep();
            $completeness = [
                'riasec' => !empty($currentStep['riasec']),
                'personality' => !empty($currentStep['personality']),
                'aptitude' => !empty($currentStep['aptitude']),
                'interests' => !empty($currentStep['interests']),
                'constraints' => !empty($currentStep['constraints'])
            ];
            
            // Calculer le pourcentage de complétude
            $completedCount = array_sum($completeness);
            $totalCount = count($completeness);
            $completenessPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
            
            $this->logger->info('✅ [EstablishmentController] Scores calculés', [
                'nombre_etablissements' => count($scores),
                'completeness' => $completenessPercentage . '%',
                'scores_sample' => array_slice($scores, 0, 5, true) // Log des 5 premiers scores pour debug
            ]);
            
            // Si aucun score n'a été généré, logger plus d'informations pour debug
            if (empty($scores)) {
                $this->logger->warning('⚠️ [EstablishmentController] Aucun score généré malgré un test complété', [
                    'testSessionId' => $testSession->getId(),
                    'currentStep_keys' => array_keys($currentStep ?? []),
                    'establishments_count' => count($establishments),
                    'sample_result' => !empty($establishments) ? $this->recommendationService->calculateEstablishmentScore($establishments[0], $user) : null
                ]);
            }

            return $this->json([
                'success' => true,
                'hasTest' => true,
                'scores' => $scores,
                'completeness' => [
                    'percentage' => $completenessPercentage,
                    'details' => $completeness,
                    'needsCompletion' => $completenessPercentage < 100
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul des recommandations d\'établissements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des recommandations: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
