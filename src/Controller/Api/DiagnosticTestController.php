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

#[Route('/api/diagnostic')]
class DiagnosticTestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TestSessionRepository $testSessionRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/questions', name: 'api_diagnostic_questions', methods: ['GET'])]
    public function getQuestions(
        Request $request
    ): JsonResponse {
        // TODO: Implémenter la récupération des questions depuis la base de données
        // Pour l'instant, retourner une structure vide
        return new JsonResponse([
            'success' => true,
            'data' => [
                // Structure des questions à définir
            ]
        ]);
    }

    #[Route('/session', name: 'api_diagnostic_session', methods: ['GET', 'POST'])]
    public function getOrCreateSession(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'diagnostic');

            if (!$session) {
                // Créer une nouvelle session
                $data = json_decode($request->getContent(), true);
                $language = $data['language'] ?? $request->query->get('language', 'fr');

                $session = new TestSession();
                $session->setUser($user);
                $session->setLanguage($language);
                $session->setTestType('diagnostic');
                $session->setStartedAt(new \DateTimeImmutable());
                $session->setTotalQuestions(0);
                $session->setIsCompleted(false);

                $metadata = [
                    "selectedLanguage" => $language,
                    "startedAt" => (new \DateTimeImmutable())->format('c'),
                    "stepDurations" => [],
                    "version" => "1.0"
                ];
                $session->setMetadata($metadata);

                $currentStep = [
                    "selectedLanguage" => $language,
                    "session" => [
                        "testType" => "diagnostic",
                        "startedAt" => (new \DateTimeImmutable())->format('c'),
                        "duration" => 0,
                        "language" => $language,
                        "totalQuestions" => 0,
                        "questions" => []
                    ],
                    "currentStep" => "welcome",
                    "steps" => ["welcome"],
                    "completedSteps" => ["welcome"],
                    "answers" => [],
                    "currentQuestionIndex" => 0
                ];
                $session->setCurrentStep($currentStep);

                $this->em->persist($session);
                $this->em->flush();
            }

            $currentStep = $session->getCurrentStep();

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'sessionId' => $session->getId(),
                    'answers' => $currentStep['answers'] ?? [],
                    'currentQuestionIndex' => $currentStep['currentQuestionIndex'] ?? 0,
                    'isCompleted' => $session->isIsCompleted()
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération/création de la session de diagnostic', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/answer', name: 'api_diagnostic_answer', methods: ['POST'])]
    public function saveAnswer(
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
        $questionId = $data['questionId'] ?? null;
        $answer = $data['answer'] ?? null;

        if (!$questionId || $answer === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'diagnostic');

            if (!$session) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Session introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            $currentStep = $session->getCurrentStep();
            
            // Sauvegarder la réponse dans currentStep
            if (!isset($currentStep['answers'])) {
                $currentStep['answers'] = [];
            }
            $currentStep['answers'][$questionId] = $answer;

            // Mettre à jour l'index de la question actuelle
            $currentStep['currentQuestionIndex'] = ($currentStep['currentQuestionIndex'] ?? 0) + 1;

            $session->setCurrentStep($currentStep);

            // Sauvegarder aussi dans TestAnswer pour traçabilité
            $testAnswer = new TestAnswer();
            $testAnswer->setTestSession($session);
            $testAnswer->setQuestionKey($questionId);
            $testAnswer->setAnswerData(['answer' => $answer]);
            $testAnswer->setStepNumber(1);

            $this->em->persist($testAnswer);
            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Réponse sauvegardée',
                'data' => [
                    'currentQuestionIndex' => $currentStep['currentQuestionIndex']
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de la réponse', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/generate', name: 'api_diagnostic_generate', methods: ['POST'])]
    public function generateDiagnostic(
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->findByUser($user->getId(), 'diagnostic');

            if (!$session) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Session introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            $currentStep = $session->getCurrentStep();
            $answers = $currentStep['answers'] ?? [];

            // TODO: Implémenter la logique de génération du diagnostic
            // Analyser les réponses et générer le diagnostic
            $diagnostic = $this->analyzeDiagnosticAnswers($answers);

            // Sauvegarder le diagnostic dans currentStep
            $currentStep['diagnostic'] = $diagnostic;
            $currentStep['results'] = $diagnostic;
            $session->setCurrentStep($currentStep);

            // Marquer comme complété
            $session->setIsCompleted(true);
            $session->setCompletedAt(new \DateTimeImmutable());

            if ($session->getStartedAt()) {
                $duration = $session->getCompletedAt()->getTimestamp() - $session->getStartedAt()->getTimestamp();
                $session->setDuration($duration);
            }

            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'diagnostic' => $diagnostic['diagnostic'] ?? '',
                    'scores' => $diagnostic['scores'] ?? [],
                    'recommendations' => $diagnostic['recommendations'] ?? []
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du diagnostic', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/result/{sessionId}', name: 'api_diagnostic_result', methods: ['GET'])]
    public function getResult(
        int $sessionId,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $session = $this->testSessionRepository->find($sessionId);

            if (!$session || $session->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Session introuvable'
                ], Response::HTTP_NOT_FOUND);
            }

            $currentStep = $session->getCurrentStep();
            $diagnostic = $currentStep['diagnostic'] ?? $currentStep['results'] ?? null;

            if (!$diagnostic) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Diagnostic non généré'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'diagnostic' => $diagnostic['diagnostic'] ?? '',
                    'scores' => $diagnostic['scores'] ?? [],
                    'recommendations' => $diagnostic['recommendations'] ?? [],
                    'completedAt' => $session->getCompletedAt()?->format('c')
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du résultat', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Analyse les réponses et génère le diagnostic
     */
    private function analyzeDiagnosticAnswers(array $answers): array
    {
        // TODO: Implémenter la logique d'analyse
        // Pour l'instant, retourner une structure de base
        return [
            'diagnostic' => 'Diagnostic généré basé sur vos réponses',
            'scores' => [
                'academic' => 75,
                'career' => 80,
                'personality' => 70,
                'skills' => 65,
                'preferences' => 75,
                'motivation' => 80
            ],
            'recommendations' => []
        ];
    }
}
