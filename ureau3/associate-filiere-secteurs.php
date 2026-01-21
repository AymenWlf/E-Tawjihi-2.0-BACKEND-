<?php

/**
 * Script pour associer automatiquement les secteurs de métiers aux filières
 * en analysant leurs noms.
 * 
 * Usage: php bin/associate-filiere-secteurs.php "mysql://root@127.0.0.1:3306/etawjihi_updated"
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

// Récupérer l'URL de la base de données depuis les arguments ou l'environnement
$databaseUrl = $argv[1] ?? $_ENV['DATABASE_URL'] ?? null;

if (!$databaseUrl) {
    echo "❌ Erreur: URL de base de données non fournie.\n";
    echo "Usage: php bin/associate-filiere-secteurs.php [DATABASE_URL]\n";
    echo "Exemple: php bin/associate-filiere-secteurs.php \"mysql://root@127.0.0.1:3306/etawjihi_updated\"\n";
    exit(1);
}

// Parser l'URL de la base de données
preg_match('/mysql:\/\/([^:]+):?([^@]*)@([^:]+):(\d+)\/([^?]+)/', $databaseUrl, $matches);
if (count($matches) < 6) {
    echo "❌ Erreur: URL de base de données invalide.\n";
    exit(1);
}

$username = $matches[1];
$password = $matches[2] ?? '';
$host = $matches[3];
$port = $matches[4];
$dbname = $matches[5];

echo "🔌 Connexion à la base de données: {$host}:{$port}/{$dbname}\n";

// Mapping des mots-clés vers les secteurs (même mapping que pour les établissements)
$secteurKeywords = [
    // Achats & Approvisionnement (29)
    'achat' => [29], 'approvisionnement' => [29], 'procurement' => [29], 'supply' => [29],
    'négoce' => [29, 34], 'negoce' => [29, 34],
    
    // Art & Design (30)
    'art' => [30], 'design' => [30], 'beaux-arts' => [30], 'beaux arts' => [30], 'arts plastiques' => [30],
    'architecture' => [30, 33], 'architecte' => [30, 33], 'urbanisme' => [30, 33], 'urbaniste' => [30, 33],
    'dramatique' => [30], 'théâtre' => [30], 'cinéma' => [30], 'audiovisuel' => [30],
    
    // Banque & Assurance (31)
    'banque' => [31], 'banking' => [31], 'finance' => [31, 35], 'assurance' => [31],
    'bancaire' => [31], 'financier' => [31],
    
    // Biologie & Agroalimentaire (32)
    'biologie' => [32], 'biologique' => [32], 'agroalimentaire' => [32], 'agronomie' => [32],
    'agronomique' => [32], 'vétérinaire' => [32], 'vétérinaires' => [32], 'veterinary' => [32],
    'agriculture' => [32], 'agricole' => [32], 'agronome' => [32],
    
    // BTP et Construction (33)
    'btp' => [33], 'construction' => [33], 'travaux publics' => [33], 'génie civil' => [33],
    'civil' => [33], 'bâtiment' => [33], 'immobilier' => [33],
    
    // Commerce & Vente (34)
    'commerce' => [34], 'commercial' => [34], 'vente' => [34], 'sales' => [34],
    'trading' => [34], 'négoce' => [29, 34], 'negoce' => [29, 34],
    
    // Comptabilité, Audit & Finance (35)
    'comptabilité' => [35], 'comptable' => [35], 'audit' => [35], 'accounting' => [35],
    'finance' => [35, 31], 'financier' => [35, 31],
    
    // Droit & Justice (36)
    'droit' => [36], 'juridique' => [36], 'justice' => [36], 'law' => [36],
    'juriste' => [36], 'avocat' => [36],
    
    // Edition & Journalisme (37)
    'journalisme' => [37], 'journaliste' => [37], 'journalism' => [37], 'édition' => [37],
    'presse' => [37], 'média' => [37], 'media' => [37],
    
    // Enseignement (38)
    'enseignement' => [38], 'éducation' => [38], 'pédagogie' => [38], 'enseignant' => [38],
    'professeur' => [38], 'formation' => [38], 'normale' => [38],
    
    // Electrique (39)
    'électrique' => [39], 'électricité' => [39], 'électrotechnique' => [39], 'electrical' => [39],
    'électronique' => [39], 'télécommunication' => [39], 'télécom' => [39, 53], 'telecom' => [39, 53],
    
    // Energies renouvelables (40)
    'énergie' => [40], 'energie' => [40], 'renouvelable' => [40], 'renewable' => [40],
    'solaire' => [40], 'éolien' => [40], 'eolien' => [40],
    
    // Fonction Publique (41)
    'fonction publique' => [41], 'publique' => [41], 'public' => [41], 'administration' => [41],
    'administratif' => [41],
    
    // Gestion & Organisation (42)
    'gestion' => [42], 'management' => [42], 'organisation' => [42], 'administration' => [42],
    'business' => [42], 'entreprise' => [42], 'entrepreneuriat' => [42], 'entrepreneur' => [42],
    
    // Hôtellerie & Tourisme (43)
    'hôtellerie' => [43], 'hotellerie' => [43], 'tourisme' => [43], 'hospitality' => [43],
    'tourism' => [43], 'restauration' => [43], 'hôtel' => [43], 'hotel' => [43],
    
    // Informatique & Système d'information (44)
    'informatique' => [44], 'computer' => [44], 'software' => [44], 'programmation' => [44],
    'développement' => [44], 'développeur' => [44], 'digital' => [44], 'numérique' => [44],
    'technologie' => [44], 'tech' => [44], 'it' => [44], 'intelligence artificielle' => [44],
    'ia' => [44], 'ai' => [44], 'data' => [44], 'système d\'information' => [44],
    'system information' => [44], 'si' => [44],
    
    // Intelligence économique (45)
    'intelligence économique' => [45], 'intelligence economique' => [45], 'economic intelligence' => [45],
    'ingénierie économiques' => [45], 'ingenierie economiques' => [45],
    
    // L'humanitaire (46)
    'humanitaire' => [46], 'humanitarian' => [46], 'social' => [46], 'société' => [46],
    
    // Marketing & Communication (47)
    'marketing' => [47], 'communication' => [47], 'publicité' => [47], 'publicite' => [47],
    'advertising' => [47], 'branding' => [47], 'public relations' => [47],
    
    // Mécanique (48)
    'mécanique' => [48], 'mecanique' => [48], 'mechanical' => [48],
    
    // Mode (49)
    'mode' => [49], 'fashion' => [49], 'stylisme' => [49], 'textile' => [49], 'couture' => [49],
    
    // Production & Industrialisation (50)
    'production' => [50], 'industrialisation' => [50], 'industrie' => [50], 'industriel' => [50],
    'manufacturing' => [50], 'usine' => [50],
    
    // Qualité & Sécurité (51)
    'qualité' => [51], 'qualite' => [51], 'sécurité' => [51], 'securite' => [51],
    'quality' => [51], 'safety' => [51],
    
    // Recherche & Développement (52)
    'recherche' => [52], 'développement' => [52, 44], 'r&d' => [52], 'rd' => [52],
    'research' => [52], 'development' => [52, 44],
    
    // Réseau & Télécom (53)
    'réseau' => [53], 'reseau' => [53], 'télécom' => [53, 39], 'telecom' => [53, 39],
    'network' => [53], 'telecommunication' => [53], 'télécommunication' => [53],
    
    // Ressources Humaines (54)
    'ressources humaines' => [54], 'rh' => [54], 'hr' => [54], 'human resources' => [54],
    'personnel' => [54], 'recrutement' => [54],
    
    // Sciences de la Santé (55)
    'santé' => [55], 'sante' => [55], 'health' => [55], 'médecine' => [55], 'medecine' => [55],
    'medicine' => [55], 'pharmacie' => [55], 'pharmacien' => [55], 'pharmacy' => [55],
    'dentaire' => [55], 'dentiste' => [55], 'dentistry' => [55], 'paramédical' => [55],
    'paramedical' => [55], 'infirmier' => [55], 'infirmière' => [55], 'nurse' => [55],
    'soins' => [55], 'médical' => [55], 'medical' => [55], 'santé publique' => [55],
    'sante publique' => [55],
    
    // Sport (56)
    'sport' => [56], 'sportif' => [56], 'sports' => [56], 'athlétisme' => [56],
    'athlete' => [56], 'coaching' => [56],
    
    // Pêche maritime (ID à mettre à jour après exécution de app:add-new-secteurs)
    // Exemple: 'pêche' => [ID_DU_SECTEUR], une fois l'ID connu
    // 'pêche' => [], 'peche' => [], 'pêche maritime' => [], 'peche maritime' => [],
    // 'halieutique' => [], 'aquaculture' => [], 'fishery' => [], 'fishing' => [],
    
    // Génie (général)
    'ingénieur' => [39, 44, 48, 53], 'ingénierie' => [39, 44, 48, 53], 'engineering' => [39, 44, 48, 53],
    'génie' => [39, 44, 48, 53], 'genie' => [39, 44, 48, 53],
    
    // Affaires internationales
    'affaires internationales' => [42, 45], 'affaires internationales' => [42, 45],
    'international' => [42, 45], 'internationales' => [42, 45],
];

try {
    // Créer la connexion
    $connectionParams = [
        'driver' => 'pdo_mysql',
        'host' => $host,
        'port' => $port,
        'user' => $username,
        'password' => $password,
        'dbname' => $dbname,
        'charset' => 'utf8mb4',
    ];
    
    $conn = DriverManager::getConnection($connectionParams);
    
    // Détecter la structure de la base de données
    $tables = $conn->executeQuery("SHOW TABLES")->fetchFirstColumn();
    
    // Détecter le nom de la table des filières
    $filiereTable = null;
    foreach (['filieres', 'filiere', 'programmes'] as $possibleName) {
        if (in_array($possibleName, $tables)) {
            $filiereTable = $possibleName;
            break;
        }
    }
    
    if (!$filiereTable) {
        echo "❌ Erreur: Aucune table de filières trouvée.\n";
        exit(1);
    }
    
    // Détecter le nom de la table des secteurs
    $secteurTable = null;
    foreach (['secteur', 'secteurs', 'sector', 'sectors'] as $possibleName) {
        if (in_array($possibleName, $tables)) {
            $secteurTable = $possibleName;
            break;
        }
    }
    
    if (!$secteurTable) {
        echo "❌ Erreur: Aucune table de secteurs trouvée.\n";
        exit(1);
    }
    
    echo "✅ Tables détectées: {$filiereTable}, {$secteurTable}\n";
    
    // Récupérer tous les secteurs
    $secteurs = $conn->executeQuery("SELECT id, titre FROM {$secteurTable}")->fetchAllAssociative();
    $secteursMap = [];
    foreach ($secteurs as $secteur) {
        $secteursMap[$secteur['id']] = $secteur['titre'];
    }
    
    echo "✅ " . count($secteurs) . " secteurs trouvés.\n";
    
    // Détecter les colonnes de la table filiere
    $filiereCols = $conn->executeQuery("SHOW COLUMNS FROM {$filiereTable}")->fetchAllAssociative();
    $nomColumn = null;
    $secteursIdsCol = null;
    
    foreach ($filiereCols as $col) {
        $field = strtolower($col['Field']);
        if (!$nomColumn && in_array($field, ['nom', 'name', 'name_fr', 'titre'])) {
            $nomColumn = $col['Field'];
        }
        if (in_array($field, ['secteurs_ids', 'secteursids', 'secteur_ids', 'secteurids'])) {
            $secteursIdsCol = $col['Field'];
        }
    }
    
    // Créer la colonne si elle n'existe pas
    if (!$secteursIdsCol) {
        echo "📝 Création de la colonne 'secteursIds' dans la table '{$filiereTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$filiereTable} ADD COLUMN secteursIds JSON DEFAULT NULL");
        $secteursIdsCol = 'secteursIds';
    }
    
    // Récupérer toutes les filières
    echo "\n📊 Récupération des filières...\n";
    $selectFields = "id";
    if ($nomColumn) {
        $selectFields .= ", {$nomColumn} as nom";
    } else {
        $selectFields .= ", CONCAT('Filière ', id) as nom";
    }
    
    $filieres = $conn->executeQuery("SELECT {$selectFields} FROM {$filiereTable}")->fetchAllAssociative();
    $total = count($filieres);
    echo "✅ {$total} filières trouvées.\n\n";
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($filieres as $filiere) {
        $filiereId = $filiere['id'];
        $filiereNom = $filiere['nom'] ?? 'Sans nom';
        
        echo "📚 [{$filiereId}] {$filiereNom}...\n";
        
        try {
            // Normaliser le texte pour la recherche
            $originalText = strtolower($filiereNom);
            $textToSearchNormalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $originalText);
            $textToSearchNormalized = preg_replace('/[^a-z0-9\s]/', ' ', $textToSearchNormalized);
            $textToSearchNormalized = preg_replace('/\s+/', ' ', $textToSearchNormalized);
            
            // Chercher les mots-clés correspondants
            $foundSecteurs = [];
            
            // Mots-clés à exclure pour éviter les faux positifs
            $excludePatterns = [
                // Exclure "mode" si on trouve "médecine", etc.
                '/médecine|medical|pharmacie|pharmacy|dentaire|dentistry|santé|health/' => [49],
            ];
            
            // Mots-clés qui nécessitent un contexte technique pour "informatique"
            $techContextKeywords = ['informatique', 'computer', 'software', 'programmation', 'développement', 'digital', 'numérique', 'tech', 'it', 'ia', 'ai', 'data', 'système d\'information'];
            
            foreach ($secteurKeywords as $keyword => $secteurIds) {
                // Recherche dans le texte normalisé (sans accents)
                $foundInNormalized = preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $textToSearchNormalized);
                
                // Recherche dans le texte original (avec accents)
                $foundInOriginal = stripos($originalText, $keyword) !== false;
                
                if ($foundInNormalized || $foundInOriginal) {
                    // Vérifier les exclusions
                    $shouldExclude = false;
                    foreach ($excludePatterns as $pattern => $excludedSecteurs) {
                        if (preg_match($pattern, $originalText)) {
                            foreach ($secteurIds as $secteurId) {
                                if (in_array($secteurId, $excludedSecteurs)) {
                                    $shouldExclude = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    // Pour "informatique", vérifier qu'il y a un contexte technique
                    if (!$shouldExclude && in_array(44, $secteurIds) && $keyword === 'informatique') {
                        $hasTechContext = false;
                        foreach ($techContextKeywords as $techKeyword) {
                            if ($techKeyword !== 'informatique' && stripos($originalText, $techKeyword) !== false) {
                                $hasTechContext = true;
                                break;
                            }
                        }
                        // Si on trouve juste "formation" ou "information" sans contexte tech, exclure
                        if (!$hasTechContext && (stripos($originalText, 'formation') !== false || stripos($originalText, 'information') !== false)) {
                            $shouldExclude = true;
                        }
                    }
                    
                    if (!$shouldExclude) {
                        foreach ($secteurIds as $secteurId) {
                            if (!in_array($secteurId, $foundSecteurs)) {
                                $foundSecteurs[] = $secteurId;
                            }
                        }
                    }
                }
            }
            
            if (empty($foundSecteurs)) {
                echo "   ⚠️  Aucun secteur trouvé, passage au suivant.\n";
                $skipped++;
                continue;
            }
            
            // Trier les secteurs par ID
            sort($foundSecteurs);
            
            // Préparer la valeur JSON
            $secteursIdsJson = json_encode($foundSecteurs, JSON_UNESCAPED_UNICODE);
            
            // Mettre à jour la filière
            $updateSql = "UPDATE {$filiereTable} SET {$secteursIdsCol} = ? WHERE id = ?";
            $conn->executeStatement($updateSql, [$secteursIdsJson, $filiereId]);
            
            $secteursNoms = array_map(function($id) use ($secteursMap) {
                return $secteursMap[$id] ?? "Secteur {$id}";
            }, $foundSecteurs);
            
            echo "   ✅ Mis à jour: " . count($foundSecteurs) . " secteur(s) - " . implode(', ', $secteursNoms) . "\n";
            
            $updated++;
            
        } catch (\Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Résumé:\n";
    echo "   ✅ Filières mises à jour: {$updated}\n";
    echo "   ⚠️  Filières ignorées (aucun secteur trouvé): {$skipped}\n";
    echo "   ❌ Erreurs: {$errors}\n";
    echo "   📝 Total traité: {$total}\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
