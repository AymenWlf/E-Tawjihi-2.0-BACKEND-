<?php

namespace App\Command;

use App\Entity\Filiere;
use App\Entity\Establishment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:populate-filieres',
    description: 'Populate filieres with mock data',
)]
class PopulateFilieresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'truncate',
            't',
            InputOption::VALUE_NONE,
            'Truncate existing filieres before populating'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Filieres with Mock Data');

        // Option pour vider la table
        if ($input->getOption('truncate')) {
            $io->warning('Truncating existing filieres...');
            $this->em->createQuery('DELETE FROM App\Entity\Filiere')->execute();
            $this->em->flush();
            $io->success('Existing filieres deleted.');
        }

        // Récupérer les établissements
        $establishments = $this->em->getRepository(Establishment::class)->findAll();
        
        if (empty($establishments)) {
            $io->error('No establishments found! Please populate establishments first.');
            return Command::FAILURE;
        }

        // Créer un mapping des établissements par sigle
        $establishmentsBySigle = [];
        foreach ($establishments as $establishment) {
            $establishmentsBySigle[$establishment->getSigle()] = $establishment;
        }

        // Données mock des filières par établissement
        $filieresData = [
            // EMSI
            [
                'establishmentSigle' => 'EMSI',
                'nom' => 'Génie Informatique',
                'nomArabe' => 'هندسة المعلوماتية',
                'description' => 'La filière Génie Informatique forme des ingénieurs capables de concevoir, développer et maintenir des systèmes informatiques complexes. Les étudiants acquièrent des compétences en programmation, bases de données, réseaux, intelligence artificielle et cybersécurité.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Informatique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'fraisInscription' => '3000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies Électriques'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 120,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'EMSI',
                'nom' => 'Génie Industriel',
                'nomArabe' => 'الهندسة الصناعية',
                'description' => 'La filière Génie Industriel prépare les étudiants à optimiser les processus de production, gérer les chaînes logistiques et améliorer la performance industrielle.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Industriel',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'fraisInscription' => '3000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 80,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'EMSI',
                'nom' => 'Génie Civil',
                'nomArabe' => 'الهندسة المدنية',
                'description' => 'La filière Génie Civil forme des ingénieurs spécialisés dans la conception, la construction et la maintenance des infrastructures.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Génie Civil',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'fraisInscription' => '3000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 60,
                'echangeInternational' => true,
            ],
            // EST Casa
            [
                'establishmentSigle' => 'EST Casa',
                'nom' => 'Génie Informatique',
                'nomArabe' => 'هندسة المعلوماتية',
                'description' => 'Formation en Génie Informatique menant au DUT et Licence Professionnelle. Programme axé sur la programmation, les bases de données et les réseaux.',
                'diplome' => 'DUT / Licence Professionnelle',
                'domaine' => 'Informatique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '800.00',
                'nombreAnnees' => '3 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies Électriques'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 150,
                'echangeInternational' => false,
            ],
            [
                'establishmentSigle' => 'EST Casa',
                'nom' => 'Génie Électrique',
                'nomArabe' => 'الهندسة الكهربائية',
                'description' => 'Formation en Génie Électrique avec spécialisation en automatisme et électronique.',
                'diplome' => 'DUT / Licence Professionnelle',
                'domaine' => 'Électrique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '800.00',
                'nombreAnnees' => '3 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies Électriques'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 100,
                'echangeInternational' => false,
            ],
            // ENSIAS
            [
                'establishmentSigle' => 'ENSIAS',
                'nom' => 'Ingénierie des Systèmes d\'Information',
                'nomArabe' => 'هندسة أنظمة المعلومات',
                'description' => 'Formation d\'ingénieurs d\'État spécialisés en systèmes d\'information, bases de données et architecture logicielle.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Informatique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '1500.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 80,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'ENSIAS',
                'nom' => 'Ingénierie des Réseaux et Télécommunications',
                'nomArabe' => 'هندسة الشبكات والاتصالات',
                'description' => 'Formation d\'ingénieurs d\'État en réseaux, télécommunications et sécurité informatique.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Télécommunications',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '1500.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 60,
                'echangeInternational' => true,
            ],
            // EHTP
            [
                'establishmentSigle' => 'EHTP',
                'nom' => 'Génie Civil',
                'nomArabe' => 'الهندسة المدنية',
                'description' => 'Formation d\'ingénieurs d\'État en génie civil, spécialisés en structures et travaux publics.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Génie Civil',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '2000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 100,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'EHTP',
                'nom' => 'Génie Industriel',
                'nomArabe' => 'الهندسة الصناعية',
                'description' => 'Formation d\'ingénieurs d\'État en génie industriel, optimisation des processus et gestion de production.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Industriel',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '2000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 80,
                'echangeInternational' => true,
            ],
            // ENSEM
            [
                'establishmentSigle' => 'ENSEM',
                'nom' => 'Génie Électrique',
                'nomArabe' => 'الهندسة الكهربائية',
                'description' => 'Formation d\'ingénieurs d\'État en génie électrique, spécialisés en automatisme et énergies renouvelables.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Électrique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '2000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences et Technologies Électriques'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 90,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'ENSEM',
                'nom' => 'Génie Mécanique',
                'nomArabe' => 'الهندسة الميكانيكية',
                'description' => 'Formation d\'ingénieurs d\'État en génie mécanique, conception et fabrication mécanique.',
                'diplome' => 'Ingénieur d\'État',
                'domaine' => 'Mécanique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '2000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 70,
                'echangeInternational' => true,
            ],
            // ISCAE
            [
                'establishmentSigle' => 'ISCAE',
                'nom' => 'Management',
                'nomArabe' => 'الإدارة',
                'description' => 'Formation en management et gestion d\'entreprise, menant au Master en Management.',
                'diplome' => 'Master',
                'domaine' => 'Commerce',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '1500.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales', 'Sciences Économiques'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 200,
                'echangeInternational' => true,
            ],
            [
                'establishmentSigle' => 'ISCAE',
                'nom' => 'Finance',
                'nomArabe' => 'المالية',
                'description' => 'Formation en finance d\'entreprise et marchés financiers.',
                'diplome' => 'Master',
                'domaine' => 'Finance',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '1500.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Économiques'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 120,
                'echangeInternational' => true,
            ],
            // Centrale Casablanca
            [
                'establishmentSigle' => 'Centrale Casablanca',
                'nom' => 'Ingénierie Générale',
                'nomArabe' => 'الهندسة العامة',
                'description' => 'Formation d\'ingénieurs généralistes avec spécialisation en 3ème année.',
                'diplome' => 'Ingénieur',
                'domaine' => 'Généraliste',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '60000.00',
                'fraisInscription' => '5000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'bacCompatible' => true,
                'bacType' => 'both',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => true,
                'concours' => true,
                'nbPlaces' => 50,
                'echangeInternational' => true,
            ],
            // EST Fès
            [
                'establishmentSigle' => 'EST Fès',
                'nom' => 'Génie Informatique',
                'nomArabe' => 'هندسة المعلوماتية',
                'description' => 'Formation en Génie Informatique menant au DUT et Licence Professionnelle.',
                'diplome' => 'DUT / Licence Professionnelle',
                'domaine' => 'Informatique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '800.00',
                'nombreAnnees' => '3 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 100,
                'echangeInternational' => false,
            ],
            // EST Marrakech
            [
                'establishmentSigle' => 'EST Marrakech',
                'nom' => 'Génie Électrique',
                'nomArabe' => 'الهندسة الكهربائية',
                'description' => 'Formation en Génie Électrique menant au DUT et Licence Professionnelle.',
                'diplome' => 'DUT / Licence Professionnelle',
                'domaine' => 'Électrique',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '0.00',
                'fraisInscription' => '800.00',
                'nombreAnnees' => '3 ans',
                'typeEcole' => 'Public',
                'bacCompatible' => true,
                'bacType' => 'normal',
                'filieresAcceptees' => ['Sciences Mathématiques', 'Sciences Expérimentales'],
                'recommandee' => false,
                'concours' => true,
                'nbPlaces' => 80,
                'echangeInternational' => false,
            ],
        ];

        $io->progressStart(count($filieresData));

        $created = 0;
        $skipped = 0;

        foreach ($filieresData as $data) {
            // Vérifier si l'établissement existe
            if (!isset($establishmentsBySigle[$data['establishmentSigle']])) {
                $io->warning(sprintf('Establishment %s not found, skipping filiere %s', $data['establishmentSigle'], $data['nom']));
                $io->progressAdvance();
                $skipped++;
                continue;
            }

            $establishment = $establishmentsBySigle[$data['establishmentSigle']];

            // Vérifier si la filière existe déjà
            $existing = $this->em->getRepository(Filiere::class)->findOneBy([
                'nom' => $data['nom'],
                'establishment' => $establishment
            ]);
            
            if ($existing) {
                $io->progressAdvance();
                $skipped++;
                continue;
            }

            $filiere = new Filiere();
            $filiere->setNom($data['nom']);
            $filiere->setNomArabe($data['nomArabe'] ?? null);
            $filiere->setDescription($data['description'] ?? null);
            $filiere->setDiplome($data['diplome'] ?? null);
            $filiere->setDomaine($data['domaine'] ?? null);
            $filiere->setLangueEtudes($data['langueEtudes'] ?? null);
            $filiere->setFraisScolarite($data['fraisScolarite'] ?? null);
            $filiere->setFraisInscription($data['fraisInscription'] ?? null);
            $filiere->setNombreAnnees($data['nombreAnnees'] ?? null);
            $filiere->setTypeEcole($data['typeEcole'] ?? null);
            $filiere->setBacCompatible($data['bacCompatible'] ?? false);
            $filiere->setBacType($data['bacType'] ?? null);
            $filiere->setFilieresAcceptees($data['filieresAcceptees'] ?? null);
            $filiere->setRecommandee($data['recommandee'] ?? false);
            $filiere->setConcours($data['concours'] ?? false);
            $filiere->setNbPlaces($data['nbPlaces'] ?? null);
            $filiere->setEchangeInternational($data['echangeInternational'] ?? false);
            $filiere->setEstablishment($establishment);
            $filiere->setIsActive(true);
            
            // Générer le slug unique (nom + établissement)
            $slugBase = $this->slugger->slug($data['nom'] . ' ' . $establishment->getSigle())->lower();
            $slug = $slugBase;
            $counter = 1;
            // Vérifier l'unicité du slug
            while ($this->em->getRepository(Filiere::class)->findOneBy(['slug' => $slug])) {
                $slug = $slugBase . '-' . $counter;
                $counter++;
            }
            $filiere->setSlug($slug);

            $this->em->persist($filiere);
            $created++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Successfully populated %d filieres! (%d skipped)', $created, $skipped));

        return Command::SUCCESS;
    }
}
