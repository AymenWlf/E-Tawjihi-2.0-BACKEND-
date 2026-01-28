# Analyse Architecture et Migration - Etablissement, Filiere, Universite

## 📊 Architecture Actuelle

### 1. ENTITÉ ESTABLISHMENT (Backend)

#### Table: `establishments`

| Attribut | Type PHP | Type DB | Nullable | Description |
|----------|----------|---------|----------|-------------|
| `id` | `int` | INT | No | ID unique |
| `nom` | `string` | VARCHAR(255) | No | Nom de l'établissement |
| `sigle` | `string` | VARCHAR(50) | No | Sigle (ex: ENSIAS) |
| `nomArabe` | `string` | VARCHAR(255) | Yes | Nom en arabe |
| `type` | `string` | VARCHAR(50) | No | Public/Privé/Semi-Public/Militaire |
| `ville` | `string` | VARCHAR(100) | No | Ville principale |
| `villes` | `array` | JSON | Yes | Liste des villes (JSON) |
| `pays` | `string` | VARCHAR(100) | Yes | Pays |
| `universite` | `string` | VARCHAR(255) | Yes | Nom de l'université (chaîne) |
| `description` | `string` | TEXT | Yes | Description |
| `logo` | `string` | VARCHAR(500) | Yes | Chemin/URL du logo |
| `imageCouverture` | `string` | VARCHAR(500) | Yes | Image de couverture |
| `email` | `string` | VARCHAR(255) | Yes | Email de contact |
| `telephone` | `string` | VARCHAR(50) | Yes | Téléphone |
| `siteWeb` | `string` | VARCHAR(500) | Yes | Site web |
| `adresse` | `string` | TEXT | Yes | Adresse complète |
| `codePostal` | `string` | VARCHAR(20) | Yes | Code postal |
| `facebook` | `string` | VARCHAR(500) | Yes | URL Facebook |
| `instagram` | `string` | VARCHAR(500) | Yes | URL Instagram |
| `twitter` | `string` | VARCHAR(500) | Yes | URL Twitter |
| `linkedin` | `string` | VARCHAR(500) | Yes | URL LinkedIn |
| `youtube` | `string` | VARCHAR(500) | Yes | URL YouTube |
| `nbEtudiants` | `int` | INT | Yes | Nombre d'étudiants |
| `nbFilieres` | `int` | INT | Yes | Nombre de filières |
| `anneeCreation` | `int` | INT | Yes | Année de création |
| `accreditationEtat` | `bool` | BOOLEAN | No | Accréditation État (default: false) |
| `concours` | `bool` | BOOLEAN | No | Concours requis (default: false) |
| `echangeInternational` | `bool` | BOOLEAN | No | Échange international (default: false) |
| `anneesEtudes` | `int` | INT | Yes | Nombre d'années d'études |
| `dureeEtudesMin` | `int` | INT | Yes | Durée min (années) |
| `dureeEtudesMax` | `int` | INT | Yes | Durée max (années) |
| `fraisScolariteMin` | `string` | DECIMAL(10,2) | Yes | Frais scolarité min |
| `fraisScolariteMax` | `string` | DECIMAL(10,2) | Yes | Frais scolarité max |
| `fraisInscriptionMin` | `string` | DECIMAL(10,2) | Yes | Frais inscription min |
| `fraisInscriptionMax` | `string` | DECIMAL(10,2) | Yes | Frais inscription max |
| `diplomesDelivres` | `array` | JSON | Yes | Liste des diplômes |
| `bacObligatoire` | `bool` | BOOLEAN | No | Bac obligatoire (default: false) |
| `slug` | `string` | VARCHAR(255) | No | Slug unique |
| `metaTitle` | `string` | VARCHAR(255) | Yes | Meta title SEO |
| `metaDescription` | `string` | TEXT | Yes | Meta description SEO |
| `metaKeywords` | `string` | TEXT | Yes | Meta keywords SEO |
| `ogImage` | `string` | VARCHAR(500) | Yes | Image OG (Open Graph) |
| `canonicalUrl` | `string` | VARCHAR(500) | Yes | URL canonique |
| `schemaType` | `string` | VARCHAR(100) | Yes | Type Schema.org (default: EducationalOrganization) |
| `noIndex` | `bool` | BOOLEAN | No | No index (default: false) |
| `isActive` | `bool` | BOOLEAN | No | Actif (default: true) |
| `isRecommended` | `bool` | BOOLEAN | No | Recommandé (default: false) |
| `isSponsored` | `bool` | BOOLEAN | No | Sponsorisé (default: false) |
| `isFeatured` | `bool` | BOOLEAN | No | En vedette (default: false) |
| `videoUrl` | `string` | VARCHAR(500) | Yes | URL vidéo |
| `documents` | `array` | JSON | Yes | Documents (JSON array) |
| `photos` | `array` | JSON | Yes | Photos (JSON array) |
| `status` | `string` | VARCHAR(50) | Yes | Statut (default: 'Brouillon') |
| `isComplet` | `bool` | BOOLEAN | No | Complet (default: false) |
| `hasDetailPage` | `bool` | BOOLEAN | No | Page détail (default: false) |
| `eTawjihiInscription` | `bool` | BOOLEAN | No | Inscription e-Tawjihi (default: false) |
| `bacType` | `string` | VARCHAR(20) | Yes | Type bac: 'normal', 'mission', 'both' |
| `filieresAcceptees` | `array` | JSON | Yes | Filières acceptées (bac normal) |
| `combinaisonsBacMission` | `array` | JSON | Yes | Combinaisons bac mission (JSON) |
| `secteursIds` | `array` | JSON | Yes | IDs secteurs associés |
| `filieresIds` | `array` | JSON | Yes | IDs filières d'études associés |
| `createdAt` | `DateTime` | DATETIME | No | Date création |
| `updatedAt` | `DateTime` | DATETIME | No | Date mise à jour |

#### Relations:
- **OneToMany** → `Campus` (collection)
- **OneToMany** → `Filiere` (collection)

#### Fichiers (Médias):
- `logo`: Chemin/URL (string)
- `imageCouverture`: Chemin/URL (string)
- `ogImage`: Chemin/URL (string)
- `documents`: Array JSON avec structure `[{titre, description, url, fileName, fileSize}]`
- `photos`: Array JSON avec structure `[{url, description, fileName, fileSize?}]`
- `videoUrl`: URL (string)

---

### 2. ENTITÉ FILIERE (Backend)

#### Table: `filieres`

| Attribut | Type PHP | Type DB | Nullable | Description |
|----------|----------|---------|----------|-------------|
| `id` | `int` | INT | No | ID unique |
| `nom` | `string` | VARCHAR(255) | No | Nom de la filière |
| `nomArabe` | `string` | VARCHAR(255) | Yes | Nom en arabe |
| `slug` | `string` | VARCHAR(255) | No | Slug unique |
| `description` | `string` | TEXT | Yes | Description |
| `imageCouverture` | `string` | VARCHAR(500) | Yes | Image de couverture |
| `diplome` | `string` | VARCHAR(100) | Yes | Diplôme délivré (Master, Licence, etc.) |
| `domaine` | `string` | VARCHAR(100) | Yes | Domaine d'études |
| `langueEtudes` | `string` | VARCHAR(50) | Yes | Langue d'enseignement |
| `fraisScolarite` | `string` | DECIMAL(10,2) | Yes | Frais de scolarité |
| `fraisInscription` | `string` | DECIMAL(10,2) | Yes | Frais d'inscription |
| `concours` | `bool` | BOOLEAN | No | Concours requis (default: false) |
| `nbPlaces` | `int` | INT | Yes | Nombre de places |
| `nombreAnnees` | `string` | VARCHAR(50) | Yes | Durée (ex: "3 ans") |
| `typeEcole` | `string` | VARCHAR(50) | Yes | Privé/Public |
| `bacCompatible` | `bool` | BOOLEAN | No | Compatible bac (default: false) |
| `bacType` | `string` | VARCHAR(50) | Yes | Type bac: 'normal', 'mission', 'both' |
| `filieresAcceptees` | `array` | JSON | Yes | Filières acceptées (bac normal) |
| `combinaisonsBacMission` | `array` | JSON | Yes | Combinaisons bac mission |
| `recommandee` | `bool` | BOOLEAN | No | Recommandée (default: false) |
| `metier` | `array` | JSON | Yes | Informations métier |
| `objectifs` | `array` | JSON | Yes | Objectifs formation |
| `programme` | `array` | JSON | Yes | Programme par semestre |
| `documents` | `array` | JSON | Yes | Documents |
| `photos` | `array` | JSON | Yes | Photos |
| `videoUrl` | `string` | VARCHAR(500) | Yes | URL vidéo |
| `reconnaissance` | `string` | VARCHAR(100) | Yes | Reconnaissance diplôme |
| `echangeInternational` | `bool` | BOOLEAN | No | Échange international (default: false) |
| `establishment_id` | `int` | INT | No | **FK → Establishment** |
| `metaTitle` | `string` | VARCHAR(255) | Yes | Meta title SEO |
| `metaDescription` | `string` | TEXT | Yes | Meta description SEO |
| `metaKeywords` | `string` | TEXT | Yes | Meta keywords SEO |
| `ogImage` | `string` | VARCHAR(500) | Yes | Image OG |
| `canonicalUrl` | `string` | VARCHAR(500) | Yes | URL canonique |
| `schemaType` | `string` | VARCHAR(100) | Yes | Type Schema.org (default: EducationalProgram) |
| `noIndex` | `bool` | BOOLEAN | No | No index (default: false) |
| `isActive` | `bool` | BOOLEAN | No | Actif (default: true) |
| `isSponsored` | `bool` | BOOLEAN | No | Sponsorisé (default: false) |
| `createdAt` | `DateTime` | DATETIME | No | Date création |
| `updatedAt` | `DateTime` | DATETIME | No | Date mise à jour |

#### Relations:
- **ManyToOne** → `Establishment` (obligatoire)
- **ManyToMany** → `Campus` (via table `filiere_campus`)

#### Fichiers (Médias):
- `imageCouverture`: Chemin/URL (string)
- `ogImage`: Chemin/URL (string)
- `documents`: Array JSON avec structure `[{titre, description, url, fileName, fileSize}]`
- `photos`: Array JSON avec structure `[{url, description, fileName, fileSize?}]`
- `videoUrl`: URL (string)

---

### 3. ENTITÉ UNIVERSITE (Backend)

#### Table: `universites`

| Attribut | Type PHP | Type DB | Nullable | Description |
|----------|----------|---------|----------|-------------|
| `id` | `int` | INT | No | ID unique |
| `nom` | `string` | VARCHAR(255) | No | Nom de l'université |
| `sigle` | `string` | VARCHAR(100) | Yes | Sigle |
| `nomArabe` | `string` | VARCHAR(255) | Yes | Nom en arabe |
| `ville` | `string` | VARCHAR(100) | Yes | Ville |
| `region` | `string` | VARCHAR(100) | Yes | Région |
| `pays` | `string` | VARCHAR(100) | Yes | Pays |
| `type` | `string` | VARCHAR(50) | Yes | Type d'université |
| `description` | `string` | TEXT | Yes | Description |
| `logo` | `string` | VARCHAR(500) | Yes | Chemin/URL du logo |
| `siteWeb` | `string` | VARCHAR(500) | Yes | Site web |
| `email` | `string` | VARCHAR(255) | Yes | Email |
| `telephone` | `string` | VARCHAR(50) | Yes | Téléphone |
| `isActive` | `bool` | BOOLEAN | No | Actif (default: true) |
| `createdAt` | `DateTime` | DATETIME | No | Date création |
| `updatedAt` | `DateTime` | DATETIME | No | Date mise à jour |

#### Relations:
- Aucune relation directe avec Establishment ou Filiere (juste référence via `universite` string dans Establishment)

#### Fichiers (Médias):
- `logo`: Chemin/URL (string)

---

### 4. ENTITÉ CAMPUS (Backend)

#### Table: `campus`

| Attribut | Type PHP | Type DB | Nullable | Description |
|----------|----------|---------|----------|-------------|
| `id` | `int` | INT | No | ID unique |
| `nom` | `string` | VARCHAR(255) | No | Nom du campus |
| `city_id` | `int` | INT | No | **FK → City** |
| `quartier` | `string` | VARCHAR(100) | Yes | Quartier |
| `adresse` | `string` | TEXT | Yes | Adresse |
| `codePostal` | `string` | VARCHAR(20) | Yes | Code postal |
| `telephone` | `string` | VARCHAR(50) | Yes | Téléphone |
| `email` | `string` | VARCHAR(255) | Yes | Email |
| `mapUrl` | `string` | TEXT | Yes | URL Google Maps |
| `ordre` | `int` | INT | Yes | Ordre d'affichage |
| `establishment_id` | `int` | INT | No | **FK → Establishment** |

#### Relations:
- **ManyToOne** → `City`
- **ManyToOne** → `Establishment`
- **ManyToMany** → `Filiere` (via table `filiere_campus`)

---

## 🔄 Structure Frontend (TypeScript/React)

### Interface Establishment (Frontend)

```typescript
interface Establishment {
  id?: number;
  nom: string;
  sigle: string;
  nomArabe?: string;
  type: string;
  ville: string;
  villes?: string[];
  pays?: string;
  universite?: string;
  description?: string;
  logo?: string;
  imageCouverture?: string;
  email?: string;
  telephone?: string;
  siteWeb?: string;
  adresse?: string;
  codePostal?: string;
  facebook?: string;
  instagram?: string;
  twitter?: string;
  linkedin?: string;
  youtube?: string;
  nbEtudiants?: number;
  nbFilieres?: number;
  anneeCreation?: number;
  accreditationEtat: boolean;
  concours: boolean;
  echangeInternational: boolean;
  anneesEtudes?: number;
  dureeEtudesMin?: number;
  dureeEtudesMax?: number;
  fraisScolariteMin?: string;
  fraisScolariteMax?: string;
  fraisInscriptionMin?: string;
  fraisInscriptionMax?: string;
  diplomesDelivres?: string[];
  bacObligatoire: boolean;
  slug: string;
  metaTitle?: string;
  metaDescription?: string;
  metaKeywords?: string;
  ogImage?: string;
  canonicalUrl?: string;
  schemaType?: string;
  noIndex: boolean;
  isActive: boolean;
  isRecommended: boolean;
  isSponsored: boolean;
  isFeatured: boolean;
  eTawjihiInscription?: boolean;
  bacType?: string;
  filieresAcceptees?: string[];
  combinaisonsBacMission?: string[][];
  videoUrl?: string;
  documents?: any[];
  photos?: any[];
  campus?: Campus[];
  status?: string;
  isComplet: boolean;
  hasDetailPage: boolean;
  createdAt?: string;
  updatedAt?: string;
  secteursIds?: number[];
  filieresIds?: number[];
}
```

### Interface Filiere (Frontend)

```typescript
interface Filiere {
  id: number;
  nom: string;
  nomArabe?: string;
  slug: string;
  description?: string;
  imageCouverture?: string;
  logo?: string;
  diplome?: string;
  domaine?: string;
  langueEtudes?: string;
  fraisScolarite?: string;
  fraisInscription?: string;
  concours: boolean;
  nbPlaces?: number;
  nombreAnnees?: string;
  typeEcole?: string;
  bacCompatible: boolean;
  recommandee: boolean;
  metier?: any;
  objectifs?: string[];
  programme?: any[];
  documents?: any[];
  photos?: string[];
  videoUrl?: string;
  reconnaissance?: string;
  echangeInternational: boolean;
  establishment?: {
    id: number;
    nom: string;
    sigle?: string;
    slug?: string;
    logo?: string;
    pays?: string;
    universite?: string;
    type?: string;
    url?: string;
    eTawjihiInscription?: boolean;
  };
  campus?: Array<{
    id: number;
    nom: string;
    ville: string;
    cityId?: number;
    city?: {
      id: number;
      titre: string;
    };
    quartier?: string;
    adresse?: string;
  }>;
  url?: string;
  isActive: boolean;
  isSponsored: boolean;
  bacType?: string;
  filieresAcceptees?: string[];
  combinaisonsBacMission?: string[][];
}
```

### Interface Universite (Frontend)

```typescript
interface University {
  id: number;
  nom: string;
  sigle?: string;
  nomArabe?: string;
  ville?: string;
  region?: string;
  pays?: string;
  type?: string;
  description?: string;
  logo?: string;
  siteWeb?: string;
  email?: string;
  telephone?: string;
  isActive: boolean;
  createdAt?: string;
  updatedAt?: string;
}
```

---

## 📁 Structure des Fichiers Médias

### Format JSON pour Documents

```json
[
  {
    "titre": "Brochure 2024",
    "description": "Brochure complète",
    "url": "/uploads/documents/establishment_123_brochure.pdf",
    "fileName": "brochure.pdf",
    "fileSize": 2048576
  }
]
```

### Format JSON pour Photos

```json
[
  {
    "url": "/uploads/photos/establishment_123_photo1.jpg",
    "description": "Photo du campus",
    "fileName": "photo1.jpg",
    "fileSize": 524288
  }
]
```

---

## ⚠️ Points d'Attention pour Migration

1. **Référence Universite** : 
   - Backend : `Establishment.universite` est une **chaîne** (pas une FK)
   - À migrer : Créer une relation ManyToOne avec entité Universite si nécessaire

2. **Campus** :
   - Relation ManyToOne avec Establishment
   - Relation ManyToMany avec Filiere (table pivot `filiere_campus`)
   - Relation ManyToOne avec City

3. **Fichiers** :
   - Logos : Stockés comme chemin relatif/absolu dans la colonne
   - Documents/Photos : Stockés en JSON avec structure détaillée
   - Besoin de copier physiquement les fichiers lors de la migration

4. **JSON Fields** :
   - `villes`, `diplomesDelivres`, `filieresAcceptees`, `combinaisonsBacMission`, `secteursIds`, `filieresIds`, `documents`, `photos`
   - S'assurer de la conversion correcte lors de la migration

5. **Slugs** :
   - Unique pour Establishment et Filiere
   - Générer automatiquement si manquant lors de la migration

6. **Dates** :
   - `createdAt` et `updatedAt` : Préserver les dates originales si possible

7. **Relations** :
   - Préserver les relations Campus ↔ Establishment
   - Préserver les relations Filiere ↔ Establishment
   - Préserver les relations ManyToMany Filiere ↔ Campus

---

## 🎯 Plan de Migration

Voir les scripts de migration dans :
- `E-TAWJIHI-BACKEND/src/Command/MigrateDataCommand.php` (à créer)
- `E-TAWJIHI-BACKEND/src/Service/MigrationService.php` (à créer)
