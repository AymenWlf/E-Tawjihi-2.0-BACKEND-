<?php

namespace App\Command;

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
    name: 'app:populate-establishments',
    description: 'Populate establishments with mock data',
)]
class PopulateEstablishmentsCommand extends Command
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
            'Truncate existing establishments before populating'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Establishments with Mock Data');

        // Option pour vider la table
        if ($input->getOption('truncate')) {
            $io->warning('Truncating existing establishments...');
            $this->em->createQuery('DELETE FROM App\Entity\Establishment')->execute();
            $this->em->flush();
            $io->success('Existing establishments deleted.');
        }

        // Données mock des établissements marocains
        $establishmentsData = [
            [
                'nom' => 'École Marocaine des Sciences de l\'Ingénieur',
                'sigle' => 'EMSI',
                'nomArabe' => 'المدرسة المغربية لعلوم المهندس',
                'type' => 'Privé',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca', 'Rabat', 'Marrakech', 'Tanger'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => 'Honoris United Universities',
                'description' => 'EMSI est une institution d\'enseignement supérieur privé de référence au Maroc, créée en 1986. Elle propose des formations d\'excellence en ingénierie et technologies.',
                'email' => 'contact@emsi.ma',
                'telephone' => '0522272727',
                'siteWeb' => 'https://www.emsi.ma',
                'adresse' => 'Rue Abou Kacem Echabi, Casablanca',
                'codePostal' => '20100',
                'nbEtudiants' => 5000,
                'nbFilieres' => 15,
                'anneeCreation' => 1986,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 3,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 35000,
                'fraisScolariteMax' => 45000,
                'fraisInscriptionMin' => 2000,
                'fraisInscriptionMax' => 3000,
                'diplomesDelivres' => ['Ingénieur d\'État', 'Master', 'Licence'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => true,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => true,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Supérieure de Technologie de Casablanca',
                'sigle' => 'EST Casa',
                'nomArabe' => 'المدرسة العليا للتكنولوجيا بالدار البيضاء',
                'type' => 'Public',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => 'Université Hassan II de Casablanca',
                'description' => 'EST Casablanca propose des DUT et Licences Professionnelles en Génie Informatique, Électrique, Mécanique. Formation publique gratuite.',
                'email' => 'est@uh2c.ac.ma',
                'telephone' => '0522230000',
                'siteWeb' => 'https://www.estc.ma',
                'adresse' => 'Km 7, Route d\'El Jadida, Casablanca',
                'codePostal' => '20230',
                'nbEtudiants' => 3500,
                'nbFilieres' => 12,
                'anneeCreation' => 1992,
                'anneesEtudes' => 3,
                'dureeEtudesMin' => 2,
                'dureeEtudesMax' => 3,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 500,
                'fraisInscriptionMax' => 1000,
                'diplomesDelivres' => ['DUT', 'Licence Professionnelle'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => false,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Nationale Supérieure d\'Informatique et d\'Analyse des Systèmes',
                'sigle' => 'ENSIAS',
                'nomArabe' => 'المدرسة الوطنية العليا للمعلوميات وتحليل النظم',
                'type' => 'Public',
                'ville' => 'Rabat',
                'villes' => ['Rabat'],
                'region' => 'Rabat-Salé-Kénitra',
                'pays' => 'Maroc',
                'universite' => 'Université Mohammed V',
                'description' => 'ENSIAS est une grande école d\'ingénieurs marocaine spécialisée en informatique et analyse des systèmes. Elle forme des ingénieurs d\'État de haut niveau.',
                'email' => 'contact@ensias.ma',
                'telephone' => '0537771815',
                'siteWeb' => 'https://www.ensias.ma',
                'adresse' => 'Avenue Mohammed Ben Abdellah Regragui, Rabat',
                'codePostal' => '10100',
                'nbEtudiants' => 1200,
                'nbFilieres' => 8,
                'anneeCreation' => 1992,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 5,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 1000,
                'fraisInscriptionMax' => 1500,
                'diplomesDelivres' => ['Ingénieur d\'État'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => true,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Hassania des Travaux Publics',
                'sigle' => 'EHTP',
                'nomArabe' => 'المدرسة الحسنية للأشغال العمومية',
                'type' => 'Public',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => 'Université Hassan II de Casablanca',
                'description' => 'EHTP est une grande école d\'ingénieurs marocaine spécialisée en génie civil, génie industriel et génie mécanique.',
                'email' => 'contact@ehtp.ac.ma',
                'telephone' => '0522300000',
                'siteWeb' => 'https://www.ehtp.ac.ma',
                'adresse' => 'Km 7, Route d\'El Jadida, Casablanca',
                'codePostal' => '20230',
                'nbEtudiants' => 1500,
                'nbFilieres' => 10,
                'anneeCreation' => 1971,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 5,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 1500,
                'fraisInscriptionMax' => 2000,
                'diplomesDelivres' => ['Ingénieur d\'État'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => true,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Nationale Supérieure d\'Électricité et de Mécanique',
                'sigle' => 'ENSEM',
                'nomArabe' => 'المدرسة الوطنية العليا للكهرباء والميكانيك',
                'type' => 'Public',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => 'Université Hassan II de Casablanca',
                'description' => 'ENSEM forme des ingénieurs d\'État en génie électrique, génie mécanique et génie industriel.',
                'email' => 'contact@ensem.ac.ma',
                'telephone' => '0522300000',
                'siteWeb' => 'https://www.ensem.ac.ma',
                'adresse' => 'Km 7, Route d\'El Jadida, Casablanca',
                'codePostal' => '20230',
                'nbEtudiants' => 1800,
                'nbFilieres' => 12,
                'anneeCreation' => '1986',
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 5,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 1500,
                'fraisInscriptionMax' => 2000,
                'diplomesDelivres' => ['Ingénieur d\'État'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => false,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Supérieure de Commerce et de Management',
                'sigle' => 'ESCM',
                'nomArabe' => 'المدرسة العليا للتجارة والإدارة',
                'type' => 'Public',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => 'Université Hassan II de Casablanca',
                'description' => 'ESCM forme des cadres supérieurs en commerce, gestion et management.',
                'email' => 'contact@escm.ac.ma',
                'telephone' => '0522300000',
                'siteWeb' => 'https://www.escm.ac.ma',
                'adresse' => 'Avenue Allal El Fassi, Casablanca',
                'codePostal' => '20000',
                'nbEtudiants' => 2000,
                'nbFilieres' => 10,
                'anneeCreation' => 1993,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 3,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 1000,
                'fraisInscriptionMax' => 1500,
                'diplomesDelivres' => ['Master', 'Licence'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => false,
                'isFeatured' => false,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Centrale Casablanca',
                'sigle' => 'Centrale Casablanca',
                'nomArabe' => 'المدرسة المركزية بالدار البيضاء',
                'type' => 'Privé',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => null,
                'description' => 'École Centrale Casablanca est une grande école d\'ingénieurs privée, membre du réseau des Écoles Centrales.',
                'email' => 'contact@centrale-casablanca.ma',
                'telephone' => '0522400000',
                'siteWeb' => 'https://www.centrale-casablanca.ma',
                'adresse' => 'Boulevard de la Résistance, Casablanca',
                'codePostal' => '20000',
                'nbEtudiants' => 600,
                'nbFilieres' => 5,
                'anneeCreation' => 2013,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 5,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 55000,
                'fraisScolariteMax' => 65000,
                'fraisInscriptionMin' => 3000,
                'fraisInscriptionMax' => 5000,
                'diplomesDelivres' => ['Ingénieur', 'Master'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => true,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => true,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'Institut Supérieur de Commerce et d\'Administration des Entreprises',
                'sigle' => 'ISCAE',
                'nomArabe' => 'المعهد العالي للتجارة وإدارة المقاولات',
                'type' => 'Public',
                'ville' => 'Casablanca',
                'villes' => ['Casablanca', 'Rabat'],
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'universite' => null,
                'description' => 'ISCAE est une grande école de commerce et de management au Maroc.',
                'email' => 'contact@iscae.ma',
                'telephone' => '0522300000',
                'siteWeb' => 'https://www.iscae.ma',
                'adresse' => 'Avenue Allal El Fassi, Casablanca',
                'codePostal' => '20000',
                'nbEtudiants' => 2500,
                'nbFilieres' => 15,
                'anneeCreation' => 1971,
                'anneesEtudes' => 5,
                'dureeEtudesMin' => 3,
                'dureeEtudesMax' => 5,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 1500,
                'fraisInscriptionMax' => 2000,
                'diplomesDelivres' => ['Master', 'Licence'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => true,
                'isFeatured' => true,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Supérieure de Technologie de Fès',
                'sigle' => 'EST Fès',
                'nomArabe' => 'المدرسة العليا للتكنولوجيا بفاس',
                'type' => 'Public',
                'ville' => 'Fès',
                'villes' => ['Fès'],
                'region' => 'Fès-Meknès',
                'pays' => 'Maroc',
                'universite' => 'Université Sidi Mohammed Ben Abdellah',
                'description' => 'EST Fès propose des DUT et Licences Professionnelles en Génie Informatique, Électrique, Mécanique.',
                'email' => 'est@usmba.ac.ma',
                'telephone' => '0535600700',
                'siteWeb' => 'https://www.estfes.ma',
                'adresse' => 'Route d\'Imouzzer, Fès',
                'codePostal' => '30000',
                'nbEtudiants' => 2500,
                'nbFilieres' => 10,
                'anneeCreation' => 1995,
                'anneesEtudes' => 3,
                'dureeEtudesMin' => 2,
                'dureeEtudesMax' => 3,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 500,
                'fraisInscriptionMax' => 1000,
                'diplomesDelivres' => ['DUT', 'Licence Professionnelle'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => false,
                'isFeatured' => false,
                'status' => 'Publié',
                'isComplet' => true,
            ],
            [
                'nom' => 'École Supérieure de Technologie de Marrakech',
                'sigle' => 'EST Marrakech',
                'nomArabe' => 'المدرسة العليا للتكنولوجيا بمراكش',
                'type' => 'Public',
                'ville' => 'Marrakech',
                'villes' => ['Marrakech'],
                'region' => 'Marrakech-Safi',
                'pays' => 'Maroc',
                'universite' => 'Université Cadi Ayyad',
                'description' => 'EST Marrakech propose des DUT et Licences Professionnelles en Génie Informatique, Électrique, Mécanique.',
                'email' => 'est@uca.ac.ma',
                'telephone' => '0524434649',
                'siteWeb' => 'https://www.estmarrakech.ma',
                'adresse' => 'Route d\'Essaouira, Marrakech',
                'codePostal' => '40000',
                'nbEtudiants' => 2000,
                'nbFilieres' => 8,
                'anneeCreation' => 1995,
                'anneesEtudes' => 3,
                'dureeEtudesMin' => 2,
                'dureeEtudesMax' => 3,
                'fraisScolariteMin' => 0,
                'fraisScolariteMax' => 0,
                'fraisInscriptionMin' => 500,
                'fraisInscriptionMax' => 1000,
                'diplomesDelivres' => ['DUT', 'Licence Professionnelle'],
                'accreditationEtat' => true,
                'concours' => true,
                'echangeInternational' => true,
                'bacObligatoire' => true,
                'eTawjihiInscription' => false,
                'isActive' => true,
                'isRecommended' => false,
                'isFeatured' => false,
                'status' => 'Publié',
                'isComplet' => true,
            ],
        ];

        $io->progressStart(count($establishmentsData));

        foreach ($establishmentsData as $data) {
            // Vérifier si l'établissement existe déjà
            $existing = $this->em->getRepository(Establishment::class)->findOneBy(['sigle' => $data['sigle']]);
            
            if ($existing) {
                $io->progressAdvance();
                continue;
            }

            $establishment = new Establishment();
            $establishment->setNom($data['nom']);
            $establishment->setSigle($data['sigle']);
            $establishment->setNomArabe($data['nomArabe'] ?? null);
            $establishment->setType($data['type']);
            $establishment->setVille($data['ville']);
            $establishment->setVilles($data['villes'] ?? [$data['ville']]);
            $establishment->setPays($data['pays']);
            $establishment->setUniversite($data['universite'] ?? null);
            $establishment->setDescription($data['description'] ?? null);
            $establishment->setEmail($data['email'] ?? null);
            $establishment->setTelephone($data['telephone'] ?? null);
            $establishment->setSiteWeb($data['siteWeb'] ?? null);
            $establishment->setAdresse($data['adresse'] ?? null);
            $establishment->setCodePostal($data['codePostal'] ?? null);
            $establishment->setNbEtudiants($data['nbEtudiants'] ?? null);
            $establishment->setNbFilieres($data['nbFilieres'] ?? null);
            $establishment->setAnneeCreation($data['anneeCreation'] ?? null);
            $establishment->setAnneesEtudes($data['anneesEtudes'] ?? null);
            $establishment->setDureeEtudesMin($data['dureeEtudesMin'] ?? null);
            $establishment->setDureeEtudesMax($data['dureeEtudesMax'] ?? null);
            $establishment->setFraisScolariteMin($data['fraisScolariteMin'] ?? null);
            $establishment->setFraisScolariteMax($data['fraisScolariteMax'] ?? null);
            $establishment->setFraisInscriptionMin($data['fraisInscriptionMin'] ?? null);
            $establishment->setFraisInscriptionMax($data['fraisInscriptionMax'] ?? null);
            $establishment->setDiplomesDelivres($data['diplomesDelivres'] ?? null);
            $establishment->setAccreditationEtat($data['accreditationEtat'] ?? false);
            $establishment->setConcours($data['concours'] ?? false);
            $establishment->setEchangeInternational($data['echangeInternational'] ?? false);
            $establishment->setBacObligatoire($data['bacObligatoire'] ?? false);
            $establishment->setETawjihiInscription($data['eTawjihiInscription'] ?? false);
            $establishment->setIsActive($data['isActive'] ?? true);
            $establishment->setIsRecommended($data['isRecommended'] ?? false);
            $establishment->setIsFeatured($data['isFeatured'] ?? false);
            $establishment->setStatus($data['status'] ?? 'Brouillon');
            $establishment->setIsComplet($data['isComplet'] ?? false);
            
            // Générer le slug
            $slug = $this->slugger->slug($data['nom'])->lower();
            $establishment->setSlug($slug);

            $this->em->persist($establishment);
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Successfully populated %d establishments!', count($establishmentsData)));

        return Command::SUCCESS;
    }
}
