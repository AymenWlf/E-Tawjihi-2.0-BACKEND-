<?php

namespace App\Command;

use App\Entity\City;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-regions-cities',
    description: 'Import regions and cities from e-tawjihi database',
)]
class ImportRegionsCitiesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Database host')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Database port')
            ->addOption('dbname', null, InputOption::VALUE_OPTIONAL, 'Database name')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'Database user')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Database password')
            ->addOption('env-file', null, InputOption::VALUE_OPTIONAL, 'Path to e-tawjihi .env file', __DIR__ . '/../../../e-tawjihi/.env')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Regions and Cities from e-tawjihi database');

        // Essayer de lire la configuration depuis le fichier .env du projet e-tawjihi
        $envFile = $input->getOption('env-file');
        $oldDbConfig = $this->parseDatabaseUrlFromEnv($envFile, $io);

        // Les options en ligne de commande ont la priorité
        if ($input->getOption('host')) {
            $oldDbConfig['host'] = $input->getOption('host');
        }
        if ($input->getOption('port')) {
            $oldDbConfig['port'] = $input->getOption('port');
        }
        if ($input->getOption('dbname')) {
            $oldDbConfig['dbname'] = $input->getOption('dbname');
        }
        if ($input->getOption('user')) {
            $oldDbConfig['user'] = $input->getOption('user');
        }
        if ($input->getOption('password')) {
            $oldDbConfig['password'] = $input->getOption('password');
        }

        try {
            // Connexion à l'ancienne base de données
            $oldDbConnection = new \PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $oldDbConfig['host'],
                    $oldDbConfig['port'],
                    $oldDbConfig['dbname']
                ),
                $oldDbConfig['user'],
                $oldDbConfig['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );

            $io->section('Step 1: Importing Regions...');
            
            // Récupérer toutes les régions
            $regionsStmt = $oldDbConnection->query('SELECT id, titre, logitude, latitude FROM region');
            $regions = $regionsStmt->fetchAll();
            
            $io->progressStart(count($regions));
            $regionMap = []; // Pour mapper les anciens IDs aux nouveaux IDs
            
            foreach ($regions as $oldRegion) {
                $region = new Region();
                $region->setTitre($oldRegion['titre']);
                $region->setLongitude($oldRegion['logitude']); // Note: typo dans l'ancienne DB
                $region->setLatitude($oldRegion['latitude']);
                
                $this->em->persist($region);
                $this->em->flush();
                
                $regionMap[$oldRegion['id']] = $region->getId();
                $io->progressAdvance();
            }
            
            $io->progressFinish();
            $io->success(sprintf('Imported %d regions', count($regions)));

            $io->section('Step 2: Importing Cities...');
            
            // Récupérer toutes les villes
            $citiesStmt = $oldDbConnection->query('SELECT id, titre, longitude, latitude, region_id FROM ville');
            $cities = $citiesStmt->fetchAll();
            
            $io->progressStart(count($cities));
            $imported = 0;
            $skipped = 0;
            
            foreach ($cities as $oldCity) {
                $city = new City();
                $city->setTitre($oldCity['titre']);
                $city->setLongitude($oldCity['longitude'] ? (float)$oldCity['longitude'] : null);
                $city->setLatitude($oldCity['latitude'] ? (float)$oldCity['latitude'] : null);
                
                // Associer la région si elle existe
                if ($oldCity['region_id'] && isset($regionMap[$oldCity['region_id']])) {
                    $region = $this->em->getRepository(Region::class)->find($regionMap[$oldCity['region_id']]);
                    if ($region) {
                        $city->setRegion($region);
                    }
                }
                
                $this->em->persist($city);
                $imported++;
                
                // Flush par batch de 100
                if ($imported % 100 === 0) {
                    $this->em->flush();
                }
                
                $io->progressAdvance();
            }
            
            // Flush final
            $this->em->flush();
            $io->progressFinish();
            
            $io->success(sprintf('Imported %d cities', $imported));
            $io->note('Import completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during import: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function parseDatabaseUrlFromEnv(string $envFile, SymfonyStyle $io): array
    {
        $defaultConfig = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'e-tawjihi_vf',
            'user' => 'root',
            'password' => '',
        ];

        if (!file_exists($envFile)) {
            $io->warning(sprintf('Env file not found: %s. Using default values.', $envFile));
            return $defaultConfig;
        }

        $envContent = file_get_contents($envFile);
        
        // Chercher la ligne DATABASE_URL non commentée
        if (preg_match('/^DATABASE_URL="([^"]+)"/m', $envContent, $matches)) {
            $databaseUrl = $matches[1];
            
            // Parser l'URL: mysql://user:password@host:port/dbname?params
            if (preg_match('/^mysql:\/\/(?:([^:]+)(?::([^@]+))?@)?([^:]+)(?::(\d+))?\/([^?]+)/', $databaseUrl, $urlMatches)) {
                $user = $urlMatches[1] ?? 'root';
                $password = $urlMatches[2] ?? '';
                $host = $urlMatches[3] ?? '127.0.0.1';
                $port = $urlMatches[4] ?? '3306';
                $dbname = $urlMatches[5] ?? 'e-tawjihi_vf';
                
                $io->info(sprintf('Found database config in .env: %s@%s:%s/%s', $user, $host, $port, $dbname));
                
                return [
                    'host' => $host,
                    'port' => $port,
                    'dbname' => $dbname,
                    'user' => $user,
                    'password' => $password,
                ];
            }
        }

        $io->warning('Could not parse DATABASE_URL from .env file. Using default values.');
        return $defaultConfig;
    }
}
