<?php

namespace App\Command;

use App\Service\MigrationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:migrate-data',
    description: 'Migrate data from old system to new system (Etablissement, Filiere, Universite)',
)]
class MigrateDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private MigrationService $migrationService,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source-db', null, InputOption::VALUE_OPTIONAL, 'Source database connection name (if different)')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'Entity type to migrate (establishment, filiere, universite, all)', 'all')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records to migrate', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset for pagination', 0)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode (no database writes)')
            ->addOption('source-file', null, InputOption::VALUE_OPTIONAL, 'Source JSON file path (instead of database)')
            ->setHelp('
Cette commande migre les données depuis l\'ancien système vers le nouveau système.
Options disponibles:
  --entity: Type d\'entité à migrer (establishment, filiere, universite, all)
  --limit: Nombre maximum d\'enregistrements à migrer
  --offset: Décalage pour la pagination
  --dry-run: Mode test sans écriture en base
  --source-file: Chemin vers un fichier JSON contenant les données à migrer
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migration de données - Etablissement, Filiere, Universite');

        $entityType = $input->getOption('entity');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;
        $offset = (int) $input->getOption('offset');
        $dryRun = $input->getOption('dry-run');
        $sourceFile = $input->getOption('source-file');

        if ($dryRun) {
            $io->warning('Mode DRY-RUN activé - Aucune donnée ne sera écrite en base');
        }

        try {
            if ($sourceFile && file_exists($sourceFile)) {
                // Migration depuis un fichier JSON
                return $this->migrateFromFile($io, $sourceFile, $entityType, $dryRun);
            } else {
                // Migration depuis l'ancienne base de données
                return $this->migrateFromDatabase($io, $entityType, $limit, $offset, $dryRun);
            }
        } catch (\Exception $e) {
            $io->error('Erreur lors de la migration: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Migration depuis un fichier JSON
     */
    private function migrateFromFile(SymfonyStyle $io, string $filePath, string $entityType, bool $dryRun): int
    {
        $io->section('Migration depuis fichier JSON: ' . $filePath);

        $data = json_decode(file_get_contents($filePath), true);
        if (!$data) {
            $io->error('Impossible de décoder le fichier JSON');
            return Command::FAILURE;
        }

        $stats = [
            'establishments' => ['success' => 0, 'errors' => 0],
            'filieres' => ['success' => 0, 'errors' => 0],
            'universites' => ['success' => 0, 'errors' => 0],
        ];

        // Migrer les établissements
        if (($entityType === 'all' || $entityType === 'establishment') && isset($data['establishments'])) {
            $io->section('Migration des établissements...');
            $progressBar = $io->createProgressBar(count($data['establishments']));
            $progressBar->start();

            foreach ($data['establishments'] as $oldData) {
                try {
                    $establishment = $this->migrationService->migrateEstablishment($oldData);
                    if ($establishment) {
                        if (!$dryRun) {
                            $this->em->persist($establishment);
                            $this->em->flush();
                        }
                        $stats['establishments']['success']++;
                    } else {
                        $stats['establishments']['errors']++;
                    }
                } catch (\Exception $e) {
                    $io->error('Erreur: ' . $e->getMessage());
                    $stats['establishments']['errors']++;
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Migrer les filières
        if (($entityType === 'all' || $entityType === 'filiere') && isset($data['filieres'])) {
            $io->section('Migration des filières...');
            $progressBar = $io->createProgressBar(count($data['filieres']));
            $progressBar->start();

            foreach ($data['filieres'] as $oldData) {
                try {
                    $filiere = $this->migrationService->migrateFiliere($oldData);
                    if ($filiere) {
                        if (!$dryRun) {
                            $this->em->persist($filiere);
                            $this->em->flush();
                        }
                        $stats['filieres']['success']++;
                    } else {
                        $stats['filieres']['errors']++;
                    }
                } catch (\Exception $e) {
                    $io->error('Erreur: ' . $e->getMessage());
                    $stats['filieres']['errors']++;
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Migrer les universités
        if (($entityType === 'all' || $entityType === 'universite') && isset($data['universites'])) {
            $io->section('Migration des universités...');
            $progressBar = $io->createProgressBar(count($data['universites']));
            $progressBar->start();

            foreach ($data['universites'] as $oldData) {
                try {
                    $universite = $this->migrationService->migrateUniversite($oldData);
                    if ($universite) {
                        if (!$dryRun) {
                            $this->em->persist($universite);
                            $this->em->flush();
                        }
                        $stats['universites']['success']++;
                    } else {
                        $stats['universites']['errors']++;
                    }
                } catch (\Exception $e) {
                    $io->error('Erreur: ' . $e->getMessage());
                    $stats['universites']['errors']++;
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        // Afficher les statistiques
        $this->displayStats($io, $stats, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Migration depuis l'ancienne base de données
     */
    private function migrateFromDatabase(SymfonyStyle $io, string $entityType, ?int $limit, int $offset, bool $dryRun): int
    {
        $io->section('Migration depuis l\'ancienne base de données');

        // Note: Cette méthode nécessite une connexion à l'ancienne base
        // Vous devrez adapter selon votre configuration
        
        $io->warning('Migration depuis base de données non implémentée dans cette version.');
        $io->note('Utilisez --source-file pour migrer depuis un fichier JSON.');

        return Command::SUCCESS;
    }

    /**
     * Affiche les statistiques de migration
     */
    private function displayStats(SymfonyStyle $io, array $stats, bool $dryRun): void
    {
        $io->section('Statistiques de migration' . ($dryRun ? ' (DRY-RUN)' : ''));

        $table = [];
        foreach ($stats as $entity => $stat) {
            $total = $stat['success'] + $stat['errors'];
            $table[] = [
                ucfirst($entity),
                $stat['success'],
                $stat['errors'],
                $total,
                $total > 0 ? round(($stat['success'] / $total) * 100, 2) . '%' : '0%'
            ];
        }

        $io->table(
            ['Entité', 'Succès', 'Erreurs', 'Total', 'Taux de succès'],
            $table
        );

        if (!$dryRun) {
            $io->success('Migration terminée avec succès!');
        } else {
            $io->note('Mode DRY-RUN: Aucune donnée n\'a été écrite.');
        }
    }
}
