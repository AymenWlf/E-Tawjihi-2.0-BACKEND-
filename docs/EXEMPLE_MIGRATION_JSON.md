# Exemple de Format JSON pour Migration

## Format du Fichier de Migration

Le fichier JSON doit avoir la structure suivante :

```json
{
  "establishments": [
    {
      "id": 1,
      "nom": "École Marocaine des Sciences de l'Ingénieur",
      "sigle": "EMSI",
      "nomArabe": "المدرسة المغربية لعلوم المهندس",
      "type": "Privé",
      "ville": "Casablanca",
      "villes": ["Casablanca", "Rabat", "Marrakech", "Tanger"],
      "pays": "Maroc",
      "universite": "Honoris United Universities",
      "description": "Description de l'établissement...",
      "logo": "/old_uploads/establishments/logo_emsi.png",
      "imageCouverture": "/old_uploads/establishments/cover_emsi.jpg",
      "email": "contact@emsi.ma",
      "telephone": "0522272727",
      "siteWeb": "https://www.emsi.ma",
      "adresse": "Rue Abou Kacem Echabi, Casablanca",
      "codePostal": "20100",
      "facebook": "https://facebook.com/emsi",
      "instagram": "https://instagram.com/emsi",
      "twitter": "https://twitter.com/emsi",
      "linkedin": "https://linkedin.com/company/emsi",
      "youtube": "https://youtube.com/emsi",
      "nbEtudiants": 5000,
      "nbFilieres": 15,
      "anneeCreation": 1986,
      "accreditationEtat": true,
      "concours": true,
      "echangeInternational": true,
      "anneesEtudes": 5,
      "dureeEtudesMin": 3,
      "dureeEtudesMax": 5,
      "fraisScolariteMin": "35000.00",
      "fraisScolariteMax": "45000.00",
      "fraisInscriptionMin": "2000.00",
      "fraisInscriptionMax": "3000.00",
      "diplomesDelivres": ["Ingénieur d'État", "Master", "Licence"],
      "bacObligatoire": true,
      "slug": "ecole-marocaine-sciences-ingenieur",
      "metaTitle": "EMSI - École Marocaine des Sciences de l'Ingénieur",
      "metaDescription": "EMSI est une institution d'enseignement supérieur...",
      "metaKeywords": "EMSI, école ingénieur, Maroc, formation ingénieur",
      "ogImage": "/old_uploads/establishments/og_emsi.jpg",
      "canonicalUrl": "https://www.etawjihi.ma/etablissements/emsi",
      "schemaType": "EducationalOrganization",
      "noIndex": false,
      "isActive": true,
      "isRecommended": true,
      "isSponsored": false,
      "isFeatured": true,
      "videoUrl": "https://youtube.com/watch?v=...",
      "documents": [
        {
          "titre": "Brochure 2024",
          "description": "Brochure complète de l'établissement",
          "url": "/old_uploads/establishments/documents/brochure_2024.pdf",
          "fileName": "brochure_2024.pdf",
          "fileSize": 2048576
        }
      ],
      "photos": [
        {
          "url": "/old_uploads/establishments/photos/campus1.jpg",
          "description": "Photo du campus principal",
          "fileName": "campus1.jpg",
          "fileSize": 524288
        }
      ],
      "status": "Publié",
      "isComplet": true,
      "hasDetailPage": true,
      "eTawjihiInscription": true,
      "bacType": "both",
      "filieresAcceptees": ["Sciences Mathématiques A", "Sciences Physiques"],
      "combinaisonsBacMission": [
        ["Mathématiques", "Physique-Chimie"],
        ["SVT", "NSI"]
      ],
      "secteursIds": [1, 5, 12],
      "filieresIds": [3, 7, 15],
      "campus": [
        {
          "nom": "Campus Casablanca",
          "city_id": 1,
          "ville": "Casablanca",
          "quartier": "Aïn Sebaâ",
          "adresse": "123 Rue Mohammed V",
          "codePostal": "20100",
          "telephone": "0522272727",
          "email": "casablanca@emsi.ma",
          "mapUrl": "https://maps.google.com/...",
          "ordre": 1
        }
      ],
      "createdAt": "1986-01-01 00:00:00",
      "updatedAt": "2024-01-15 12:00:00"
    }
  ],
  "filieres": [
    {
      "id": 1,
      "nom": "Ingénierie Informatique",
      "nomArabe": "هندسة المعلوماتية",
      "slug": "ingenierie-informatique",
      "description": "Formation en ingénierie informatique...",
      "imageCouverture": "/old_uploads/filieres/cover_info.jpg",
      "diplome": "Diplôme d'Ingénieur d'État",
      "domaine": "Informatique",
      "langueEtudes": "Français",
      "fraisScolarite": "38000.00",
      "fraisInscription": "2500.00",
      "concours": true,
      "nbPlaces": 100,
      "nombreAnnees": "5 ans",
      "typeEcole": "Privé",
      "bacCompatible": true,
      "bacType": "both",
      "filieresAcceptees": ["Sciences Mathématiques A", "Sciences Physiques"],
      "combinaisonsBacMission": [
        ["Mathématiques", "Physique-Chimie"]
      ],
      "recommandee": true,
      "metier": [
        "Ingénieur en développement logiciel",
        "Architecte logiciel",
        "Chef de projet IT"
      ],
      "objectifs": [
        "Former des ingénieurs en informatique",
        "Développer les compétences techniques"
      ],
      "programme": [
        {
          "semestre": "Semestre 1",
          "modules": ["Algorithmique", "Programmation", "Mathématiques"]
        }
      ],
      "documents": [
        {
          "titre": "Programme détaillé",
          "description": "Programme complet de la formation",
          "url": "/old_uploads/filieres/documents/programme_info.pdf",
          "fileName": "programme_info.pdf",
          "fileSize": 1024000
        }
      ],
      "photos": [
        {
          "url": "/old_uploads/filieres/photos/lab_info.jpg",
          "description": "Laboratoire informatique",
          "fileName": "lab_info.jpg"
        }
      ],
      "videoUrl": "https://youtube.com/watch?v=...",
      "reconnaissance": "Diplôme reconnu par l'État",
      "echangeInternational": true,
      "establishment_id": 1,
      "campus_ids": [1, 2],
      "metaTitle": "Ingénierie Informatique - EMSI",
      "metaDescription": "Formation en ingénierie informatique...",
      "metaKeywords": "informatique, ingénierie, formation",
      "ogImage": "/old_uploads/filieres/og_info.jpg",
      "canonicalUrl": "https://www.etawjihi.ma/filieres/ingenierie-informatique",
      "schemaType": "EducationalProgram",
      "noIndex": false,
      "isActive": true,
      "isSponsored": false,
      "createdAt": "2020-01-01 00:00:00",
      "updatedAt": "2024-01-15 12:00:00"
    }
  ],
  "universites": [
    {
      "id": 1,
      "nom": "Université Mohammed V",
      "sigle": "UM5",
      "nomArabe": "جامعة محمد الخامس",
      "ville": "Rabat",
      "region": "Rabat-Salé-Kénitra",
      "pays": "Maroc",
      "type": "Université Publique",
      "description": "Université publique marocaine...",
      "logo": "/old_uploads/universites/logo_um5.png",
      "siteWeb": "https://www.um5.ac.ma",
      "email": "contact@um5.ac.ma",
      "telephone": "0537772727",
      "isActive": true,
      "createdAt": "1957-01-01 00:00:00",
      "updatedAt": "2024-01-15 12:00:00"
    }
  ]
}
```

## Notes Importantes

1. **Chemins de fichiers** : Les chemins de fichiers (logos, images, documents) doivent être relatifs au répertoire `public/old_uploads` de l'ancien système.

2. **Types de données** :
   - Les booléens peuvent être `true`, `false`, `1`, `0`, `"true"`, `"false"`
   - Les nombres décimaux doivent être en string avec 2 décimales (ex: `"35000.00"`)
   - Les dates doivent être au format `YYYY-MM-DD HH:MM:SS` ou `YYYY-MM-DD`

3. **Arrays JSON** : Les champs JSON doivent être des arrays valides :
   - `villes`: `["Ville1", "Ville2"]`
   - `diplomesDelivres`: `["Diplôme1", "Diplôme2"]`
   - `secteursIds`: `[1, 5, 12]` (IDs numériques)
   - `filieresIds`: `[3, 7, 15]` (IDs numériques)
   - `combinaisonsBacMission`: `[["Math", "Physique"], ["SVT", "NSI"]]`

4. **Relations** :
   - `establishment_id` ou `etablissement_id` pour lier une filière à un établissement
   - `city_id` pour lier un campus à une ville
   - `campus_ids` (array) pour lier une filière à plusieurs campus

5. **Slugs** : Si le slug n'est pas fourni, il sera généré automatiquement à partir du nom.

6. **Fichiers** : Tous les fichiers seront copiés vers le nouveau répertoire `/uploads/{entity_type}/{file_type}/` avec un nom unique.
