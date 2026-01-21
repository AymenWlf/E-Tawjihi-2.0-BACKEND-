<?php

namespace App\Command;

use App\Entity\Secteur;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:populate-secteurs-images',
    description: 'Remplit automatiquement les images des secteurs en utilisant Unsplash API'
)]
class PopulateSecteursImagesCommand extends Command
{
    private const UNSPLASH_API_URL = 'https://api.unsplash.com/search/photos';
    private const PEXELS_API_URL = 'https://api.pexels.com/v1/search';
    
    // Mapping des secteurs vers des URLs d'images Unsplash directes
    private const SECTEUR_IMAGES = [
        'ENVIRONNEMENT' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=800&h=600&fit=crop&auto=format',
        'BATIMENT' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop&auto=format',
        'INDUSTRIE' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800&h=600&fit=crop&auto=format',
        'AGRICULTURE' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=800&h=600&fit=crop&auto=format',
        'CULTURE' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=600&fit=crop&auto=format',
        'SHS' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800&h=600&fit=crop&auto=format',
        'DEFENSE' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&h=600&fit=crop&auto=format',
        'JEUX_VIDEO' => 'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=800&h=600&fit=crop&auto=format',
        'SPORT' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=600&fit=crop&auto=format',
        'TOURISME' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800&h=600&fit=crop&auto=format',
        'PECHE_MARITIME' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&h=600&fit=crop&auto=format',
        'SCIENCES_POLITIQUES' => 'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=800&h=600&fit=crop&auto=format',
        'SANTE' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800&h=600&fit=crop&auto=format',
        'EDUCATION' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=600&fit=crop&auto=format',
        'INFORMATIQUE' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&auto=format',
        'COMMERCE' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&auto=format',
        'FINANCE' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&h=600&fit=crop&auto=format',
        'DROIT' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&h=600&fit=crop&auto=format',
        // Ajout des secteurs manquants
        'TECHNOLOGIE' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&auto=format',
        'EDUCATION_ALT' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=600&fit=crop&auto=format',
        'JURIDIQUE' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&h=600&fit=crop&auto=format',
        'ARTS' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=600&fit=crop&auto=format',
        'COMMUNICATION' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8f2d1c6?w=800&h=600&fit=crop&auto=format',
        'MARKETING' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&auto=format',
        'RESSOURCES_HUMAINES' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=800&h=600&fit=crop&auto=format',
        'TRANSPORT' => 'https://images.unsplash.com/photo-1570125909517-53cb21c89ff2?w=800&h=600&fit=crop&auto=format',
        'LOGISTIQUE' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&h=600&fit=crop&auto=format',
        'HOTELLERIE' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop&auto=format',
        'RESTAURATION' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&auto=format',
        'SERVICES_PUBLICS' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format',
        'ENTREPRENEURIAT' => 'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=800&h=600&fit=crop&auto=format',
        'RECHERCHE' => 'https://images.unsplash.com/photo-1532619675605-1ede6c9ed2d7?w=800&h=600&fit=crop&auto=format',
    ];
    
    private ?string $unsplashAccessKey = null;
    private ?string $pexelsApiKey = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecteurRepository $secteurRepository,
        private SluggerInterface $slugger,
        private HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la mise à jour même si une image existe déjà')
            ->addOption('clear-all', null, InputOption::VALUE_NONE, 'Supprimer toutes les images existantes avant de télécharger')
            ->addOption('secteur-id', null, InputOption::VALUE_OPTIONAL, 'Traiter uniquement un secteur spécifique par ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Remplissage automatique des images des secteurs');

        // Récupérer les clés API depuis les variables d'environnement (optionnel)
        $unsplashKey = $_ENV['UNSPLASH_ACCESS_KEY'] ?? null;
        $pexelsKey = $_ENV['PEXELS_API_KEY'] ?? null;
        
        $hasUnsplash = !empty($unsplashKey);
        $hasPexels = !empty($pexelsKey);
        
        // Stocker les clés pour utilisation dans les méthodes
        $this->unsplashAccessKey = $unsplashKey;
        $this->pexelsApiKey = $pexelsKey;
        
        if ($hasUnsplash || $hasPexels) {
            $io->info('Utilisation des APIs configurées pour télécharger les images.');
        } else {
            $io->warning('Aucune clé API configurée. Utilisation d\'images publiques depuis Unsplash Source.');
            $io->note('Pour de meilleurs résultats, configurez une clé API dans votre .env :');
            $io->note('  UNSPLASH_ACCESS_KEY=votre_cle (https://unsplash.com/developers)');
            $io->note('  OU');
            $io->note('  PEXELS_API_KEY=votre_cle (https://www.pexels.com/api/)');
        }

        // Récupérer les secteurs
        $secteurId = $input->getOption('secteur-id');
        if ($secteurId) {
            $secteur = $this->secteurRepository->find($secteurId);
            $secteurs = $secteur ? [$secteur] : [];
        } else {
            $secteurs = $this->secteurRepository->findAll();
        }

        if (empty($secteurs)) {
            $io->warning('Aucun secteur trouvé');
            return Command::FAILURE;
        }

        $force = $input->getOption('force');
        $clearAll = $input->getOption('clear-all');
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $deleted = 0;

        // Si --clear-all, supprimer toutes les images existantes
        if ($clearAll) {
            $io->info('Suppression de toutes les images existantes...');
            foreach ($secteurs as $secteur) {
                if ($secteur->getImage()) {
                    // Supprimer le fichier physique
                    $imagePath = $secteur->getImage();
                    if (strpos($imagePath, '/uploads/') === 0) {
                        $fullPath = $this->projectDir . '/public' . $imagePath;
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                            $deleted++;
                        }
                    }
                    // Supprimer la référence dans la base de données
                    $secteur->setImage(null);
                }
            }
            $this->entityManager->flush();
            $io->success(sprintf('%d images supprimées.', $deleted));
            $io->newLine();
        }

        $io->progressStart(count($secteurs));

        foreach ($secteurs as $secteur) {
            $io->progressAdvance();

            // Si force ou clear-all, supprimer l'image existante avant de télécharger
            if (($force || $clearAll) && $secteur->getImage()) {
                $oldImagePath = $secteur->getImage();
                if (strpos($oldImagePath, '/uploads/') === 0) {
                    $fullPath = $this->projectDir . '/public' . $oldImagePath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                $secteur->setImage(null);
                $this->entityManager->flush();
            }

            // Skip si déjà une image et pas en mode force ou clear-all
            if (!$force && !$clearAll && $secteur->getImage()) {
                $skipped++;
                continue;
            }

            try {
                // Obtenir le terme de recherche
                $searchTerm = $this->getSearchTerm($secteur);
                
                if (!$searchTerm) {
                    $io->warning(sprintf('Aucun terme de recherche trouvé pour le secteur "%s" (code: %s)', $secteur->getTitre(), $secteur->getCode()));
                    $failed++;
                    continue;
                }

                // Rechercher une image sur Unsplash, Pexels ou via Unsplash Source (sans clé)
                $imageUrl = null;
                if ($hasUnsplash) {
                    $imageUrl = $this->searchImageOnUnsplash($searchTerm, $io);
                }
                if (!$imageUrl && $hasPexels) {
                    $imageUrl = $this->searchImageOnPexels($searchTerm, $io);
                }
                // Fallback : utiliser Unsplash Source (pas besoin de clé, mais moins précis)
                if (!$imageUrl) {
                    $imageUrl = $this->getImageFromUnsplashSource($searchTerm, $secteur->getCode());
                }
                
                if (!$imageUrl) {
                    $io->warning(sprintf('Aucune image trouvée pour "%s" (secteur: %s)', $searchTerm, $secteur->getTitre()));
                    $failed++;
                    continue;
                }

                // Télécharger et sauvegarder l'image avec retry
                $savedImagePath = null;
                $maxRetries = 2;
                
                for ($retry = 0; $retry <= $maxRetries; $retry++) {
                    if ($retry > 0) {
                        // Attendre un peu avant de réessayer
                        sleep(1);
                        // Essayer une URL alternative si disponible
                        $alternativeUrl = $this->getAlternativeImageUrl($secteur->getCode(), $searchTerm);
                        if ($alternativeUrl) {
                            $imageUrl = $alternativeUrl;
                        }
                    }
                    
                    $savedImagePath = $this->downloadAndSaveImage($imageUrl, $secteur);
                    if ($savedImagePath) {
                        break; // Succès, on sort de la boucle
                    }
                }
                
                if (!$savedImagePath) {
                    $io->warning(sprintf('Erreur lors du téléchargement de l\'image pour le secteur "%s" (URL: %s)', $secteur->getTitre(), substr($imageUrl, 0, 60) . '...'));
                    $failed++;
                    continue;
                }

                // Mettre à jour le secteur
                $secteur->setImage($savedImagePath);
                $this->entityManager->flush();

                $updated++;
                $io->success(sprintf('Image ajoutée pour "%s" : %s', $secteur->getTitre(), $savedImagePath));

                // Pause pour éviter de dépasser les limites de l'API
                sleep(1);

            } catch (\Exception $e) {
                $io->error(sprintf('Erreur pour le secteur "%s": %s', $secteur->getTitre(), $e->getMessage()));
                $failed++;
            }
        }

        $io->progressFinish();
        $io->newLine();

        $message = sprintf(
            'Terminé ! %d secteurs mis à jour, %d ignorés, %d échecs',
            $updated,
            $skipped,
            $failed
        );
        
        if ($clearAll && $deleted > 0) {
            $message .= sprintf(', %d images supprimées', $deleted);
        }
        
        $io->success($message);

        return Command::SUCCESS;
    }

    private function getSearchTerm(Secteur $secteur): ?string
    {
        $code = $secteur->getCode();
        $titre = $secteur->getTitre();

        // Mapping des codes vers des termes de recherche en anglais (pour les APIs)
        $searchTermsMap = [
            'ENVIRONNEMENT' => 'environment sustainability',
            'BATIMENT' => 'architecture construction',
            'INDUSTRIE' => 'industry manufacturing',
            'AGRICULTURE' => 'agriculture farming',
            'CULTURE' => 'culture arts',
            'SHS' => 'humanities social sciences',
            'DEFENSE' => 'defense security',
            'JEUX_VIDEO' => 'video games technology',
            'SPORT' => 'sports athletics',
            'TOURISME' => 'tourism travel',
            'PECHE_MARITIME' => 'fishing maritime',
            'SCIENCES_POLITIQUES' => 'politics government',
            'SANTE' => 'healthcare medical',
            'EDUCATION' => 'education school',
            'INFORMATIQUE' => 'computer technology',
            'COMMERCE' => 'business commerce',
            'FINANCE' => 'finance banking',
            'DROIT' => 'law justice',
            'TECHNOLOGIE' => 'technology innovation',
            'JURIDIQUE' => 'law justice legal',
            'ARTS' => 'arts creative',
            'COMMUNICATION' => 'communication media',
            'MARKETING' => 'marketing business',
            'RESSOURCES_HUMAINES' => 'human resources people',
            'TRANSPORT' => 'transportation logistics',
            'LOGISTIQUE' => 'logistics supply chain',
            'HOTELLERIE' => 'hospitality hotel',
            'RESTAURATION' => 'restaurant culinary',
            'SERVICES_PUBLICS' => 'public service government',
            'ENTREPRENEURIAT' => 'entrepreneurship business',
            'RECHERCHE' => 'research science',
        ];

        // Vérifier le mapping direct par code
        if (isset($searchTermsMap[$code])) {
            return $searchTermsMap[$code];
        }

        // Générer un terme de recherche à partir du titre (en anglais si possible)
        $titreLower = mb_strtolower($titre);
        
        // Mapping des mots français vers anglais pour les titres
        $translationMap = [
            'technologie' => 'technology',
            'éducation' => 'education',
            'juridique' => 'law',
            'arts' => 'arts',
            'créatif' => 'creative',
            'communication' => 'communication',
            'médias' => 'media',
            'marketing' => 'marketing',
            'ressources' => 'resources',
            'humaines' => 'human',
            'transport' => 'transportation',
            'logistique' => 'logistics',
            'hôtellerie' => 'hospitality',
            'restauration' => 'restaurant',
            'services' => 'services',
            'publics' => 'public',
            'entrepreneuriat' => 'entrepreneurship',
            'recherche' => 'research',
            'finance' => 'finance',
        ];

        // Extraire les mots principaux du titre (ignorer "et", "de", "la", etc.)
        $stopWords = ['et', 'de', 'la', 'le', 'les', 'des', 'du', 'en', 'pour', 'avec', 'dans'];
        $words = preg_split('/[\s-]+/', $titreLower);
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });
        
        // Prendre les 2-3 premiers mots significatifs
        $words = array_slice(array_values($words), 0, 3);
        
        // Traduire si nécessaire
        $translatedWords = array_map(function($word) use ($translationMap) {
            // Nettoyer le mot (enlever accents pour correspondance)
            $cleanWord = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $word);
            $cleanWord = strtolower($cleanWord);
            
            foreach ($translationMap as $fr => $en) {
                if (strpos($cleanWord, iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fr)) !== false) {
                    return $en;
                }
            }
            
            // Si pas de traduction, retourner le mot original
            return $word;
        }, $words);

        return implode(' ', $translatedWords) ?: 'professional';
    }

    private function searchImageOnUnsplash(string $query, SymfonyStyle $io): ?string
    {
        if (empty($this->unsplashAccessKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::UNSPLASH_API_URL, [
                'query' => [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'landscape',
                    'content_filter' => 'high'
                ],
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->unsplashAccessKey
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            
            if (empty($data['results'])) {
                return null;
            }

            // Récupérer l'URL de l'image en taille régulière
            $photo = $data['results'][0];
            return $photo['urls']['regular'] ?? $photo['urls']['small'] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function searchImageOnPexels(string $query, SymfonyStyle $io): ?string
    {
        if (empty($this->pexelsApiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::PEXELS_API_URL, [
                'query' => [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'landscape'
                ],
                'headers' => [
                    'Authorization' => $this->pexelsApiKey
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            
            if (empty($data['photos'])) {
                return null;
            }

            // Récupérer l'URL de l'image en taille large
            $photo = $data['photos'][0];
            return $photo['src']['large'] ?? $photo['src']['medium'] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère une image depuis le mapping direct (sans clé API requise)
     * Utilise des URLs d'images Unsplash directes
     */
    private function getImageFromUnsplashSource(string $query, string $code): ?string
    {
        // Vérifier si on a une image mappée pour ce code
        if (isset(self::SECTEUR_IMAGES[$code])) {
            return self::SECTEUR_IMAGES[$code];
        }

        // Si le code contient certains mots-clés, essayer de trouver une correspondance
        $codeUpper = strtoupper($code);
        foreach (self::SECTEUR_IMAGES as $mappedCode => $imageUrl) {
            // Vérifier si le code contient des mots-clés similaires
            if (strpos($codeUpper, $mappedCode) !== false || strpos($mappedCode, $codeUpper) !== false) {
                return $imageUrl;
            }
        }

        // Mapping par mots-clés dans le titre/code
        $keywordMapping = [
            'technologie' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&auto=format',
            'education' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=600&fit=crop&auto=format',
            'juridique' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&h=600&fit=crop&auto=format',
            'droit' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&h=600&fit=crop&auto=format',
            'arts' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=600&fit=crop&auto=format',
            'creatif' => 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=600&fit=crop&auto=format',
            'communication' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8f2d1c6?w=800&h=600&fit=crop&auto=format',
            'medias' => 'https://images.unsplash.com/photo-1432888622747-4eb9a8f2d1c6?w=800&h=600&fit=crop&auto=format',
            'marketing' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&auto=format',
            'ressources' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?w=800&h=600&fit=crop&auto=format',
            'humaines' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?w=800&h=600&fit=crop&auto=format',
            'transport' => 'https://images.unsplash.com/photo-1570125909517-53cb21c89ff2?w=800&h=600&fit=crop&auto=format',
            'logistique' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&h=600&fit=crop&auto=format',
            'hotellerie' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop&auto=format',
            'restauration' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&auto=format',
            'services' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format',
            'publics' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format',
            'entrepreneuriat' => 'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=800&h=600&fit=crop&auto=format',
            'recherche' => 'https://images.unsplash.com/photo-1532619675605-1ede6c9ed2d7?w=800&h=600&fit=crop&auto=format',
            'finance' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&h=600&fit=crop&auto=format',
        ];

        // Chercher dans les mots-clés du query
        $queryLower = strtolower($query);
        foreach ($keywordMapping as $keyword => $imageUrl) {
            if (strpos($queryLower, $keyword) !== false) {
                return $imageUrl;
            }
        }

        // Image générique par défaut
        return 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format';
    }

    /**
     * Obtient une URL alternative pour un secteur en cas d'échec
     */
    private function getAlternativeImageUrl(string $code, string $query): ?string
    {
        // URLs alternatives pour les secteurs problématiques
        $alternativeUrls = [
            'PECHE_MARITIME' => [
                'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1591160690555-5debfba089f0?w=800&h=600&fit=crop&auto=format',
            ],
            'SHS' => [
                'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=600&fit=crop&auto=format',
            ],
            'TECHNOLOGIE' => [
                'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&h=600&fit=crop&auto=format',
            ],
            'EDUCATION' => [
                'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=600&fit=crop&auto=format',
            ],
            'JURIDIQUE' => [
                'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1589994965851-a8f479c573a9?w=800&h=600&fit=crop&auto=format',
            ],
            'ARTS' => [
                'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=800&h=600&fit=crop&auto=format',
            ],
            'COMMUNICATION' => [
                'https://images.unsplash.com/photo-1432888622747-4eb9a8f2d1c6?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&auto=format',
            ],
            'MARKETING' => [
                'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&h=600&fit=crop&auto=format',
            ],
            'RESSOURCES_HUMAINES' => [
                'https://images.unsplash.com/photo-1552664730-d307ca884978?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=800&h=600&fit=crop&auto=format',
            ],
            'TRANSPORT' => [
                'https://images.unsplash.com/photo-1570125909517-53cb21c89ff2?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&h=600&fit=crop&auto=format',
            ],
            'LOGISTIQUE' => [
                'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop&auto=format',
            ],
            'HOTELLERIE' => [
                'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=600&fit=crop&auto=format',
            ],
            'RESTAURATION' => [
                'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop&auto=format',
            ],
            'SERVICES_PUBLICS' => [
                'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=600&fit=crop&auto=format',
            ],
            'ENTREPRENEURIAT' => [
                'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1551434678-e076c223a692?w=800&h=600&fit=crop&auto=format',
            ],
            'RECHERCHE' => [
                'https://images.unsplash.com/photo-1532619675605-1ede6c9ed2d7?w=800&h=600&fit=crop&auto=format',
                'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&h=600&fit=crop&auto=format',
            ],
        ];

        if (isset($alternativeUrls[$code])) {
            // Retourner une URL aléatoire parmi les alternatives
            $urls = $alternativeUrls[$code];
            return $urls[array_rand($urls)];
        }

        // Essayer de trouver une correspondance par mot-clé dans le query
        $queryLower = strtolower($query);
        $codeUpper = strtoupper($code);
        
        // Chercher dans le mapping principal par code
        foreach (self::SECTEUR_IMAGES as $mappedCode => $imageUrl) {
            if (strpos($codeUpper, $mappedCode) !== false || strpos($mappedCode, $codeUpper) !== false) {
                return $imageUrl;
            }
        }

        // Image générique par défaut
        return 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=600&fit=crop&auto=format';
    }

    private function downloadAndSaveImage(string $imageUrl, Secteur $secteur): ?string
    {
        try {
            // Télécharger l'image avec timeout et gestion d'erreurs
            $response = $this->httpClient->request('GET', $imageUrl, [
                'timeout' => 30,
                'max_redirects' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return null;
            }

            $imageContent = $response->getContent();
            
            // Vérifier que le contenu n'est pas vide
            if (empty($imageContent)) {
                return null;
            }
            
            // Vérifier que c'est bien une image (magic bytes)
            $imageInfo = @getimagesizefromstring($imageContent);
            if ($imageInfo === false) {
                // Ce n'est pas une image valide
                return null;
            }
            
            // Calculer le hash MD5 du contenu pour détecter les doublons
            $imageHash = md5($imageContent);
            
            // Créer le dossier de destination s'il n'existe pas
            $uploadsDir = $this->projectDir . '/public/uploads/covers';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Vérifier si une image avec le même hash existe déjà
            // Chercher dans tous les fichiers du dossier covers
            $existingFiles = glob($uploadsDir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
            foreach ($existingFiles as $existingFile) {
                if (is_file($existingFile)) {
                    $existingContent = file_get_contents($existingFile);
                    if ($existingContent && md5($existingContent) === $imageHash) {
                        // Image identique trouvée, retourner le chemin existant
                        $relativePath = '/uploads/covers/' . basename($existingFile);
                        return $relativePath;
                    }
                }
            }
            
            // Déterminer l'extension depuis les informations de l'image
            $extension = 'jpg';
            $mimeType = $imageInfo['mime'] ?? '';
            if (strpos($mimeType, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($mimeType, 'webp') !== false) {
                $extension = 'webp';
            } elseif (strpos($mimeType, 'gif') !== false) {
                $extension = 'gif';
            } elseif (strpos($mimeType, 'jpeg') !== false || strpos($mimeType, 'jpg') !== false) {
                $extension = 'jpg';
            } else {
                // Essayer depuis le Content-Type de la réponse
                $contentType = $response->getHeaders()['content-type'][0] ?? '';
                if (strpos($contentType, 'png') !== false) {
                    $extension = 'png';
                } elseif (strpos($contentType, 'webp') !== false) {
                    $extension = 'webp';
                } elseif (strpos($contentType, 'gif') !== false) {
                    $extension = 'gif';
                }
            }

            // Créer le nom de fichier basé sur le hash pour faciliter la détection des doublons
            // Utiliser les 12 premiers caractères du hash pour un nom court mais unique
            $filename = sprintf('%s.%s', substr($imageHash, 0, 12), $extension);

            // Sauvegarder l'image
            $filePath = $uploadsDir . '/' . $filename;
            $bytesWritten = file_put_contents($filePath, $imageContent);
            
            if ($bytesWritten === false || $bytesWritten === 0) {
                return null;
            }

            // Retourner le chemin relatif
            return '/uploads/covers/' . $filename;

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            // Erreur de transport (timeout, connexion, etc.)
            return null;
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            // Erreur HTTP (4xx, 5xx)
            return null;
        } catch (\Exception $e) {
            // Autres erreurs
            return null;
        }
    }
}
