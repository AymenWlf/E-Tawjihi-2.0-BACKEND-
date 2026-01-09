<?php

namespace App\Controller\Api;

use App\Entity\Filiere;
use App\Entity\Establishment;
use App\Entity\Campus;
use App\Repository\FiliereRepository;
use App\Repository\EstablishmentRepository;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/filieres', name: 'api_filieres_')]
class FiliereController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FiliereRepository $filiereRepository,
        private EstablishmentRepository $establishmentRepository,
        private CampusRepository $campusRepository,
        private SerializerInterface $serializer,
        private SluggerInterface $slugger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste toutes les filières avec filtres optionnels
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $filters = [
                'establishmentId' => $request->query->get('establishmentId'),
                'diplome' => $request->query->get('diplome'),
                'langueEtudes' => $request->query->get('langueEtudes'),
                'typeEcole' => $request->query->get('typeEcole'),
                'recommandee' => $request->query->get('recommandee'),
                'search' => $request->query->get('search'),
            ];

            // Nettoyer les filtres null
            $filters = array_filter($filters, fn($value) => $value !== null);

            $filieres = $this->filiereRepository->findWithFilters($filters);

            $data = $this->serializer->normalize($filieres, null, [
                'groups' => ['filiere:list'],
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                    return $object->getId();
                },
            ]);

            // Enrichir chaque filière avec les données de l'établissement (notamment le logo)
            $enrichedData = [];
            foreach ($filieres as $index => $filiere) {
                $filiereData = $data[$index] ?? [];
                $enrichedData[] = $this->enrichFiliereData($filiereData, $filiere);
            }

            return $this->json([
                'success' => true,
                'data' => $enrichedData,
                'count' => count($enrichedData)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des filières', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des filières: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère une filière par ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $filiere = $this->filiereRepository->find($id);

            if (!$filiere) {
                return $this->json([
                    'success' => false,
                    'message' => 'Filière non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->normalize($filiere, null, [
                'groups' => ['filiere:read']
            ]);

            // Enrichir les données
            $data = $this->enrichFiliereData($data, $filiere);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la filière', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la filière: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère une filière par slug
     */
    #[Route('/slug/{slug}', name: 'get_by_slug', methods: ['GET'], requirements: ['slug' => '.+'])]
    public function getBySlug(string $slug): JsonResponse
    {
        try {
            $filiere = $this->filiereRepository->findOneBySlug($slug);

            if (!$filiere) {
                return $this->json([
                    'success' => false,
                    'message' => 'Filière non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->normalize($filiere, null, [
                'groups' => ['filiere:read']
            ]);

            // Enrichir les données
            $data = $this->enrichFiliereData($data, $filiere);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la filière par slug', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la filière: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée une nouvelle filière
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $filiere = new Filiere();
            $this->hydrateFiliere($filiere, $data);

            // Générer le slug si non fourni
            if (empty($filiere->getSlug())) {
                $slug = $this->slugger->slug(strtolower($filiere->getNom()))->toString();
                $filiere->setSlug($slug);
            }

            $this->entityManager->persist($filiere);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($filiere, null, [
                'groups' => ['filiere:read'],
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                    return $object->getId();
                }
            ]);

            // Enrichir les données
            $responseData = $this->enrichFiliereData($responseData, $filiere);

            return $this->json([
                'success' => true,
                'message' => 'Filière créée avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la filière', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Met à jour une filière
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $filiere = $this->filiereRepository->find($id);

            if (!$filiere) {
                return $this->json([
                    'success' => false,
                    'message' => 'Filière non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->hydrateFiliere($filiere, $data);

            // Générer le slug si non fourni et qu'il est vide
            if (empty($filiere->getSlug())) {
                $slug = $this->slugger->slug(strtolower($filiere->getNom()))->toString();
                $filiere->setSlug($slug);
            }

            // Forcer la mise à jour de l'entité
            $this->entityManager->persist($filiere);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($filiere, null, [
                'groups' => ['filiere:read'],
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                    return $object->getId();
                }
            ]);

            // Enrichir les données
            $responseData = $this->enrichFiliereData($responseData, $filiere);

            return $this->json([
                'success' => true,
                'message' => 'Filière mise à jour avec succès',
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour de la filière', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime une filière
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $filiere = $this->filiereRepository->find($id);

            if (!$filiere) {
                return $this->json([
                    'success' => false,
                    'message' => 'Filière non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($filiere);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Filière supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de la filière', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les filières d'un établissement
     */
    #[Route('/establishment/{establishmentId}', name: 'get_by_establishment', methods: ['GET'], requirements: ['establishmentId' => '\d+'])]
    public function getByEstablishment(int $establishmentId): JsonResponse
    {
        try {
            $establishment = $this->establishmentRepository->find($establishmentId);

            if (!$establishment) {
                return $this->json([
                    'success' => false,
                    'message' => 'Établissement non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $filieres = $this->filiereRepository->findByEstablishment($establishmentId);

            $data = $this->serializer->normalize($filieres, null, [
                'groups' => ['filiere:list']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des filières de l\'établissement', [
                'establishmentId' => $establishmentId,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Hydrate une filière avec les données fournies
     */
    private function hydrateFiliere(Filiere $filiere, array $data): void
    {
        if (isset($data['nom'])) $filiere->setNom($data['nom']);
        if (isset($data['nomArabe'])) $filiere->setNomArabe($data['nomArabe']);
        if (isset($data['titreArabe'])) $filiere->setNomArabe($data['titreArabe']); // Alias pour compatibilité
        if (isset($data['slug'])) $filiere->setSlug($data['slug']);
        if (isset($data['description'])) $filiere->setDescription($data['description']);
        if (isset($data['imageCouverture'])) $filiere->setImageCouverture($data['imageCouverture']);
        if (isset($data['diplome'])) $filiere->setDiplome($data['diplome']);
        if (isset($data['domaine'])) $filiere->setDomaine($data['domaine']);
        if (isset($data['langueEtudes'])) $filiere->setLangueEtudes($data['langueEtudes']);
        if (isset($data['fraisScolarite'])) $filiere->setFraisScolarite($data['fraisScolarite']);
        if (isset($data['fraisInscription'])) $filiere->setFraisInscription($data['fraisInscription']);
        if (isset($data['concours'])) $filiere->setConcours($data['concours']);
        if (isset($data['nbPlaces'])) $filiere->setNbPlaces($data['nbPlaces']);
        if (isset($data['nombreAnnees'])) $filiere->setNombreAnnees($data['nombreAnnees']);
        if (isset($data['typeEcole'])) $filiere->setTypeEcole($data['typeEcole']);
        if (isset($data['bacCompatible'])) $filiere->setBacCompatible($data['bacCompatible']);
        if (isset($data['bacType'])) $filiere->setBacType($data['bacType']);
        if (isset($data['filieresAcceptees'])) $filiere->setFilieresAcceptees($data['filieresAcceptees']);
        if (isset($data['combinaisonsBacMission'])) $filiere->setCombinaisonsBacMission($data['combinaisonsBacMission']);
        if (isset($data['recommandee'])) $filiere->setRecommandee($data['recommandee']);
        if (isset($data['metier'])) $filiere->setMetier($data['metier']);
        if (isset($data['objectifs'])) $filiere->setObjectifs($data['objectifs']);
        if (isset($data['programme'])) $filiere->setProgramme($data['programme']);
        if (isset($data['documents'])) $filiere->setDocuments($data['documents']);
        if (isset($data['photos'])) $filiere->setPhotos($data['photos']);
        if (isset($data['videoUrl'])) $filiere->setVideoUrl($data['videoUrl']);
        if (isset($data['reconnaissance'])) $filiere->setReconnaissance($data['reconnaissance']);
        if (isset($data['echangeInternational'])) $filiere->setEchangeInternational($data['echangeInternational']);
        if (isset($data['metaTitle'])) $filiere->setMetaTitle($data['metaTitle']);
        if (isset($data['metaDescription'])) $filiere->setMetaDescription($data['metaDescription']);
        if (isset($data['metaKeywords'])) $filiere->setMetaKeywords($data['metaKeywords']);
        if (isset($data['ogImage'])) $filiere->setOgImage($data['ogImage']);
        if (isset($data['canonicalUrl'])) $filiere->setCanonicalUrl($data['canonicalUrl']);
        if (isset($data['schemaType'])) $filiere->setSchemaType($data['schemaType']);
        if (isset($data['noIndex'])) $filiere->setNoIndex($data['noIndex']);
        if (isset($data['isSponsored'])) $filiere->setIsSponsored($data['isSponsored']);
        if (isset($data['isActive'])) $filiere->setIsActive($data['isActive']);

        // Gérer la relation avec l'établissement
        $establishment = null;
        if (isset($data['establishmentId'])) {
            $establishment = $this->establishmentRepository->find($data['establishmentId']);
            if ($establishment) {
                $filiere->setEstablishment($establishment);
            }
        } else {
            // Si pas de changement d'établissement, récupérer l'établissement actuel
            $establishment = $filiere->getEstablishment();
        }

        // Le logo de la filière est automatiquement celui de l'établissement
        if ($establishment && $establishment->getLogo()) {
            $filiere->setImageCouverture($establishment->getLogo());
        }

        // Gérer les campus (plusieurs campus possibles)
        if (isset($data['campusIds'])) {
            // Vider la collection actuelle
            $currentCampus = $filiere->getCampus()->toArray();
            foreach ($currentCampus as $campus) {
                $filiere->removeCampus($campus);
            }
            
            // Ajouter les nouveaux campus si le tableau n'est pas vide
            if (is_array($data['campusIds']) && !empty($data['campusIds'])) {
                foreach ($data['campusIds'] as $campusId) {
                    if (!empty($campusId)) {
                        $campus = $this->campusRepository->find((int)$campusId);
                        if ($campus) {
                            $filiere->addCampus($campus);
                        }
                    }
                }
            }
        }
    }

    /**
     * Enrichit les données de la filière avec des informations calculées
     */
    private function enrichFiliereData(array $data, Filiere $filiere): array
    {
        // Calculer l'URL de la page de détail
        $data['url'] = '/filieres/' . $filiere->getSlug();

        // Formater les dates
        $data['createdAtFormatted'] = $filiere->getCreatedAt()?->format('Y-m-d H:i:s');
        $data['updatedAtFormatted'] = $filiere->getUpdatedAt()?->format('Y-m-d H:i:s');

        // Informations de l'établissement
        if ($filiere->getEstablishment()) {
            $establishment = $filiere->getEstablishment();
            $data['establishment'] = [
                'id' => $establishment->getId(),
                'nom' => $establishment->getNom(),
                'sigle' => $establishment->getSigle(),
                'logo' => $establishment->getLogo(),
                'pays' => $establishment->getPays(),
                'universite' => $establishment->getUniversite(),
                'type' => $establishment->getType(),
                'url' => '/etablissements/' . $establishment->getId() . '/' . $establishment->getSlug(),
                'eTawjihiInscription' => $establishment->isETawjihiInscription()
            ];
            
            // Le logo de la filière est toujours celui de l'établissement
            $data['imageCouverture'] = $establishment->getLogo();
            $data['logo'] = $establishment->getLogo();
        }

        // Informations des campus
        $campusData = [];
        foreach ($filiere->getCampus() as $campus) {
            $campusData[] = [
                'id' => $campus->getId(),
                'nom' => $campus->getNom(),
                'ville' => $campus->getVille(), // Retourne le titre de la City pour compatibilité
                'cityId' => $campus->getCity()?->getId(),
                'city' => $campus->getCity() ? [
                    'id' => $campus->getCity()->getId(),
                    'titre' => $campus->getCity()->getTitre(),
                ] : null,
                'quartier' => $campus->getQuartier(),
                'adresse' => $campus->getAdresse(),
            ];
        }
        $data['campus'] = $campusData;

        return $data;
    }
}
