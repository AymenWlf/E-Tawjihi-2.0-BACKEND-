<?php

namespace App\Command;

use App\Entity\Filiere;
use App\Entity\Establishment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:create-filieres-emsi',
    description: 'Create 3 filieres for EMSI',
)]
class CreateFilieresForEMSICommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating Filieres for EMSI');

        // Trouver l'établissement EMSI
        $emsi = $this->em->getRepository(Establishment::class)->findOneBy(['sigle' => 'EMSI']);
        
        if (!$emsi) {
            $io->error('EMSI establishment not found!');
            return Command::FAILURE;
        }

        $io->info(sprintf('Found EMSI: %s (ID: %d)', $emsi->getNom(), $emsi->getId()));

        // Données des 3 filières
        $filieresData = [
            [
                'nom' => 'Génie Informatique',
                'description' => 'La filière Génie Informatique forme des ingénieurs capables de concevoir, développer et maintenir des systèmes informatiques complexes. Les étudiants acquièrent des compétences en programmation, bases de données, réseaux, intelligence artificielle et cybersécurité.',
                'diplome' => 'Cycle d\'ingénieur',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'metier' => ['Développeur Full Stack', 'Ingénieur Logiciel', 'Architecte Système', 'Chef de Projet IT', 'Data Scientist', 'Ingénieur Cybersécurité'],
                'recommandee' => true,
            ],
            [
                'nom' => 'Génie Industriel',
                'description' => 'La filière Génie Industriel prépare les étudiants à optimiser les processus de production, gérer les chaînes logistiques et améliorer la performance industrielle. Les compétences acquises couvrent la gestion de production, la qualité, la logistique et l\'optimisation des systèmes industriels.',
                'diplome' => 'Cycle d\'ingénieur',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'metier' => ['Ingénieur Production', 'Ingénieur Qualité', 'Ingénieur Logistique', 'Chef de Projet Industriel', 'Consultant en Optimisation', 'Responsable Supply Chain'],
                'recommandee' => true,
            ],
            [
                'nom' => 'Génie Civil',
                'description' => 'La filière Génie Civil forme des ingénieurs spécialisés dans la conception, la construction et la maintenance des infrastructures. Les étudiants apprennent à gérer des projets de construction, à analyser les structures et à respecter les normes de sécurité et d\'environnement.',
                'diplome' => 'Cycle d\'ingénieur',
                'langueEtudes' => 'Français',
                'fraisScolarite' => '45000.00',
                'nombreAnnees' => '5 ans',
                'typeEcole' => 'Privé',
                'metier' => ['Ingénieur BTP', 'Ingénieur Structures', 'Chef de Chantier', 'Ingénieur Projet', 'Bureau d\'Études', 'Ingénieur Qualité Construction'],
                'recommandee' => false,
            ],
        ];

        $io->section('Creating Filieres...');
        $io->progressStart(count($filieresData));
        
        $created = 0;
        $skipped = 0;

        foreach ($filieresData as $filiereData) {
            // Vérifier si la filière existe déjà
            $existingFiliere = $this->em->getRepository(Filiere::class)->findOneBy([
                'nom' => $filiereData['nom'],
                'establishment' => $emsi
            ]);

            if ($existingFiliere) {
                $io->warning(sprintf('Filiere "%s" already exists, skipping...', $filiereData['nom']));
                $skipped++;
                $io->progressAdvance();
                continue;
            }

            // Générer le slug
            $slug = strtolower($this->slugger->slug($filiereData['nom'])->toString());
            
            // Vérifier l'unicité du slug
            $slugBase = $slug;
            $counter = 1;
            while ($this->em->getRepository(Filiere::class)->findOneBy(['slug' => $slug])) {
                $slug = $slugBase . '-' . $counter;
                $counter++;
            }

            // Créer la filière
            $filiere = new Filiere();
            $filiere->setNom($filiereData['nom']);
            $filiere->setSlug($slug);
            $filiere->setDescription($filiereData['description']);
            $filiere->setDiplome($filiereData['diplome']);
            $filiere->setLangueEtudes($filiereData['langueEtudes']);
            $filiere->setFraisScolarite($filiereData['fraisScolarite']);
            $filiere->setNombreAnnees($filiereData['nombreAnnees']);
            $filiere->setTypeEcole($filiereData['typeEcole']);
            $filiere->setMetier($filiereData['metier']);
            $filiere->setRecommandee($filiereData['recommandee']);
            $filiere->setBacCompatible(true);
            $filiere->setEstablishment($emsi);
            $filiere->setIsActive(true);

            $this->em->persist($filiere);
            $created++;
            
            $io->progressAdvance();
        }

        // Flush toutes les filières
        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Created %d new filieres, %d already existed', $created, $skipped));
        
        // Afficher un résumé
        $io->section('Summary');
        $filieres = $this->em->getRepository(Filiere::class)->findBy(['establishment' => $emsi], ['nom' => 'ASC']);
        $io->table(
            ['ID', 'Nom', 'Diplôme', 'Slug', 'Recommandée'],
            array_map(function ($f) {
                return [
                    $f->getId(),
                    $f->getNom(),
                    $f->getDiplome(),
                    $f->getSlug(),
                    $f->isRecommandee() ? 'Oui' : 'Non'
                ];
            }, $filieres)
        );

        return Command::SUCCESS;
    }
}
