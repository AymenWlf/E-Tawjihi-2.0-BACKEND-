<?php

namespace App\Controller\Api;

use App\Entity\Establishment;
use App\Entity\EstablishmentQuestion;
use App\Entity\EstablishmentAnswer;
use App\Entity\User;
use App\Repository\EstablishmentRepository;
use App\Repository\EstablishmentQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/establishments', name: 'api_establishment_comments_')]
class EstablishmentCommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EstablishmentRepository $establishmentRepository,
        private EstablishmentQuestionRepository $questionRepository
    ) {
    }

    /**
     * Récupère toutes les questions d'un établissement
     * IMPORTANT: Priorité élevée pour éviter les conflits avec /{id}/{slug}
     */
    #[Route('/{id}/questions', name: 'get_questions', methods: ['GET'], priority: 20)]
    public function getQuestions(int $id): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);
        
        if (!$establishment) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $questions = $this->questionRepository->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->leftJoin('q.answers', 'a')
            ->leftJoin('a.user', 'au')
            ->addSelect('u')
            ->addSelect('a')
            ->addSelect('au')
            ->where('q.establishment = :establishmentId')
            ->andWhere('q.isActive = :active')
            ->andWhere('q.isApproved = :approved')
            ->setParameter('establishmentId', $id)
            ->setParameter('active', true)
            ->setParameter('approved', true)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $questionsData = [];
        foreach ($questions as $question) {
            $answersData = [];
            foreach ($question->getAnswers() as $answer) {
                if ($answer->isActive() && $answer->isApproved()) {
                    $answerUser = $answer->getUser();
                    $answersData[] = [
                        'id' => $answer->getId(),
                        'content' => $answer->getContent(),
                        'likes' => $answer->getLikes(),
                        'isVerified' => $answer->isVerified(),
                        'createdAt' => $answer->getCreatedAt()->format('Y-m-d H:i:s'),
                        'author' => [
                            'id' => $answerUser->getId(),
                            'name' => $answerUser->getEmail() ?: $answerUser->getPhone(),
                            'email' => $answerUser->getEmail(),
                            'phone' => $answerUser->getPhone(),
                            'role' => $this->getUserRole($answerUser),
                            'avatar' => $this->generateAvatarUrl($answerUser)
                        ]
                    ];
                }
            }

            $questionUser = $question->getUser();
            $questionsData[] = [
                'id' => $question->getId(),
                'content' => $question->getContent(),
                'likes' => $question->getLikes(),
                'createdAt' => $question->getCreatedAt()->format('Y-m-d H:i:s'),
                'answers' => $answersData,
                'author' => [
                    'id' => $questionUser->getId(),
                    'name' => $questionUser->getEmail() ?: $questionUser->getPhone(),
                    'email' => $questionUser->getEmail(),
                    'phone' => $questionUser->getPhone(),
                    'role' => $this->getUserRole($questionUser),
                    'avatar' => $this->generateAvatarUrl($questionUser)
                ]
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $questionsData
        ]);
    }

    /**
     * Crée une nouvelle question pour un établissement
     * IMPORTANT: Priorité élevée pour éviter les conflits avec /{id}/{slug}
     */
    #[Route('/{id}/questions', name: 'create_question', methods: ['POST'], priority: 20)]
    public function createQuestion(
        int $id,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $establishment = $this->establishmentRepository->find($id);
        
        if (!$establishment) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['content']) || empty(trim($data['content']))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le contenu de la question est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $question = new EstablishmentQuestion();
        $question->setEstablishment($establishment);
        $question->setUser($user);
        $question->setContent(trim($data['content']));
        $question->setIsApproved(false); // En attente de modération par défaut

        $this->em->persist($question);
        $this->em->flush();

        $questionUser = $question->getUser();
        return new JsonResponse([
            'success' => true,
            'message' => 'Question créée avec succès. Elle sera visible après validation par un administrateur.',
            'data' => [
                'id' => $question->getId(),
                'content' => $question->getContent(),
                'likes' => $question->getLikes(),
                'createdAt' => $question->getCreatedAt()->format('Y-m-d H:i:s'),
                'isApproved' => $question->isApproved(),
                'answers' => [],
                'author' => [
                    'id' => $questionUser->getId(),
                    'name' => $questionUser->getEmail() ?: $questionUser->getPhone(),
                    'email' => $questionUser->getEmail(),
                    'phone' => $questionUser->getPhone(),
                    'role' => $this->getUserRole($questionUser),
                    'avatar' => $this->generateAvatarUrl($questionUser)
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Crée une réponse à une question
     * IMPORTANT: Priorité élevée pour éviter les conflits avec /{id}/{slug}
     */
    #[Route('/questions/{questionId}/answers', name: 'create_answer', methods: ['POST'], priority: 20)]
    public function createAnswer(
        int $questionId,
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionRepository->find($questionId);
        
        if (!$question) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Question non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['content']) || empty(trim($data['content']))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le contenu de la réponse est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $answer = new EstablishmentAnswer();
        $answer->setQuestion($question);
        $answer->setUser($user);
        $answer->setContent(trim($data['content']));
        $answer->setIsApproved(false); // En attente de modération par défaut
        
        // Vérifier si l'utilisateur est admin E-TAWJIHI ou admin de l'établissement pour marquer comme vérifié et approuvé automatiquement
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $answer->setIsVerified(true);
            $answer->setIsApproved(true); // Les admins sont approuvés automatiquement
        }

        $this->em->persist($answer);
        $this->em->flush();

        $answerUser = $answer->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        return new JsonResponse([
            'success' => true,
            'message' => $isAdmin 
                ? 'Réponse créée avec succès' 
                : 'Réponse créée avec succès. Elle sera visible après validation par un administrateur.',
            'data' => [
                'id' => $answer->getId(),
                'content' => $answer->getContent(),
                'likes' => $answer->getLikes(),
                'isVerified' => $answer->isVerified(),
                'isApproved' => $answer->isApproved(),
                'createdAt' => $answer->getCreatedAt()->format('Y-m-d H:i:s'),
                'author' => [
                    'id' => $answerUser->getId(),
                    'name' => $answerUser->getEmail() ?: $answerUser->getPhone(),
                    'email' => $answerUser->getEmail(),
                    'phone' => $answerUser->getPhone(),
                    'role' => $this->getUserRole($answerUser),
                    'avatar' => $this->generateAvatarUrl($answerUser)
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Like une question
     * IMPORTANT: Priorité élevée pour éviter les conflits avec /{id}/{slug}
     */
    #[Route('/questions/{id}/like', name: 'like_question', methods: ['POST'], priority: 20)]
    public function likeQuestion(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $question = $this->questionRepository->find($id);
        
        if (!$question) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Question non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $question->incrementLikes();
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'data' => ['likes' => $question->getLikes()]
        ]);
    }

    /**
     * Like une réponse
     * IMPORTANT: Priorité élevée pour éviter les conflits avec /{id}/{slug}
     */
    #[Route('/answers/{id}/like', name: 'like_answer', methods: ['POST'], priority: 20)]
    public function likeAnswer(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $answer = $this->em->getRepository(EstablishmentAnswer::class)->find($id);
        
        if (!$answer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Réponse non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $answer->incrementLikes();
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'data' => ['likes' => $answer->getLikes()]
        ]);
    }

    /**
     * Détermine le rôle de l'utilisateur pour l'affichage
     */
    private function getUserRole(User $user): string
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin_e_tawjihi';
        }
        
        // TODO: Vérifier si l'utilisateur est admin de l'établissement
        // if ($user->isAdminOfEstablishment($establishment)) {
        //     return 'admin_etablissement';
        // }
        
        // TODO: Vérifier si l'utilisateur est étudiant/lauréat de l'établissement
        // if ($user->isStudentOfEstablishment($establishment)) {
        //     return 'etudiant_ecole';
        // }
        // if ($user->isGraduateOfEstablishment($establishment)) {
        //     return 'laureat_ecole';
        // }
        
        // Vérifier si c'est un tuteur
        if (in_array('ROLE_TUTOR', $roles)) {
            return 'tuteur';
        }
        
        // Par défaut, étudiant
        return 'etudiant';
    }

    /**
     * Génère l'URL de l'avatar de l'utilisateur
     */
    private function generateAvatarUrl(User $user): string
    {
        $name = $user->getEmail() ?: $user->getPhone() ?: 'User';
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=1e40af&color=fff';
    }
}
