<?php

namespace App\Command;

use App\Entity\City;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-regions-cities',
    description: 'Populate regions and cities of Morocco',
)]
class PopulateRegionsCitiesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Regions and Cities of Morocco');

        // Données des régions du Maroc avec leurs coordonnées approximatives
        $regionsData = [
            ['Tanger-Tétouan-Al Hoceïma', '-5.8333', '35.7667'],
            ['Oriental', '-2.9333', '34.6833'],
            ['Fès-Meknès', '-4.9833', '34.0333'],
            ['Rabat-Salé-Kénitra', '-6.8500', '34.0200'],
            ['Béni Mellal-Khénifra', '-6.3500', '32.3333'],
            ['Casablanca-Settat', '-7.5833', '33.5333'],
            ['Marrakech-Safi', '-7.9833', '31.6333'],
            ['Drâa-Tafilalet', '-4.0000', '31.6167'],
            ['Souss-Massa', '-9.6000', '30.4167'],
            ['Guelmim-Oued Noun', '-10.0500', '28.9833'],
            ['Laâyoune-Sakia El Hamra', '-13.2000', '27.1500'],
            ['Dakhla-Oued Ed-Dahab', '-15.9333', '23.7167'],
        ];

        // Données des villes par région
        $citiesData = [
            'Tanger-Tétouan-Al Hoceïma' => [
                ['Tanger', '-5.8333', '35.7667'],
                ['Tétouan', '-5.3667', '35.5667'],
                ['Al Hoceïma', '-4.2667', '35.2500'],
                ['Larache', '-6.1500', '35.1833'],
                ['Chefchaouen', '-5.2667', '35.1667'],
                ['Ouezzane', '-5.5833', '34.8000'],
                ['Fnideq', '-5.3500', '35.8500'],
                ['M\'diq', '-5.3167', '35.6833'],
            ],
            'Oriental' => [
                ['Oujda', '-2.9333', '34.6833'],
                ['Nador', '-2.9333', '35.1667'],
                ['Berkane', '-2.3167', '34.9167'],
                ['Taourirt', '-2.9000', '34.4167'],
                ['Jerada', '-2.1667', '34.3167'],
                ['Figuig', '-1.2333', '32.1167'],
                ['Bouarfa', '-2.4167', '32.5167'],
            ],
            'Fès-Meknès' => [
                ['Fès', '-4.9833', '34.0333'],
                ['Meknès', '-5.5500', '33.8833'],
                ['Taza', '-4.0167', '34.2167'],
                ['Sefrou', '-4.8333', '33.8333'],
                ['Moulay Yacoub', '-5.1833', '34.0833'],
                ['Ifrane', '-5.1000', '33.5167'],
                ['El Hajeb', '-5.3667', '33.6833'],
                ['Azrou', '-5.2167', '33.4333'],
            ],
            'Rabat-Salé-Kénitra' => [
                ['Rabat', '-6.8500', '34.0200'],
                ['Salé', '-6.8167', '34.0500'],
                ['Kénitra', '-6.5833', '34.2500'],
                ['Témara', '-6.9167', '33.9167'],
                ['Skhirat', '-7.0333', '33.8500'],
                ['Mohammédia', '-7.3833', '33.6833'],
                ['Sidi Kacem', '-5.7000', '34.2167'],
                ['Sidi Slimane', '-5.9167', '34.2667'],
            ],
            'Béni Mellal-Khénifra' => [
                ['Béni Mellal', '-6.3500', '32.3333'],
                ['Khénifra', '-5.6667', '32.9333'],
                ['Khouribga', '-6.9000', '32.8833'],
                ['Fquih Ben Salah', '-6.5333', '32.5000'],
                ['Azilal', '-6.5667', '31.9667'],
                ['Kasba Tadla', '-6.2667', '32.6000'],
            ],
            'Casablanca-Settat' => [
                ['Casablanca', '-7.5833', '33.5333'],
                ['Settat', '-7.6167', '33.0000'],
                ['El Jadida', '-8.5000', '33.2500'],
                ['Berrechid', '-7.5833', '33.2667'],
                ['Mohammédia', '-7.3833', '33.6833'],
                ['Benslimane', '-7.1167', '33.6167'],
                ['Sidi Bennour', '-8.4167', '32.6500'],
                ['Nouaceur', '-7.6167', '33.3667'],
            ],
            'Marrakech-Safi' => [
                ['Marrakech', '-7.9833', '31.6333'],
                ['Safi', '-9.2167', '32.2833'],
                ['Essaouira', '-9.7667', '31.5167'],
                ['El Kelâa des Sraghna', '-7.4000', '32.0500'],
                ['Youssoufia', '-8.5333', '32.2500'],
                ['Chichaoua', '-8.7667', '31.5500'],
                ['Rehamna', '-7.9333', '32.2833'],
                ['Al Haouz', '-7.9167', '31.5167'],
            ],
            'Drâa-Tafilalet' => [
                ['Errachidia', '-4.4167', '31.9333'],
                ['Ouarzazate', '-6.9167', '30.9167'],
                ['Zagora', '-5.8333', '30.3167'],
                ['Tinghir', '-5.5167', '31.5167'],
                ['Midelt', '-4.7500', '32.6833'],
                ['Rissani', '-4.2667', '31.2833'],
            ],
            'Souss-Massa' => [
                ['Agadir', '-9.6000', '30.4167'],
                ['Inezgane', '-9.5333', '30.3667'],
                ['Taroudant', '-8.8667', '30.4667'],
                ['Tiznit', '-9.7167', '29.7167'],
                ['Oulad Teima', '-9.2167', '30.4000'],
                ['Aït Melloul', '-9.4833', '30.3333'],
                ['Biougra', '-9.3667', '30.2167'],
            ],
            'Guelmim-Oued Noun' => [
                ['Guelmim', '-10.0500', '28.9833'],
                ['Tan-Tan', '-11.0833', '28.4333'],
                ['Sidi Ifni', '-10.1667', '29.3833'],
                ['Assa', '-9.4167', '28.6167'],
                ['Foum Zguid', '-8.8667', '30.0667'],
            ],
            'Laâyoune-Sakia El Hamra' => [
                ['Laâyoune', '-13.2000', '27.1500'],
                ['Boujdour', '-14.4167', '26.1333'],
                ['Tarfaya', '-12.9167', '27.9333'],
                ['Smara', '-11.6667', '26.7333'],
            ],
            'Dakhla-Oued Ed-Dahab' => [
                ['Dakhla', '-15.9333', '23.7167'],
                ['Aousserd', '-15.0833', '22.5500'],
            ],
        ];

        try {
            $io->section('Step 1: Creating Regions...');
            
            $regionMap = [];
            $io->progressStart(count($regionsData));
            
            foreach ($regionsData as $regionInfo) {
                [$name, $longitude, $latitude] = $regionInfo;
                
                // Vérifier si la région existe déjà
                $existingRegion = $this->em->getRepository(Region::class)->findOneBy(['titre' => $name]);
                
                if (!$existingRegion) {
                    $region = new Region();
                    $region->setTitre($name);
                    $region->setLongitude($longitude);
                    $region->setLatitude($latitude);
                    
                    $this->em->persist($region);
                    $this->em->flush();
                    
                    $regionMap[$name] = $region;
                    $io->progressAdvance();
                } else {
                    $regionMap[$name] = $existingRegion;
                    $io->progressAdvance();
                }
            }
            
            $io->progressFinish();
            $io->success(sprintf('Processed %d regions', count($regionsData)));

            $io->section('Step 2: Creating Cities...');
            
            $totalCities = array_sum(array_map('count', $citiesData));
            $io->progressStart($totalCities);
            $imported = 0;
            $skipped = 0;
            
            foreach ($citiesData as $regionName => $cities) {
                $region = $regionMap[$regionName] ?? null;
                
                if (!$region) {
                    $io->warning(sprintf('Region "%s" not found, skipping cities', $regionName));
                    continue;
                }
                
                foreach ($cities as $cityInfo) {
                    [$cityName, $longitude, $latitude] = $cityInfo;
                    
                    // Vérifier si la ville existe déjà
                    $existingCity = $this->em->getRepository(City::class)->findOneBy(['titre' => $cityName]);
                    
                    if (!$existingCity) {
                        $city = new City();
                        $city->setTitre($cityName);
                        $city->setLongitude($longitude ? (float)$longitude : null);
                        $city->setLatitude($latitude ? (float)$latitude : null);
                        $city->setRegion($region);
                        
                        $this->em->persist($city);
                        $imported++;
                    } else {
                        // Mettre à jour la région si elle n'est pas définie
                        if (!$existingCity->getRegion()) {
                            $existingCity->setRegion($region);
                            $this->em->persist($existingCity);
                        }
                        $skipped++;
                    }
                    
                    // Flush par batch de 50
                    if (($imported + $skipped) % 50 === 0) {
                        $this->em->flush();
                    }
                    
                    $io->progressAdvance();
                }
            }
            
            // Flush final
            $this->em->flush();
            $io->progressFinish();
            
            $io->success(sprintf('Imported %d new cities, %d already existed', $imported, $skipped));
            $io->note('Population completed successfully!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during population: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
