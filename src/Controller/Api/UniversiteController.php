<?php

namespace App\Controller\Api;

use App\Entity\Universite;
use App\Repository\UniversiteRepository;
use App\Repository\CityRepository;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/universites', name: 'api_universites_')]
class UniversiteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UniversiteRepository $universiteRepository,
        private CityRepository $cityRepository,
        private RegionRepository $regionRepository,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste toutes les universités avec filtres optionnels
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query->get('search'),
                'ville' => $request->query->get('ville'),
                'pays' => $request->query->get('pays'),
            ];

            // Nettoyer les filtres null
            $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            $universites = $this->universiteRepository->findWithFilters($filters);

            $data = $this->serializer->normalize($universites, null, [
                'groups' => ['universite:list']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des universités', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des universités: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère une université par ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        try {
            $universite = $this->universiteRepository->find($id);

            if (!$universite) {
                return $this->json([
                    'success' => false,
                    'message' => 'Université non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->normalize($universite, null, [
                'groups' => ['universite:read']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération de l\'université', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'université: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée une nouvelle université
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

            $universite = new Universite();
            $this->hydrateUniversite($universite, $data);

            $this->entityManager->persist($universite);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($universite, null, [
                'groups' => ['universite:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Université créée avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'université', [
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
     * Met à jour une université
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $universite = $this->universiteRepository->find($id);

            if (!$universite) {
                return $this->json([
                    'success' => false,
                    'message' => 'Université non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->hydrateUniversite($universite, $data);

            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($universite, null, [
                'groups' => ['universite:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Université mise à jour avec succès',
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour de l\'université', [
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
     * Supprime une université
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $universite = $this->universiteRepository->find($id);

            if (!$universite) {
                return $this->json([
                    'success' => false,
                    'message' => 'Université non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($universite);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Université supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de l\'université', [
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
     * Hydrate une université avec les données fournies
     */
    private function hydrateUniversite(Universite $universite, array $data): void
    {
        if (isset($data['nom'])) $universite->setNom($data['nom']);
        if (isset($data['sigle'])) $universite->setSigle($data['sigle']);
        if (isset($data['nomArabe'])) $universite->setNomArabe($data['nomArabe']);
        
        // Gérer la localisation : soit ville, soit région (exclusif)
        // Si locationType est 'ville', utiliser la ville et effacer la région
        // Si locationType est 'region', utiliser la région et effacer la ville
        $locationType = $data['locationType'] ?? 'ville';
        
        if ($locationType === 'ville') {
            // Gérer la ville : si cityId est fourni, récupérer le nom de la ville depuis City
            if (isset($data['cityId']) && $data['cityId']) {
                $city = $this->cityRepository->find((int)$data['cityId']);
                if ($city) {
                    $universite->setVille($city->getTitre());
                }
            } elseif (isset($data['ville'])) {
                $universite->setVille($data['ville']);
            }
            // Effacer la région
            $universite->setRegion(null);
        } elseif ($locationType === 'region') {
            // Gérer la région : si regionId est fourni, récupérer le nom de la région depuis Region
            if (isset($data['regionId']) && $data['regionId']) {
                $region = $this->regionRepository->find((int)$data['regionId']);
                if ($region) {
                    $universite->setRegion($region->getTitre());
                }
            } elseif (isset($data['region'])) {
                $universite->setRegion($data['region']);
            }
            // Effacer la ville
            $universite->setVille(null);
        }
        
        if (isset($data['pays'])) $universite->setPays($data['pays']);
        if (isset($data['type'])) $universite->setType($data['type']);
        if (isset($data['description'])) $universite->setDescription($data['description']);
        // Logo : mettre à jour seulement si une valeur est fournie
        // Si logo n'est pas dans les données, on ne le modifie pas (garde la valeur existante)
        if (isset($data['logo'])) {
            if ($data['logo'] !== '' && $data['logo'] !== null) {
                $universite->setLogo($data['logo']);
            } else {
                // Si logo est explicitement vide ou null, le supprimer
                $universite->setLogo(null);
            }
        }
        if (isset($data['siteWeb'])) $universite->setSiteWeb($data['siteWeb']);
        if (isset($data['email'])) $universite->setEmail($data['email']);
        if (isset($data['telephone'])) $universite->setTelephone($data['telephone']);
        if (isset($data['isActive'])) $universite->setIsActive($data['isActive']);
    }
}
