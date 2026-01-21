<?php

namespace App\Controller\Api;

use App\Entity\Secteur;
use App\Entity\User;
use App\Entity\TestSession;
use App\Repository\SecteurRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\FiliereRepository;
use App\Repository\TestSessionRepository;
use App\Repository\UserRepository;
use App\Service\SecteurRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/secteurs', name: 'api_secteurs_')]
class SecteurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecteurRepository $secteurRepository,
        private EstablishmentRepository $establishmentRepository,
        private FiliereRepository $filiereRepository,
        private TestSessionRepository $testSessionRepository,
        private UserRepository $userRepository,
        private SecteurRecommendationService $recommendationService,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste tous les secteurs (avec filtres)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            // Récupérer les filtres
            $filters = [
                'search' => $request->query->get('search'),
                'status' => $request->query->get('status'),
                'isActivate' => $request->query->get('isActivate') !== null ? (bool) $request->query->get('isActivate') : null,
                'isComplet' => $request->query->get('isComplet') !== null ? (bool) $request->query->get('isComplet') : null,
                'afficherDansTest' => $request->query->get('afficherDansTest') !== null ? filter_var($request->query->get('afficherDansTest'), FILTER_VALIDATE_BOOLEAN) : null,
            ];

            // Nettoyer les filtres vides
            $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Récupérer les secteurs avec filtres
            $secteurs = $this->secteurRepository->findWithFilters($filters);
            $total = count($secteurs);

            // Pagination
            $secteurs = array_slice($secteurs, $offset, $limit);

            // Sérialiser les données
            $data = $this->serializer->normalize($secteurs, null, [
                'groups' => ['secteur:list']
            ]);

            // Récupérer les recommandations si l'utilisateur est authentifié
            $recommendations = [];
            $completeness = null;
            if ($user) {
                try {
                    // Récupérer le test de diagnostic de l'utilisateur
                    $testSession = $this->testSessionRepository->findByUser($user->getId(), 'diagnostic');
                    if (!$testSession) {
                        $testSession = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
                    }
                    
                    if ($testSession && $testSession->isIsCompleted()) {
                        // Calculer les scores de recommandation
                        $scores = $this->recommendationService->calculateRecommendationScores($testSession);
                        $recommendations = $scores;
                        
                        // Calculer la complétude
                        $currentStep = $testSession->getCurrentStep();
                        $completenessDetails = [
                            'riasec' => !empty($currentStep['riasec']),
                            'personality' => !empty($currentStep['personality']),
                            'aptitude' => !empty($currentStep['aptitude']),
                            'interests' => !empty($currentStep['interests']),
                            'constraints' => !empty($currentStep['constraints'])
                        ];
                        $completedCount = array_sum($completenessDetails);
                        $totalCount = count($completenessDetails);
                        $completenessPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                        
                        $completeness = [
                            'percentage' => $completenessPercentage,
                            'details' => $completenessDetails,
                            'needsCompletion' => $completenessPercentage < 100
                        ];
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, continuer sans recommandations
                    $this->logger->warning('Erreur lors de la récupération des recommandations dans list', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Enrichir les données
            foreach ($data as $index => $secteurData) {
                $secteurId = $secteurData['id'];
                
                // Formater le salaire (enlever .00 et utiliser Dhs)
                $salaireMin = $secteurData['salaireMin'] ? rtrim(rtrim((string)$secteurData['salaireMin'], '0'), '.') : null;
                $salaireMax = $secteurData['salaireMax'] ? rtrim(rtrim((string)$secteurData['salaireMax'], '0'), '.') : null;
                
                if ($salaireMin && $salaireMax) {
                    $data[$index]['salaire'] = $salaireMin . ' - ' . $salaireMax . ' Dhs';
                } elseif ($salaireMin) {
                    $data[$index]['salaire'] = 'À partir de ' . $salaireMin . ' Dhs';
                } elseif ($salaireMax) {
                    $data[$index]['salaire'] = 'Jusqu\'à ' . $salaireMax . ' Dhs';
                } else {
                    $data[$index]['salaire'] = 'Variable';
                }

                // Compter les métiers
                $data[$index]['nbMetiers'] = $secteurData['metiers'] ? count($secteurData['metiers']) : 0;

                // Compter les établissements associés à ce secteur
                $data[$index]['nbEcoles'] = $this->establishmentRepository->countBySecteur($secteurId);

                // Compter les filières directement associées à ce secteur (via secteursIds)
                $data[$index]['nbFilieres'] = $this->filiereRepository->countBySecteur($secteurId);
            }

            $response = [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit)
                ]
            ];
            
            // Ajouter les recommandations si disponibles
            if (!empty($recommendations)) {
                $response['recommendations'] = [
                    'scores' => $recommendations,
                    'completeness' => $completeness
                ];
            }
            
            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des secteurs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des secteurs: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère un secteur par ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $secteur = $this->secteurRepository->find($id);

            if (!$secteur) {
                return $this->json([
                    'success' => false,
                    'message' => 'Secteur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->normalize($secteur, null, [
                'groups' => ['secteur:read']
            ]);

            // Enrichir les données
                // Formater le salaire (enlever .00 et utiliser Dhs)
                $salaireMin = $data['salaireMin'] ? rtrim(rtrim((string)$data['salaireMin'], '0'), '.') : null;
                $salaireMax = $data['salaireMax'] ? rtrim(rtrim((string)$data['salaireMax'], '0'), '.') : null;
                
                if ($salaireMin && $salaireMax) {
                    $data['salaire'] = $salaireMin . ' - ' . $salaireMax . ' Dhs';
                } elseif ($salaireMin) {
                    $data['salaire'] = 'À partir de ' . $salaireMin . ' Dhs';
                } elseif ($salaireMax) {
                    $data['salaire'] = 'Jusqu\'à ' . $salaireMax . ' Dhs';
                } else {
                    $data['salaire'] = 'Variable';
                }

            $data['nbMetiers'] = $data['metiers'] ? count($data['metiers']) : 0;

            // Compter les établissements associés à ce secteur
            $secteurId = $data['id'];
            $data['nbEcoles'] = $this->establishmentRepository->countBySecteur($secteurId);

            // Compter les filières directement associées à ce secteur (via secteursIds)
            $data['nbFilieres'] = $this->filiereRepository->countBySecteur($secteurId);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée un nouveau secteur
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

            $secteur = new Secteur();
            $this->hydrateSecteur($secteur, $data);

            $this->entityManager->persist($secteur);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($secteur, null, [
                'groups' => ['secteur:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Secteur créé avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du secteur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour un secteur
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $secteur = $this->secteurRepository->find($id);

            if (!$secteur) {
                return $this->json([
                    'success' => false,
                    'message' => 'Secteur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->hydrateSecteur($secteur, $data);

            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($secteur, null, [
                'groups' => ['secteur:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Secteur mis à jour avec succès',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du secteur', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime un secteur
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $secteur = $this->secteurRepository->find($id);

            if (!$secteur) {
                return $this->json([
                    'success' => false,
                    'message' => 'Secteur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($secteur);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Secteur supprimé avec succès'
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

            $secteurs = $this->secteurRepository->findBy(['id' => $ids]);

            foreach ($secteurs as $secteur) {
                switch ($action) {
                    case 'activate':
                        $secteur->setIsActivate(true);
                        $secteur->setStatus('Actif');
                        break;
                    case 'deactivate':
                        $secteur->setIsActivate(false);
                        $secteur->setStatus('Inactif');
                        break;
                    case 'delete':
                        $this->entityManager->remove($secteur);
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
                'message' => count($secteurs) . ' secteur(s) ' . $action . ' avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action en masse: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les scores de recommandation pour tous les secteurs basés sur le test d'orientation
     */
    #[Route('/recommendations', name: 'recommendations', methods: ['GET'])]
    public function getRecommendations(
        Request $request,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        try {
            // Si #[CurrentUser] n'a pas fonctionné, essayer plusieurs méthodes pour récupérer l'utilisateur
            if (!$user) {
                // Méthode 1: Essayer depuis les paramètres de requête (userId ou phone)
                $userId = $request->query->get('userId');
                $userPhone = $request->query->get('phone');
                
                if ($userId) {
                    $user = $this->userRepository->find((int)$userId);
                    $this->logger->info('✅ [SecteurController] Utilisateur récupéré depuis userId param', [
                        'userId' => $userId,
                        'user_id' => $user ? $user->getId() : null
                    ]);
                } elseif ($userPhone) {
                    $user = $this->userRepository->findOneBy(['phone' => $userPhone]);
                    $this->logger->info('✅ [SecteurController] Utilisateur récupéré depuis phone param', [
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
                                    
                                    $this->logger->info('✅ [SecteurController] Utilisateur récupéré depuis le token JWT', [
                                        'username' => $username,
                                        'user_id' => $user ? $user->getId() : null,
                                        'user_phone' => $user ? $user->getPhone() : null
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning('⚠️ [SecteurController] Erreur lors du décodage du token JWT', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            }
            
            // Log pour déboguer
            $this->logger->info('🔍 [SecteurController] getRecommendations appelé', [
                'user' => $user ? $user->getId() : 'null',
                'user_phone' => $user ? $user->getPhone() : null,
                'headers' => $request->headers->all(),
                'authorization' => $request->headers->get('Authorization') ? 'présent' : 'absent'
            ]);
            
            if (!$user) {
                $this->logger->info('ℹ️ [SecteurController] Utilisateur non authentifié - Retour de scores vides', [
                    'headers' => $request->headers->all()
                ]);
                return $this->json([
                    'success' => true,
                    'hasTest' => false,
                    'scores' => [],
                    'message' => 'Non authentifié'
                ]);
            }
            
            $this->logger->info('✅ [SecteurController] Utilisateur authentifié', [
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
                $this->logger->info('⚠️ [SecteurController] TestSession non trouvée ou non complétée', [
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

            $this->logger->info('✅ [SecteurController] TestSession trouvée et complétée, calcul des scores...', [
                'testSessionId' => $testSession->getId(),
                'completedAt' => $testSession->getCompletedAt()?->format('Y-m-d H:i:s')
            ]);
            
            // Calculer les scores de recommandation
            $scores = $this->recommendationService->calculateRecommendationScores($testSession);
            
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
            
            $this->logger->info('✅ [SecteurController] Scores calculés', [
                'nombre_secteurs' => count($scores),
                'completeness' => $completenessPercentage . '%'
            ]);

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
            $this->logger->error('Erreur lors du calcul des recommandations de secteurs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des recommandations: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Hydrate l'entité Secteur avec les données
     */
    private function hydrateSecteur(Secteur $secteur, array $data): void
    {
        // Informations de base
        if (isset($data['titre'])) $secteur->setTitre($data['titre']);
        if (isset($data['code'])) $secteur->setCode(strtoupper($data['code']));
        if (isset($data['description'])) $secteur->setDescription($data['description']);
        if (isset($data['icon'])) $secteur->setIcon($data['icon']);

        // Image
        if (isset($data['image']) && $data['image'] !== '') {
            $secteur->setImage($data['image']);
        } elseif (array_key_exists('image', $data) && $data['image'] === '') {
            $secteur->setImage(null);
        }

        // Tableaux JSON
        if (isset($data['softSkills'])) {
            $secteur->setSoftSkills(is_array($data['softSkills']) ? $data['softSkills'] : null);
        }
        if (isset($data['personnalites'])) {
            $secteur->setPersonnalites(is_array($data['personnalites']) ? $data['personnalites'] : null);
        }
        if (isset($data['bacs'])) {
            $secteur->setBacs(is_array($data['bacs']) ? $data['bacs'] : null);
        }
        if (isset($data['typeBacs'])) {
            $secteur->setTypeBacs(is_array($data['typeBacs']) ? $data['typeBacs'] : null);
        }
        if (isset($data['avantages'])) {
            $secteur->setAvantages(is_array($data['avantages']) ? $data['avantages'] : null);
        }
        if (isset($data['inconvenients'])) {
            $secteur->setInconvenients(is_array($data['inconvenients']) ? $data['inconvenients'] : null);
        }
        if (isset($data['metiers'])) {
            $secteur->setMetiers(is_array($data['metiers']) ? $data['metiers'] : null);
        }
        if (isset($data['keywords'])) {
            $secteur->setKeywords(is_array($data['keywords']) ? $data['keywords'] : null);
        } elseif (array_key_exists('keywords', $data) && $data['keywords'] === null) {
            $secteur->setKeywords(null);
        }

        // Salaires
        if (isset($data['salaireMin'])) {
            $secteur->setSalaireMin($data['salaireMin'] === '' || $data['salaireMin'] === null ? null : (string) $data['salaireMin']);
        }
        if (isset($data['salaireMax'])) {
            $secteur->setSalaireMax($data['salaireMax'] === '' || $data['salaireMax'] === null ? null : (string) $data['salaireMax']);
        }

        // Statut
        if (isset($data['isActivate'])) {
            $secteur->setIsActivate((bool) $data['isActivate']);
        }
        if (isset($data['status'])) {
            $secteur->setStatus($data['status']);
        }
        if (isset($data['isComplet'])) {
            $secteur->setIsComplet((bool) $data['isComplet']);
        }
        if (isset($data['afficherDansTest'])) {
            $secteur->setAfficherDansTest((bool) $data['afficherDansTest']);
        }
    }
}
