<?php

namespace App\Controller\Api;

use App\Entity\City;
use App\Repository\CityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CityController extends AbstractController
{
    #[Route('/api/cities', name: 'api_cities', methods: ['GET'])]
    public function getCities(Request $request, CityRepository $cityRepository): JsonResponse
    {
        try {
            $search = $request->query->get('search', '');
            $limit = (int) $request->query->get('limit', 500);
            
            // Si pas de recherche, charger toutes les villes (avec limite)
            if (empty($search)) {
                $cities = $cityRepository->createQueryBuilder('c')
                    ->orderBy('c.titre', 'ASC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
            } else {
                $cities = $cityRepository->findBySearch($search, $limit);
            }
            
            $data = array_map(function (City $city) {
                return [
                    'id' => $city->getId(),
                    'titre' => $city->getTitre() ?: '',
                    'region' => $city->getRegion() ? [
                        'id' => $city->getRegion()->getId(),
                        'titre' => $city->getRegion()->getTitre(),
                    ] : null,
                ];
            }, $cities);
            
            return new JsonResponse([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des villes: ' . $e->getMessage(),
                'data' => [],
                'count' => 0,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
