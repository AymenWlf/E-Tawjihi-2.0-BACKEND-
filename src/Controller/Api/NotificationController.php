<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

#[Route('/api/notifications', name: 'api_notifications_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère toutes les notifications de l'utilisateur
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[CurrentUser] ?User $user = null,
        Request $request
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $limit = (int) $request->query->get('limit', 50);
        $offset = (int) $request->query->get('offset', 0);
        $unreadOnly = $request->query->get('unread_only') === 'true';

        $notifications = $this->notificationRepository->findByUser(
            $user,
            $limit,
            $offset,
            $unreadOnly
        );

        $total = $this->notificationRepository->countByUser($user, $unreadOnly);
        $unreadCount = $this->notificationRepository->countUnreadByUser($user);

        $notificationsData = array_map(function (Notification $notification) {
            return [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'type' => $notification->getType(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'timeAgo' => $this->getTimeAgo($notification->getCreatedAt()),
                'metadata' => $notification->getMetadata(),
            ];
        }, $notifications);

        return $this->json([
            'success' => true,
            'data' => $notificationsData,
            'pagination' => [
                'total' => $total,
                'unreadCount' => $unreadCount,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/{id}/read', name: 'mark_read', methods: ['POST'])]
    public function markAsRead(
        int $id,
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);

        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marque toutes les notifications comme lues
     */
    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['POST'])]
    public function markAllAsRead(
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $notifications = $this->notificationRepository->findUnreadByUser($user);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
            'count' => count($notifications)
        ]);
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);

        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    /**
     * Récupère le nombre de notifications non lues
     */
    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function getUnreadCount(
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'count' => 0
            ], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->notificationRepository->countUnreadByUser($user);

        return $this->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Calcule le temps écoulé depuis une date
     */
    private function getTimeAgo(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) {
            return "Il y a {$diff->y} an" . ($diff->y > 1 ? 's' : '');
        } elseif ($diff->m > 0) {
            return "Il y a {$diff->m} mois";
        } elseif ($diff->d > 0) {
            return "Il y a {$diff->d} jour" . ($diff->d > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return "Il y a {$diff->h} heure" . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return "Il y a {$diff->i} minute" . ($diff->i > 1 ? 's' : '');
        } else {
            return "À l'instant";
        }
    }

    /**
     * Crée une notification pour l'achèvement d'une étape du plan de réussite
     */
    #[Route('/plan-step-completed', name: 'plan_step_completed', methods: ['POST'])]
    public function createPlanStepCompletedNotification(
        Request $request,
        #[CurrentUser] ?User $user = null
    ): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $stepName = $data['stepName'] ?? '';
        $stepLabel = $data['stepLabel'] ?? '';

        if (!$stepName || !$stepLabel) {
            return $this->json([
                'success' => false,
                'message' => 'stepName et stepLabel sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $notificationService = new \App\Service\NotificationService(
                $this->entityManager,
                $this->notificationRepository
            );
            $notification = $notificationService->createPlanStepCompletedNotification($user, $stepName, $stepLabel);

            return $this->json([
                'success' => true,
                'message' => 'Notification créée',
                'data' => [
                    'id' => $notification->getId(),
                    'title' => $notification->getTitle(),
                    'message' => $notification->getMessage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la notification: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
