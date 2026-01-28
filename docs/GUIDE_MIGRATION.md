# Guide de Migration - Etablissement, Filiere, Universite

## 📋 Vue d'ensemble

Ce système permet de migrer les données depuis l'ancien système e-tawjihi vers le nouveau système, en incluant :
- ✅ Mapping complet des attributs
- ✅ Migration des fichiers (logos, documents, photos)
- ✅ Préservation des relations (Campus, Filiere ↔ Establishment)
- ✅ Transformation automatique des types de données
- ✅ Génération automatique des slugs
- ✅ Mode dry-run pour tester sans écrire

## 🚀 Utilisation

### 1. Préparation des données

#### Option A : Depuis un fichier JSON (Recommandé)

Créez un fichier JSON avec la structure suivante :

```bash
# Exemple de structure
{
  "establishments": [...],
  "filieres": [...],
  "universites": [...]
}
```

Voir `EXEMPLE_MIGRATION_JSON.md` pour le format détaillé.

#### Option B : Depuis l'ancienne base de données

La migration depuis la base de données nécessite une configuration supplémentaire dans `MigrateDataCommand.php`.

### 2. Préparation des fichiers

Placez tous les fichiers (logos, images, documents) dans le répertoire :
```
public/old_uploads/
  ├── establishments/
  │   ├── logos/
  │   ├── covers/
  │   ├── documents/
  │   └── photos/
  ├── filieres/
  │   ├── covers/
  │   ├── documents/
  │   └── photos/
  └── universites/
      └── logos/
```

### 3. Exécution de la migration

#### Mode dry-run (test sans écriture)

```bash
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --dry-run
```

#### Migration complète

```bash
# Tous les types d'entités
php bin/console app:migrate-data --source-file=path/to/data.json

# Seulement les établissements
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --entity=establishment

# Seulement les filières
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --entity=filiere

# Seulement les universités
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --entity=universite
```

#### Migration avec pagination

```bash
# Migrer seulement 50 établissements
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --entity=establishment \
  --limit=50

# Migrer les 50 suivants (offset)
php bin/console app:migrate-data \
  --source-file=path/to/data.json \
  --entity=establishment \
  --limit=50 \
  --offset=50
```

## 📊 Mapping des Attributs

### Establishment

| Ancien Nom | Nouveau Nom | Type | Notes |
|------------|-------------|------|-------|
| `nom` | `nom` | string | Direct mapping |
| `nom_arabe` | `nomArabe` | string | Conversion camelCase |
| `logo` | `logo` | string | Fichier copié vers `/uploads/establishments/logo/` |
| `image_couverture` | `imageCouverture` | string | Fichier copié vers `/uploads/establishments/cover/` |
| `universite` | `universite` | string | **Chaîne, pas une FK** |
| `villes` | `villes` | JSON array | Conversion automatique |
| `documents` | `documents` | JSON array | Migration des fichiers |
| `photos` | `photos` | JSON array | Migration des fichiers |
| `secteurs_ids` | `secteursIds` | JSON array | IDs numériques |
| `filieres_ids` | `filieresIds` | JSON array | IDs numériques |

### Filiere

| Ancien Nom | Nouveau Nom | Type | Notes |
|------------|-------------|------|-------|
| `nom` / `titre` | `nom` | string | Direct mapping |
| `titre_arabe` | `nomArabe` | string | Conversion |
| `image_couverture` | `imageCouverture` | string | Fichier copié |
| `establishment_id` | `establishment` | FK | Relation ManyToOne |
| `campus_ids` | `campus` | ManyToMany | Via table pivot |
| `frais_annuels` | `fraisScolarite` | decimal | Conversion type |
| `duree` | `nombreAnnees` | string | Ex: "3 ans" |

### Universite

| Ancien Nom | Nouveau Nom | Type | Notes |
|------------|-------------|------|-------|
| `nom` | `nom` | string | Direct mapping |
| `logo` | `logo` | string | Fichier copié |

## 🔧 Transformation des Types

Le service de migration effectue automatiquement :

1. **Booléens** : Convertit `1`, `0`, `"true"`, `"false"` vers `true`/`false`
2. **Nombres** : Convertit les strings numériques vers int/float
3. **Décimales** : Formate avec 2 décimales (ex: `"35000.00"`)
4. **Dates** : Convertit les strings vers `DateTime`
5. **JSON** : Décode les strings JSON vers des arrays
6. **Arrays** : Normalise les structures de données

## 📁 Migration des Fichiers

Tous les fichiers sont copiés automatiquement :

- **Logos** : `public/old_uploads/...` → `public/uploads/{entity_type}/logo/{unique_name}`
- **Images couverture** : → `public/uploads/{entity_type}/cover/{unique_name}`
- **Documents** : → `public/uploads/{entity_type}/documents/{unique_name}`
- **Photos** : → `public/uploads/{entity_type}/photos/{unique_name}`

Les nouveaux chemins sont mis à jour dans les entités migrées.

## ⚠️ Points d'Attention

1. **Slugs** : Générés automatiquement si manquants. Les slugs existants sont préservés.

2. **Relations** :
   - **Filiere ↔ Establishment** : Doit avoir `establishment_id` ou `etablissement_id`
   - **Filiere ↔ Campus** : Doit avoir `campus_ids` (array)
   - **Campus ↔ City** : Doit avoir `city_id` ou chercher par nom de ville

3. **Université** : Le champ `universite` dans Establishment est une **chaîne**, pas une FK.

4. **Fichiers manquants** : Si un fichier source n'est pas trouvé, un avertissement est loggé mais la migration continue.

5. **Doublons** : Vérifiez les slugs avant la migration pour éviter les conflits.

## 🔍 Validation Post-Migration

Après la migration, vérifiez :

```bash
# Compter les établissements migrés
php bin/console dbal:run-sql "SELECT COUNT(*) FROM establishments"

# Compter les filières migrées
php bin/console dbal:run-sql "SELECT COUNT(*) FROM filieres"

# Vérifier les relations Campus
php bin/console dbal:run-sql "SELECT e.nom, COUNT(c.id) as nb_campus FROM establishments e LEFT JOIN campus c ON c.establishment_id = e.id GROUP BY e.id"

# Vérifier les fichiers migrés
ls -la public/uploads/establishments/logo/
ls -la public/uploads/filieres/photos/
```

## 🐛 Dépannage

### Erreur : "Fichier source non trouvé"

Vérifiez que les fichiers sont bien dans `public/old_uploads/` ou ajustez le chemin dans `MigrationService.php`.

### Erreur : "Slug already exists"

Le service génère automatiquement un slug unique en ajoutant un suffixe numérique.

### Erreur : "Establishment not found for Filiere"

Vérifiez que `establishment_id` ou `etablissement_id` existe dans les données JSON et correspond à un établissement déjà migré.

### Erreur : "City not found for Campus"

Vérifiez que `city_id` existe ou que la ville existe dans la base de données.

## 📝 Logs

Les logs de migration sont disponibles dans :
- Console (durant l'exécution)
- Fichiers de logs Symfony (`var/log/`)

## ✅ Checklist de Migration

- [ ] Préparer le fichier JSON avec les données
- [ ] Copier tous les fichiers dans `public/old_uploads/`
- [ ] Exécuter en mode dry-run pour tester
- [ ] Vérifier les résultats du dry-run
- [ ] Exécuter la migration réelle
- [ ] Vérifier les statistiques de migration
- [ ] Vérifier les fichiers copiés
- [ ] Vérifier les relations en base
- [ ] Tester l'affichage dans le frontend
