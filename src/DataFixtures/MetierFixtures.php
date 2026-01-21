<?php

namespace App\DataFixtures;

use App\Entity\Metier;
use App\Entity\Secteur;
use App\Repository\SecteurRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class MetierFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    // Mapping des métiers par secteur (extrait du test de compatibilité)
    private const METIERS_PAR_SECTEUR = [
        'SANTE' => [
            ['nom' => 'Médecin généraliste', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Médecin spécialiste', 'niveauAccessibilite' => 'Très difficile'],
            ['nom' => 'Chirurgien', 'niveauAccessibilite' => 'Très difficile'],
            ['nom' => 'Pharmacien', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Infirmier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Kinésithérapeute', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Psychologue clinicien', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'TECH' => [
            ['nom' => 'Ingénieur informatique', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Ingénieur civil', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Ingénieur électrique', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Architecte', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Développeur web', 'niveauAccessibilite' => 'Facile'],
            ['nom' => 'Data scientist', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Expert en cybersécurité', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'EDUC' => [
            ['nom' => 'Enseignant primaire', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Enseignant secondaire', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Professeur universitaire', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Professeur de langues', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Conseiller d\'orientation', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Directeur d\'école', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Éducateur spécialisé', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'FIN' => [
            ['nom' => 'Expert-comptable', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Contrôleur de gestion', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Analyste financier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Banquier d\'affaires', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Conseiller financier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Auditeur', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Gestionnaire de patrimoine', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'JUR' => [
            ['nom' => 'Avocat pénaliste', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Avocat d\'affaires', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Notaire', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Juriste d\'entreprise', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Magistrat', 'niveauAccessibilite' => 'Très difficile'],
            ['nom' => 'Médiateur', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Juriste en propriété intellectuelle', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'ARTS' => [
            ['nom' => 'Designer graphique', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Photographe', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Designer produit', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Styliste', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Décorateur d\'intérieur', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Musicien', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Réalisateur', 'niveauAccessibilite' => 'Difficile'],
        ],
        'COMM' => [
            ['nom' => 'Journaliste', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Rédacteur web', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Community manager', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Responsable communication', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Présentateur TV/Radio', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Producteur audiovisuel', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Relations publiques', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'COMMERCE' => [
            ['nom' => 'Commercial B2B', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Responsable commercial', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Business developer', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Responsable export', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Négociateur immobilier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'E-commerce manager', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Acheteur', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'MKT' => [
            ['nom' => 'Responsable marketing', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Digital marketer', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'SEO/SEM specialist', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Brand manager', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Product marketing manager', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Growth hacker', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Analyste marketing', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'RH' => [
            ['nom' => 'Responsable RH', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Recruteur', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Consultant RH', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Responsable formation', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Compensation & benefits', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'HRBP (Business partner)', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Chief happiness officer', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'TRANS' => [
            ['nom' => 'Pilote de ligne', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Contrôleur aérien', 'niveauAccessibilite' => 'Très difficile'],
            ['nom' => 'Capitaine de navire', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Conducteur de train', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Logisticien', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Supply chain manager', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Responsable entrepôt', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'HOTEL' => [
            ['nom' => 'Chef cuisinier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Directeur d\'hôtel', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Réceptionniste', 'niveauAccessibilite' => 'Facile'],
            ['nom' => 'Concierge', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Maître d\'hôtel', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Guide touristique', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Wedding planner', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'PUBLIC' => [
            ['nom' => 'Diplomate', 'niveauAccessibilite' => 'Très difficile'],
            ['nom' => 'Administrateur civil', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Contrôleur des impôts', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Douanier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Policier', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Gendarme', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Pompier', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'ENTREP' => [
            ['nom' => 'Chef d\'entreprise', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Consultant en stratégie', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Consultant IT', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Chef de projet', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Entrepreneur tech', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Consultant freelance', 'niveauAccessibilite' => 'Variable'],
            ['nom' => 'Business analyst', 'niveauAccessibilite' => 'Moyenne'],
        ],
        'RECH' => [
            ['nom' => 'Chercheur scientifique', 'niveauAccessibilite' => 'Difficile'],
            ['nom' => 'Ingénieur R&D', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Laboratoire privé', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Statisticien', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Économiste', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Biologiste', 'niveauAccessibilite' => 'Moyenne'],
            ['nom' => 'Physicien', 'niveauAccessibilite' => 'Difficile'],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $secteurRepository = $manager->getRepository(Secteur::class);

        foreach (self::METIERS_PAR_SECTEUR as $codeSecteur => $metiers) {
            // Trouver le secteur par son code
            $secteur = $secteurRepository->findOneBy(['code' => $codeSecteur]);
            
            if (!$secteur) {
                // Si le secteur n'existe pas, on le crée ou on skip
                continue;
            }

            foreach ($metiers as $metierData) {
                $metier = new Metier();
                $metier->setNom($metierData['nom']);
                
                // Générer le slug
                $slug = strtolower($this->slugger->slug($metierData['nom'])->toString());
                $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
                $slug = trim($slug, '-');
                $metier->setSlug($slug);
                
                $metier->setSecteur($secteur);
                $metier->setNiveauAccessibilite($metierData['niveauAccessibilite']);
                $metier->setIsActivate(true);
                $metier->setAfficherDansTest($metierData['afficherDansTest'] ?? true);

                $manager->persist($metier);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SecteurFixtures::class,
        ];
    }
}
