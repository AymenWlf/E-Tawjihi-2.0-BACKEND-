# 🔗 Intégration HubSpot - Formulaire Maroc

## 📋 Vue d'Ensemble

L'intégration HubSpot permet de synchroniser automatiquement les leads depuis le formulaire Maroc (`/form/maroc`) vers HubSpot. Le système gère :
- ✅ Création de contacts
- ✅ Mise à jour de contacts existants (détection par téléphone)
- ✅ Distribution en round robin des propriétaires
- ✅ Gestion des sources multiples (3 niveaux)
- ✅ Mapping automatique des propriétés personnalisées

## 🏗️ Architecture

### Structure des Services

```
src/Service/
├── HubSpotService.php              # Service principal (CRUD contacts)
└── HubSpotRoundRobinService.php    # Distribution round robin
```

### Contrôleurs

```
src/Controller/Api/
└── MarocFormController.php         # Point d'entrée formulaire
```

### Frontend

```
src/pages/
└── MarocFormPage.tsx               # Page du formulaire

src/services/
└── marocFormService.ts             # Service API frontend
```

## ⚙️ Configuration

### 1. Variables d'Environnement

Dans le fichier `.env` du backend :

```env
# Clé API HubSpot (Private App Token ou API Key)
HUBSPOT_API_KEY=votre_cle_api_ici

# IDs des propriétaires pour le round robin (optionnel, séparés par virgules)
HUBSPOT_ROUNDROBIN_OWNER_IDS=id1,id2
```

### 2. Détection du Type de Clé API

Le système détecte automatiquement le type de clé :
- **Private App Token** : Commence par `pat-` ou longueur > 50 caractères → Utilise `Authorization: Bearer`
- **API Key** : Format court → Utilise `hapikey` dans query parameters

## 🔧 Utilisation

### Frontend

1. Accéder au formulaire : `https://votre-domaine.com/form/maroc`
2. Remplir le formulaire avec les informations de l'étudiant
3. Soumettre le formulaire
4. Les données sont automatiquement synchronisées avec HubSpot

### Paramètres URL

Le formulaire accepte des paramètres URL pour le tracking :
- `?source=ads` : Définit la source du lead
- `?type=nom-adset` : Définit le nom de l'adset publicitaire

Exemple : `https://votre-domaine.com/form/maroc?source=google-ads&type=campagne-2025`

## 📊 Mapping des Données

### Champs du Formulaire → Propriétés HubSpot

| Champ Formulaire | Propriété HubSpot | Transformation |
|-----------------|-------------------|----------------|
| `nom_prenom` | `firstname` + `lastname` | Séparation par espace |
| `telephone` | `phone` | Nettoyage (suppression espaces, +212→0) |
| `tuteur_eleve` | `est_tuteut` | `'tuteur'` → `true`, sinon `false` |
| `niveau_etude` | `niveau_detude` | Mapping vers valeurs HubSpot |
| `filiere_bac` | `filiere` | Extraction partie française |
| `type_ecole` | `type_decole` | Normalisation (Public/Privé) |
| `ville` | `city` | Utilisation directe |
| `pret_payer` | `case_paiement_compris` | `'oui'` → `true` |
| Besoins cochés | `besoins_coches` | Concaténation avec virgules |
| `source` | `source_du_lead` | Mapping vers valeurs autorisées |
| `source` | `source_du_lead_2` | Si source_du_lead_2 vide |
| `source` | `source_du_lead_3` | Si source_du_lead_2 rempli |
| `adset_name` | `nom_adset` | Utilisation directe |
| - | `statut_de_traitement` | Toujours "Nouveau" pour nouveaux |
| - | `derniere_date_de_generation` | Date/heure actuelle (UTC) |
| - | `hubspot_owner_id` | Round robin |

## 🔄 Flux de Synchronisation

```
┌─────────────────────────────────┐
│  Soumission Formulaire          │
│  (POST /api/form/maroc/submit)  │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  1. Validation des données      │
│  2. Sauvegarde en BDD (optionnel)│
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  HubSpot configuré ?            │
└──────────────┬──────────────────┘
               │
        ┌──────┴──────┐
        │             │
       NON           OUI
        │             │
        ▼             ▼
   ┌────────┐  ┌──────────────────┐
   │ Ignoré │  │ Round Robin      │
   └────────┘  │ getNextOwnerId() │
               └────────┬─────────┘
                        │
                        ▼
              ┌──────────────────┐
              │ syncLeadToHubSpot│
              │ (formData, ownerId)│
              └────────┬─────────┘
                       │
                       ▼
              ┌──────────────────┐
              │ findContactByPhone│
              │ (telephone)       │
              └────────┬─────────┘
                       │
            ┌──────────┴──────────┐
            │                      │
         EXISTANT              INEXISTANT
            │                      │
            ▼                      ▼
   ┌─────────────────┐   ┌─────────────────┐
   │ updateContact() │   │ createContact() │
   └─────────────────┘   └─────────────────┘
```

## ✅ Propriétés HubSpot Requises

### Propriétés Standard (déjà dans HubSpot)
- `phone` - Numéro de téléphone
- `firstname` - Prénom
- `lastname` - Nom
- `email` - Email (optionnel)
- `city` - Ville
- `hubspot_owner_id` - Propriétaire du contact

### Propriétés Personnalisées à Créer dans HubSpot

1. **est_tuteut** (Type: Boolean)
2. **statut_de_traitement** (Type: Single-line text) - Valeur par défaut : "Nouveau"
3. **niveau_detude** (Type: Single-line text) - Valeurs : "1ère année Baccalauréat", "2ème année Baccalauréat", "BAC+1", "BAC+3", "Autres"
4. **filiere** (Type: Single-line text)
5. **type_decole** (Type: Single-line text) - Valeurs : "Public", "Privé"
6. **derniere_date_de_generation** (Type: Date)
7. **source_du_lead** (Type: Single-line text)
8. **source_du_lead_2** (Type: Single-line text)
9. **source_du_lead_3** (Type: Single-line text)
10. **case_paiement_compris** (Type: Boolean)
11. **besoins_coches** (Type: Single-line text)
12. **nom_adset** (Type: Single-line text)
13. **specialites_mission** (Type: Single-line text)

## 🧪 Tests

### Test de Création de Contact

1. Accéder à `/form/maroc`
2. Remplir le formulaire avec un nouveau numéro de téléphone
3. Soumettre
4. Vérifier dans HubSpot que le contact a été créé

### Test de Mise à Jour de Contact

1. Accéder à `/form/maroc`
2. Remplir le formulaire avec un numéro de téléphone existant
3. Soumettre
4. Vérifier dans HubSpot que le contact a été mis à jour

### Test du Round Robin

1. Configurer plusieurs propriétaires dans `HUBSPOT_ROUNDROBIN_OWNER_IDS`
2. Soumettre plusieurs formulaires
3. Vérifier que les contacts sont distribués équitablement

## 📝 Notes Importantes

1. **Non-bloquant** : Les erreurs HubSpot ne bloquent jamais la soumission du formulaire
2. **Idempotence** : Plusieurs soumissions avec le même téléphone = mise à jour (pas de doublon)
3. **Round Robin** : La persistance dans `var/hubspot_roundrobin_state.json` garantit la continuité
4. **Sources** : La logique en cascade préserve l'historique (source_du_lead → source_du_lead_2 → source_du_lead_3)
5. **Dates** : Toujours utiliser UTC pour HubSpot
6. **Nettoyage Téléphone** : Essentiel pour éviter les doublons

## 🔍 Dépannage

### Les contacts ne sont pas créés dans HubSpot

1. Vérifier que `HUBSPOT_API_KEY` est configuré dans `.env`
2. Vérifier les logs Symfony : `tail -f var/log/dev.log`
3. Vérifier que la clé API a les permissions nécessaires dans HubSpot

### Le round robin ne fonctionne pas

1. Vérifier que `HUBSPOT_ROUNDROBIN_OWNER_IDS` contient des IDs valides
2. Vérifier que le fichier `var/hubspot_roundrobin_state.json` est créé et accessible en écriture
3. Vérifier les logs pour les erreurs

### Les propriétés ne sont pas mappées correctement

1. Vérifier que toutes les propriétés personnalisées existent dans HubSpot
2. Vérifier les noms exacts des propriétés (sensible à la casse)
3. Vérifier les valeurs autorisées pour les propriétés de type select

## 📚 Ressources

- **Documentation HubSpot API** : https://developers.hubspot.com/docs/api/crm/contacts
- **Private Apps** : https://developers.hubspot.com/docs/api/working-with-oauth
- **Scopes Requis** : `crm.objects.contacts.read`, `crm.objects.contacts.write`, `settings.users.read`

---

**Date de création :** 2025-01-27  
**Version :** 1.0
