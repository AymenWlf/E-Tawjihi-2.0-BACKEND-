# Instructions pour l’autre système (old.e-tawjihi.ma)

Lorsqu’un utilisateur fait **« Mot de passe oublié »** via ManyChat, le nouveau backend (e-tawjihi.ma) met à jour le mot de passe puis envoie **téléphone + mot de passe** à **old.e-tawjihi.ma** pour que le même utilisateur ait le même mot de passe sur l’ancien système.

Voici ce qu’il faut faire côté **old.e-tawjihi.ma** pour recevoir cette synchronisation.

---

## Option A : Même codebase E-TAWJIHI-BACKEND (Symfony) sur old.e-tawjihi.ma

Si old.e-tawjihi.ma utilise le même projet **E-TAWJIHI-BACKEND** (Symfony) :

### 1. Déployer la dernière version du code

- S’assurer que le code contient la route `POST /api/sync_password` (dans `AuthController`).
- Déployer / pull comme d’habitude.

### 2. Vérifier la sécurité (route publique)

- Dans `config/packages/security.yaml`, la route doit être en accès public, par exemple :
  ```yaml
  access_control:
      - { path: ^/api/sync_password, roles: PUBLIC_ACCESS }
  ```
- Pas de JWT ni autre auth sur cette route.

### 3. Vérifier Nginx / reverse proxy

- Les requêtes `POST /api/sync_password` doivent arriver jusqu’à l’application Symfony (même config que les autres routes `/api/`).
- Exemple Nginx :
  ```nginx
  location /api {
      proxy_pass http://127.0.0.1:8001;   # ou le port de PHP-FPM/Symfony
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
  }
  ```

### 4. Aucune variable d’environnement spécifique

- Pas besoin de `SYNC_PASSWORD_BACKEND_URL` ni de clé sur **old.e-tawjihi.ma**.
- Il suffit que l’URL `https://old.e-tawjihi.ma/api/sync_password` soit joignable en POST depuis le nouveau backend.

### 5. Tester l’endpoint

```bash
curl -X POST https://old.e-tawjihi.ma/api/sync_password \
  -H "Content-Type: application/json" \
  -d '{"phone":"0612345678","password":"TestPassword123!"}'
```

- Si l’utilisateur existe : réponse `200` avec `{"success":true}`.
- Si utilisateur inconnu : `404` avec `{"success":false,"message":"User not found"}`.

---

## Option B : Autre stack (Laravel, autre Symfony, etc.)

Si old.e-tawjihi.ma est un **autre projet** (Laravel, autre Symfony, API custom), il faut **exposer un endpoint équivalent** qui :

1. Accepte **POST** avec un body JSON : `{"phone": "...", "password": "..."}`.
2. Normalise le numéro de téléphone comme suit :
   - Supprimer tout sauf les chiffres.
   - Si le numéro est en 212/00212 + 9 chiffres (5, 6 ou 7 au début), le convertir en format local `0XXXXXXXX`.
   - Sinon garder le numéro tel quel.
3. Cherche l’utilisateur par **téléphone** (champ `phone` / `tel` selon votre BDD).
4. Si trouvé : hash le mot de passe (même algorithme que pour le login, ex. bcrypt) et enregistre en base.
5. Répond en JSON :
   - **200** : `{"success": true}`.
   - **400** : `{"success": false, "message": "phone and password are required"}` si body invalide.
   - **404** : `{"success": false, "message": "User not found"}` si aucun utilisateur pour ce téléphone.

### Exemple de contrat d’API

| Élément | Valeur |
|--------|--------|
| URL | `https://old.e-tawjihi.ma/api/sync_password` (ou le chemin que vous exposez) |
| Méthode | `POST` |
| Header | `Content-Type: application/json` |
| Body | `{"phone": "0612345678", "password": "nouveau_mot_de_passe_en_clair"}` |

Réponses possibles :

- `200` → `{"success": true}`
- `400` → `{"success": false, "message": "phone and password are required"}`
- `404` → `{"success": false, "message": "User not found"}`

### Normalisation du numéro (à reproduire côté old)

- Supprimer tout caractère non numérique.
- Si le résultat matche `^(212|00212)?([5-7]\d{8})$` → utiliser `0` + les 9 derniers chiffres (ex. `0612345678`).
- Sinon utiliser le numéro nettoyé tel quel pour la recherche en base.

---

## Résumé

| Côté | Rôle |
|------|------|
| **Nouveau backend (e-tawjihi.ma)** | Reçoit ManyChat → met à jour le mdp → envoie POST `phone` + `password` vers old.e-tawjihi.ma. |
| **Ancien système (old.e-tawjihi.ma)** | Expose `POST /api/sync_password`, reçoit `phone` + `password`, met à jour l’utilisateur correspondant et répond `200` ou `404`. |

Aucune clé à configurer sur l’autre système : il suffit que l’endpoint soit déployé, public (sans auth), et que la logique ci-dessus soit respectée.
