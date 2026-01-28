# Éléments à afficher dans la page Profil Étudiant

## Données disponibles via l'API `/api/user/profile`

### ✅ **Informations Personnelles** (actuellement retournées)
1. **Nom** (`nom`) - String
2. **Prénom** (`prenom`) - String
3. **Email** (`email`) - String (fallback: `User.email`)
4. **Téléphone** (`phone`) - String (depuis `User.phone`)
5. **Date de naissance** (`dateNaissance`) - Date (format: Y-m-d)
6. **Genre** (`genre`) - String ('Homme' ou 'Femme')
7. **Ville** (`ville`) - Object { id: number, titre: string } ou null

### ✅ **Informations Académiques** (actuellement retournées)
1. **Type d'utilisateur** (`userType`) - String ('student' ou 'tutor')
2. **Niveau d'études** (`niveau`) - String (ex: "Terminale", "Bac+1", etc.)
3. **Type de Bac** (`bacType`) - String ('normal' ou 'mission')
4. **Filière** (`filiere`) - String (filière actuelle ou préférée)
5. **Spécialité 1** (`specialite1`) - String (pour Bac Mission)
6. **Spécialité 2** (`specialite2`) - String (pour Bac Mission)
7. **Spécialité 3** (`specialite3`) - String (pour Bac Mission)
8. **Diplôme en cours** (`diplomeEnCours`) - String
9. **Nom de l'établissement** (`nomEtablissement`) - String

### ⚠️ **Préférences** (disponibles dans UserProfile mais PAS retournées par l'API actuellement)
1. **Type d'école préféré** (`typeEcolePrefere`) - Array ['public', 'prive', 'militaire', 'semi-public']
2. **Services préférés** (`servicesPrefere`) - Array ['orientation', 'inscription', 'notifications']

### ⚠️ **Informations Tuteur** (disponibles dans UserProfile mais PAS retournées par l'API actuellement)
1. **Type de tuteur** (`tuteur`) - String ('Père', 'Mère', 'Autre')
2. **Nom du tuteur** (`nomTuteur`) - String
3. **Prénom du tuteur** (`prenomTuteur`) - String
4. **Téléphone du tuteur** (`telTuteur`) - String
5. **Profession du tuteur** (`professionTuteur`) - String
6. **Adresse du tuteur** (`adresseTuteur`) - String

### ⚠️ **Autres informations** (disponibles dans UserProfile mais PAS retournées par l'API actuellement)
1. **Consentement de contact** (`consentContact`) - Boolean
2. **Date de création** (`createdAt`) - DateTime
3. **Date de mise à jour** (`updatedAt`) - DateTime

### ℹ️ **Informations depuis User** (disponibles via `/api/me` ou `currentUser`)
1. **Téléphone** (`phone`) - String (identifiant principal)
2. **Email** (`email`) - String (peut être null)
3. **Rôles** (`roles`) - Array ['ROLE_USER', 'ROLE_ADMIN', etc.]
4. **Statut de configuration** (`is_setup`) - Boolean

---

## Structure recommandée de la page Profil Étudiant

### **Section 1 : En-tête du Profil**
- Photo/Avatar (générée à partir du nom/prénom ou email)
- Nom complet (prénom + nom)
- Email
- Badge Premium/Normal (basé sur `roles` ou un champ premium futur)
- Statut de configuration (`is_setup`)

### **Section 2 : Informations Personnelles**
- Nom
- Prénom
- Email *
- Téléphone *
- Date de naissance
- Genre
- Ville (avec possibilité de sélection si liste disponible)

### **Section 3 : Formation & Études**
- Niveau d'études actuel (`niveau`)
- Type de Bac (`bacType` avec formatage: "Bac Normal" ou "Bac Mission")
- Filière (`filiere`)
- **Si Bac Mission** :
  - Spécialité 1 (`specialite1`)
  - Spécialité 2 (`specialite2`)
  - Spécialité 3 (`specialite3`) - optionnel
- Diplôme en cours (`diplomeEnCours`)
- Nom de l'établissement actuel (`nomEtablissement`)

### **Section 4 : Préférences d'Orientation** (⚠️ Nécessite mise à jour API)
- Type d'école préféré (`typeEcolePrefere`)
  - Afficher comme badges : Public, Privé, Militaire, Semi-public
- Services préférés (`servicesPrefere`)
  - Afficher comme badges : Orientation, Inscription, Notifications

### **Section 5 : Informations Tuteur** (⚠️ Nécessite mise à jour API, optionnel)
- Type de tuteur (`tuteur`)
- Nom complet du tuteur (`nomTuteur` + `prenomTuteur`)
- Téléphone du tuteur (`telTuteur`)
- Profession du tuteur (`professionTuteur`)
- Adresse du tuteur (`adresseTuteur`)

### **Section 6 : Paramètres & Confidentialité** (⚠️ Nécessite mise à jour API)
- Consentement de contact (`consentContact`)
- Dates de création et mise à jour (informations système)

---

## Actions recommandées

### 1. **Mise à jour de l'API `/api/user/profile`**
Ajouter les champs manquants dans la réponse :
```php
'typeEcolePrefere' => $profile->getTypeEcolePrefere(),
'servicesPrefere' => $profile->getServicesPrefere(),
'tuteur' => $profile->getTuteur(),
'nomTuteur' => $profile->getNomTuteur(),
'prenomTuteur' => $profile->getPrenomTuteur(),
'telTuteur' => $profile->getTelTuteur(),
'professionTuteur' => $profile->getProfessionTuteur(),
'adresseTuteur' => $profile->getAdresseTuteur(),
'consentContact' => $profile->getConsentContact(),
'createdAt' => $profile->getCreatedAt()?->format('Y-m-d H:i:s'),
'updatedAt' => $profile->getUpdatedAt()?->format('Y-m-d H:i:s'),
```

### 2. **Mise à jour de l'API `/api/user/profile` (PUT)**
S'assurer que tous ces champs peuvent être mis à jour via l'endpoint PUT.

### 3. **Mise à jour du service frontend `userProfileService.js`**
Adapter le service pour utiliser la structure réelle de l'API au lieu de la structure fictive actuelle.

### 4. **Mise à jour de la page `ProfilEtudiant.tsx`**
- Afficher tous les champs disponibles
- Gérer l'affichage conditionnel des sections selon les données disponibles
- Ajouter des sections pour les préférences et informations tuteur si elles sont renseignées

---

## Champs marqués comme obligatoires (*)

Dans l'interface utilisateur, marquer comme obligatoires :
- **Email** : Utilisé pour l'authentification et les communications
- **Téléphone** : Identifiant principal de l'utilisateur (obligatoire à la création)

Les autres champs peuvent être optionnels et affichés avec "Non renseigné" si vides.

---

## Formatage recommandé

### Type de Bac
- `'normal'` → "Bac Normal"
- `'mission'` → "Bac Mission"

### Genre
- `'Homme'` → "Homme"
- `'Femme'` → "Femme"

### Type d'école préféré
- Afficher comme badges colorés
- `'public'` → Badge bleu "Public"
- `'prive'` → Badge vert "Privé"
- `'militaire'` → Badge rouge "Militaire"
- `'semi-public'` → Badge orange "Semi-public"

### Services préférés
- Afficher comme badges avec icônes
- `'orientation'` → Badge avec icône cible
- `'inscription'` → Badge avec icône inscription
- `'notifications'` → Badge avec icône cloche

---

## Exemple de réponse API complète (souhaitée)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "nom": "Ouali",
    "prenom": "Aymen",
    "email": "aymen@example.com",
    "dateNaissance": "2000-05-15",
    "genre": "Homme",
    "ville": {
      "id": 1,
      "titre": "Casablanca"
    },
    "userType": "student",
    "niveau": "Terminale",
    "bacType": "mission",
    "filiere": "Sciences Mathématiques",
    "specialite1": "Mathématiques",
    "specialite2": "Physique-Chimie",
    "specialite3": null,
    "diplomeEnCours": null,
    "nomEtablissement": "Lycée Hassan II",
    "typeEcolePrefere": ["public", "prive"],
    "servicesPrefere": ["orientation", "inscription"],
    "tuteur": "Père",
    "nomTuteur": "Ouali",
    "prenomTuteur": "Mohamed",
    "telTuteur": "0612345678",
    "professionTuteur": "Ingénieur",
    "adresseTuteur": "123 Rue Example, Casablanca",
    "consentContact": true,
    "createdAt": "2024-01-10 10:30:00",
    "updatedAt": "2024-01-10 15:45:00"
  }
}
```
