# Synchronisation mot de passe (mot de passe oublié)

Quand un utilisateur réinitialise son mot de passe via **mot de passe oublié** (ManyChat → `POST /api/mdp_oublie`), le backend met à jour le mot de passe ici puis envoie **téléphone + mot de passe** à l’autre backend (old.e-tawjihi.ma) pour qu’il fasse la même mise à jour.

Pas de clé API : une fois mis à jour ici, on envoie tel + mdp à l’autre.

## Configuration (backend appelé par ManyChat)

Dans `.env` :

```env
SYNC_PASSWORD_BACKEND_URL=https://old.e-tawjihi.ma
```

- L’endpoint appelé est `SYNC_PASSWORD_BACKEND_URL/api/sync_password` (ex. `https://old.e-tawjihi.ma/api/sync_password`).
- Laisser vide pour désactiver la synchronisation.

## API de réception (old.e-tawjihi.ma)

- **Route** : `POST /api/sync_password`
- **Body JSON** :
  - `phone` (string) : numéro (local ou international, normalisé côté réception).
  - `password` (string) : nouveau mot de passe en clair.

Réponses :

- `200` : `{ "success": true }` — mot de passe mis à jour.
- `400` : `phone` ou `password` manquant.
- `404` : utilisateur non trouvé pour ce `phone`.

## Comportement

- La synchronisation est **non bloquante** : si l’appel vers l’autre backend échoue, la réponse de `mdp_oublie` reste inchangée et le mot de passe a bien été mis à jour localement. Les erreurs sont loguées.
- Utiliser HTTPS entre les deux backends.
