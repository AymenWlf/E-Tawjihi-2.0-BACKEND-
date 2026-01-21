<?php

/**
 * Script pour remplir automatiquement les champs de baccalauréat (bacType, filieresAcceptees, combinaisonsBacMission)
 * pour tous les établissements en se basant sur leurs filières associées.
 * 
 * Usage: php bin/console app:fill-establishment-bac-data [--database-url="mysql://root@127.0.0.1:3306/etawjihi_updated"]
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

// Récupérer l'URL de la base de données depuis les arguments ou l'environnement
$databaseUrl = $argv[1] ?? $_ENV['DATABASE_URL'] ?? null;

if (!$databaseUrl) {
    echo "❌ Erreur: URL de base de données non fournie.\n";
    echo "Usage: php bin/fill-establishment-bac-data.php [DATABASE_URL]\n";
    echo "Exemple: php bin/fill-establishment-bac-data.php \"mysql://root@127.0.0.1:3306/etawjihi_updated\"\n";
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
    
    // Vérifier que les tables existent et détecter les noms de colonnes
    $tables = $conn->executeQuery("SHOW TABLES")->fetchFirstColumn();
    
    // Détecter le nom de la table des établissements
    $establishmentTable = null;
    foreach (['establishments', 'etablissements', 'ecoles'] as $possibleName) {
        if (in_array($possibleName, $tables)) {
            $establishmentTable = $possibleName;
            break;
        }
    }
    
    if (!$establishmentTable) {
        echo "❌ Erreur: Aucune table d'établissements trouvée (cherché: establishments, etablissements, ecoles).\n";
        echo "Tables disponibles: " . implode(', ', $tables) . "\n";
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
        echo "❌ Erreur: Aucune table de filières trouvée (cherché: filieres, filiere, programmes).\n";
        echo "Tables disponibles: " . implode(', ', $tables) . "\n";
        exit(1);
    }
    
    echo "✅ Tables détectées: {$establishmentTable}, {$filiereTable}\n";
    
    // Détecter le nom de la colonne de clé étrangère
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
        echo "⚠️  Avertissement: Colonne de clé étrangère vers établissement non trouvée automatiquement.\n";
        echo "Colonnes disponibles dans {$filiereTable}: " . implode(', ', array_column($filiereColumns, 'Field')) . "\n";
        echo "Tentative avec 'establishment_id'...\n";
        $establishmentIdColumn = 'establishment_id';
    }
    
    echo "✅ Colonne de clé étrangère: {$establishmentIdColumn}\n";
    
    // Vérifier si les colonnes existent, sinon les créer
    $establishmentColumns = $conn->executeQuery("SHOW COLUMNS FROM {$establishmentTable} LIKE 'bacType'")->fetchAllAssociative();
    if (empty($establishmentColumns)) {
        echo "📝 Création de la colonne 'bacType' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN bacType VARCHAR(20) DEFAULT NULL");
    }
    
    $establishmentColumns = $conn->executeQuery("SHOW COLUMNS FROM {$establishmentTable} LIKE 'filieresAcceptees'")->fetchAllAssociative();
    if (empty($establishmentColumns)) {
        echo "📝 Création de la colonne 'filieresAcceptees' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN filieresAcceptees JSON DEFAULT NULL");
    }
    
    $establishmentColumns = $conn->executeQuery("SHOW COLUMNS FROM {$establishmentTable} LIKE 'combinaisonsBacMission'")->fetchAllAssociative();
    if (empty($establishmentColumns)) {
        echo "📝 Création de la colonne 'combinaisonsBacMission' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN combinaisonsBacMission JSON DEFAULT NULL");
    }
    
    // Détecter les noms de colonnes pour nom et sigle
    $establishmentCols = $conn->executeQuery("SHOW COLUMNS FROM {$establishmentTable}")->fetchAllAssociative();
    $nomColumn = null;
    $sigleColumn = null;
    foreach ($establishmentCols as $col) {
        $field = strtolower($col['Field']);
        if (in_array($field, ['nom', 'name', 'name_fr', 'titre'])) {
            $nomColumn = $col['Field'];
        }
        if (in_array($field, ['sigle', 'abbreviation', 'acronym'])) {
            $sigleColumn = $col['Field'];
        }
    }
    
    if (!$nomColumn) {
        echo "❌ Erreur: Colonne 'nom' non trouvée dans {$establishmentTable}.\n";
        exit(1);
    }
    
    // Récupérer tous les établissements
    echo "\n📊 Récupération des établissements...\n";
    $selectFields = "id";
    if ($nomColumn) $selectFields .= ", {$nomColumn} as nom";
    if ($sigleColumn) $selectFields .= ", {$sigleColumn} as sigle";
    
    $establishments = $conn->executeQuery("SELECT {$selectFields} FROM {$establishmentTable}")->fetchAllAssociative();
    $total = count($establishments);
    echo "✅ {$total} établissements trouvés.\n\n";
    
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    
    // Liste des filières acceptées possibles (Bac Normal)
    $allFilieresAcceptees = [
        'Sciences Math A',
        'Sciences Math B',
        'Sciences Physique',
        'SVT',
        'Sciences économique',
        'Sciences gestion comptable',
        'Lettres',
        'Sciences humaines',
        'Arts Appliqués',
        'Sciences et technologies électriques',
        'Sciences et technologies mécaniques',
        'Sciences agronomiques',
        'Sciences de la chariaa'
    ];
    
    foreach ($establishments as $establishment) {
        $establishmentId = $establishment['id'];
        $establishmentNom = $establishment['nom'] ?? 'Sans nom';
        $establishmentSigle = $establishment['sigle'] ?? '';
        
        echo "🏫 [{$establishmentId}] {$establishmentSigle} {$establishmentNom}...\n";
        
        try {
            // Détecter les noms de colonnes pour les champs de baccalauréat dans la table filieres
            $filiereCols = $conn->executeQuery("SHOW COLUMNS FROM {$filiereTable}")->fetchAllAssociative();
            $bacTypeCol = null;
            $filieresAccepteesCol = null;
            $combinaisonsCol = null;
            
            foreach ($filiereCols as $col) {
                $field = strtolower($col['Field']);
                if (in_array($field, ['bac_type', 'bactype', 'bac_type'])) {
                    $bacTypeCol = $col['Field'];
                }
                if (in_array($field, ['filieres_acceptees', 'filieresacceptees', 'filieres_acceptees'])) {
                    $filieresAccepteesCol = $col['Field'];
                }
                if (in_array($field, ['combinaisons_bac_mission', 'combinaisonsbacmission', 'combinaisons_bac_mission'])) {
                    $combinaisonsCol = $col['Field'];
                }
            }
            
            // Construire la requête SELECT avec les colonnes détectées
            $selectFields = "id, nom";
            if ($bacTypeCol) $selectFields .= ", {$bacTypeCol} as bacType";
            if ($filieresAccepteesCol) $selectFields .= ", {$filieresAccepteesCol} as filieresAcceptees";
            if ($combinaisonsCol) $selectFields .= ", {$combinaisonsCol} as combinaisonsBacMission";
            
            // Récupérer toutes les filières de cet établissement
            $filieres = $conn->executeQuery(
                "SELECT {$selectFields} 
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
            
            // Agrégation des données de baccalauréat
            $allFilieresAccepteesFromFilieres = [];
            $allCombinaisonsBacMission = [];
            $hasBacNormal = false;
            $hasBacMission = false;
            
            foreach ($filieres as $filiere) {
                // Vérifier le bacType de la filière
                $filiereBacType = $filiere['bacType'] ?? null;
                
                if ($filiereBacType === 'normal' || $filiereBacType === 'both') {
                    $hasBacNormal = true;
                    // Récupérer les filières acceptées
                    $filieresAccepteesData = $filiere['filieresAcceptees'] ?? null;
                    if ($filieresAccepteesData) {
                        // Peut être JSON ou texte séparé par virgules
                        $filieresAcceptees = null;
                        if (is_string($filieresAccepteesData)) {
                            // Essayer de décoder comme JSON
                            $decoded = json_decode($filieresAccepteesData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $filieresAcceptees = $decoded;
                            } else {
                                // Essayer comme texte séparé par virgules
                                $filieresAcceptees = array_map('trim', explode(',', $filieresAccepteesData));
                                $filieresAcceptees = array_filter($filieresAcceptees);
                            }
                        } elseif (is_array($filieresAccepteesData)) {
                            $filieresAcceptees = $filieresAccepteesData;
                        }
                        
                        if (is_array($filieresAcceptees) && !empty($filieresAcceptees)) {
                            $allFilieresAccepteesFromFilieres = array_merge(
                                $allFilieresAccepteesFromFilieres,
                                $filieresAcceptees
                            );
                        }
                    }
                }
                
                if ($filiereBacType === 'mission' || $filiereBacType === 'both') {
                    $hasBacMission = true;
                    // Récupérer les combinaisons
                    $combinaisonsData = $filiere['combinaisonsBacMission'] ?? null;
                    if ($combinaisonsData) {
                        // Peut être JSON ou texte
                        $combinaisons = null;
                        if (is_string($combinaisonsData)) {
                            $decoded = json_decode($combinaisonsData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $combinaisons = $decoded;
                            }
                        } elseif (is_array($combinaisonsData)) {
                            $combinaisons = $combinaisonsData;
                        }
                        
                        if (is_array($combinaisons)) {
                            foreach ($combinaisons as $combinaison) {
                                if (is_array($combinaison) && count($combinaison) === 2) {
                                    // Vérifier si la combinaison n'existe pas déjà
                                    $exists = false;
                                    foreach ($allCombinaisonsBacMission as $existing) {
                                        if ($existing[0] === $combinaison[0] && $existing[1] === $combinaison[1]) {
                                            $exists = true;
                                            break;
                                        }
                                    }
                                    if (!$exists) {
                                        $allCombinaisonsBacMission[] = $combinaison;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Déterminer le bacType de l'établissement
            $establishmentBacType = null;
            if ($hasBacNormal && $hasBacMission) {
                $establishmentBacType = 'both';
            } elseif ($hasBacNormal) {
                $establishmentBacType = 'normal';
            } elseif ($hasBacMission) {
                $establishmentBacType = 'mission';
            }
            
            // Dédupliquer les filières acceptées
            $allFilieresAccepteesFromFilieres = array_unique($allFilieresAccepteesFromFilieres);
            $allFilieresAccepteesFromFilieres = array_values($allFilieresAccepteesFromFilieres);
            
            // Préparer les valeurs JSON
            $filieresAccepteesJson = !empty($allFilieresAccepteesFromFilieres) 
                ? json_encode($allFilieresAccepteesFromFilieres, JSON_UNESCAPED_UNICODE) 
                : null;
            $combinaisonsJson = !empty($allCombinaisonsBacMission) 
                ? json_encode($allCombinaisonsBacMission, JSON_UNESCAPED_UNICODE) 
                : null;
            
            // Mettre à jour l'établissement
            $updateSql = "UPDATE {$establishmentTable} SET 
                          bacType = ?,
                          filieresAcceptees = ?,
                          combinaisonsBacMission = ?
                          WHERE id = ?";
            
            $conn->executeStatement($updateSql, [
                $establishmentBacType,
                $filieresAccepteesJson,
                $combinaisonsJson,
                $establishmentId
            ]);
            
            echo "   ✅ Mis à jour: bacType={$establishmentBacType}, ";
            echo "filieresAcceptees=" . count($allFilieresAccepteesFromFilieres) . ", ";
            echo "combinaisonsBacMission=" . count($allCombinaisonsBacMission) . "\n";
            
            $updated++;
            
        } catch (\Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Résumé:\n";
    echo "   ✅ Établissements mis à jour: {$updated}\n";
    echo "   ⚠️  Établissements ignorés (pas de filières): {$skipped}\n";
    echo "   ❌ Erreurs: {$errors}\n";
    echo "   📝 Total traité: {$total}\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
