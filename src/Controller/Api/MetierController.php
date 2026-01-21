<?php

namespace App\Controller\Api;

use App\Entity\Metier;
use App\Entity\Secteur;
use App\Repository\MetierRepository;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/metiers')]
class MetierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('', name: 'api_metiers_list', methods: ['GET'])]
    public function list(MetierRepository $repository, Request $request): JsonResponse
    {
        $secteurId = $request->query->get('secteur');
        $search = $request->query->get('search');
        $afficherDansTest = $request->query->get('afficherDansTest');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $queryBuilder = $repository->createQueryBuilder('m')
            ->where('m.isActivate = :isActivate')
            ->setParameter('isActivate', true);

        if ($secteurId) {
            $queryBuilder
                ->andWhere('m.secteur = :secteurId')
                ->setParameter('secteurId', $secteurId);
        }

        if ($search) {
            $queryBuilder
                ->andWhere('m.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($afficherDansTest !== null) {
            $queryBuilder
                ->andWhere('m.afficherDansTest = :afficherDansTest')
                ->setParameter('afficherDansTest', filter_var($afficherDansTest, FILTER_VALIDATE_BOOLEAN));
        }

        $total = count($queryBuilder->getQuery()->getResult());
        $metiers = $queryBuilder
            ->orderBy('m.nom', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->json([
            'success' => true,
            'data' => $metiers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], Response::HTTP_OK, [], ['groups' => ['metier:list', 'secteur:list']]);
    }

    #[Route('/{id}', name: 'api_metiers_get', methods: ['GET'])]
    public function get(Metier $metier): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $metier
        ], Response::HTTP_OK, [], ['groups' => ['metier:read', 'secteur:list']]);
    }

    #[Route('/slug/{slug}', name: 'api_metiers_get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug, MetierRepository $repository): JsonResponse
    {
        $metier = $repository->findBySlug($slug);
        
        if (!$metier) {
            return $this->json([
                'success' => false,
                'message' => 'Métier non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $metier
        ], Response::HTTP_OK, [], ['groups' => ['metier:read', 'secteur:list']]);
    }

    #[Route('', name: 'api_metiers_create', methods: ['POST'])]
    public function create(Request $request, SecteurRepository $secteurRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || !isset($data['secteur_id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Le nom et le secteur sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $secteur = $secteurRepository->find($data['secteur_id']);
        if (!$secteur) {
            return $this->json([
                'success' => false,
                'message' => 'Secteur non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $metier = new Metier();
        $this->hydrateMetier($metier, $data, $secteur);

        $this->em->persist($metier);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Métier créé avec succès',
            'data' => $metier
        ], Response::HTTP_CREATED, [], ['groups' => ['metier:read', 'secteur:list']]);
    }

    #[Route('/{id}', name: 'api_metiers_update', methods: ['PUT', 'PATCH'])]
    public function update(Metier $metier, Request $request, SecteurRepository $secteurRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $secteur = null;
        if (isset($data['secteur_id'])) {
            $secteur = $secteurRepository->find($data['secteur_id']);
            if (!$secteur) {
                return $this->json([
                    'success' => false,
                    'message' => 'Secteur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            $secteur = $metier->getSecteur();
        }

        $this->hydrateMetier($metier, $data, $secteur);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Métier mis à jour avec succès',
            'data' => $metier
        ], Response::HTTP_OK, [], ['groups' => ['metier:read', 'secteur:list']]);
    }

    #[Route('/{id}', name: 'api_metiers_delete', methods: ['DELETE'])]
    public function delete(Metier $metier): JsonResponse
    {
        $this->em->remove($metier);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Métier supprimé avec succès'
        ], Response::HTTP_OK);
    }

    private function hydrateMetier(Metier $metier, array $data, ?Secteur $secteur): void
    {
        if (isset($data['nom'])) {
            $metier->setNom($data['nom']);
            
            // Générer le slug si non fourni
            if (!isset($data['slug']) || empty($data['slug'])) {
                $slug = strtolower($this->slugger->slug($data['nom'])->toString());
                $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
                $slug = trim($slug, '-');
                $metier->setSlug($slug);
            } else {
                $metier->setSlug($data['slug']);
            }
        }

        if ($secteur) {
            $metier->setSecteur($secteur);
        }

        if (isset($data['nomArabe'])) {
            $metier->setNomArabe($data['nomArabe']);
        }

        if (isset($data['description'])) {
            $metier->setDescription($data['description']);
        }

        if (isset($data['niveauAccessibilite'])) {
            $metier->setNiveauAccessibilite($data['niveauAccessibilite']);
        }

        if (isset($data['salaireMin'])) {
            $metier->setSalaireMin($data['salaireMin']);
        }

        if (isset($data['salaireMax'])) {
            $metier->setSalaireMax($data['salaireMax']);
        }

        if (isset($data['competences'])) {
            $metier->setCompetences($data['competences']);
        }

        if (isset($data['formations'])) {
            $metier->setFormations($data['formations']);
        }

        if (isset($data['isActivate'])) {
            $metier->setIsActivate($data['isActivate']);
        }

        if (isset($data['afficherDansTest'])) {
            $metier->setAfficherDansTest($data['afficherDansTest']);
        } elseif ($metier->getId() === null) {
            // Par défaut, afficher dans le test pour les nouveaux métiers
            $metier->setAfficherDansTest(true);
        }
    }
}
