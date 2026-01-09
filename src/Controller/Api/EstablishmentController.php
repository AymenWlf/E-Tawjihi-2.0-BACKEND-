<?php

namespace App\Controller\Api;

use App\Entity\Establishment;
use App\Entity\Campus;
use App\Entity\City;
use App\Repository\EstablishmentRepository;
use App\Repository\CampusRepository;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/establishments', name: 'api_establishments_')]
class EstablishmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EstablishmentRepository $establishmentRepository,
        private CampusRepository $campusRepository,
        private CityRepository $cityRepository,
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
        if ($request->query->has('isActive')) {
            $filters['isActive'] = filter_var($request->query->get('isActive'), FILTER_VALIDATE_BOOLEAN);
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

        // Retirer les filtres vides
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        $establishments = $this->establishmentRepository->findWithFilters($filters);
        $total = count($establishments);

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 18)));
        $offset = ($page - 1) * $limit;

        $paginatedEstablishments = array_slice($establishments, $offset, $limit);

        $data = $this->serializer->normalize($paginatedEstablishments, null, [
            'groups' => ['establishment:list']
        ]);

        // Enrichir les données avec les villes calculées depuis les campus et les diplômes depuis les filières
        foreach ($data as $index => $establishmentData) {
            $establishment = $paginatedEstablishments[$index];
            $villesFromCampus = [];
            
            // Extraire les villes uniques depuis les campus
            foreach ($establishment->getCampus() as $campus) {
                $ville = $campus->getVille(); // Retourne le titre de la City
                if ($ville && !in_array($ville, $villesFromCampus)) {
                    $villesFromCampus[] = $ville;
                }
            }
            
            // Mettre à jour les villes dans les données
            $data[$index]['villes'] = $villesFromCampus;
            
            // Si aucune ville depuis les campus, utiliser la ville principale de l'établissement
            if (empty($villesFromCampus) && $establishment->getVille()) {
                $data[$index]['villes'] = [$establishment->getVille()];
            }
            
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
            
            // Ajouter les diplômes, le nombre de filières et la durée d'études
            $data[$index]['diplomes'] = $diplomesDelivres;
            $data[$index]['nbFilieres'] = $nbFilieres > 0 ? $nbFilieres : ($establishment->getNbFilieres() ?? 0);
            $data[$index]['dureeEtudes'] = $dureeEtudes ?? $establishment->getAnneesEtudes();
        }

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
        if (isset($data['universite'])) $establishment->setUniversite($data['universite']);
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
        $data['location'] = [
            'ville' => $establishment->getVille(),
            'villes' => $establishment->getVilles() ?? [],
            'pays' => $establishment->getPays(),
            'universite' => $establishment->getUniversite(),
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

        return $data;
    }
}
