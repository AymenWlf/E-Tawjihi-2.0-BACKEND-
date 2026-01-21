<?php

namespace App\Command;

use App\Entity\Universite;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-universites',
    description: 'Populate universities with mock data',
)]
class PopulateUniversitesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'truncate',
            't',
            InputOption::VALUE_NONE,
            'Truncate existing universities before populating'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Universities with Mock Data');

        // Option pour vider la table
        if ($input->getOption('truncate')) {
            $io->warning('Truncating existing universities...');
            $this->em->createQuery('DELETE FROM App\Entity\Universite')->execute();
            $this->em->flush();
            $io->success('Existing universities deleted.');
        }

        // Données mock des universités marocaines
        $universitesData = [
            [
                'nom' => 'Université Mohammed V',
                'sigle' => 'UM5',
                'nomArabe' => 'جامعة محمد الخامس',
                'ville' => 'Rabat',
                'region' => 'Rabat-Salé-Kénitra',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Rabat, fondée en 1957. Elle est l\'une des plus grandes universités du Maroc avec plusieurs facultés et écoles.',
                'siteWeb' => 'https://www.um5.ac.ma',
                'email' => 'contact@um5.ac.ma',
                'telephone' => '0537771815',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Hassan II',
                'sigle' => 'UH2',
                'nomArabe' => 'جامعة الحسن الثاني',
                'ville' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Casablanca. Elle comprend plusieurs établissements répartis dans différentes villes.',
                'siteWeb' => 'https://www.uh2.ac.ma',
                'email' => 'contact@uh2.ac.ma',
                'telephone' => '0522234567',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Cadi Ayyad',
                'sigle' => 'UCA',
                'nomArabe' => 'جامعة القاضي عياض',
                'ville' => 'Marrakech',
                'region' => 'Marrakech-Safi',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Marrakech, fondée en 1978. Elle est reconnue pour ses programmes en sciences et technologies.',
                'siteWeb' => 'https://www.uca.ac.ma',
                'email' => 'contact@uca.ac.ma',
                'telephone' => '0524434649',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Sidi Mohammed Ben Abdellah',
                'sigle' => 'USMBA',
                'nomArabe' => 'جامعة سيدي محمد بن عبد الله',
                'ville' => 'Fès',
                'region' => 'Fès-Meknès',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Fès, fondée en 1975. Elle propose une large gamme de programmes académiques.',
                'siteWeb' => 'https://www.usmba.ac.ma',
                'email' => 'contact@usmba.ac.ma',
                'telephone' => '0535600700',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Ibn Tofail',
                'sigle' => 'UIT',
                'nomArabe' => 'جامعة ابن طفيل',
                'ville' => 'Kénitra',
                'region' => 'Rabat-Salé-Kénitra',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Kénitra, fondée en 1989. Elle est spécialisée dans les sciences et technologies.',
                'siteWeb' => 'https://www.uit.ac.ma',
                'email' => 'contact@uit.ac.ma',
                'telephone' => '0537322400',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Abdelmalek Essaâdi',
                'sigle' => 'UAE',
                'nomArabe' => 'جامعة عبد المالك السعدي',
                'ville' => 'Tétouan',
                'region' => 'Tanger-Tétouan-Al Hoceïma',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Tétouan, fondée en 1989. Elle couvre plusieurs régions du nord du Maroc.',
                'siteWeb' => 'https://www.uae.ma',
                'email' => 'contact@uae.ma',
                'telephone' => '0539960000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Chouaib Doukkali',
                'sigle' => 'UCD',
                'nomArabe' => 'جامعة شعيب الدكالي',
                'ville' => 'El Jadida',
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à El Jadida, fondée en 1989. Elle propose des formations dans divers domaines.',
                'siteWeb' => 'https://www.ucd.ac.ma',
                'email' => 'contact@ucd.ac.ma',
                'telephone' => '0523340000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Ibn Zohr',
                'sigle' => 'UIZ',
                'nomArabe' => 'جامعة ابن زهر',
                'ville' => 'Agadir',
                'region' => 'Souss-Massa',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Agadir, fondée en 1989. Elle dessert la région de Souss-Massa.',
                'siteWeb' => 'https://www.uiz.ac.ma',
                'email' => 'contact@uiz.ac.ma',
                'telephone' => '0528220000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Mohammed Premier',
                'sigle' => 'UMP',
                'nomArabe' => 'جامعة محمد الأول',
                'ville' => 'Oujda',
                'region' => 'Oriental',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Oujda, fondée en 1978. Elle couvre la région de l\'Oriental.',
                'siteWeb' => 'https://www.ump.ac.ma',
                'email' => 'contact@ump.ac.ma',
                'telephone' => '0536360000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Moulay Ismail',
                'sigle' => 'UMI',
                'nomArabe' => 'جامعة مولاي إسماعيل',
                'ville' => 'Meknès',
                'region' => 'Fès-Meknès',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université public marocaine située à Meknès, fondée en 1982. Elle propose des formations variées.',
                'siteWeb' => 'https://www.umi.ac.ma',
                'email' => 'contact@umi.ac.ma',
                'telephone' => '0535520000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Privée de Fès',
                'sigle' => 'UPF',
                'nomArabe' => 'الجامعة الخاصة بفاس',
                'ville' => 'Fès',
                'region' => 'Fès-Meknès',
                'pays' => 'Maroc',
                'type' => 'Privé',
                'description' => 'Université privée marocaine située à Fès, offrant des formations dans plusieurs domaines.',
                'siteWeb' => 'https://www.upf.ac.ma',
                'email' => 'contact@upf.ac.ma',
                'telephone' => '0535620000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Euro Méditerranéenne de Fès',
                'sigle' => 'UEMF',
                'nomArabe' => 'الجامعة الأورومتوسطية بفاس',
                'ville' => 'Fès',
                'region' => 'Fès-Meknès',
                'pays' => 'Maroc',
                'type' => 'Privé',
                'description' => 'Université privée internationale située à Fès, offrant des formations d\'excellence.',
                'siteWeb' => 'https://www.ueuromed.org',
                'email' => 'contact@ueuromed.org',
                'telephone' => '0535500000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Mohammed VI Polytechnique',
                'sigle' => 'UM6P',
                'nomArabe' => 'جامعة محمد السادس متعددة التخصصات',
                'ville' => 'Ben Guerir',
                'region' => 'Marrakech-Safi',
                'pays' => 'Maroc',
                'type' => 'Public',
                'description' => 'Université semi-public marocaine située à Ben Guerir, spécialisée dans l\'innovation et la recherche.',
                'siteWeb' => 'https://www.um6p.ma',
                'email' => 'contact@um6p.ma',
                'telephone' => '0525200000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Mundiapolis de Casablanca',
                'sigle' => 'Mundiapolis',
                'nomArabe' => 'جامعة مونديابوليس بالدار البيضاء',
                'ville' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'type' => 'Privé',
                'description' => 'Université privée marocaine située à Casablanca, offrant des formations modernes et innovantes.',
                'siteWeb' => 'https://www.mundiapolis.ma',
                'email' => 'contact@mundiapolis.ma',
                'telephone' => '0522300000',
                'isActive' => true,
            ],
            [
                'nom' => 'Université Internationale de Casablanca',
                'sigle' => 'UIC',
                'nomArabe' => 'الجامعة الدولية بالدار البيضاء',
                'ville' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'pays' => 'Maroc',
                'type' => 'Privé',
                'description' => 'Université privée marocaine située à Casablanca, proposant des programmes internationaux.',
                'siteWeb' => 'https://www.uic.ac.ma',
                'email' => 'contact@uic.ac.ma',
                'telephone' => '0522400000',
                'isActive' => true,
            ],
        ];

        $io->progressStart(count($universitesData));

        foreach ($universitesData as $data) {
            // Vérifier si l'université existe déjà
            $existing = $this->em->getRepository(Universite::class)->findOneBy(['sigle' => $data['sigle']]);
            
            if ($existing) {
                $io->progressAdvance();
                continue;
            }

            $universite = new Universite();
            $universite->setNom($data['nom']);
            $universite->setSigle($data['sigle']);
            $universite->setNomArabe($data['nomArabe'] ?? null);
            $universite->setVille($data['ville']);
            $universite->setRegion($data['region'] ?? null);
            $universite->setPays($data['pays']);
            $universite->setType($data['type'] ?? null);
            $universite->setDescription($data['description'] ?? null);
            $universite->setSiteWeb($data['siteWeb'] ?? null);
            $universite->setEmail($data['email'] ?? null);
            $universite->setTelephone($data['telephone'] ?? null);
            $universite->setIsActive($data['isActive'] ?? true);

            $this->em->persist($universite);
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Successfully populated %d universities!', count($universitesData)));

        return Command::SUCCESS;
    }
}
