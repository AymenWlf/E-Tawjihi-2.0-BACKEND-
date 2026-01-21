<?php

namespace App\Controller\Api;

use App\Entity\Favoris;
use App\Entity\Secteur;
use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Repository\FavorisRepository;
use App\Repository\SecteurRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\FiliereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/favoris')]
#[IsGranted('ROLE_USER')]
class FavorisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FavorisRepository $favorisRepository,
        private SecteurRepository $secteurRepository,
        private EstablishmentRepository $establishmentRepository,
        private FiliereRepository $filiereRepository
    ) {
    }

    /**
     * Ajoute ou retire un secteur des favoris
     */
    #[Route('/secteur/{id}', name: 'api_favoris_secteur_toggle', methods: ['POST', 'DELETE'])]
    public function toggleSecteur(int $id): JsonResponse
    {
        $user = $this->getUser();
        $secteur = $this->secteurRepository->find($id);

        if (!$secteur) {
            return $this->json([
                'success' => false,
                'message' => 'Secteur non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $favoris = $this->favorisRepository->findByUserAndSecteur($user, $id);

        if ($favoris) {
            // Retirer des favoris
            $this->entityManager->remove($favoris);
            $action = 'removed';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteRemovedNotification(
                    $user,
                    'secteur',
                    $secteur->getTitre(),
                    ['secteur_id' => $secteur->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        } else {
            // Ajouter aux favoris
            $favoris = new Favoris();
            $favoris->setUser($user);
            $favoris->setSecteur($secteur);
            $this->entityManager->persist($favoris);
            $action = 'added';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteAddedNotification(
                    $user,
                    'secteur',
                    $secteur->getTitre(),
                    ['secteur_id' => $secteur->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'action' => $action,
            'message' => $action === 'added' ? 'Secteur ajouté aux favoris' : 'Secteur retiré des favoris'
        ]);
    }

    /**
     * Ajoute ou retire un établissement des favoris
     */
    #[Route('/establishment/{id}', name: 'api_favoris_establishment_toggle', methods: ['POST', 'DELETE'])]
    public function toggleEstablishment(int $id): JsonResponse
    {
        $user = $this->getUser();
        $establishment = $this->establishmentRepository->find($id);

        if (!$establishment) {
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $favoris = $this->favorisRepository->findByUserAndEstablishment($user, $id);

        if ($favoris) {
            // Retirer des favoris
            $this->entityManager->remove($favoris);
            $action = 'removed';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteRemovedNotification(
                    $user,
                    'establishment',
                    $establishment->getNom(),
                    ['establishment_id' => $establishment->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        } else {
            // Ajouter aux favoris
            $favoris = new Favoris();
            $favoris->setUser($user);
            $favoris->setEstablishment($establishment);
            $this->entityManager->persist($favoris);
            $action = 'added';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteAddedNotification(
                    $user,
                    'establishment',
                    $establishment->getNom(),
                    ['establishment_id' => $establishment->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'action' => $action,
            'message' => $action === 'added' ? 'Établissement ajouté aux favoris' : 'Établissement retiré des favoris'
        ]);
    }

    /**
     * Ajoute ou retire une filière des favoris
     */
    #[Route('/filiere/{id}', name: 'api_favoris_filiere_toggle', methods: ['POST', 'DELETE'])]
    public function toggleFiliere(int $id): JsonResponse
    {
        $user = $this->getUser();
        $filiere = $this->filiereRepository->find($id);

        if (!$filiere) {
            return $this->json([
                'success' => false,
                'message' => 'Filière non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $favoris = $this->favorisRepository->findByUserAndFiliere($user, $id);

        if ($favoris) {
            // Retirer des favoris
            $this->entityManager->remove($favoris);
            $action = 'removed';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteRemovedNotification(
                    $user,
                    'filiere',
                    $filiere->getNom(),
                    ['filiere_id' => $filiere->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        } else {
            // Ajouter aux favoris
            $favoris = new Favoris();
            $favoris->setUser($user);
            $favoris->setFiliere($filiere);
            $this->entityManager->persist($favoris);
            $action = 'added';
            
            // Créer une notification
            try {
                $notificationService = new \App\Service\NotificationService(
                    $this->entityManager,
                    $this->entityManager->getRepository(\App\Entity\Notification::class)
                );
                $notificationService->createFavoriteAddedNotification(
                    $user,
                    'filiere',
                    $filiere->getNom(),
                    ['filiere_id' => $filiere->getId()]
                );
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'action' => $action,
            'message' => $action === 'added' ? 'Filière ajoutée aux favoris' : 'Filière retirée des favoris'
        ]);
    }

    /**
     * Récupère tous les favoris de l'utilisateur
     */
    #[Route('', name: 'api_favoris_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $favoris = $this->favorisRepository->findByUser($user);

        $secteurIds = $this->favorisRepository->findSecteurIdsByUser($user);
        $establishmentIds = $this->favorisRepository->findEstablishmentIdsByUser($user);
        $filiereIds = $this->favorisRepository->findFiliereIdsByUser($user);

        return $this->json([
            'success' => true,
            'data' => [
                'secteurs' => $secteurIds,
                'establishments' => $establishmentIds,
                'filieres' => $filiereIds
            ]
        ]);
    }

    /**
     * Vérifie si un secteur est en favoris
     */
    #[Route('/secteur/{id}/check', name: 'api_favoris_secteur_check', methods: ['GET'])]
    public function checkSecteur(int $id): JsonResponse
    {
        $user = $this->getUser();
        $favoris = $this->favorisRepository->findByUserAndSecteur($user, $id);

        return $this->json([
            'success' => true,
            'isFavorite' => $favoris !== null
        ]);
    }

    /**
     * Vérifie si un établissement est en favoris
     */
    #[Route('/establishment/{id}/check', name: 'api_favoris_establishment_check', methods: ['GET'])]
    public function checkEstablishment(int $id): JsonResponse
    {
        $user = $this->getUser();
        $favoris = $this->favorisRepository->findByUserAndEstablishment($user, $id);

        return $this->json([
            'success' => true,
            'isFavorite' => $favoris !== null
        ]);
    }

    /**
     * Vérifie si une filière est en favoris
     */
    #[Route('/filiere/{id}/check', name: 'api_favoris_filiere_check', methods: ['GET'])]
    public function checkFiliere(int $id): JsonResponse
    {
        $user = $this->getUser();
        $favoris = $this->favorisRepository->findByUserAndFiliere($user, $id);

        return $this->json([
            'success' => true,
            'isFavorite' => $favoris !== null
        ]);
    }
}
