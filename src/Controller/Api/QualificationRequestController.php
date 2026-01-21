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
use Doctrine\ORM\EntityManagerInterface;
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
        private CityRepository $cityRepository
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
}
