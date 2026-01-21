<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Crée une notification de bienvenue
     */
    public function createWelcomeNotification(User $user): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle('Bienvenue sur E-TAWJIHI ! 🎉');
        $notification->setMessage('Merci de nous rejoindre ! Explorez nos services et commencez votre parcours d\'orientation.');
        $notification->setType('welcome');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour l'ajout d'un favori
     */
    public function createFavoriteAddedNotification(User $user, string $itemType, string $itemName, ?array $metadata = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle("Ajouté aux favoris ✓");
        $notification->setMessage("Vous avez ajouté \"{$itemName}\" à vos favoris.");
        $notification->setType('favorite_added');
        $notification->setMetadata(array_merge($metadata ?? [], ['item_type' => $itemType, 'item_name' => $itemName]));

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour le retrait d'un favori
     */
    public function createFavoriteRemovedNotification(User $user, string $itemType, string $itemName, ?array $metadata = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle("Retiré des favoris");
        $notification->setMessage("Vous avez retiré \"{$itemName}\" de vos favoris.");
        $notification->setType('favorite_removed');
        $notification->setMetadata(array_merge($metadata ?? [], ['item_type' => $itemType, 'item_name' => $itemName]));

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification pour l'achèvement d'une étape du plan de réussite
     */
    public function createPlanStepCompletedNotification(User $user, string $stepName, string $stepLabel): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle("Étape complétée ✓");
        $notification->setMessage("Félicitations ! Vous avez complété l'étape : \"{$stepLabel}\".");
        $notification->setType('plan_step_completed');
        $notification->setMetadata(['step_name' => $stepName, 'step_label' => $stepLabel]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }
}
