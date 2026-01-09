<?php

namespace App\Controller\Api;

use App\Entity\TestSession;
use App\Entity\TestAnswer;
use App\Entity\User;
use App\Repository\TestSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/orientation-test')]
class OrientationTestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TestSessionRepository $testSessionRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/start', name: 'api_orientation_test_start', methods: ['POST'])]
    public function startTest(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $language = $data['selectedLanguage'] ?? 'fr';

        // Vérifier si l'utilisateur a déjà une session
        $existingSession = $this->testSessionRepository->findByUser($user->getId(), 'orientation');

        if ($existingSession) {
            // Si la session est complétée et que l'utilisateur veut recommencer
            if ($existingSession->isIsCompleted() && isset($data['restart']) && $data['restart'] === true) {
                // Réinitialiser la session existante
                $existingSession->setIsCompleted(false);
                $existingSession->setStartedAt(new \DateTimeImmutable());
                $existingSession->setCompletedAt(null);
                $existingSession->setDuration(null);

                // Supprimer les anciennes réponses
                foreach ($existingSession->getAnswers() as $answer) {
                    $this->em->remove($answer);
                }

                // Réinitialiser les métadonnées
                $metadata = [
                    "selectedLanguage" => $language,
                    "startedAt" => (new \DateTimeImmutable())->format('c'),
                    "stepDurations" => ["welcome" => 0],
                    "version" => "1.0"
                ];
                $existingSession->setMetadata($metadata);

                // Réinitialiser l'étape actuelle
                $welcomeStep = [
                    "selectedLanguage" => $language,
                    "session" => [
                        "testType" => "welcome",
                        "startedAt" => (new \DateTimeImmutable())->format('c'),
                        "completedAt" => (new \DateTimeImmutable())->format('c'),
                        "duration" => 0,
                        "language" => $language,
                        "totalQuestions" => 0,
                        "questions" => []
                    ],
                    "currentStep" => "personalInfo",
                    "steps" => [],
                    "completedSteps" => []
                ];
                $existingSession->setCurrentStep($welcomeStep);
                $existingSession->setLanguage($language);

                $this->em->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Session réinitialisée',
                    'sessionId' => $existingSession->getId(),
                    'data' => [
                        'metadata' => $existingSession->getMetadata(),
                        'currentStep' => $existingSession->getCurrentStep()
                    ]
                ]);
            }

            // Sinon, on renvoie la session existante
            return new JsonResponse([
                'success' => true,
                'message' => $existingSession->isIsCompleted() ? 'Session déjà complétée' : 'Reprise de la session',
                'sessionId' => $existingSession->getId(),
                'isCompleted' => $existingSession->isIsCompleted(),
                'data' => [
                    'metadata' => $existingSession->getMetadata(),
                    'currentStep' => $existingSession->getCurrentStep()
                ]
            ]);
        }

        // Créer une nouvelle session de test
        $session = new TestSession();
        $session->setUser($user);
        $session->setLanguage($language);
        $session->setTestType('orientation');
        $session->setStartedAt(new \DateTimeImmutable());
        $session->setTotalQuestions(0);
        $session->setIsCompleted(false);

        // Définir les métadonnées
        $metadata = [
            "selectedLanguage" => $language,
            "startedAt" => (new \DateTimeImmutable())->format('c'),
            "stepDurations" => ["welcome" => 0],
            "version" => "1.0"
        ];
        $session->setMetadata($metadata);

        // Définir l'étape actuelle (welcome)
        $welcomeStep = [
            "selectedLanguage" => $language,
            "session" => [
                "testType" => "welcome",
                "startedAt" => (new \DateTimeImmutable())->format('c'),
                "completedAt" => (new \DateTimeImmutable())->format('c'),
                "duration" => 0,
                "language" => $language,
                "totalQuestions" => 0,
                "questions" => []
            ],
            "currentStep" => "welcome",
            "steps" => ["welcome"],
            "completedSteps" => ["welcome"]
        ];
        $session->setCurrentStep($welcomeStep);

        $this->em->persist($session);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Nouvelle session créée',
            'sessionId' => $session->getId(),
            'data' => [
                'metadata' => $session->getMetadata(),
                'currentStep' => $session->getCurrentStep()
            ]
        ]);
    }

    #[Route('/save-step', name: 'api_orientation_test_save_step', methods: ['POST'])]
    public function saveStep(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['stepName']) || !isset($data['stepData'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $stepName = $data['stepName'];
            $stepData = $data['stepData'];
            $stepNumber = $data['stepNumber'] ?? null;
            $duration = $data['duration'] ?? 0;
            $language = $data['language'] ?? 'fr';

            // Récupérer la session de l'utilisateur
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            // Si la session n'existe pas, la créer automatiquement
            if (!$session) {
                $session = new TestSession();
                $session->setUser($user);
                $session->setLanguage($language);
                $session->setTestType('orientation');
                $session->setStartedAt(new \DateTimeImmutable());
                $session->setTotalQuestions(0);
                $session->setIsCompleted(false);

                // Définir les métadonnées
                $metadata = [
                    "selectedLanguage" => $language,
                    "startedAt" => (new \DateTimeImmutable())->format('c'),
                    "stepDurations" => [],
                    "version" => "1.0"
                ];
                $session->setMetadata($metadata);

                // Définir l'étape actuelle initiale
                $initialStep = [
                    "selectedLanguage" => $language,
                    "session" => [
                        "testType" => "welcome",
                        "startedAt" => (new \DateTimeImmutable())->format('c'),
                        "duration" => 0,
                        "language" => $language,
                        "totalQuestions" => 0,
                        "questions" => []
                    ],
                    "currentStep" => "welcome",
                    "steps" => ["welcome"],
                    "completedSteps" => []
                ];
                $session->setCurrentStep($initialStep);
                
                $this->em->persist($session);
            }

            // Mettre à jour les métadonnées
            $this->updateSessionMetadata($session, $stepName, $duration);

            // Traiter l'étape selon son type
            if ($stepName === 'personalInfo') {
                $this->processPersonalInfoStep($session, $stepData, $stepName, $user);
            } else {
                $this->processGenericStep($session, $stepData, $stepName);
            }

            // IMPORTANT: Mettre à jour le tracking APRÈS avoir sauvegardé les données
            // Cela garantit que l'étape est ajoutée à completedSteps
            $this->updateStepTracking($session, $stepName);

            // Enregistrer les réponses
            $this->saveStepAnswers($session, $stepData, $stepNumber ?? 1);

            // Vérifier si toutes les étapes sont complétées
            $this->checkAndCompleteTest($session);

            // S'assurer que la session est persistée
            $this->em->persist($session);
            $this->em->flush();

            // Vérifier que les données sont bien sauvegardées
            $this->em->refresh($session);
            $savedCurrentStep = $session->getCurrentStep();
            
            // Vérifier que l'étape est bien dans completedSteps
            $savedCompletedSteps = $savedCurrentStep['completedSteps'] ?? [];
            $stepIsCompleted = in_array($stepName, $savedCompletedSteps);
            
            $this->logger->info('Étape sauvegardée', [
                'stepName' => $stepName,
                'sessionId' => $session->getId(),
                'hasStepData' => isset($savedCurrentStep[$stepName]),
                'stepIsInCompletedSteps' => $stepIsCompleted,
                'completedSteps' => $savedCompletedSteps,
                'completedCount' => count($savedCompletedSteps),
                'isCompleted' => $session->isIsCompleted(),
                'progress' => round((count($savedCompletedSteps) / 8) * 100),
                'stepDataKeys' => isset($savedCurrentStep[$stepName]) ? array_keys($savedCurrentStep[$stepName]) : []
            ]);
            
            // Si l'étape n'est pas dans completedSteps, c'est un problème
            if (!$stepIsCompleted && in_array($stepName, ['personalInfo', 'riasec', 'personality', 'aptitude', 'interests', 'career', 'constraints', 'languages'])) {
                $this->logger->warning('Étape sauvegardée mais pas dans completedSteps', [
                    'stepName' => $stepName,
                    'sessionId' => $session->getId(),
                    'completedSteps' => $savedCompletedSteps
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Étape sauvegardée avec succès',
                'sessionId' => $session->getId(),
                'isCompleted' => $session->isIsCompleted(),
                'data' => [
                    'metadata' => $session->getMetadata(),
                    'currentStep' => $session->getCurrentStep()
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de l\'étape du test d\'orientation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/resume', name: 'api_orientation_test_resume', methods: ['GET'])]
    public function resumeTest(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
        
        if (!$session) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Session introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

                return new JsonResponse([
                    'success' => true,
            'sessionId' => $session->getId(),
            'data' => [
                'metadata' => $session->getMetadata(),
                'currentStep' => $session->getCurrentStep(),
                'isCompleted' => $session->isIsCompleted()
            ]
        ]);
    }

    #[Route('/complete', name: 'api_orientation_test_complete', methods: ['POST'])]
    public function completeTest(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
        
        if (!$session) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Session introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($session->isIsCompleted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce test est déjà terminé'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Marquer la session comme terminée
        $session->setIsCompleted(true);
        $session->setCompletedAt(new \DateTimeImmutable());

        // Calculer la durée totale
        if ($session->getStartedAt()) {
            $duration = $session->getCompletedAt()->getTimestamp() - $session->getStartedAt()->getTimestamp();
            $session->setDuration($duration);
        }

        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Test complété avec succès',
            'sessionId' => $session->getId(),
            'data' => [
                'metadata' => $session->getMetadata(),
                'currentStep' => $session->getCurrentStep(),
                'completedAt' => $session->getCompletedAt()->format('c'),
                'duration' => $session->getDuration()
            ]
        ]);
    }

    #[Route('/repair', name: 'api_orientation_test_repair', methods: ['POST'])]
    public function repair(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            if (!$session) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Aucune session de test trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $currentStep = $session->getCurrentStep();
            $allExpectedSteps = [
                'personalInfo',
                'riasec',
                'personality',
                'aptitude',
                'interests',
                'career',
                'constraints',
                'languages'
            ];

            // Réparer completedSteps en vérifiant quelles étapes ont des données
            $completedSteps = [];
            $steps = [];
            $lastCompletedStep = null;

            foreach ($allExpectedSteps as $stepName) {
                $hasData = isset($currentStep[$stepName]) && 
                           is_array($currentStep[$stepName]) && 
                           !empty($currentStep[$stepName]);
                
                if ($hasData) {
                    $completedSteps[] = $stepName;
                    $steps[] = $stepName;
                    $lastCompletedStep = $stepName;
                }
            }

            // Mettre à jour currentStep
            $currentStep['completedSteps'] = array_values(array_unique($completedSteps));
            $currentStep['steps'] = array_values(array_unique($steps));
            
            // Définir l'étape actuelle : si toutes les étapes sont complétées, rester sur la dernière
            // Sinon, définir la prochaine étape à compléter
            if (count($completedSteps) === count($allExpectedSteps)) {
                $currentStep['currentStep'] = $lastCompletedStep;
                $session->setIsCompleted(true);
                if (!$session->getCompletedAt()) {
                    $session->setCompletedAt(new \DateTimeImmutable());
                }
            } else {
                // Trouver la prochaine étape non complétée
                $nextStep = null;
                foreach ($allExpectedSteps as $stepName) {
                    if (!in_array($stepName, $completedSteps)) {
                        $nextStep = $stepName;
                        break;
                    }
                }
                $currentStep['currentStep'] = $nextStep ?: 'personalInfo';
            }

            $session->setCurrentStep($currentStep);
            $this->em->flush();

            $this->logger->info('Session réparée', [
                'sessionId' => $session->getId(),
                'completedSteps' => $completedSteps,
                'currentStep' => $currentStep['currentStep'],
                'isCompleted' => $session->isIsCompleted()
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Session réparée avec succès',
                'data' => [
                    'completedSteps' => $completedSteps,
                    'currentStep' => $currentStep['currentStep'],
                    'isCompleted' => $session->isIsCompleted(),
                    'progress' => count($completedSteps) > 0 ? round((count($completedSteps) / count($allExpectedSteps)) * 100) : 0
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la réparation de la session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la réparation: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/analyze', name: 'api_orientation_test_analyze', methods: ['GET'])]
    public function analyze(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            if (!$session) {
                return new JsonResponse([
                    'success' => true,
                    'hasTest' => false,
                    'analysis' => [
                        'message' => 'Aucune session de test trouvée',
                        'userId' => $user->getId(),
                        'userEmail' => $user->getEmail()
                    ]
                ]);
            }

            $currentStep = $session->getCurrentStep();
            $metadata = $session->getMetadata();
            $answers = $session->getAnswers();

            // Analyser les étapes complétées
            $completedSteps = $currentStep['completedSteps'] ?? [];
            $steps = $currentStep['steps'] ?? [];
            $currentStepName = $currentStep['currentStep'] ?? null;

            // Liste des étapes attendues
            $allExpectedSteps = [
                'personalInfo',
                'riasec',
                'personality',
                'aptitude',
                'interests',
                'career',
                'constraints',
                'languages'
            ];

            // Analyser chaque étape
            $stepsAnalysis = [];
            foreach ($allExpectedSteps as $stepName) {
                $stepData = $currentStep[$stepName] ?? null;
                $hasData = $stepData !== null && is_array($stepData) && !empty($stepData);
                
                $stepAnalysis = [
                    'name' => $stepName,
                    'isCompleted' => in_array($stepName, $completedSteps),
                    'hasData' => $hasData,
                    'dataKeys' => $hasData ? array_keys($stepData) : [],
                    'dataSize' => $hasData ? count($stepData, COUNT_RECURSIVE) : 0
                ];

                // Détails spécifiques par étape
                if ($hasData) {
                    if ($stepName === 'riasec' && isset($stepData['riasec']['scores'])) {
                        $stepAnalysis['scores'] = $stepData['riasec']['scores'];
                    }
                    if ($stepName === 'personality' && isset($stepData['personality']['scores'])) {
                        $stepAnalysis['scores'] = $stepData['personality']['scores'];
                    }
                    if ($stepName === 'aptitude' && isset($stepData['aptitude']['scores'])) {
                        $stepAnalysis['scores'] = $stepData['aptitude']['scores'];
                    }
                    if ($stepName === 'personalInfo') {
                        $stepAnalysis['hasNotes'] = isset($stepData['notes']);
                        $stepAnalysis['hasFirstName'] = isset($stepData['firstName']);
                        $stepAnalysis['hasLastName'] = isset($stepData['lastName']);
                    }
                }

                $stepsAnalysis[] = $stepAnalysis;
            }

            // Compter les réponses par étape
            $answersByStep = [];
            foreach ($answers as $answer) {
                $stepNum = $answer->getStepNumber();
                if (!isset($answersByStep[$stepNum])) {
                    $answersByStep[$stepNum] = 0;
                }
                $answersByStep[$stepNum]++;
            }

            // Calculer la progression
            $totalSteps = count($allExpectedSteps);
            $completedCount = count(array_intersect($completedSteps, $allExpectedSteps));
            $progressPercentage = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;

            return new JsonResponse([
                'success' => true,
                'hasTest' => true,
                'analysis' => [
                    'session' => [
                        'id' => $session->getId(),
                        'testType' => $session->getTestType(),
                        'startedAt' => $session->getStartedAt()?->format('c'),
                        'completedAt' => $session->getCompletedAt()?->format('c'),
                        'duration' => $session->getDuration(),
                        'language' => $session->getLanguage(),
                        'isCompleted' => $session->isIsCompleted(),
                        'totalQuestions' => $session->getTotalQuestions()
                    ],
                    'progress' => [
                        'currentStep' => $currentStepName,
                        'completedSteps' => $completedSteps,
                        'allSteps' => $steps,
                        'completedCount' => $completedCount,
                        'totalSteps' => $totalSteps,
                        'progressPercentage' => $progressPercentage,
                        'isFullyCompleted' => $session->isIsCompleted() || $progressPercentage === 100
                    ],
                    'stepsAnalysis' => $stepsAnalysis,
                    'answers' => [
                        'total' => count($answers),
                        'byStep' => $answersByStep
                    ],
                    'metadata' => $metadata
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'analyse des données de test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-all', name: 'api_orientation_test_get_all', methods: ['GET'])]
    #[Route('/my-test', name: 'api_orientation_test_my_test', methods: ['GET'])]
    public function getAll(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            if (!$session) {
                return new JsonResponse([
                    'success' => true,
                    'hasTest' => false,
                    'data' => null
                ]);
            }

            $currentStep = $session->getCurrentStep();
            $metadata = $session->getMetadata();

            // Calculer la durée totale si disponible
            $totalDuration = 0;
            if (isset($metadata['stepDurations'])) {
                $totalDuration = array_sum($metadata['stepDurations']);
            }

            // Log pour déboguer
            $this->logger->info('Récupération des données du test', [
                'sessionId' => $session->getId(),
                'hasPersonalInfo' => isset($currentStep['personalInfo']),
                'personalInfoKeys' => isset($currentStep['personalInfo']) ? array_keys($currentStep['personalInfo']) : [],
                'currentStepKeys' => array_keys($currentStep)
            ]);

            // Retourner les données dans le format attendu par le frontend
            // Les données sont stockées dans currentStep avec les clés: personalInfo, riasec, personality, etc.
            return new JsonResponse([
                'success' => true,
                'hasTest' => true,
                'data' => [
                    'currentStep' => $currentStep['currentStep'] ?? null,
                    'isCompleted' => $session->isIsCompleted(),
                    'startedAt' => $session->getStartedAt()?->format('Y-m-d H:i:s'),
                    'completedAt' => $session->getCompletedAt()?->format('Y-m-d H:i:s'),
                    // Extraire les données depuis currentStep
                    'personalInfo' => $currentStep['personalInfo'] ?? null,
                    'riasec' => $currentStep['riasec'] ?? null,
                    'personality' => $currentStep['personality'] ?? null,
                    'aptitude' => $currentStep['aptitude'] ?? null,
                    'interests' => $currentStep['interests'] ?? null,
                    'career' => $currentStep['career'] ?? $currentStep['careerCompatibility'] ?? null,
                    'constraints' => $currentStep['constraints'] ?? null,
                    'languages' => $currentStep['languageSkills'] ?? $currentStep['languages'] ?? null,
                    'testMetadata' => $metadata,
                    'totalDuration' => $totalDuration,
                    // Retourner aussi currentStep complet pour compatibilité
                    'currentStepData' => $currentStep
                ],
                'sessionId' => $session->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du test d\'orientation', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-step', name: 'api_orientation_test_get_step', methods: ['GET'])]
    public function getStep(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $stepName = $request->query->get('stepName');
        
        if (!$stepName) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Nom d\'étape requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            if (!$session) {
                return new JsonResponse([
                    'success' => true,
                    'hasData' => false,
                    'data' => null
                ]);
            }

            $currentStep = $session->getCurrentStep();
            $stepData = $currentStep[$stepName] ?? null;

            // Retourner les données dans le format attendu par le frontend
            // Le frontend attend response.data.data.personalInfo pour l'étape personalInfo
            return new JsonResponse([
                'success' => true,
                'hasData' => $stepData !== null,
                'data' => $stepData !== null ? [
                    $stepName => $stepData
                ] : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'étape du test d\'orientation', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-progress', name: 'api_orientation_test_get_progress', methods: ['GET'])]
    public function getProgress(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
            
            if (!$session) {
                // Si aucune session n'existe, retourner les données par défaut avec la première étape active
                return new JsonResponse([
                    'success' => true,
                    'hasProgress' => false,
                    'data' => [
                        'currentStep' => 'personalInfo',
                        'steps' => ['personalInfo'],
                        'completedSteps' => [],
                        'isCompleted' => false,
                        'progress' => 0,
                        'totalSteps' => 8
                    ]
                ]);
            }

            $currentStep = $session->getCurrentStep();
            $steps = $currentStep['steps'] ?? [];
            $completedSteps = $currentStep['completedSteps'] ?? [];
            $currentStepName = $currentStep['currentStep'] ?? null;

            // Liste complète des étapes attendues (exclure 'welcome')
            $allExpectedSteps = [
                'personalInfo',
                'riasec',
                'personality',
                'aptitude',
                'interests',
                'career',
                'constraints',
                'languages'
            ];

            // Filtrer les étapes pour exclure 'welcome' et autres étapes invalides
            $steps = array_filter($steps, function($step) use ($allExpectedSteps) {
                return in_array($step, $allExpectedSteps);
            });
            $completedSteps = array_filter($completedSteps, function($step) use ($allExpectedSteps) {
                return in_array($step, $allExpectedSteps);
            });
            
            // Réindexer les tableaux pour éviter les problèmes avec array_intersect
            $steps = array_values($steps);
            $completedSteps = array_values($completedSteps);
            
            // S'assurer que toutes les étapes attendues sont dans la liste steps
            $allSteps = array_unique(array_merge($steps, $allExpectedSteps));
            
            // Calculer le pourcentage de progression basé sur les étapes complétées
            $totalSteps = count($allExpectedSteps);
            // Utiliser array_intersect avec des tableaux réindexés
            $completedCount = count(array_intersect($completedSteps, $allExpectedSteps));
            $progressPercentage = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;
            
            // Log pour déboguer
            $this->logger->info('Calcul de progression', [
                'sessionId' => $session->getId(),
                'completedSteps' => $completedSteps,
                'completedCount' => $completedCount,
                'totalSteps' => $totalSteps,
                'progressPercentage' => $progressPercentage,
                'isCompleted' => $session->isIsCompleted()
            ]);
            
            // Si currentStep est 'welcome', le remplacer par 'personalInfo'
            if ($currentStepName === 'welcome') {
                $currentStepName = 'personalInfo';
            }

            // Vérifier à nouveau si toutes les étapes sont complétées (au cas où)
            if (!$session->isIsCompleted() && $completedCount === $totalSteps) {
                $this->checkAndCompleteTest($session);
                // Recharger depuis la base pour avoir la valeur à jour
                $this->em->refresh($session);
            }

            return new JsonResponse([
                'success' => true,
                'hasProgress' => true,
                'data' => [
                    'currentStep' => $currentStepName ?: 'personalInfo', // Par défaut, la première étape
                    'steps' => array_values($allSteps), // Réindexer pour éviter les problèmes
                    'completedSteps' => $completedSteps, // Déjà réindexé
                    'isCompleted' => $session->isIsCompleted(),
                    'progress' => $progressPercentage, // Pourcentage de 0 à 100
                    'totalSteps' => $totalSteps,
                    'completedCount' => $completedCount
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la progression', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour les métadonnées de la session
     */
    private function updateSessionMetadata(TestSession $session, string $stepName, int $duration): void
    {
        $metadata = $session->getMetadata();
        $metadata['stepDurations'][$stepName] = $duration;
        $metadata['lastUpdated'] = (new \DateTimeImmutable())->format('c');
        $session->setMetadata($metadata);
    }

    /**
     * Met à jour le suivi des étapes
     */
    private function updateStepTracking(TestSession $session, string $stepName): void
    {
        $currentStep = $session->getCurrentStep();

        // Initialiser les tableaux si nécessaire
        if (!isset($currentStep['steps']) || !is_array($currentStep['steps'])) {
            $currentStep['steps'] = [];
        }
        if (!isset($currentStep['completedSteps']) || !is_array($currentStep['completedSteps'])) {
            $currentStep['completedSteps'] = [];
        }

        // Liste des étapes valides (exclure 'welcome' qui n'est pas une étape de test)
        $validSteps = [
            'personalInfo',
            'riasec',
            'personality',
            'aptitude',
            'interests',
            'career',
            'constraints',
            'languages'
        ];

        // Ajouter l'étape actuelle aux étapes suivies seulement si c'est une étape valide
        if (in_array($stepName, $validSteps) && !in_array($stepName, $currentStep['steps'])) {
            $currentStep['steps'][] = $stepName;
        }

        // Ajouter l'étape actuelle aux étapes complétées seulement si c'est une étape valide
        if (in_array($stepName, $validSteps) && !in_array($stepName, $currentStep['completedSteps'])) {
            $currentStep['completedSteps'][] = $stepName;
        }

        // Définir l'étape actuelle
        $currentStep['currentStep'] = $stepName;

        $session->setCurrentStep($currentStep);
    }

    /**
     * Traite l'étape des informations personnelles
     */
    private function processPersonalInfoStep(TestSession $session, array $stepData, string $stepName, User $user): void
    {
        $currentStep = $session->getCurrentStep();
        
        // Stocker les données dans currentStep
        // Les données sont déjà dans le bon format depuis le frontend
        $currentStep[$stepName] = $stepData;
        
        // Mettre à jour le tracking des étapes
        $this->updateStepTracking($session, $stepName);
        
        // Mettre à jour la session
        $session->setCurrentStep($currentStep);
        
        // Mettre à jour le profil utilisateur si disponible
        // Les informations personnelles sont stockées dans UserProfile, pas directement dans User
        if (isset($stepData['firstName']) || isset($stepData['lastName']) || isset($stepData['phoneNumber'])) {
            $profile = $user->getProfile();
            if (!$profile) {
                // Créer un nouveau profil si nécessaire
                $profile = new \App\Entity\UserProfile();
                $profile->setUser($user);
                $this->em->persist($profile);
            }
            
            if (isset($stepData['firstName'])) {
                $profile->setPrenom($stepData['firstName']);
            }
            if (isset($stepData['lastName'])) {
                $profile->setNom($stepData['lastName']);
            }
            if (isset($stepData['phoneNumber'])) {
                // Le numéro de téléphone est déjà dans User->phone
                // On peut le mettre à jour si nécessaire
                if ($user->getPhone() !== $stepData['phoneNumber']) {
                    $user->setPhone($stepData['phoneNumber']);
                }
            }
        }
    }

    /**
     * Traite une étape générique
     */
    private function processGenericStep(TestSession $session, array $stepData, string $stepName): void
    {
        $currentStep = $session->getCurrentStep();
        $currentStep[$stepName] = $stepData;
        // Mettre à jour la session AVANT updateStepTracking
        $session->setCurrentStep($currentStep);
        
        // Note: updateStepTracking sera appelé après cette méthode dans saveStep
    }

    /**
     * Enregistre les réponses fournies dans l'étape
     */
    private function saveStepAnswers(TestSession $session, array $stepData, int $stepNumber): void
    {
        if (isset($stepData['answers']) && is_array($stepData['answers'])) {
            foreach ($stepData['answers'] as $questionKey => $answerData) {
                $answer = new TestAnswer();
                $answer->setTestSession($session);
                $answer->setQuestionKey($questionKey);
                $answer->setAnswerData($answerData);
                $answer->setStepNumber($stepNumber);

                $this->em->persist($answer);
                $session->addAnswer($answer);
            }
        }
    }

    /**
     * Vérifie si toutes les étapes sont complétées et marque le test comme terminé si c'est le cas
     */
    private function checkAndCompleteTest(TestSession $session): void
    {
        if ($session->isIsCompleted()) {
            return; // Déjà complété
        }

        $currentStep = $session->getCurrentStep();
        $completedSteps = $currentStep['completedSteps'] ?? [];

        // Liste complète des étapes attendues
        $allExpectedSteps = [
            'personalInfo',
            'riasec',
            'personality',
            'aptitude',
            'interests',
            'career',
            'constraints',
            'languages'
        ];

        // Filtrer pour ne garder que les étapes valides
        $completedSteps = array_filter($completedSteps, function($step) use ($allExpectedSteps) {
            return in_array($step, $allExpectedSteps);
        });
        $completedSteps = array_values($completedSteps); // Réindexer

        // Vérifier si toutes les étapes sont complétées
        $allStepsCompleted = count($allExpectedSteps) === count($completedSteps) && 
                            empty(array_diff($allExpectedSteps, $completedSteps));

        if ($allStepsCompleted && !$session->isIsCompleted()) {
            $session->setIsCompleted(true);
            $session->setCompletedAt(new \DateTimeImmutable());
            
            // Calculer la durée totale
            if ($session->getStartedAt()) {
                $duration = $session->getCompletedAt()->getTimestamp() - $session->getStartedAt()->getTimestamp();
                $session->setDuration($duration);
            }

            $this->logger->info('Test complété automatiquement', [
                'sessionId' => $session->getId(),
                'completedSteps' => $completedSteps,
                'totalSteps' => count($allExpectedSteps)
            ]);
        }
    }
}
