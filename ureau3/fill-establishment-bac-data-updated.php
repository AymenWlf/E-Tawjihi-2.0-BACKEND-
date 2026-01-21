<?php

/**
 * Script pour remplir automatiquement les champs de baccalauréat pour tous les établissements
 * Compatible avec les bases de données etawjihi_new et etawjihi_updated
 * 
 * Usage: php bin/fill-establishment-bac-data-updated.php "mysql://root@127.0.0.1:3306/etawjihi_updated"
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
    echo "Usage: php bin/fill-establishment-bac-data-updated.php [DATABASE_URL]\n";
    echo "Exemple: php bin/fill-establishment-bac-data-updated.php \"mysql://root@127.0.0.1:3306/etawjihi_updated\"\n";
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
        echo "Tables disponibles: " . implode(', ', array_slice($tables, 0, 20)) . "...\n";
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
    $bacTypeCol = null;
    $filieresAccepteesCol = null;
    $combinaisonsCol = null;
    $isBacMissionCol = null;
    
    foreach ($establishmentCols as $col) {
        $field = strtolower($col['Field']);
        if (!$nomColumn && in_array($field, ['nom', 'name', 'name_fr', 'titre'])) {
            $nomColumn = $col['Field'];
        }
        if (!$sigleColumn && in_array($field, ['sigle', 'abbreviation', 'acronym'])) {
            $sigleColumn = $col['Field'];
        }
        if (in_array($field, ['bac_type', 'bactype'])) {
            $bacTypeCol = $col['Field'];
        }
        if (in_array($field, ['filieres_acceptees', 'filieresacceptees'])) {
            $filieresAccepteesCol = $col['Field'];
        }
        if (in_array($field, ['combinaisons_bac_mission', 'combinaisonsbacmission'])) {
            $combinaisonsCol = $col['Field'];
        }
        if (in_array($field, ['is_bac_mission_accepte', 'isbacmissionaccepte'])) {
            $isBacMissionCol = $col['Field'];
        }
    }
    
    // Créer les colonnes si elles n'existent pas (pour etawjihi_new)
    if (!$bacTypeCol) {
        echo "📝 Création de la colonne 'bacType' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN bacType VARCHAR(20) DEFAULT NULL");
        $bacTypeCol = 'bacType';
    }
    
    if (!$filieresAccepteesCol) {
        echo "📝 Création de la colonne 'filieresAcceptees' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN filieresAcceptees JSON DEFAULT NULL");
        $filieresAccepteesCol = 'filieresAcceptees';
    }
    
    if (!$combinaisonsCol) {
        echo "📝 Création de la colonne 'combinaisonsBacMission' dans la table '{$establishmentTable}'...\n";
        $conn->executeStatement("ALTER TABLE {$establishmentTable} ADD COLUMN combinaisonsBacMission JSON DEFAULT NULL");
        $combinaisonsCol = 'combinaisonsBacMission';
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
        echo "⚠️  Avertissement: Colonne de clé étrangère vers établissement non trouvée.\n";
        $establishmentIdColumn = 'establishment_id';
    }
    
    // Détecter les colonnes de bac dans filiere
    $filiereBacTypeCol = null;
    $filiereFilieresAccepteesCol = null;
    $filiereCombinaisonsCol = null;
    
    foreach ($filiereColumns as $col) {
        $field = strtolower($col['Field']);
        if (in_array($field, ['bac_type', 'bactype'])) {
            $filiereBacTypeCol = $col['Field'];
        }
        if (in_array($field, ['filieres_acceptees', 'filieresacceptees'])) {
            $filiereFilieresAccepteesCol = $col['Field'];
        }
        if (in_array($field, ['combinaisons_bac_mission', 'combinaisonsbacmission'])) {
            $filiereCombinaisonsCol = $col['Field'];
        }
    }
    
    // Vérifier si on a une table type_bac (pour etawjihi_updated)
    $hasTypeBacTable = in_array('type_bac', $tables);
    $hasRelationFiliereOption = in_array('relation_filiere_option', $tables);
    
    // Récupérer tous les établissements
    echo "\n📊 Récupération des établissements...\n";
    $selectFields = "id";
    if ($nomColumn) {
        $selectFields .= ", {$nomColumn} as nom";
    } else {
        // Si pas de colonne nom trouvée, utiliser id comme fallback
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
            // Détecter le nom de colonne pour le nom de la filière
            $filiereNomCol = null;
            foreach ($filiereColumns as $col) {
                $field = strtolower($col['Field']);
                if (in_array($field, ['nom', 'name', 'name_fr', 'titre'])) {
                    $filiereNomCol = $col['Field'];
                    break;
                }
            }
            if (!$filiereNomCol) {
                $filiereNomCol = 'id'; // Fallback
            }
            
            // Récupérer toutes les filières de cet établissement
            $selectFiliereFields = "id, {$filiereNomCol} as nom";
            if ($filiereBacTypeCol) $selectFiliereFields .= ", {$filiereBacTypeCol} as bacType";
            if ($filiereFilieresAccepteesCol) $selectFiliereFields .= ", {$filiereFilieresAccepteesCol} as filieresAcceptees";
            if ($filiereCombinaisonsCol) $selectFiliereFields .= ", {$filiereCombinaisonsCol} as combinaisonsBacMission";
            
            $filieres = $conn->executeQuery(
                "SELECT {$selectFiliereFields} 
                 FROM {$filiereTable} 
                 WHERE {$establishmentIdColumn} = ?",
                [$establishmentId]
            )->fetchAllAssociative();
            
            // Si on a la table relation_filiere_option, récupérer aussi les bacs depuis là
            if ($hasRelationFiliereOption && $hasTypeBacTable) {
                $filieresIds = array_column($filieres, 'id');
                if (!empty($filieresIds)) {
                    $placeholders = implode(',', array_fill(0, count($filieresIds), '?'));
                    $bacOptions = $conn->executeQuery(
                        "SELECT DISTINCT tb.titre 
                         FROM relation_filiere_option rfo 
                         JOIN type_bac tb ON rfo.optionn_id = tb.id 
                         WHERE rfo.filiere_id IN ({$placeholders})",
                        $filieresIds
                    )->fetchFirstColumn();
                    
                    if (!empty($bacOptions)) {
                        // Si on a des options de bac, on considère que c'est du bac normal
                        foreach ($bacOptions as $bacOption) {
                            if (!in_array($bacOption, $allFilieresAccepteesFromFilieres)) {
                                $allFilieresAccepteesFromFilieres[] = $bacOption;
                            }
                        }
                    }
                }
            }
            
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
                        $filieresAcceptees = null;
                        if (is_string($filieresAccepteesData)) {
                            $decoded = json_decode($filieresAccepteesData, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $filieresAcceptees = $decoded;
                            } else {
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
                                // Gérer les deux formats : [specialite1, specialite2] ou {specialite1, specialite2}
                                $specialite1 = null;
                                $specialite2 = null;
                                
                                if (is_array($combinaison)) {
                                    if (isset($combinaison['specialite1']) && isset($combinaison['specialite2'])) {
                                        // Format {specialite1, specialite2}
                                        $specialite1 = $combinaison['specialite1'];
                                        $specialite2 = $combinaison['specialite2'];
                                    } elseif (count($combinaison) >= 2) {
                                        // Format [specialite1, specialite2]
                                        $specialite1 = $combinaison[0];
                                        $specialite2 = $combinaison[1];
                                    }
                                }
                                
                                if ($specialite1 && $specialite2) {
                                    // Vérifier si la combinaison n'existe pas déjà
                                    $exists = false;
                                    foreach ($allCombinaisonsBacMission as $existing) {
                                        if (is_array($existing) && count($existing) >= 2) {
                                            if ($existing[0] === $specialite1 && $existing[1] === $specialite2) {
                                                $exists = true;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$exists) {
                                        $allCombinaisonsBacMission[] = [$specialite1, $specialite2];
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
            
            // Si on a is_bac_mission_accepte, l'utiliser aussi
            if ($isBacMissionCol && !$hasBacMission) {
                $isBacMission = $conn->executeQuery(
                    "SELECT {$isBacMissionCol} FROM {$establishmentTable} WHERE id = ?",
                    [$establishmentId]
                )->fetchOne();
                
                if ($isBacMission) {
                    $hasBacMission = true;
                    if ($establishmentBacType === 'normal') {
                        $establishmentBacType = 'both';
                    } elseif (!$establishmentBacType) {
                        $establishmentBacType = 'mission';
                    }
                }
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
            $updateSql = "UPDATE {$establishmentTable} SET ";
            $updateParams = [];
            $updateFields = [];
            
            if ($bacTypeCol) {
                $updateFields[] = "{$bacTypeCol} = ?";
                $updateParams[] = $establishmentBacType;
            }
            
            if ($filieresAccepteesCol) {
                $updateFields[] = "{$filieresAccepteesCol} = ?";
                $updateParams[] = $filieresAccepteesJson;
            }
            
            if ($combinaisonsCol) {
                $updateFields[] = "{$combinaisonsCol} = ?";
                $updateParams[] = $combinaisonsJson;
            }
            
            if ($isBacMissionCol && $hasBacMission) {
                $updateFields[] = "{$isBacMissionCol} = ?";
                $updateParams[] = 1;
            }
            
            if (!empty($updateFields)) {
                $updateSql .= implode(', ', $updateFields);
                $updateSql .= " WHERE id = ?";
                $updateParams[] = $establishmentId;
                
                $conn->executeStatement($updateSql, $updateParams);
                
                echo "   ✅ Mis à jour: bacType={$establishmentBacType}, ";
                echo "filieresAcceptees=" . count($allFilieresAccepteesFromFilieres) . ", ";
                echo "combinaisonsBacMission=" . count($allCombinaisonsBacMission) . "\n";
                
                $updated++;
            } else {
                echo "   ⚠️  Aucune donnée à mettre à jour.\n";
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
