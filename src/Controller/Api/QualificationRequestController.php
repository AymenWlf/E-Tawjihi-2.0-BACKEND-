<?php

namespace App\Controller\Api;

use App\Entity\QualificationRequest;
use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Entity\City;
use App\Repository\QualificationRequestRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\FiliereRepository;
use App\Repository\CityRepository;
use App\Service\HubSpotService;
use App\Service\HubSpotRoundRobinService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/qualification-requests')]
class QualificationRequestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private QualificationRequestRepository $qualificationRequestRepository,
        private EstablishmentRepository $establishmentRepository,
        private FiliereRepository $filiereRepository,
        private CityRepository $cityRepository,
        private HubSpotService $hubSpotService,
        private HubSpotRoundRobinService $hubSpotRoundRobinService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'api_qualification_request_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des champs requis
        $requiredFields = ['source', 'tuteur_eleve', 'nom_prenom', 'telephone', 'pret_payer'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => "Le champ '$field' est requis"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Créer la requête de qualification
        $qualificationRequest = new QualificationRequest();
        $qualificationRequest->setSource($data['source']);
        $qualificationRequest->setTuteurEleve($data['tuteur_eleve']);
        $qualificationRequest->setNomPrenom(trim($data['nom_prenom']));
        $qualificationRequest->setTelephone(trim($data['telephone']));
        $qualificationRequest->setPretPayer($data['pret_payer']);

        // Champs optionnels
        if (isset($data['type_ecole']) && !empty($data['type_ecole'])) {
            $qualificationRequest->setTypeEcole($data['type_ecole']);
        }

        if (isset($data['ville']) && !empty($data['ville'])) {
            // Si ville est un ID
            if (is_numeric($data['ville'])) {
                $city = $this->cityRepository->find($data['ville']);
                if ($city) {
                    $qualificationRequest->setVille($city);
                }
            }
        }

        if (isset($data['niveau_etude']) && !empty($data['niveau_etude'])) {
            $qualificationRequest->setNiveauEtude($data['niveau_etude']);
        }

        if (isset($data['filiere_bac']) && !empty($data['filiere_bac'])) {
            $qualificationRequest->setFiliereBac($data['filiere_bac']);
        }

        // Besoins (checkboxes)
        $qualificationRequest->setBesoinOrientation($data['besoin_orientation'] ?? false);
        $qualificationRequest->setBesoinTest($data['besoin_test'] ?? false);
        $qualificationRequest->setBesoinNotification($data['besoin_notification'] ?? false);
        $qualificationRequest->setBesoinInscription($data['besoin_inscription'] ?? false);

        // Lier l'établissement si fourni
        if (isset($data['establishment_id']) && !empty($data['establishment_id'])) {
            $establishment = $this->establishmentRepository->find($data['establishment_id']);
            if ($establishment) {
                $qualificationRequest->setEstablishment($establishment);
            }
        }

        // Lier la filière si fournie
        if (isset($data['filiere_id']) && !empty($data['filiere_id'])) {
            $filiere = $this->filiereRepository->find($data['filiere_id']);
            if ($filiere) {
                $qualificationRequest->setFiliere($filiere);
            }
        }

        try {
            $this->em->persist($qualificationRequest);
            $this->em->flush();

            // Synchroniser avec HubSpot (non-bloquant)
            try {
                if ($this->hubSpotService->isConfigured()) {
                    // Préparer les données pour HubSpot
                    $hubSpotData = $this->prepareHubSpotData($data, $qualificationRequest);
                    
                    // Round robin pour obtenir le propriétaire
                    $hubSpotOwnerId = $this->hubSpotRoundRobinService->getNextOwnerId();
                    
                    // Synchroniser (création ou mise à jour)
                    $hubSpotContact = $this->hubSpotService->syncLeadToHubSpot(
                        $hubSpotData, 
                        $hubSpotOwnerId, 
                        $request
                    );
                    
                    if ($hubSpotContact) {
                        $this->logger->info('✅ Contact HubSpot synchronisé depuis QualificationRequest - ID: ' . ($hubSpotContact['id'] ?? 'unknown'));
                    }
                }
            } catch (\Exception $e) {
                // Ne pas bloquer la réponse si HubSpot échoue
                $this->logger->error('❌ Erreur synchronisation HubSpot (QualificationRequest): ' . $e->getMessage());
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Formulaire de qualification soumis avec succès',
                'data' => [
                    'id' => $qualificationRequest->getId(),
                    'source' => $qualificationRequest->getSource(),
                    'created_at' => $qualificationRequest->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Prépare les données pour la synchronisation HubSpot
     */
    private function prepareHubSpotData(array $data, QualificationRequest $qualificationRequest): array
    {
        $hubSpotData = [
            'nom_prenom' => $qualificationRequest->getNomPrenom(),
            'telephone' => $qualificationRequest->getTelephone(),
            'tuteur_eleve' => $qualificationRequest->getTuteurEleve(),
            'source' => $qualificationRequest->getSource() ?? 'Plateforme/App',
            'pret_payer' => $qualificationRequest->getPretPayer(),
            'besoin_orientation' => $qualificationRequest->getBesoinOrientation(),
            'besoin_test' => $qualificationRequest->getBesoinTest(),
            'besoin_notification' => $qualificationRequest->getBesoinNotification(),
            'besoin_inscription' => $qualificationRequest->getBesoinInscription(),
        ];

        // Ajouter les champs optionnels
        if ($qualificationRequest->getTypeEcole()) {
            $hubSpotData['type_ecole'] = $qualificationRequest->getTypeEcole();
        }

        if ($qualificationRequest->getVille()) {
            $hubSpotData['ville'] = $qualificationRequest->getVille()->getTitre();
        } elseif (isset($data['ville']) && is_string($data['ville'])) {
            $hubSpotData['ville'] = $data['ville'];
        }

        if ($qualificationRequest->getNiveauEtude()) {
            $hubSpotData['niveau_etude'] = $qualificationRequest->getNiveauEtude();
        }

        if ($qualificationRequest->getFiliereBac()) {
            $hubSpotData['filiere_bac'] = $qualificationRequest->getFiliereBac();
        }

        return $hubSpotData;
    }
}
