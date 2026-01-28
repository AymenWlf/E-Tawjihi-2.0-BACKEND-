# E-TAWJIHI Backend - Symfony 6.4

Backend API pour la plateforme E-TAWJIHI (orientation & admissions au Maroc).

## 🚀 Installation

```bash
# Installer les dépendances
symfony composer install

# Créer la base de données
symfony console doctrine:database:create

# Exécuter les migrations
symfony console doctrine:migrations:migrate

# Créer un utilisateur de test (optionnel)
symfony console make:user
```

## 📋 Configuration

### CORS
Le backend est configuré pour accepter les requêtes depuis :
- `http://localhost:5173`
- `http://localhost:5174`
- `https://localhost:5173`
- `https://localhost:5174`

Configuration dans `config/packages/nelmio_cors.yaml`

### Security
- Entité User créée avec email/password
- Provider configuré pour authentification par email
- JSON Login activé sur `/api/login`
- Logout sur `/api/logout`

## 🔐 Authentification

### Endpoints API

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

#### Logout
```http
POST /api/logout
```

## 📦 Bundles installés

- `symfony/security-bundle` - Authentification et sécurité
- `symfony/maker-bundle` - Génération de code
- `nelmio/cors-bundle` - Gestion CORS

## 🗄️ Base de données

L'entité `User` est prête avec :
- `id` (auto)
- `email` (unique)
- `password` (hashé)
- `roles` (array)

## 🔧 Prochaines étapes

1. Créer les entités métier (Écoles, Filières, Tests, etc.)
2. Créer les contrôleurs API
3. Implémenter JWT pour l'authentification stateless (optionnel)
4. Ajouter la validation des données
5. Créer les services métier

## 📝 Notes

- Le projet utilise Symfony 6.4
- PHP 8.1+ requis
- Base de données configurée via Doctrine


# E-Tawjihi-2.0-BACKEND-
