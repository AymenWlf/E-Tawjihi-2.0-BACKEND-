#!/bin/bash

# Script de setup pour le système d'établissements
# Usage: ./setup-establishments.sh

echo "🏫 Setup du système d'établissements E-TAWJIHI"
echo "=============================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Vérifier que nous sommes dans le bon dossier
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Erreur: composer.json non trouvé${NC}"
    echo "Veuillez exécuter ce script depuis la racine du projet backend"
    exit 1
fi

echo -e "${BLUE}📦 1. Installation des dépendances...${NC}"
composer install
echo ""

echo -e "${BLUE}🗄️  2. Création de la base de données (si nécessaire)...${NC}"
php bin/console doctrine:database:create --if-not-exists
echo ""

echo -e "${BLUE}📝 3. Génération de la migration...${NC}"
php bin/console make:migration
echo ""

echo -e "${YELLOW}⚠️  Veuillez vérifier le fichier de migration généré dans migrations/...${NC}"
read -p "Appuyez sur Entrée pour continuer avec l'application de la migration..."
echo ""

echo -e "${BLUE}🚀 4. Application de la migration...${NC}"
php bin/console doctrine:migrations:migrate --no-interaction
echo ""

echo -e "${BLUE}🌱 5. Chargement des données d'exemple (fixtures)...${NC}"
echo "   - EMSI (École Privée)"
echo "   - EST Casablanca (École Publique)"
echo "   - ERSSM (École Militaire)"
php bin/console doctrine:fixtures:load --no-interaction
echo -e "${GREEN}✓ 3 établissements d'exemple ajoutés${NC}"
echo ""

echo -e "${BLUE}📁 6. Création des dossiers d'upload...${NC}"
mkdir -p public/uploads/logos
mkdir -p public/uploads/covers
mkdir -p public/uploads/brochures
mkdir -p public/uploads/general
chmod -R 755 public/uploads
echo -e "${GREEN}✓ Dossiers créés avec succès${NC}"
echo ""

echo -e "${BLUE}🔧 7. Vider le cache...${NC}"
php bin/console cache:clear
echo ""

echo -e "${BLUE}📋 8. Vérification des routes API...${NC}"
php bin/console debug:router | grep -E "(establishment|upload)"
echo ""

echo -e "${GREEN}✅ Setup terminé avec succès !${NC}"
echo ""
echo "📚 Prochaines étapes:"
echo "  1. Démarrer le serveur: symfony serve -d  (ou php -S localhost:8001 -t public)"
echo "  2. Tester l'API: curl http://localhost:8001/api/establishments"
echo "  3. Consulter la documentation: ../documentations/BACKEND_FRONTEND_ESTABLISHMENTS.md"
echo ""
echo "🎯 Endpoints disponibles:"
echo "  GET    /api/establishments              Liste"
echo "  GET    /api/establishments/{id}         Détail"
echo "  POST   /api/establishments              Créer"
echo "  PUT    /api/establishments/{id}         Modifier"
echo "  DELETE /api/establishments/{id}         Supprimer"
echo "  POST   /api/establishments/bulk         Actions en masse"
echo "  POST   /api/upload/file                 Upload fichier"
echo ""
