<?php

namespace App\Controller\Api;

use App\Service\HubSpotService;
use App\Service\HubSpotRoundRobinService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/form/maroc', name: 'api_form_maroc_')]
class MarocFormController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubSpotService $hubSpotService,
        private HubSpotRoundRobinService $hubSpotRoundRobinService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/submit', name: 'submit', methods: ['POST'])]
    public function submitMarocForm(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides'
                ], 400);
            }

            // Validation des champs requis
            $requiredFields = ['nom_prenom', 'telephone', 'niveau_etude', 'filiere_bac'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => "Le champ '$field' est requis"
                    ], 400);
                }
            }

            // Ici, vous pouvez sauvegarder le lead dans votre base de données locale
            // Exemple (à adapter selon votre structure) :
            // $crmLead = $this->createCrmLeadFromFormData($data);
            // $this->em->persist($crmLead);
            // $this->em->flush();

            // Synchroniser avec HubSpot (non-bloquant)
            try {
                if ($this->hubSpotService->isConfigured()) {
                    // Round robin pour obtenir le propriétaire
                    $hubSpotOwnerId = $this->hubSpotRoundRobinService->getNextOwnerId();
                    
                    // Synchroniser (création ou mise à jour)
                    $hubSpotContact = $this->hubSpotService->syncLeadToHubSpot(
                        $data, 
                        $hubSpotOwnerId, 
                        $request
                    );
                    
                    if ($hubSpotContact) {
                        $this->logger->info('✅ Contact HubSpot synchronisé - ID: ' . ($hubSpotContact['id'] ?? 'unknown'));
                    }
                }
            } catch (\Exception $e) {
                // Ne pas bloquer la réponse si HubSpot échoue
                $this->logger->error('❌ Erreur synchronisation HubSpot: ' . $e->getMessage());
            }

            // Retourner succès (même si HubSpot échoue)
            return new JsonResponse([
                'success' => true,
                'message' => 'Formulaire soumis avec succès'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Erreur soumission formulaire Maroc: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la soumission du formulaire'
            ], 500);
        }
    }
}
