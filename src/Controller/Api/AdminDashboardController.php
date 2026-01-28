<?php

namespace App\Controller\Api;

use App\Service\AdminDashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private AdminDashboardStatsService $dashboardStats,
    ) {
    }

    /**
     * Statistiques détaillées du dashboard admin.
     * Query: schoolYear (ex. 2024-2025). Année scolaire = octobre → septembre.
     */
    #[Route('/stats', name: 'api_admin_dashboard_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $schoolYear = $request->query->get('schoolYear');
        $schoolYear = \is_string($schoolYear) && $schoolYear !== '' ? trim($schoolYear) : null;
        $data = $this->dashboardStats->getStats($schoolYear);
        return new JsonResponse(['success' => true, 'data' => $data]);
    }
}
