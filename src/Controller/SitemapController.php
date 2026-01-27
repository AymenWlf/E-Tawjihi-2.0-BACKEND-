<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Entity\Article;
use App\Entity\Filiere;
use App\Entity\Secteur;
use App\Repository\EstablishmentRepository;
use App\Repository\ArticleRepository;
use App\Repository\FiliereRepository;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sitemap', name: 'sitemap_')]
class SitemapController extends AbstractController
{
    // URL du frontend (pas du backend)
    private const FRONTEND_URL = 'https://e-tawjihi.ma';
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EstablishmentRepository $establishmentRepository,
        private ArticleRepository $articleRepository,
        private FiliereRepository $filiereRepository,
        private SecteurRepository $secteurRepository
    ) {
    }

    /**
     * Génère la sitemap XML complète du site
     */
    #[Route('.xml', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Toujours utiliser l'URL du frontend pour les URLs du sitemap
        $baseUrl = self::FRONTEND_URL;
        $urls = [];

        // Pages statiques principales
        $urls[] = $this->createUrl($baseUrl . '/', '1.0', 'daily');
        $urls[] = $this->createUrl($baseUrl . '/etablissements', '0.9', 'daily');
        $urls[] = $this->createUrl($baseUrl . '/secteurs', '0.9', 'daily');
        $urls[] = $this->createUrl($baseUrl . '/services', '0.8', 'weekly');
        $urls[] = $this->createUrl($baseUrl . '/blog', '0.8', 'daily');
        $urls[] = $this->createUrl($baseUrl . '/filieres', '0.8', 'daily');

        // Établissements (avec slug)
        $establishments = $this->establishmentRepository->findBy(['isActive' => true]);
        foreach ($establishments as $establishment) {
            if ($establishment->getSlug()) {
                $lastmod = $establishment->getUpdatedAt() 
                    ? $establishment->getUpdatedAt()->format(self::DATE_FORMAT)
                    : date(self::DATE_FORMAT);
                
                $urls[] = $this->createUrl(
                    $baseUrl . '/etablissements/' . $establishment->getId() . '/' . $establishment->getSlug(),
                    '0.9',
                    'weekly',
                    $lastmod
                );
            }
        }

        // Secteurs (sans slug, donc on utilise l'ID ou on liste tous les secteurs)
        $secteurs = $this->secteurRepository->findBy(['isActivate' => true]);
        foreach ($secteurs as $secteur) {
            // Les secteurs sont accessibles via /secteurs avec des filtres
            // On peut aussi créer une page détail par secteur si elle existe
            // Pour l'instant, on met juste la page principale des secteurs
        }

        // Articles/Blog (avec slug)
        $articles = $this->articleRepository->findBy(['status' => 'Publié', 'isActivate' => true]);
        foreach ($articles as $article) {
            if ($article->getSlug()) {
                $lastmod = $article->getUpdatedAt() 
                    ? $article->getUpdatedAt()->format(self::DATE_FORMAT)
                    : ($article->getDatePublication() 
                        ? $article->getDatePublication()->format(self::DATE_FORMAT)
                        : date(self::DATE_FORMAT));
                
                $urls[] = $this->createUrl(
                    $baseUrl . '/blog/' . $article->getSlug(),
                    '0.7',
                    'monthly',
                    $lastmod
                );
            }
        }

        // Filières (avec slug)
        $filieres = $this->filiereRepository->findBy(['isActive' => true]);
        foreach ($filieres as $filiere) {
            if ($filiere->getSlug()) {
                $lastmod = $filiere->getUpdatedAt() 
                    ? $filiere->getUpdatedAt()->format(self::DATE_FORMAT)
                    : date(self::DATE_FORMAT);
                
                $urls[] = $this->createUrl(
                    $baseUrl . '/filieres/' . $filiere->getSlug(),
                    '0.8',
                    'monthly',
                    $lastmod
                );
            }
        }

        // Générer le XML
        $xml = $this->generateSitemapXml($urls);

        $response = new Response($xml, Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');

        return $response;
    }

    /**
     * Crée une entrée URL pour la sitemap
     */
    private function createUrl(string $loc, string $priority, string $changefreq, ?string $lastmod = null): array
    {
        $url = [
            'loc' => $loc,
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];

        if ($lastmod) {
            $url['lastmod'] = $lastmod;
        }

        return $url;
    }

    /**
     * Génère le XML de la sitemap
     */
    private function generateSitemapXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
        $xml .= '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            
            if (isset($url['lastmod'])) {
                $xml .= "    <lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            
            $xml .= "    <changefreq>" . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= "    <priority>" . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            $xml .= "  </url>\n\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
