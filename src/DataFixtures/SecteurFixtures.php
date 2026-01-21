<?php

namespace App\DataFixtures;

use App\Entity\Secteur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SecteurFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $secteurs = $this->getSecteursData();

        foreach ($secteurs as $secteurData) {
            $secteur = new Secteur();
            $secteur->setTitre($secteurData['titre']);
            $secteur->setCode($secteurData['code']);
            $secteur->setDescription($secteurData['description']);
            $secteur->setIcon($secteurData['icon']);
            $secteur->setImage($secteurData['image'] ?? null);
            $secteur->setSoftSkills($secteurData['softSkills']);
            $secteur->setPersonnalites($secteurData['personnalites']);
            $secteur->setBacs($secteurData['bacs']);
            $secteur->setTypeBacs($secteurData['typeBacs']);
            $secteur->setAvantages($secteurData['avantages']);
            $secteur->setInconvenients($secteurData['inconvenients']);
            $secteur->setMetiers($secteurData['metiers']);
            $secteur->setSalaireMin($secteurData['salaireMin']);
            $secteur->setSalaireMax($secteurData['salaireMax']);
            $secteur->setIsActivate($secteurData['isActivate']);
            $secteur->setStatus($secteurData['status']);
            $secteur->setIsComplet($secteurData['isComplet']);

            $manager->persist($secteur);
        }

        $manager->flush();
    }

    private function getSecteursData(): array
    {
        return [
            [
                'titre' => 'Santé',
                'code' => 'SANTE',
                'description' => '<p>Le secteur de la santé regroupe tous les métiers liés aux soins médicaux, à la prévention, au bien-être et à la recherche médicale. C\'est un secteur en constante évolution qui offre de nombreuses opportunités professionnelles.</p><p>Les professionnels de la santé travaillent dans des hôpitaux, cliniques, cabinets privés, laboratoires, pharmacies et centres de recherche. Ils contribuent à améliorer la qualité de vie des patients et à préserver leur santé.</p>',
                'icon' => 'health',
                'image' => 'https://cdn-icons-png.flaticon.com/512/2965/2965879.png',
                'softSkills' => ['empathie', 'communication', 'gestion-stress', 'rigueur', 'patience'],
                'personnalites' => ['sociable', 'analytique', 'methodique'],
                'bacs' => ['scientifique', 'lettres'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Sens du service et de l\'aide aux autres',
                    'Stabilité de l\'emploi',
                    'Évolution de carrière possible',
                    'Diversité des métiers',
                    'Reconnaissance sociale'
                ],
                'inconvenients' => [
                    'Horaires parfois contraignants',
                    'Stress et responsabilités importantes',
                    'Formation longue et exigeante',
                    'Contact avec la souffrance'
                ],
                'metiers' => [
                    'Médecin généraliste',
                    'Médecin spécialiste',
                    'Chirurgien',
                    'Infirmier(ère)',
                    'Pharmacien(ne)',
                    'Kinésithérapeute',
                    'Sage-femme',
                    'Dentiste',
                    'Vétérinaire',
                    'Psychologue',
                    'Diététicien(ne)',
                    'Optométriste',
                    'Radiologue',
                    'Anesthésiste',
                    'Pédiatre'
                ],
                'salaireMin' => '5000',
                'salaireMax' => '15000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Technologie',
                'code' => 'TECH',
                'description' => '<p>Le secteur de la technologie regroupe l\'informatique, l\'ingénierie et l\'innovation technologique. Un secteur en pleine expansion avec de nombreuses opportunités dans le développement logiciel, la cybersécurité, l\'intelligence artificielle et les nouvelles technologies.</p><p>Les professionnels de la tech travaillent dans des entreprises technologiques, des startups, des agences digitales ou en freelance. Ils contribuent à la transformation digitale et à l\'innovation.</p>',
                'icon' => 'tech',
                'image' => 'https://cdn-icons-png.flaticon.com/512/2103/2103633.png',
                'softSkills' => ['creativite', 'adaptabilite', 'travail-equipe', 'autonomie', 'rigueur'],
                'personnalites' => ['analytique', 'creatif', 'independant'],
                'bacs' => ['scientifique', 'technique'],
                'typeBacs' => ['normal', 'mission', 'international'],
                'avantages' => [
                    'Salaires attractifs',
                    'Innovation constante',
                    'Télétravail possible',
                    'Évolution rapide',
                    'Opportunités internationales'
                ],
                'inconvenients' => [
                    'Formation continue nécessaire',
                    'Concurrence importante',
                    'Technologies qui évoluent rapidement',
                    'Pression des deadlines'
                ],
                'metiers' => [
                    'Développeur web',
                    'Développeur mobile',
                    'Ingénieur logiciel',
                    'Data scientist',
                    'Cybersécurité',
                    'DevOps',
                    'Architecte logiciel',
                    'Product manager',
                    'UX/UI Designer',
                    'Chef de projet IT',
                    'Ingénieur réseau',
                    'Administrateur système',
                    'Testeur logiciel',
                    'Consultant IT',
                    'Blockchain developer'
                ],
                'salaireMin' => '8000',
                'salaireMax' => '25000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Éducation',
                'code' => 'EDUC',
                'description' => '<p>Le secteur de l\'éducation regroupe l\'enseignement et la formation. Un secteur noble qui contribue à l\'épanouissement des générations futures et au développement des compétences.</p><p>Les professionnels de l\'éducation travaillent dans des écoles, collèges, lycées, universités, centres de formation ou en tant que formateurs indépendants. Ils transmettent le savoir et accompagnent les apprenants.</p>',
                'icon' => 'education',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
                'softSkills' => ['patience', 'communication', 'empathie', 'organisation', 'pedagogie'],
                'personnalites' => ['sociable', 'methodique', 'intuitif'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Vacances scolaires',
                    'Sens de la mission',
                    'Stabilité',
                    'Contact avec les jeunes',
                    'Évolution possible'
                ],
                'inconvenients' => [
                    'Salaires parfois modestes',
                    'Charge de travail importante',
                    'Gestion de classe difficile',
                    'Pression des résultats'
                ],
                'metiers' => [
                    'Professeur des écoles',
                    'Professeur de collège/lycée',
                    'Professeur d\'université',
                    'Formateur',
                    'Conseiller pédagogique',
                    'Directeur d\'établissement',
                    'Éducateur spécialisé',
                    'Animateur socio-éducatif',
                    'Conseiller d\'orientation',
                    'Documentaliste',
                    'Inspecteur de l\'éducation'
                ],
                'salaireMin' => '4000',
                'salaireMax' => '12000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Finance',
                'code' => 'FIN',
                'description' => '<p>Le secteur de la finance regroupe la banque, l\'assurance et la gestion financière. Un secteur stratégique pour l\'économie qui offre des opportunités dans la gestion de patrimoine, l\'analyse financière et le conseil.</p><p>Les professionnels de la finance travaillent dans des banques, assurances, cabinets d\'audit, sociétés de gestion ou en tant que conseillers financiers indépendants.</p>',
                'icon' => 'finance',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135807.png',
                'softSkills' => ['rigueur', 'analytique', 'organisation', 'gestion-stress', 'discretion'],
                'personnalites' => ['methodique', 'analytique', 'independant'],
                'bacs' => ['economique', 'scientifique'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Salaires élevés',
                    'Évolution rapide',
                    'Prestige',
                    'Opportunités internationales',
                    'Stabilité'
                ],
                'inconvenients' => [
                    'Pression importante',
                    'Horaires chargés',
                    'Responsabilités financières',
                    'Concurrence forte'
                ],
                'metiers' => [
                    'Banquier',
                    'Conseiller financier',
                    'Analyste financier',
                    'Comptable',
                    'Auditeur',
                    'Gestionnaire de patrimoine',
                    'Courtier en assurance',
                    'Trader',
                    'Risk manager',
                    'Contrôleur de gestion',
                    'Directeur financier',
                    'Expert-comptable'
                ],
                'salaireMin' => '10000',
                'salaireMax' => '30000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => false
            ],
            [
                'titre' => 'Juridique',
                'code' => 'JUR',
                'description' => '<p>Le secteur juridique regroupe le droit et la justice. Un secteur qui défend les droits, la justice et accompagne les entreprises et les particuliers dans leurs démarches légales.</p><p>Les professionnels du droit travaillent dans des cabinets d\'avocats, entreprises, administrations, tribunaux ou en tant que juristes indépendants.</p>',
                'icon' => 'legal',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135789.png',
                'softSkills' => ['rigueur', 'communication', 'analytique', 'autonomie', 'discretion'],
                'personnalites' => ['analytique', 'methodique', 'sociable'],
                'bacs' => ['lettres', 'economique'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Prestige',
                    'Diversité des métiers',
                    'Indépendance possible',
                    'Reconnaissance sociale',
                    'Évolution de carrière'
                ],
                'inconvenients' => [
                    'Formation longue',
                    'Concurrence forte',
                    'Charge de travail importante',
                    'Stress des dossiers'
                ],
                'metiers' => [
                    'Avocat',
                    'Notaire',
                    'Huissier de justice',
                    'Juriste d\'entreprise',
                    'Juge',
                    'Procureur',
                    'Greffier',
                    'Conseiller juridique',
                    'Expert judiciaire',
                    'Médiateur',
                    'Commissaire-priseur'
                ],
                'salaireMin' => '6000',
                'salaireMax' => '20000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Arts et Créatif',
                'code' => 'ARTS',
                'description' => '<p>Le secteur des arts et du créatif regroupe le design, les arts visuels et la créativité. Un secteur qui valorise l\'expression artistique et la création visuelle.</p><p>Les professionnels créatifs travaillent dans des agences, studios, entreprises ou en tant qu\'artistes indépendants. Ils créent des visuels, designs, illustrations et œuvres artistiques.</p>',
                'icon' => 'arts',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135810.png',
                'softSkills' => ['creativite', 'intuition', 'adaptabilite', 'autonomie', 'perseverance'],
                'personnalites' => ['creatif', 'intuitif', 'independant'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Liberté créative',
                    'Passion',
                    'Diversité',
                    'Expression personnelle',
                    'Projets variés'
                ],
                'inconvenients' => [
                    'Précarité possible',
                    'Concurrence importante',
                    'Revenus irréguliers',
                    'Deadlines serrés'
                ],
                'metiers' => [
                    'Graphiste',
                    'Designer UX/UI',
                    'Illustrateur',
                    'Photographe',
                    'Vidéaste',
                    'Architecte d\'intérieur',
                    'Styliste',
                    'Artiste plasticien',
                    'Directeur artistique',
                    'Motion designer',
                    'Web designer',
                    'Dessinateur',
                    'Sculpteur',
                    'Peintre'
                ],
                'salaireMin' => '3000',
                'salaireMax' => '15000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => false
            ],
            [
                'titre' => 'Communication et Médias',
                'code' => 'COMM',
                'description' => '<p>Le secteur de la communication et des médias regroupe le journalisme, la communication et les médias. Un secteur qui informe, connecte et influence l\'opinion publique.</p><p>Les professionnels de la communication travaillent dans des médias, agences de communication, entreprises, institutions ou en tant que consultants indépendants.</p>',
                'icon' => 'media',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135823.png',
                'softSkills' => ['communication', 'creativite', 'adaptabilite', 'travail-equipe', 'resilience'],
                'personnalites' => ['extraverti', 'creatif', 'sociable'],
                'bacs' => ['lettres', 'economique'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Diversité',
                    'Innovation',
                    'Réseaux',
                    'Visibilité',
                    'Créativité'
                ],
                'inconvenients' => [
                    'Précarité',
                    'Pression médiatique',
                    'Horaires décalés',
                    'Concurrence'
                ],
                'metiers' => [
                    'Journaliste',
                    'Rédacteur web',
                    'Community manager',
                    'Attaché de presse',
                    'Chargé de communication',
                    'Animateur radio/TV',
                    'Réalisateur',
                    'Monteur vidéo',
                    'Photographe de presse',
                    'Régisseur',
                    'Producteur',
                    'Scénariste'
                ],
                'salaireMin' => '4000',
                'salaireMax' => '12000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Commerce et Vente',
                'code' => 'COMMERCE',
                'description' => '<p>Le secteur du commerce et de la vente regroupe la vente, le commerce et la distribution. Un secteur dynamique au cœur de l\'économie qui offre de nombreuses opportunités dans le retail, la vente B2B et le e-commerce.</p><p>Les professionnels du commerce travaillent dans des magasins, entreprises, e-commerce, centres commerciaux ou en tant que commerciaux indépendants.</p>',
                'icon' => 'commerce',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135812.png',
                'softSkills' => ['communication', 'persuasion', 'resilience', 'travail-equipe', 'adaptabilite'],
                'personnalites' => ['extraverti', 'sociable', 'independant'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Commissions possibles',
                    'Diversité',
                    'Évolution rapide',
                    'Contact client',
                    'Opportunités variées'
                ],
                'inconvenients' => [
                    'Pression des objectifs',
                    'Horaires variables',
                    'Revenus incertains',
                    'Concurrence'
                ],
                'metiers' => [
                    'Vendeur',
                    'Commercial',
                    'Responsable de magasin',
                    'Chef de rayon',
                    'Chargé de clientèle',
                    'Téléconseiller',
                    'E-commerçant',
                    'Représentant commercial',
                    'Négociateur',
                    'Chargé d\'affaires',
                    'Business developer',
                    'Key account manager'
                ],
                'salaireMin' => '3000',
                'salaireMax' => '20000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Marketing',
                'code' => 'MKT',
                'description' => '<p>Le secteur du marketing regroupe le marketing digital et stratégique. Un secteur en pleine transformation avec l\'essor du digital marketing, du e-commerce et des réseaux sociaux.</p><p>Les professionnels du marketing travaillent dans des agences, entreprises, startups ou en tant que consultants indépendants. Ils développent des stratégies marketing et gèrent la communication des marques.</p>',
                'icon' => 'marketing',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135793.png',
                'softSkills' => ['creativite', 'analytique', 'communication', 'adaptabilite', 'organisation'],
                'personnalites' => ['creatif', 'analytique', 'sociable'],
                'bacs' => ['economique', 'lettres'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Innovation',
                    'Diversité',
                    'Salaires attractifs',
                    'Créativité',
                    'Évolution rapide'
                ],
                'inconvenients' => [
                    'Concurrence',
                    'Formation continue',
                    'Pression des résultats',
                    'Deadlines serrés'
                ],
                'metiers' => [
                    'Chef de produit',
                    'Responsable marketing',
                    'Digital marketer',
                    'SEO/SEM specialist',
                    'Social media manager',
                    'Brand manager',
                    'Marketing analyst',
                    'Content manager',
                    'Email marketer',
                    'Influenceur marketing',
                    'Marketing automation',
                    'Growth hacker'
                ],
                'salaireMin' => '5000',
                'salaireMax' => '18000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => false
            ],
            [
                'titre' => 'Ressources Humaines',
                'code' => 'RH',
                'description' => '<p>Le secteur des ressources humaines regroupe la gestion des ressources humaines. Un secteur au service des personnes qui gère le recrutement, la formation, la paie et le développement des talents.</p><p>Les professionnels des RH travaillent dans des entreprises, cabinets de recrutement, institutions ou en tant que consultants indépendants.</p>',
                'icon' => 'rh',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135827.png',
                'softSkills' => ['empathie', 'communication', 'organisation', 'discretion', 'ecoute'],
                'personnalites' => ['sociable', 'methodique', 'intuitif'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Stabilité',
                    'Diversité',
                    'Évolution',
                    'Contact humain',
                    'Impact sur les personnes'
                ],
                'inconvenients' => [
                    'Pression managériale',
                    'Conflits possibles',
                    'Charge administrative',
                    'Responsabilités'
                ],
                'metiers' => [
                    'Responsable RH',
                    'Recruteur',
                    'Gestionnaire de paie',
                    'Chargé de formation',
                    'Conseiller en carrière',
                    'Responsable talent',
                    'Chasseur de têtes',
                    'Responsable recrutement',
                    'Chargé de développement RH',
                    'Responsable mobilité',
                    'Conseiller social'
                ],
                'salaireMin' => '5000',
                'salaireMax' => '15000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Transport et Logistique',
                'code' => 'TRANS',
                'description' => '<p>Le secteur du transport et de la logistique regroupe le transport, la logistique et la supply chain. Un secteur essentiel pour l\'économie qui assure le transport de marchandises et de personnes.</p><p>Les professionnels du transport travaillent dans des entreprises de transport, logistique, e-commerce, aéroports ou ports. Ils gèrent les flux de marchandises et de personnes.</p>',
                'icon' => 'transport',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135840.png',
                'softSkills' => ['organisation', 'rigueur', 'adaptabilite', 'travail-equipe', 'gestion-stress'],
                'personnalites' => ['methodique', 'analytique', 'independant'],
                'bacs' => ['technique', 'scientifique'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Stabilité',
                    'Diversité',
                    'Mobilité',
                    'Évolution possible',
                    'Secteur en croissance'
                ],
                'inconvenients' => [
                    'Horaires décalés',
                    'Responsabilités',
                    'Pression des délais',
                    'Conditions parfois difficiles'
                ],
                'metiers' => [
                    'Chauffeur routier',
                    'Logisticien',
                    'Responsable supply chain',
                    'Gestionnaire de stock',
                    'Transporteur',
                    'Déclarant en douane',
                    'Responsable transport',
                    'Planificateur logistique',
                    'Chargé d\'expédition',
                    'Responsable entrepôt',
                    'Agent de transit',
                    'Coordinateur logistique'
                ],
                'salaireMin' => '4000',
                'salaireMax' => '12000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => false
            ],
            [
                'titre' => 'Hôtellerie et Restauration',
                'code' => 'HOTEL',
                'description' => '<p>Le secteur de l\'hôtellerie et de la restauration regroupe l\'hôtellerie, la restauration et le tourisme. Un secteur au service de l\'accueil qui offre des opportunités dans les hôtels, restaurants, événementiel et tourisme.</p><p>Les professionnels de l\'hôtellerie travaillent dans des hôtels, restaurants, bars, événementiel, agences de voyage ou en tant que traiteurs indépendants.</p>',
                'icon' => 'hotel',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135815.png',
                'softSkills' => ['communication', 'empathie', 'adaptabilite', 'resilience', 'travail-equipe'],
                'personnalites' => ['sociable', 'extraverti', 'intuitif'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Diversité',
                    'Voyages possibles',
                    'Contact humain',
                    'Ambiance conviviale',
                    'Tips possibles'
                ],
                'inconvenients' => [
                    'Horaires décalés',
                    'Pression client',
                    'Travail les weekends',
                    'Charge physique'
                ],
                'metiers' => [
                    'Chef cuisinier',
                    'Serveur',
                    'Barman',
                    'Réceptionniste hôtel',
                    'Manager restaurant',
                    'Sommelier',
                    'Pâtissier',
                    'Traiteur',
                    'Animateur hôtel',
                    'Concierge',
                    'Chef de rang',
                    'Directeur d\'hôtel'
                ],
                'salaireMin' => '3000',
                'salaireMax' => '10000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Services Publics',
                'code' => 'PUBLIC',
                'description' => '<p>Le secteur des services publics regroupe l\'administration publique et les services d\'État. Un secteur au service de la collectivité qui assure le fonctionnement des services publics.</p><p>Les professionnels de la fonction publique travaillent dans des administrations, collectivités, établissements publics ou institutions. Ils servent l\'intérêt général.</p>',
                'icon' => 'public',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135830.png',
                'softSkills' => ['rigueur', 'organisation', 'discretion', 'service-public', 'patience'],
                'personnalites' => ['methodique', 'sociable', 'independant'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Stabilité',
                    'Sécurité',
                    'Retraite',
                    'Statut',
                    'Congés généreux'
                ],
                'inconvenients' => [
                    'Évolution lente',
                    'Bureaucratie',
                    'Salaires parfois modestes',
                    'Rigidité'
                ],
                'metiers' => [
                    'Fonctionnaire',
                    'Agent administratif',
                    'Inspecteur des impôts',
                    'Agent de police',
                    'Pompier',
                    'Douanier',
                    'Gendarme',
                    'Agent de mairie',
                    'Contrôleur fiscal',
                    'Secrétaire administratif',
                    'Attaché territorial',
                    'Directeur de service public'
                ],
                'salaireMin' => '4000',
                'salaireMax' => '15000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ],
            [
                'titre' => 'Entrepreneuriat',
                'code' => 'ENTREP',
                'description' => '<p>Le secteur de l\'entrepreneuriat regroupe la création d\'entreprise et le consulting. Un secteur pour les esprits libres qui souhaitent créer leur propre activité et être indépendants.</p><p>Les entrepreneurs créent leur propre entreprise, développent des projets innovants ou travaillent en tant que consultants indépendants. Ils prennent des risques pour réaliser leurs ambitions.</p>',
                'icon' => 'entrepreneur',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135818.png',
                'softSkills' => ['leadership', 'creativite', 'resilience', 'autonomie', 'adaptabilite'],
                'personnalites' => ['independant', 'creatif', 'analytique'],
                'bacs' => ['scientifique', 'lettres', 'economique', 'technique', 'professionnel'],
                'typeBacs' => ['normal', 'mission'],
                'avantages' => [
                    'Liberté',
                    'Potentiel élevé',
                    'Indépendance',
                    'Réalisation personnelle',
                    'Créativité'
                ],
                'inconvenients' => [
                    'Risques',
                    'Précarité',
                    'Stress',
                    'Responsabilités',
                    'Revenus incertains'
                ],
                'metiers' => [
                    'Créateur d\'entreprise',
                    'Consultant indépendant',
                    'Freelance',
                    'Startup founder',
                    'Business angel',
                    'Investisseur',
                    'Coach business',
                    'Mentor',
                    'Accompagnateur d\'entreprises',
                    'Expert-comptable indépendant',
                    'Avocat indépendant',
                    'Architecte indépendant'
                ],
                'salaireMin' => null,
                'salaireMax' => null,
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => false
            ],
            [
                'titre' => 'Recherche',
                'code' => 'RECH',
                'description' => '<p>Le secteur de la recherche regroupe la recherche scientifique et le développement. Un secteur qui fait avancer la science, développe de nouvelles technologies et contribue au progrès.</p><p>Les chercheurs travaillent dans des laboratoires, universités, centres de recherche, entreprises ou institutions. Ils mènent des recherches fondamentales ou appliquées.</p>',
                'icon' => 'research',
                'image' => 'https://cdn-icons-png.flaticon.com/512/3135/3135833.png',
                'softSkills' => ['rigueur', 'analytique', 'perseverance', 'autonomie', 'creativite'],
                'personnalites' => ['analytique', 'methodique', 'independant'],
                'bacs' => ['scientifique'],
                'typeBacs' => ['normal', 'mission', 'international'],
                'avantages' => [
                    'Innovation',
                    'Prestige',
                    'Découvertes',
                    'Liberté de recherche',
                    'International'
                ],
                'inconvenients' => [
                    'Formation longue',
                    'Financement incertain',
                    'Pression de publication',
                    'Revenus parfois modestes'
                ],
                'metiers' => [
                    'Chercheur',
                    'Doctorant',
                    'Post-doctorant',
                    'Ingénieur de recherche',
                    'Chargé de recherche',
                    'Directeur de recherche',
                    'Chercheur en biologie',
                    'Chercheur en physique',
                    'Chercheur en chimie',
                    'Chercheur en mathématiques',
                    'Chercheur en informatique',
                    'Chercheur en sciences sociales'
                ],
                'salaireMin' => '6000',
                'salaireMax' => '20000',
                'isActivate' => true,
                'status' => 'Actif',
                'isComplet' => true
            ]
        ];
    }
}
