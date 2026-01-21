<?php

namespace App\Command;

use App\Entity\Metier;
use App\Entity\Secteur;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:add-new-secteurs',
    description: 'Ajoute les nouveaux secteurs et leurs métiers sans supprimer les données existantes'
)]
class AddNewSecteursCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Ajout des nouveaux secteurs et métiers');

        $secteursRepository = $this->entityManager->getRepository(Secteur::class);
        $secteursData = $this->getSecteursData();

        $addedSecteurs = 0;
        $addedMetiers = 0;

        foreach ($secteursData as $secteurData) {
            // Vérifier si le secteur existe déjà
            $existingSecteur = $secteursRepository->findOneBy(['code' => $secteurData['code']]);
            
            if ($existingSecteur) {
                $io->warning(sprintf('Le secteur "%s" existe déjà, passage au suivant...', $secteurData['titre']));
                continue;
            }

            // Créer le secteur
            $secteur = new Secteur();
            $secteur->setTitre($secteurData['titre']);
            $secteur->setCode($secteurData['code']);
            $secteur->setDescription($secteurData['description'] ?? '');
            $secteur->setIcon($secteurData['icon'] ?? null);
            $secteur->setImage($secteurData['image'] ?? null);
            $secteur->setSoftSkills($secteurData['softSkills'] ?? []);
            $secteur->setPersonnalites($secteurData['personnalites'] ?? []);
            $secteur->setBacs($secteurData['bacs'] ?? []);
            $secteur->setTypeBacs($secteurData['typeBacs'] ?? []);
            $secteur->setAvantages($secteurData['avantages'] ?? []);
            $secteur->setInconvenients($secteurData['inconvenients'] ?? []);
            $secteur->setMetiers($secteurData['metiers'] ?? []);
            $secteur->setSalaireMin($secteurData['salaireMin'] ?? null);
            $secteur->setSalaireMax($secteurData['salaireMax'] ?? null);
            $secteur->setIsActivate($secteurData['isActivate'] ?? true);
            $secteur->setStatus($secteurData['status'] ?? 'Actif');
            $secteur->setIsComplet($secteurData['isComplet'] ?? false);

            $this->entityManager->persist($secteur);
            $addedSecteurs++;

            // Ajouter les métiers associés
            if (isset($secteurData['metiersList']) && is_array($secteurData['metiersList'])) {
                foreach ($secteurData['metiersList'] as $metierData) {
                    $metier = new Metier();
                    $metier->setNom($metierData['nom']);
                    $metier->setNomArabe($metierData['nomArabe'] ?? null);
                    
                    // Générer le slug
                    $slug = strtolower($this->slugger->slug($metierData['nom'])->toString());
                    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
                    $slug = trim($slug, '-');
                    
                    // Vérifier l'unicité du slug
                    $baseSlug = $slug;
                    $counter = 1;
                    while ($this->entityManager->getRepository(Metier::class)->findOneBy(['slug' => $slug])) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }
                    $metier->setSlug($slug);
                    
                    $metier->setSecteur($secteur);
                    $metier->setDescription($metierData['description'] ?? null);
                    $metier->setNiveauAccessibilite($metierData['niveauAccessibilite'] ?? 'Moyenne');
                    $metier->setSalaireMin($metierData['salaireMin'] ?? null);
                    $metier->setSalaireMax($metierData['salaireMax'] ?? null);
                    $metier->setCompetences($metierData['competences'] ?? []);
                    $metier->setFormations($metierData['formations'] ?? []);
                    $metier->setIsActivate($metierData['isActivate'] ?? true);
                    $metier->setAfficherDansTest($metierData['afficherDansTest'] ?? true);

                    $this->entityManager->persist($metier);
                    $addedMetiers++;
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Ajout terminé : %d secteurs et %d métiers ajoutés avec succès !',
            $addedSecteurs,
            $addedMetiers
        ));

        return Command::SUCCESS;
    }

    private function getSecteursData(): array
    {
        return [
            [
                'titre' => 'Environnement & Développement durable',
                'code' => 'ENVIRONNEMENT',
                'description' => 'Secteur dédié à la protection de l\'environnement, au développement durable et à la gestion des ressources naturelles.',
                'icon' => 'Leaf',
                'softSkills' => ['Sensibilité environnementale', 'Rigueur', 'Analyse', 'Communication', 'Travail d\'équipe'],
                'personnalites' => ['Écologiste', 'Méthodique', 'Responsable', 'Innovant'],
                'bacs' => ['Sciences de la Vie et de la Terre', 'Sciences Mathématiques', 'Sciences Expérimentales'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur en pleine croissance',
                    'Impact positif sur la société',
                    'Diversité des métiers',
                    'Perspectives d\'emploi excellentes'
                ],
                'inconvenients' => [
                    'Formation parfois longue',
                    'Salaire variable selon le poste'
                ],
                'metiers' => [
                    'Ingénieur environnement',
                    'Écologue',
                    'Chargé de mission développement durable',
                    'Gestionnaire de déchets',
                    'Conseiller en énergie',
                    'Technicien de traitement des eaux',
                    'Animateur nature',
                    'Ingénieur en énergies renouvelables'
                ],
                'metiersList' => [
                    ['nom' => 'Ingénieur environnement', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '15000'],
                    ['nom' => 'Écologue', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Chargé de mission développement durable', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '13000'],
                    ['nom' => 'Gestionnaire de déchets', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '10000'],
                    ['nom' => 'Conseiller en énergie', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Technicien de traitement des eaux', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '5500', 'salaireMax' => '11000'],
                    ['nom' => 'Animateur nature', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '4000', 'salaireMax' => '8000'],
                    ['nom' => 'Ingénieur en énergies renouvelables', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '9000', 'salaireMax' => '18000']
                ],
                'salaireMin' => '4000',
                'salaireMax' => '18000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Bâtiment, Architecture & Travaux Publics',
                'code' => 'BATIMENT',
                'description' => 'Secteur de la construction, de l\'architecture et des travaux publics, alliant créativité et technique.',
                'icon' => 'Building',
                'softSkills' => ['Créativité', 'Précision', 'Gestion de projet', 'Travail d\'équipe', 'Rigueur'],
                'personnalites' => ['Créatif', 'Méthodique', 'Organisé', 'Innovant'],
                'bacs' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur stable et en croissance',
                    'Diversité des métiers',
                    'Bonnes perspectives d\'emploi',
                    'Salaire attractif'
                ],
                'inconvenients' => [
                    'Travail parfois physique',
                    'Horaires variables'
                ],
                'metiers' => [
                    'Architecte',
                    'Ingénieur BTP',
                    'Géomètre-topographe',
                    'Chef de chantier',
                    'Conducteur de travaux',
                    'Dessinateur-projeteur',
                    'Métreur',
                    'Ingénieur structures'
                ],
                'metiersList' => [
                    ['nom' => 'Architecte', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '20000'],
                    ['nom' => 'Ingénieur BTP', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '18000'],
                    ['nom' => 'Géomètre-topographe', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Chef de chantier', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '15000'],
                    ['nom' => 'Conducteur de travaux', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '20000'],
                    ['nom' => 'Dessinateur-projeteur', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Métreur', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '13000'],
                    ['nom' => 'Ingénieur structures', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '10000', 'salaireMax' => '22000']
                ],
                'salaireMin' => '6000',
                'salaireMax' => '22000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Industrie & Production',
                'code' => 'INDUSTRIE',
                'description' => 'Secteur de l\'industrie manufacturière, de la production et de la transformation des matières premières.',
                'icon' => 'Factory',
                'softSkills' => ['Rigueur', 'Organisation', 'Travail d\'équipe', 'Adaptabilité', 'Précision'],
                'personnalites' => ['Méthodique', 'Organisé', 'Rigoureux', 'Innovant'],
                'bacs' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur stable',
                    'Diversité des postes',
                    'Évolution de carrière possible',
                    'Formation continue'
                ],
                'inconvenients' => [
                    'Horaires parfois contraignants',
                    'Environnement industriel'
                ],
                'metiers' => [
                    'Ingénieur de production',
                    'Responsable qualité',
                    'Technicien de maintenance',
                    'Chef d\'atelier',
                    'Ingénieur process',
                    'Contrôleur qualité',
                    'Technicien méthodes',
                    'Ingénieur industriel'
                ],
                'metiersList' => [
                    ['nom' => 'Ingénieur de production', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '16000'],
                    ['nom' => 'Responsable qualité', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '18000'],
                    ['nom' => 'Technicien de maintenance', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Chef d\'atelier', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Ingénieur process', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8500', 'salaireMax' => '17000'],
                    ['nom' => 'Contrôleur qualité', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5500', 'salaireMax' => '11000'],
                    ['nom' => 'Technicien méthodes', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '13000'],
                    ['nom' => 'Ingénieur industriel', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '19000']
                ],
                'salaireMin' => '5500',
                'salaireMax' => '19000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Agriculture & Agroalimentaire',
                'code' => 'AGRICULTURE',
                'description' => 'Secteur de l\'agriculture, de l\'agroalimentaire et de la transformation des produits agricoles.',
                'icon' => 'Wheat',
                'softSkills' => ['Patience', 'Rigueur', 'Sens pratique', 'Adaptabilité', 'Gestion'],
                'personnalites' => ['Pratique', 'Méthodique', 'Responsable', 'Innovant'],
                'bacs' => ['Sciences de la Vie et de la Terre', 'Sciences Expérimentales', 'Sciences et Technologies'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur essentiel',
                    'Diversité des métiers',
                    'Contact avec la nature',
                    'Innovation constante'
                ],
                'inconvenients' => [
                    'Travail saisonnier parfois',
                    'Dépendance aux conditions climatiques'
                ],
                'metiers' => [
                    'Ingénieur agronome',
                    'Vétérinaire',
                    'Technicien agricole',
                    'Ingénieur agroalimentaire',
                    'Contrôleur qualité alimentaire',
                    'Gestionnaire d\'exploitation',
                    'Conseiller agricole',
                    'Chercheur en agriculture'
                ],
                'metiersList' => [
                    ['nom' => 'Ingénieur agronome', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '15000'],
                    ['nom' => 'Vétérinaire', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Technicien agricole', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '10000'],
                    ['nom' => 'Ingénieur agroalimentaire', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '16000'],
                    ['nom' => 'Contrôleur qualité alimentaire', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Gestionnaire d\'exploitation', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Conseiller agricole', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '13000'],
                    ['nom' => 'Chercheur en agriculture', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '9000', 'salaireMax' => '17000']
                ],
                'salaireMin' => '5000',
                'salaireMax' => '18000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Culture, Événementiel & Spectacle',
                'code' => 'CULTURE',
                'description' => 'Secteur de la culture, des arts, de l\'événementiel et du spectacle vivant.',
                'icon' => 'Palette',
                'softSkills' => ['Créativité', 'Communication', 'Organisation', 'Polyvalence', 'Réseau'],
                'personnalites' => ['Créatif', 'Extraverti', 'Passionné', 'Innovant'],
                'bacs' => ['Lettres', 'Arts', 'Sciences Humaines', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur passionnant',
                    'Diversité des métiers',
                    'Contact avec le public',
                    'Créativité valorisée'
                ],
                'inconvenients' => [
                    'Précarité parfois',
                    'Horaires irréguliers',
                    'Concurrence importante'
                ],
                'metiers' => [
                    'Organisateur d\'événements',
                    'Régisseur',
                    'Médiateur culturel',
                    'Animateur culturel',
                    'Commissaire d\'exposition',
                    'Chargé de communication culturelle',
                    'Technicien spectacle',
                    'Programmateur culturel'
                ],
                'metiersList' => [
                    ['nom' => 'Organisateur d\'événements', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '15000'],
                    ['nom' => 'Régisseur', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Médiateur culturel', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '5500', 'salaireMax' => '11000'],
                    ['nom' => 'Animateur culturel', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '10000'],
                    ['nom' => 'Commissaire d\'exposition', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '16000'],
                    ['nom' => 'Chargé de communication culturelle', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '13000'],
                    ['nom' => 'Technicien spectacle', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Programmateur culturel', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000']
                ],
                'salaireMin' => '5000',
                'salaireMax' => '16000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Sciences Humaines & Sociales',
                'code' => 'SHS',
                'description' => 'Secteur des sciences humaines et sociales, de la recherche, de l\'enseignement et de l\'intervention sociale.',
                'icon' => 'Users',
                'softSkills' => ['Analyse', 'Empathie', 'Communication', 'Rigueur', 'Écoute'],
                'personnalites' => ['Réfléchi', 'Empathique', 'Curieux', 'Méthodique'],
                'bacs' => ['Lettres', 'Sciences Humaines', 'Sciences Économiques et Sociales', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur varié',
                    'Impact social',
                    'Diversité des métiers',
                    'Formation continue'
                ],
                'inconvenients' => [
                    'Salaire parfois modeste',
                    'Concurrence pour certains postes'
                ],
                'metiers' => [
                    'Sociologue',
                    'Psychologue',
                    'Anthropologue',
                    'Travailleur social',
                    'Éducateur spécialisé',
                    'Chercheur en sciences sociales',
                    'Conseiller en insertion',
                    'Médiateur social'
                ],
                'metiersList' => [
                    ['nom' => 'Sociologue', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '15000'],
                    ['nom' => 'Psychologue', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Anthropologue', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Travailleur social', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Éducateur spécialisé', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '13000'],
                    ['nom' => 'Chercheur en sciences sociales', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '8000', 'salaireMax' => '16000'],
                    ['nom' => 'Conseiller en insertion', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '12000'],
                    ['nom' => 'Médiateur social', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '13000']
                ],
                'salaireMin' => '6000',
                'salaireMax' => '18000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Défense, Sécurité & Métiers militaires',
                'code' => 'DEFENSE',
                'description' => 'Secteur de la défense, de la sécurité et des métiers militaires.',
                'icon' => 'Shield',
                'softSkills' => ['Discipline', 'Courage', 'Leadership', 'Rigueur', 'Esprit d\'équipe'],
                'personnalites' => ['Discipliné', 'Courageux', 'Loyal', 'Organisé'],
                'bacs' => ['Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Stabilité de l\'emploi',
                    'Formation continue',
                    'Évolution de carrière',
                    'Retraite anticipée'
                ],
                'inconvenients' => [
                    'Déplacements fréquents',
                    'Risques professionnels',
                    'Horaires contraignants'
                ],
                'metiers' => [
                    'Officier',
                    'Sous-officier',
                    'Gendarme',
                    'Policier',
                    'Agent de sécurité',
                    'Ingénieur défense',
                    'Technicien sécurité',
                    'Analyste sécurité'
                ],
                'metiersList' => [
                    ['nom' => 'Officier', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '10000', 'salaireMax' => '25000'],
                    ['nom' => 'Sous-officier', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Gendarme', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '20000'],
                    ['nom' => 'Policier', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8500', 'salaireMax' => '19000'],
                    ['nom' => 'Agent de sécurité', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '10000'],
                    ['nom' => 'Ingénieur défense', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '12000', 'salaireMax' => '25000'],
                    ['nom' => 'Technicien sécurité', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '14000'],
                    ['nom' => 'Analyste sécurité', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '20000']
                ],
                'salaireMin' => '5000',
                'salaireMax' => '25000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Jeux vidéo & Industries créatives numériques',
                'code' => 'JEUX_VIDEO',
                'description' => 'Secteur des jeux vidéo, de l\'animation numérique et des industries créatives numériques.',
                'icon' => 'Gamepad2',
                'softSkills' => ['Créativité', 'Innovation', 'Travail d\'équipe', 'Adaptabilité', 'Passion'],
                'personnalites' => ['Créatif', 'Innovant', 'Passionné', 'Technique'],
                'bacs' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Arts', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur en pleine croissance',
                    'Créativité valorisée',
                    'Innovation constante',
                    'Passion et plaisir au travail'
                ],
                'inconvenients' => [
                    'Concurrence importante',
                    'Horaires parfois chargés',
                    'Précarité pour certains postes'
                ],
                'metiers' => [
                    'Développeur de jeux vidéo',
                    'Game designer',
                    'Artiste 3D',
                    'Animateur 2D/3D',
                    'Programmeur gameplay',
                    'Level designer',
                    'Sound designer',
                    'Producteur de jeux vidéo'
                ],
                'metiersList' => [
                    ['nom' => 'Développeur de jeux vidéo', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '20000'],
                    ['nom' => 'Game designer', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '22000'],
                    ['nom' => 'Artiste 3D', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '18000'],
                    ['nom' => 'Animateur 2D/3D', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7500', 'salaireMax' => '19000'],
                    ['nom' => 'Programmeur gameplay', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8500', 'salaireMax' => '21000'],
                    ['nom' => 'Level designer', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '20000'],
                    ['nom' => 'Sound designer', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '17000'],
                    ['nom' => 'Producteur de jeux vidéo', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '25000']
                ],
                'salaireMin' => '7000',
                'salaireMax' => '25000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Sport & Métiers du sport',
                'code' => 'SPORT',
                'description' => 'Secteur du sport, de l\'animation sportive et des métiers liés à la pratique et à l\'encadrement sportif.',
                'icon' => 'Trophy',
                'softSkills' => ['Motivation', 'Leadership', 'Communication', 'Patience', 'Esprit d\'équipe'],
                'personnalites' => ['Sportif', 'Dynamique', 'Motivant', 'Passionné'],
                'bacs' => ['Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur passionnant',
                    'Contact avec les sportifs',
                    'Diversité des métiers',
                    'Épanouissement personnel'
                ],
                'inconvenients' => [
                    'Horaires irréguliers',
                    'Salaire variable',
                    'Carrière parfois courte'
                ],
                'metiers' => [
                    'Éducateur sportif',
                    'Entraîneur',
                    'Professeur d\'EPS',
                    'Kinésithérapeute du sport',
                    'Manager sportif',
                    'Journaliste sportif',
                    'Organisateur d\'événements sportifs',
                    'Coach personnel'
                ],
                'metiersList' => [
                    ['nom' => 'Éducateur sportif', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '12000'],
                    ['nom' => 'Entraîneur', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '20000'],
                    ['nom' => 'Professeur d\'EPS', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Kinésithérapeute du sport', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '20000'],
                    ['nom' => 'Manager sportif', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '30000'],
                    ['nom' => 'Journaliste sportif', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '15000'],
                    ['nom' => 'Organisateur d\'événements sportifs', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Coach personnel', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '6000', 'salaireMax' => '15000']
                ],
                'salaireMin' => '5000',
                'salaireMax' => '30000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Tourisme',
                'code' => 'TOURISME',
                'description' => 'Secteur du tourisme, de l\'accueil touristique et de l\'organisation de voyages. Un secteur dynamique qui contribue au développement économique et culturel des destinations.',
                'icon' => 'Plane',
                'softSkills' => ['Communication', 'Accueil', 'Organisation', 'Polyvalence', 'Adaptabilité', 'Langues étrangères'],
                'personnalites' => ['Sociable', 'Extraverti', 'Organisé', 'Passionné'],
                'bacs' => ['Lettres', 'Sciences Humaines', 'Sciences Économiques et Sociales', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur en croissance',
                    'Diversité des métiers',
                    'Voyages et découvertes',
                    'Contact avec différentes cultures',
                    'Opportunités internationales'
                ],
                'inconvenients' => [
                    'Saisonnalité',
                    'Horaires variables',
                    'Pression client',
                    'Concurrence importante'
                ],
                'metiers' => [
                    'Guide touristique',
                    'Agent de voyage',
                    'Conseiller en séjour',
                    'Animateur touristique',
                    'Responsable d\'office de tourisme',
                    'Organisateur de voyages',
                    'Accompagnateur de voyages',
                    'Chargé de promotion touristique',
                    'Réceptionniste hôtel',
                    'Concierge',
                    'Chef de produit tourisme',
                    'Consultant en tourisme'
                ],
                'metiersList' => [
                    ['nom' => 'Guide touristique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '5000', 'salaireMax' => '12000'],
                    ['nom' => 'Agent de voyage', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '14000'],
                    ['nom' => 'Conseiller en séjour', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '5500', 'salaireMax' => '13000'],
                    ['nom' => 'Animateur touristique', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '4500', 'salaireMax' => '10000'],
                    ['nom' => 'Responsable d\'office de tourisme', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '16000'],
                    ['nom' => 'Organisateur de voyages', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '15000'],
                    ['nom' => 'Accompagnateur de voyages', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '14000'],
                    ['nom' => 'Chargé de promotion touristique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '16000'],
                    ['nom' => 'Chef de produit tourisme', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Consultant en tourisme', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7500', 'salaireMax' => '20000']
                ],
                'salaireMin' => '4500',
                'salaireMax' => '20000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Pêche maritime',
                'code' => 'PECHE_MARITIME',
                'description' => 'Secteur de la pêche maritime, de l\'aquaculture et de la gestion des ressources halieutiques. Un secteur essentiel pour l\'économie maritime et la sécurité alimentaire.',
                'icon' => 'Anchor',
                'softSkills' => ['Rigueur', 'Endurance', 'Travail d\'équipe', 'Adaptabilité', 'Sens de l\'observation', 'Résistance physique'],
                'personnalites' => ['Courageux', 'Méthodique', 'Endurant', 'Responsable'],
                'bacs' => ['Sciences de la Vie et de la Terre', 'Sciences Expérimentales', 'Sciences et Technologies', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur traditionnel et essentiel',
                    'Contact avec la mer',
                    'Diversité des métiers',
                    'Opportunités dans l\'aquaculture',
                    'Secteur en évolution'
                ],
                'inconvenients' => [
                    'Conditions de travail difficiles',
                    'Horaires irréguliers',
                    'Risques professionnels',
                    'Dépendance aux conditions météorologiques',
                    'Saisonnalité'
                ],
                'metiers' => [
                    'Pêcheur',
                    'Capitaine de pêche',
                    'Marin pêcheur',
                    'Armateur',
                    'Technicien en aquaculture',
                    'Gestionnaire de pêcherie',
                    'Contrôleur des pêches',
                    'Technicien halieutique',
                    'Ingénieur halieutique',
                    'Responsable de production aquacole',
                    'Ouvrier aquacole',
                    'Technicien de maintenance navale'
                ],
                'metiersList' => [
                    ['nom' => 'Pêcheur', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '12000'],
                    ['nom' => 'Capitaine de pêche', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '25000'],
                    ['nom' => 'Marin pêcheur', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5500', 'salaireMax' => '13000'],
                    ['nom' => 'Armateur', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '12000', 'salaireMax' => '30000'],
                    ['nom' => 'Technicien en aquaculture', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6000', 'salaireMax' => '14000'],
                    ['nom' => 'Gestionnaire de pêcherie', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '16000'],
                    ['nom' => 'Contrôleur des pêches', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Technicien halieutique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '6500', 'salaireMax' => '15000'],
                    ['nom' => 'Ingénieur halieutique', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '9000', 'salaireMax' => '22000'],
                    ['nom' => 'Responsable de production aquacole', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8500', 'salaireMax' => '20000'],
                    ['nom' => 'Ouvrier aquacole', 'niveauAccessibilite' => 'Facile', 'salaireMin' => '5000', 'salaireMax' => '11000'],
                    ['nom' => 'Technicien de maintenance navale', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '16000']
                ],
                'salaireMin' => '5000',
                'salaireMax' => '30000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Sciences politiques',
                'code' => 'SCIENCES_POLITIQUES',
                'description' => 'Secteur des sciences politiques, de la gouvernance, des relations internationales et de l\'administration publique. Un secteur qui forme les futurs décideurs et acteurs du changement politique et social.',
                'icon' => 'Scale',
                'softSkills' => ['Analyse', 'Communication', 'Leadership', 'Rigueur', 'Diplomatie', 'Esprit critique'],
                'personnalites' => ['Analytique', 'Sociable', 'Méthodique', 'Engagé'],
                'bacs' => ['Lettres', 'Sciences Humaines', 'Sciences Économiques et Sociales', 'Toutes sections'],
                'typeBacs' => ['Bac Normal', 'Bac Mission'],
                'avantages' => [
                    'Secteur prestigieux',
                    'Diversité des métiers',
                    'Impact sur la société',
                    'Opportunités internationales',
                    'Évolution de carrière'
                ],
                'inconvenients' => [
                    'Concurrence importante',
                    'Formation exigeante',
                    'Pression médiatique',
                    'Responsabilités importantes'
                ],
                'metiers' => [
                    'Politologue',
                    'Diplomate',
                    'Conseiller politique',
                    'Analyste politique',
                    'Chargé de mission publique',
                    'Attaché parlementaire',
                    'Consultant en affaires publiques',
                    'Journaliste politique',
                    'Chercheur en sciences politiques',
                    'Enseignant en sciences politiques',
                    'Responsable de communication politique',
                    'Lobbyiste'
                ],
                'metiersList' => [
                    ['nom' => 'Politologue', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '20000'],
                    ['nom' => 'Diplomate', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '12000', 'salaireMax' => '30000'],
                    ['nom' => 'Conseiller politique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '25000'],
                    ['nom' => 'Analyste politique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '22000'],
                    ['nom' => 'Chargé de mission publique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Attaché parlementaire', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8500', 'salaireMax' => '20000'],
                    ['nom' => 'Consultant en affaires publiques', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '25000'],
                    ['nom' => 'Journaliste politique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '7000', 'salaireMax' => '18000'],
                    ['nom' => 'Chercheur en sciences politiques', 'niveauAccessibilite' => 'Difficile', 'salaireMin' => '9000', 'salaireMax' => '20000'],
                    ['nom' => 'Enseignant en sciences politiques', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '8000', 'salaireMax' => '18000'],
                    ['nom' => 'Responsable de communication politique', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '9000', 'salaireMax' => '22000'],
                    ['nom' => 'Lobbyiste', 'niveauAccessibilite' => 'Moyenne', 'salaireMin' => '10000', 'salaireMax' => '30000']
                ],
                'salaireMin' => '7000',
                'salaireMax' => '30000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ]
        ];
    }
}
