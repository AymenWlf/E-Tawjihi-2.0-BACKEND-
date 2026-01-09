<?php

namespace App\Controller\Api;

use App\Entity\Region;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/regions', name: 'api_regions_')]
class RegionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RegionRepository $regionRepository,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste toutes les régions avec filtres optionnels
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $search = $request->query->get('search');
            
            $queryBuilder = $this->regionRepository->createQueryBuilder('r');
            
            if ($search) {
                $queryBuilder->andWhere('r.titre LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
            
            $queryBuilder->orderBy('r.titre', 'ASC');
            
            $regions = $queryBuilder->getQuery()->getResult();
            
            $data = array_map(function (Region $region) {
                return [
                    'id' => $region->getId(),
                    'titre' => $region->getTitre(),
                ];
            }, $regions);
            
            return $this->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des régions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des régions: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
