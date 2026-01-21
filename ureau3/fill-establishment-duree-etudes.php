<?php

/**
 * Script pour remplir automatiquement les champs de durée d'études (dureeEtudesMin, dureeEtudesMax)
 * pour tous les établissements en se basant sur leurs filières associées.
 * 
 * Usage: php bin/fill-establishment-duree-etudes.php "mysql://root@127.0.0.1:3306/etawjihi_updated"
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
    echo "Usage: php bin/fill-establishment-duree-etudes.php [DATABASE_URL]\n";
    echo "Exemple: php bin/fill-establishment-duree-etudes.php \"mysql://root@127.0.0.1:3306/etawjihi_updated\"\n";
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

/**
 * Extrait le nombre d'années depuis une chaîne de caractères
 * Exemples: "5 ans" -> 5, "3" -> 3, "2-4 ans" -> 4 (max)
 */
function extractAnneeFromString($str) {
    if (empty($str) || $str === null) {
        return null;
    }
    
    // Si c'est déjà un nombre
    if (is_numeric($str)) {
        return (int)$str;
    }
    
    // Chercher des patterns comme "5 ans", "3-5 ans", "2 à 4 ans"
    $patterns = [
        '/(\d+)\s*-\s*(\d+)/',  // "3-5 ans" ou "3 - 5 ans"
        '/(\d+)\s*à\s*(\d+)/',   // "2 à 4 ans"
        '/(\d+)\s*ans?/',        // "5 ans" ou "5 an"
        '/(\d+)/',               // Juste un nombre
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $str, $matches)) {
            if (count($matches) >= 3) {
                // Plage de valeurs, retourner le max
                return max((int)$matches[1], (int)$matches[2]);
            } else {
                return (int)$matches[1];
            }
        }
    }
    
    return null;
}

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
    
    // Détecter le nom de la table des établissements
    $establishmentTable = null;
    foreach (['establishments', 'etablissement', 'etablissements', 'ecoles'] as $possibleName) {
        if (in_array($possibleName, $tables)) {
            $establishmentTable = $possibleName;
            break;
        }
    }
    
    if (!$establishmentTable) {
        echo "❌ Erreur: Aucune table d'établissements trouvée.\n";
        exit(1);
    }
    
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
    
    echo "✅ Tables détectées: {$establishmentTable}, {$filiereTable}\n";
    
    // Détecter les colonnes de la table établissement
    $establishmentCols = $conn->executeQuery("SHOW COLUMNS FROM {$establishmentTable}")->fetchAllAssociative();
    $nomColumn = null;
    $sigleColumn = null;
    $dureeEtudesMinCol = null;
    $dureeEtudesMaxCol = null;
    $anneesEtudesCol = null;
    
    foreach ($establishmentCols as $col) {
        $field = strtolower($col['Field']);
        if (!$nomColumn && in_array($field, ['nom', 'name', 'name_fr', 'titre'])) {
            $nomColumn = $col['Field'];
        }
        if (!$sigleColumn && in_array($field, ['sigle', 'abbreviation', 'acronym'])) {
            $sigleColumn = $col['Field'];
        }
        if (in_array($field, ['duree_etudes_min', 'dureeetudesmin', 'duree_etudes_min'])) {
            $dureeEtudesMinCol = $col['Field'];
        }
        if (in_array($field, ['duree_etudes_max', 'dureeetudesmax', 'duree_etudes_max'])) {
            $dureeEtudesMaxCol = $col['Field'];
        }
        if (in_array($field, ['annees_etudes', 'anneesetudes', 'nb_annee_etude', 'nb_annees_etude'])) {
            $anneesEtudesCol = $col['Field'];
        }
    }
    
    // Créer les colonnes si elles n'existent pas
    if (!$dureeEtudesMinCol) {
        echo "📝 Création de la colonne 'dureeEtudesMin' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN dureeEtudesMin INT DEFAULT NULL");
        $dureeEtudesMinCol = 'dureeEtudesMin';
    }
    
    if (!$dureeEtudesMaxCol) {
        echo "📝 Création de la colonne 'dureeEtudesMax' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN dureeEtudesMax INT DEFAULT NULL");
        $dureeEtudesMaxCol = 'dureeEtudesMax';
    }
    
    // Détecter la colonne de clé étrangère dans filiere
    $filiereColumns = $conn->executeQuery("SHOW COLUMNS FROM {$filiereTable}")->fetchAllAssociative();
    $establishmentIdColumn = null;
    foreach (['establishment_id', 'etablissement_id', 'ecole_id', 'establishmentId', 'etablissementId'] as $possibleName) {
        foreach ($filiereColumns as $col) {
            if (strtolower($col['Field']) === strtolower($possibleName)) {
                $establishmentIdColumn = $col['Field'];
                break 2;
            }
        }
    }
    
    if (!$establishmentIdColumn) {
        $establishmentIdColumn = 'establishment_id';
    }
    
    // Détecter les colonnes de durée dans filiere
    $filiereDureeCols = [];
    foreach ($filiereColumns as $col) {
        $field = strtolower($col['Field']);
        if (in_array($field, ['nb_annees', 'nb_annee', 'nombre_annees', 'nombreannees', 'duree', 'duree_formation', 'dureeformation', 'nombreAnnees'])) {
            $filiereDureeCols[] = $col['Field'];
        }
    }
    
    if (empty($filiereDureeCols)) {
        echo "⚠️  Avertissement: Aucune colonne de durée trouvée dans {$filiereTable}.\n";
        echo "Colonnes disponibles: " . implode(', ', array_column($filiereColumns, 'Field')) . "\n";
    } else {
        echo "✅ Colonnes de durée détectées dans {$filiereTable}: " . implode(', ', $filiereDureeCols) . "\n";
    }
    
    // Récupérer tous les établissements
    echo "\n📊 Récupération des établissements...\n";
    $selectFields = "id";
    if ($nomColumn) {
        $selectFields .= ", {$nomColumn} as nom";
    } else {
        $selectFields .= ", CONCAT('Établissement ', id) as nom";
    }
    if ($sigleColumn) {
        $selectFields .= ", {$sigleColumn} as sigle";
    } else {
        $selectFields .= ", NULL as sigle";
    }
    
    $establishments = $conn->executeQuery("SELECT {$selectFields} FROM {$establishmentTable}")->fetchAllAssociative();
    $total = count($establishments);
    echo "✅ {$total} établissements trouvés.\n\n";
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($establishments as $establishment) {
        $establishmentId = $establishment['id'];
        $establishmentNom = $establishment['nom'] ?? 'Sans nom';
        $establishmentSigle = $establishment['sigle'] ?? '';
        
        echo "🏫 [{$establishmentId}] {$establishmentSigle} {$establishmentNom}...\n";
        
        try {
            // Construire la requête SELECT pour les filières avec toutes les colonnes de durée
            $selectFiliereFields = "id";
            $dureeFields = [];
            foreach ($filiereDureeCols as $col) {
                $selectFiliereFields .= ", {$col}";
                $dureeFields[] = $col;
            }
            
            // Récupérer toutes les filières de cet établissement
            $filieres = $conn->executeQuery(
                "SELECT {$selectFiliereFields} 
                 FROM {$filiereTable} 
                 WHERE {$establishmentIdColumn} = ?",
                [$establishmentId]
            )->fetchAllAssociative();
            
            if (empty($filieres)) {
                echo "   ⚠️  Aucune filière associée, passage au suivant.\n";
                $skipped++;
                continue;
            }
            
            echo "   📚 " . count($filieres) . " filière(s) trouvée(s).\n";
            
            // Extraire toutes les durées d'études
            $durees = [];
            
            foreach ($filieres as $filiere) {
                foreach ($dureeFields as $dureeField) {
                    $dureeValue = $filiere[$dureeField] ?? null;
                    if ($dureeValue !== null && $dureeValue !== '') {
                        $annee = extractAnneeFromString($dureeValue);
                        if ($annee !== null && $annee > 0) {
                            $durees[] = $annee;
                        }
                    }
                }
            }
            
            if (empty($durees)) {
                echo "   ⚠️  Aucune durée d'études trouvée dans les filières, passage au suivant.\n";
                $skipped++;
                continue;
            }
            
            // Calculer min et max
            $dureeMin = min($durees);
            $dureeMax = max($durees);
            
            // Mettre à jour l'établissement
            $updateSql = "UPDATE {$establishmentTable} SET ";
            $updateParams = [];
            $updateFields = [];
            
            if ($dureeEtudesMinCol) {
                $updateFields[] = "{$dureeEtudesMinCol} = ?";
                $updateParams[] = $dureeMin;
            }
            
            if ($dureeEtudesMaxCol) {
                $updateFields[] = "{$dureeEtudesMaxCol} = ?";
                $updateParams[] = $dureeMax;
            }
            
            if (!empty($updateFields)) {
                $updateSql .= implode(', ', $updateFields);
                $updateSql .= " WHERE id = ?";
                $updateParams[] = $establishmentId;
                
                $conn->executeStatement($updateSql, $updateParams);
                
                echo "   ✅ Mis à jour: dureeEtudesMin={$dureeMin}, dureeEtudesMax={$dureeMax} (trouvé " . count($durees) . " durée(s))\n";
                
                $updated++;
            } else {
                echo "   ⚠️  Aucune colonne de durée à mettre à jour.\n";
                $skipped++;
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Résumé:\n";
    echo "   ✅ Établissements mis à jour: {$updated}\n";
    echo "   ⚠️  Établissements ignorés: {$skipped}\n";
    echo "   ❌ Erreurs: {$errors}\n";
    echo "   📝 Total traité: {$total}\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
