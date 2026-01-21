<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/articles', name: 'api_articles_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArticleRepository $articleRepository,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Liste tous les articles (avec filtres)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
            $offset = ($page - 1) * $limit;

            // Récupérer les filtres
            $filters = [
                'search' => $request->query->get('search'),
                'status' => $request->query->get('status'),
                'isActivate' => $request->query->get('isActivate') !== null ? (bool) $request->query->get('isActivate') : null,
                'isComplet' => $request->query->get('isComplet') !== null ? (bool) $request->query->get('isComplet') : null,
                'categorie' => $request->query->get('categorie'),
                'featured' => $request->query->get('featured') !== null ? (bool) $request->query->get('featured') : null,
                'tag' => $request->query->get('tag'),
            ];

            // Nettoyer les filtres vides
            $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Récupérer les articles avec filtres
            $articles = $this->articleRepository->findWithFilters($filters);
            $total = count($articles);

            // Pagination
            $articles = array_slice($articles, $offset, $limit);

            // Sérialiser les données
            $data = $this->serializer->normalize($articles, null, [
                'groups' => ['article:list']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des articles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des articles: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère un article par ID
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $article = $this->articleRepository->find($id);

            if (!$article) {
                return $this->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Incrémenter le compteur de vues
            $article->setVues($article->getVues() + 1);
            $this->entityManager->flush();

            $data = $this->serializer->normalize($article, null, [
                'groups' => ['article:read']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère un article par slug (pour le frontend)
     */
    #[Route('/slug/{slug}', name: 'show_by_slug', methods: ['GET'])]
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $article = $this->articleRepository->findOneBySlug($slug);

            if (!$article) {
                return $this->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Incrémenter le nombre de vues
            $article->setVues($article->getVues() + 1);
            $this->entityManager->flush();

            $data = $this->serializer->normalize($article, null, [
                'groups' => ['article:read']
            ]);

            return $this->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crée un nouvel article
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

            $article = new Article();
            $this->hydrateArticle($article, $data);

            // Générer le slug si non fourni
            if (empty($article->getSlug()) && !empty($article->getTitre())) {
                $article->setSlug($this->generateSlug($article->getTitre()));
            }

            // Si date de publication non fournie et status = Publié, utiliser maintenant
            if ($article->getStatus() === 'Publié' && !$article->getDatePublication()) {
                $article->setDatePublication(new \DateTime());
            }

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($article, null, [
                'groups' => ['article:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => $responseData
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'article', [
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
     * Met à jour un article
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $article = $this->articleRepository->find($id);

            if (!$article) {
                return $this->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->hydrateArticle($article, $data);

            // Si date de publication non fournie et status = Publié, utiliser maintenant
            if ($article->getStatus() === 'Publié' && !$article->getDatePublication()) {
                $article->setDatePublication(new \DateTime());
            }

            $this->entityManager->flush();

            $responseData = $this->serializer->normalize($article, null, [
                'groups' => ['article:read']
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Article mis à jour avec succès',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour de l\'article', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprime un article
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $article = $this->articleRepository->find($id);

            if (!$article) {
                return $this->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($article);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Article supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actions en masse (bulk actions)
     */
    #[Route('/bulk', name: 'bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $action = $data['action'] ?? null;
            $ids = $data['ids'] ?? [];

            if (!$action || empty($ids)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Action et IDs requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $articles = $this->articleRepository->findBy(['id' => $ids]);

            foreach ($articles as $article) {
                switch ($action) {
                    case 'activate':
                        $article->setIsActivate(true);
                        break;
                    case 'deactivate':
                        $article->setIsActivate(false);
                        break;
                    case 'publish':
                        $article->setStatus('Publié');
                        $article->setIsActivate(true);
                        if (!$article->getDatePublication()) {
                            $article->setDatePublication(new \DateTime());
                        }
                        break;
                    case 'draft':
                        $article->setStatus('Brouillon');
                        break;
                    case 'delete':
                        $this->entityManager->remove($article);
                        break;
                    default:
                        return $this->json([
                            'success' => false,
                            'message' => 'Action non reconnue'
                        ], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => count($articles) . ' article(s) ' . $action . ' avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action en masse: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Hydrate l'entité Article avec les données
     */
    private function hydrateArticle(Article $article, array $data): void
    {
        // Informations de base
        if (isset($data['titre'])) $article->setTitre($data['titre']);
        if (isset($data['slug'])) $article->setSlug($data['slug']);
        if (isset($data['description'])) $article->setDescription($data['description']);
        if (isset($data['contenu'])) $article->setContenu($data['contenu']);

        // Image
        if (isset($data['imageCouverture']) && $data['imageCouverture'] !== '') {
            $article->setImageCouverture($data['imageCouverture']);
        } elseif (array_key_exists('imageCouverture', $data) && $data['imageCouverture'] === '') {
            $article->setImageCouverture(null);
        }

        // Catégories et tags
        if (isset($data['categorie'])) $article->setCategorie($data['categorie']);
        if (isset($data['categories'])) {
            $article->setCategories(is_array($data['categories']) ? $data['categories'] : null);
        }
        if (isset($data['tags'])) {
            $article->setTags(is_array($data['tags']) ? $data['tags'] : null);
        }

        // Auteur
        if (isset($data['auteur'])) $article->setAuteur($data['auteur']);

        // Date de publication
        if (isset($data['datePublication'])) {
            if ($data['datePublication']) {
                $article->setDatePublication(new \DateTime($data['datePublication']));
            } else {
                $article->setDatePublication(null);
            }
        }

        // Status
        if (isset($data['status'])) $article->setStatus($data['status']);
        if (isset($data['featured'])) $article->setFeatured((bool) $data['featured']);

        // SEO
        if (isset($data['metaTitle'])) $article->setMetaTitle($data['metaTitle']);
        if (isset($data['metaDescription'])) $article->setMetaDescription($data['metaDescription']);
        if (isset($data['metaKeywords'])) $article->setMetaKeywords($data['metaKeywords']);
        if (isset($data['ogImage']) && $data['ogImage'] !== '') {
            $article->setOgImage($data['ogImage']);
        } elseif (array_key_exists('ogImage', $data) && $data['ogImage'] === '') {
            $article->setOgImage(null);
        }
        if (isset($data['ogTitle'])) $article->setOgTitle($data['ogTitle']);
        if (isset($data['ogDescription'])) $article->setOgDescription($data['ogDescription']);
        if (isset($data['canonicalUrl'])) $article->setCanonicalUrl($data['canonicalUrl']);
        if (isset($data['schemaType'])) $article->setSchemaType($data['schemaType']);
        if (isset($data['noIndex'])) $article->setNoIndex((bool) $data['noIndex']);

        // Statistiques
        if (isset($data['tempsLecture'])) {
            $article->setTempsLecture($data['tempsLecture'] === '' || $data['tempsLecture'] === null ? null : (int) $data['tempsLecture']);
        }
        if (isset($data['vues'])) {
            $article->setVues((int) $data['vues']);
        }

        // Statut
        if (isset($data['isActivate'])) {
            $article->setIsActivate((bool) $data['isActivate']);
        }
        if (isset($data['isComplet'])) {
            $article->setIsComplet((bool) $data['isComplet']);
        }
    }

    /**
     * Génère un slug à partir d'un titre
     */
    private function generateSlug(string $titre): string
    {
        // Convertir en minuscules
        $slug = strtolower($titre);
        
        // Remplacer les caractères accentués
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        
        // Remplacer les espaces et caractères spéciaux par des tirets
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Supprimer les tirets en début et fin
        $slug = trim($slug, '-');
        
        // Vérifier l'unicité
        $baseSlug = $slug;
        $counter = 1;
        while ($this->articleRepository->findOneBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
