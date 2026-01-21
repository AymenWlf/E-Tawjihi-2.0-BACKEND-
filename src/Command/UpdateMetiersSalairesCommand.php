<?php

namespace App\Command;

use App\Entity\Metier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-metiers-salaires',
    description: 'Met à jour les salaires des métiers qui n\'en ont pas encore'
)]
class UpdateMetiersSalairesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des salaires des métiers');

        $metiersRepository = $this->entityManager->getRepository(Metier::class);
        $metiers = $metiersRepository->findAll();

        $salairesParMetier = $this->getSalairesParMetier();
        $updated = 0;

        foreach ($metiers as $metier) {
            $nom = $metier->getNom();
            $hasSalaire = $metier->getSalaireMin() !== null || $metier->getSalaireMax() !== null;

            // Si le métier n'a pas de salaire, on essaie de le trouver dans notre mapping
            if (!$hasSalaire && isset($salairesParMetier[$nom])) {
                $salaireData = $salairesParMetier[$nom];
                $metier->setSalaireMin($salaireData['min']);
                $metier->setSalaireMax($salaireData['max']);
                $this->entityManager->persist($metier);
                $updated++;
                $io->info(sprintf('Mise à jour: %s - %s - %s Dhs', $nom, rtrim(rtrim((string)$salaireData['min'], '0'), '.'), rtrim(rtrim((string)$salaireData['max'], '0'), '.')));
            } elseif (!$hasSalaire) {
                // Si pas trouvé dans le mapping, on utilise des salaires par défaut selon le niveau d'accessibilité
                $salaireData = $this->getSalaireParNiveau($metier->getNiveauAccessibilite());
                $metier->setSalaireMin($salaireData['min']);
                $metier->setSalaireMax($salaireData['max']);
                $this->entityManager->persist($metier);
                $updated++;
                $io->info(sprintf('Mise à jour (par défaut): %s - %s - %s Dhs', $nom, rtrim(rtrim((string)$salaireData['min'], '0'), '.'), rtrim(rtrim((string)$salaireData['max'], '0'), '.')));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d métiers mis à jour avec succès !', $updated));

        return Command::SUCCESS;
    }

    private function getSalaireParNiveau(?string $niveau): array
    {
        return match($niveau) {
            'Facile' => ['min' => '4000', 'max' => '10000'],
            'Moyenne' => ['min' => '6000', 'max' => '15000'],
            'Difficile' => ['min' => '8000', 'max' => '20000'],
            'Très difficile' => ['min' => '10000', 'max' => '25000'],
            'Variable' => ['min' => '5000', 'max' => '20000'],
            default => ['min' => '6000', 'max' => '15000'],
        };
    }

    private function getSalairesParMetier(): array
    {
        return [
            // Santé
            'Médecin généraliste' => ['min' => '10000', 'max' => '25000'],
            'Médecin spécialiste' => ['min' => '15000', 'max' => '40000'],
            'Chirurgien' => ['min' => '20000', 'max' => '50000'],
            'Pharmacien' => ['min' => '8000', 'max' => '20000'],
            'Infirmier' => ['min' => '5000', 'max' => '12000'],
            'Kinésithérapeute' => ['min' => '6000', 'max' => '15000'],
            'Psychologue clinicien' => ['min' => '7000', 'max' => '18000'],
            'Dentiste' => ['min' => '10000', 'max' => '30000'],
            'Vétérinaire' => ['min' => '8000', 'max' => '18000'],
            'Sage-femme' => ['min' => '6000', 'max' => '14000'],
            'Optométriste' => ['min' => '7000', 'max' => '16000'],
            'Radiologue' => ['min' => '12000', 'max' => '30000'],
            'Anesthésiste' => ['min' => '15000', 'max' => '35000'],
            'Pédiatre' => ['min' => '12000', 'max' => '28000'],
            'Diététicien(ne)' => ['min' => '5000', 'max' => '12000'],

            // Technologie
            'Ingénieur informatique' => ['min' => '8000', 'max' => '20000'],
            'Ingénieur civil' => ['min' => '9000', 'max' => '18000'],
            'Ingénieur électrique' => ['min' => '8500', 'max' => '19000'],
            'Architecte' => ['min' => '8000', 'max' => '20000'],
            'Développeur web' => ['min' => '7000', 'max' => '18000'],
            'Data scientist' => ['min' => '10000', 'max' => '25000'],
            'Expert en cybersécurité' => ['min' => '12000', 'max' => '30000'],

            // Éducation
            'Enseignant primaire' => ['min' => '6000', 'max' => '12000'],
            'Enseignant secondaire' => ['min' => '7000', 'max' => '14000'],
            'Professeur universitaire' => ['min' => '10000', 'max' => '25000'],
            'Professeur de langues' => ['min' => '6500', 'max' => '13000'],
            'Conseiller d\'orientation' => ['min' => '7000', 'max' => '15000'],
            'Directeur d\'école' => ['min' => '12000', 'max' => '25000'],
            'Éducateur spécialisé' => ['min' => '6500', 'max' => '13000'],

            // Finance
            'Expert-comptable' => ['min' => '9000', 'max' => '20000'],
            'Contrôleur de gestion' => ['min' => '8000', 'max' => '18000'],
            'Analyste financier' => ['min' => '10000', 'max' => '25000'],
            'Banquier d\'affaires' => ['min' => '12000', 'max' => '30000'],
            'Conseiller financier' => ['min' => '7000', 'max' => '20000'],
            'Auditeur' => ['min' => '9000', 'max' => '22000'],
            'Gestionnaire de patrimoine' => ['min' => '10000', 'max' => '25000'],

            // Juridique
            'Avocat pénaliste' => ['min' => '8000', 'max' => '30000'],
            'Avocat d\'affaires' => ['min' => '10000', 'max' => '40000'],
            'Notaire' => ['min' => '12000', 'max' => '35000'],
            'Juriste d\'entreprise' => ['min' => '8000', 'max' => '20000'],
            'Magistrat' => ['min' => '15000', 'max' => '30000'],
            'Médiateur' => ['min' => '7000', 'max' => '18000'],
            'Juriste en propriété intellectuelle' => ['min' => '9000', 'max' => '22000'],

            // Arts
            'Designer graphique' => ['min' => '6000', 'max' => '15000'],
            'Photographe' => ['min' => '5000', 'max' => '20000'],
            'Designer produit' => ['min' => '7000', 'max' => '18000'],
            'Styliste' => ['min' => '5000', 'max' => '25000'],
            'Décorateur d\'intérieur' => ['min' => '6000', 'max' => '16000'],
            'Musicien' => ['min' => '4000', 'max' => '30000'],
            'Réalisateur' => ['min' => '8000', 'max' => '40000'],

            // Communication
            'Journaliste' => ['min' => '7000', 'max' => '15000'],
            'Rédacteur web' => ['min' => '6000', 'max' => '14000'],
            'Community manager' => ['min' => '6000', 'max' => '13000'],
            'Responsable communication' => ['min' => '9000', 'max' => '20000'],
            'Présentateur TV/Radio' => ['min' => '10000', 'max' => '30000'],
            'Producteur audiovisuel' => ['min' => '8000', 'max' => '25000'],
            'Relations publiques' => ['min' => '7000', 'max' => '18000'],

            // Commerce
            'Commercial B2B' => ['min' => '6000', 'max' => '20000'],
            'Responsable commercial' => ['min' => '10000', 'max' => '25000'],
            'Business developer' => ['min' => '8000', 'max' => '22000'],
            'Responsable export' => ['min' => '9000', 'max' => '24000'],
            'Négociateur immobilier' => ['min' => '7000', 'max' => '30000'],
            'E-commerce manager' => ['min' => '8000', 'max' => '20000'],
            'Acheteur' => ['min' => '7000', 'max' => '18000'],

            // Marketing
            'Responsable marketing' => ['min' => '10000', 'max' => '25000'],
            'Digital marketer' => ['min' => '8000', 'max' => '20000'],
            'SEO/SEM specialist' => ['min' => '7000', 'max' => '18000'],
            'Brand manager' => ['min' => '9000', 'max' => '22000'],
            'Product marketing manager' => ['min' => '10000', 'max' => '24000'],
            'Growth hacker' => ['min' => '8000', 'max' => '25000'],
            'Analyste marketing' => ['min' => '7000', 'max' => '18000'],

            // RH
            'Responsable RH' => ['min' => '10000', 'max' => '25000'],
            'Recruteur' => ['min' => '7000', 'max' => '18000'],
            'Consultant RH' => ['min' => '8000', 'max' => '20000'],
            'Responsable formation' => ['min' => '9000', 'max' => '22000'],
            'Compensation & benefits' => ['min' => '9000', 'max' => '21000'],
            'HRBP (Business partner)' => ['min' => '10000', 'max' => '24000'],
            'Chief happiness officer' => ['min' => '8000', 'max' => '20000'],

            // Transport
            'Pilote de ligne' => ['min' => '15000', 'max' => '40000'],
            'Contrôleur aérien' => ['min' => '12000', 'max' => '30000'],
            'Capitaine de navire' => ['min' => '10000', 'max' => '25000'],
            'Conducteur de train' => ['min' => '7000', 'max' => '15000'],
            'Logisticien' => ['min' => '6000', 'max' => '14000'],
            'Supply chain manager' => ['min' => '9000', 'max' => '20000'],
            'Responsable entrepôt' => ['min' => '7000', 'max' => '16000'],

            // Hôtellerie
            'Chef cuisinier' => ['min' => '6000', 'max' => '18000'],
            'Directeur d\'hôtel' => ['min' => '12000', 'max' => '30000'],
            'Réceptionniste' => ['min' => '4000', 'max' => '10000'],
            'Concierge' => ['min' => '5000', 'max' => '12000'],
            'Maître d\'hôtel' => ['min' => '6000', 'max' => '14000'],
            'Guide touristique' => ['min' => '5000', 'max' => '13000'],
            'Wedding planner' => ['min' => '6000', 'max' => '20000'],

            // Public
            'Diplomate' => ['min' => '15000', 'max' => '35000'],
            'Administrateur civil' => ['min' => '10000', 'max' => '25000'],
            'Contrôleur des impôts' => ['min' => '8000', 'max' => '18000'],
            'Douanier' => ['min' => '7000', 'max' => '16000'],
            'Policier' => ['min' => '8500', 'max' => '19000'],
            'Gendarme' => ['min' => '9000', 'max' => '20000'],
            'Pompier' => ['min' => '8000', 'max' => '18000'],

            // Entrepreneuriat
            'Chef d\'entreprise' => ['min' => '10000', 'max' => '100000'],
            'Consultant en stratégie' => ['min' => '12000', 'max' => '40000'],
            'Consultant IT' => ['min' => '8000', 'max' => '25000'],
            'Chef de projet' => ['min' => '9000', 'max' => '22000'],
            'Entrepreneur tech' => ['min' => '10000', 'max' => '100000'],
            'Consultant freelance' => ['min' => '8000', 'max' => '30000'],
            'Business analyst' => ['min' => '8000', 'max' => '20000'],

            // Recherche
            'Chercheur scientifique' => ['min' => '10000', 'max' => '25000'],
            'Ingénieur R&D' => ['min' => '9000', 'max' => '22000'],
            'Laboratoire privé' => ['min' => '8000', 'max' => '20000'],
            'Statisticien' => ['min' => '8000', 'max' => '19000'],
            'Économiste' => ['min' => '9000', 'max' => '21000'],
            'Biologiste' => ['min' => '8000', 'max' => '20000'],
            'Physicien' => ['min' => '10000', 'max' => '24000'],
        ];
    }
}
