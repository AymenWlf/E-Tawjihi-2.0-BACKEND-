<?php

namespace App\Command;

use App\Entity\Establishment;
use App\Repository\EstablishmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:generate-slugs',
    description: 'Génère les slugs manquants pour tous les établissements',
)]
class GenerateSlugsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EstablishmentRepository $establishmentRepository,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Génération des slugs pour les établissements');
        
        // Récupérer tous les établissements
        $establishments = $this->establishmentRepository->findAll();
        $io->info(sprintf('Nombre d\'établissements trouvés: %d', count($establishments)));
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($establishments as $establishment) {
            // Si le slug existe déjà, passer au suivant
            if (!empty($establishment->getSlug())) {
                $skipped++;
                continue;
            }
            
            // Générer le slug à partir du nom
            $nom = $establishment->getNom();
            if (empty($nom)) {
                $io->warning(sprintf('Établissement ID %d: Nom vide, impossible de générer le slug', $establishment->getId()));
                continue;
        }

            // Générer le slug
            $slug = $this->slugger->slug(strtolower($nom))->toString();
            
            // Vérifier l'unicité
            $existing = $this->establishmentRepository->findOneBy(['slug' => $slug]);
            if ($existing && $existing->getId() !== $establishment->getId()) {
                // Ajouter l'ID pour garantir l'unicité
                $slug = $slug . '-' . $establishment->getId();
        }

            $establishment->setSlug($slug);
            $this->entityManager->persist($establishment);
            
            $io->writeln(sprintf('  ✓ ID %d: "%s" → slug: "%s"', 
                $establishment->getId(), 
                $nom, 
                $slug
            ));
            
            $updated++;
        }
        
        // Sauvegarder tous les changements
        if ($updated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('✅ %d slug(s) généré(s) avec succès', $updated));
        } else {
            $io->info('Aucun slug à générer, tous les établissements ont déjà un slug.');
        }
        
        if ($skipped > 0) {
            $io->info(sprintf('⏭️  %d établissement(s) déjà avec slug, ignoré(s)', $skipped));
        }

        return Command::SUCCESS;
    }
}
