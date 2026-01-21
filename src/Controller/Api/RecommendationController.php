<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Repository\EstablishmentRepository;
use App\Repository\FiliereRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/recommendations', name: 'api_recommendations_')]
class RecommendationController extends AbstractController
{
    public function __construct(
        private RecommendationService $recommendationService,
        private EstablishmentRepository $establishmentRepository,
        private FiliereRepository $filiereRepository
    ) {
    }

    /**
     * Récupère le score de recommandation pour un établissement
     */
    #[Route('/establishment/{id}', name: 'establishment', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEstablishmentRecommendation(
        int $id,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $establishment = $this->establishmentRepository->find($id);
            
            if (!$establishment) {
                return $this->json([
                    'success' => false,
                    'message' => 'Établissement non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $recommendation = $this->recommendationService->calculateEstablishmentScore($establishment, $user);

            return $this->json([
                'success' => true,
                'data' => $recommendation
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère le score de recommandation pour une filière
     */
    #[Route('/filiere/{id}', name: 'filiere', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getFiliereRecommendation(
        int $id,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $filiere = $this->filiereRepository->find($id);
            
            if (!$filiere) {
                return $this->json([
                    'success' => false,
                    'message' => 'Filière non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $recommendation = $this->recommendationService->calculateFiliereScore($filiere, $user);

            return $this->json([
                'success' => true,
                'data' => $recommendation
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les scores de recommandation pour plusieurs établissements
     */
    #[Route('/establishments', name: 'establishments_batch', methods: ['POST'])]
    public function getEstablishmentsRecommendations(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $establishmentIds = $data['ids'] ?? [];

            if (empty($establishmentIds) || !is_array($establishmentIds)) {
                return $this->json([
                    'success' => false,
                    'message' => 'IDs d\'établissements requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $recommendations = [];
            foreach ($establishmentIds as $id) {
                $establishment = $this->establishmentRepository->find($id);
                if ($establishment) {
                    $recommendation = $this->recommendationService->calculateEstablishmentScore($establishment, $user);
                    $recommendations[$id] = $recommendation;
                }
            }

            return $this->json([
                'success' => true,
                'data' => $recommendations
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les scores de recommandation pour plusieurs filières
     */
    #[Route('/filieres', name: 'filieres_batch', methods: ['POST'])]
    public function getFilieresRecommendations(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $filiereIds = $data['ids'] ?? [];

            if (empty($filiereIds) || !is_array($filiereIds)) {
                return $this->json([
                    'success' => false,
                    'message' => 'IDs de filières requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $recommendations = [];
            foreach ($filiereIds as $id) {
                $filiere = $this->filiereRepository->find($id);
                if ($filiere) {
                    $recommendation = $this->recommendationService->calculateFiliereScore($filiere, $user);
                    $recommendations[$id] = $recommendation;
                }
            }

            return $this->json([
                'success' => true,
                'data' => $recommendations
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
