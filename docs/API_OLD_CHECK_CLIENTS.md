# API old.e-tawjihi.ma — Check clients par téléphone

Le backend **E-TAWJIHI** (nouveau) appelle **old.e-tawjihi.ma** pour vérifier si des numéros de téléphone sont des clients et récupérer leurs infos (contrat, services, tuteur, etc.).

## Environnements

| Contexte | Ancien système (expose check-clients) | Nouveau backend (appelle check-clients) |
|----------|----------------------------------------|----------------------------------------|
| **Dev**  | `http://localhost:8000` (e-tawjihi)    | `http://localhost:8001` (E-TAWJIHI-BACKEND) |
| **Prod** | `https://old.e-tawjihi.ma`             | `https://apinew.e-tawjihi.ma`           |

## Configuration (nouveau backend)

Variable : **`OLD_ETAWJIHI_API_URL`** (base URL de l’API old, sans `/api/check-clients`).

- **En dev** : par défaut `http://localhost:8000` (via `config/packages/dev/services.yaml`). Surcharge possible avec `OLD_ETAWJIHI_API_URL=http://127.0.0.1:8000` dans `.env`. Lancer **e-tawjihi** sur le port **8000** et **E-TAWJIHI-BACKEND** sur **8001**.
- **En prod** : définir `OLD_ETAWJIHI_API_URL=https://old.e-tawjihi.ma` (`.env.local` ou variables d’environnement).

L’endpoint appelé : base URL + `/api/check-clients` (POST).

## Contract API (old.e-tawjihi.ma)

L’ancien système doit exposer :

- **Méthode** : `POST`
- **URL** : `/api/check-clients`
- **Headers** : `Content-Type: application/json`
- **Body** :
  ```json
  {
    "tel": ["0622073449", "0612345678", ...]
  }
  ```
  La variable doit s’appeler **`tel`** (liste de numéros), pas `phone`.

- **Réponse 2xx** :
  ```json
  {
    "success": true,
    "data": {
      "0622073449": {
        "id": 28502,
        "numeroContrat": "ETAW-2026-0857",
        "telephone": "0622073449",
        "nom": "El Omari ",
        "prenom": "Mohamed",
        "numeroTuteur": "",
        "services": [...],
        "prixTotal": 2050,
        "totalPaye": 2050,
        "montantNetPaye": 2050,
        ...
      },
      "0612345678": null
    }
  }
  ```
  - `data` : objet dont les clés sont les `tel` envoyés.
  - Valeur : objet client (comme l’exemple) si client, sinon `null`.

Si l’endpoint n’existe pas ou renvoie une erreur, le nouveau backend retourne pour chaque `tel` une entrée `null` (sans faire échouer l’appel admin).

## Comment tester

1. **Dev** : Démarrer **e-tawjihi** (ancien système) sur le port **8000** et **E-TAWJIHI-BACKEND** sur le port **8001**.
2. **Backend** : `php bin/console app:test-check-old-clients 0614369090` (remplacer par un numéro client connu).
3. **Ancien système (curl)** : `curl -X POST http://127.0.0.1:8000/api/check-clients -H "Content-Type: application/json" -d '{"tel":["0614369090"]}'`
4. **Prod** : Remplacer les URLs par `https://old.e-tawjihi.ma` et `https://apinew.e-tawjihi.ma`.
