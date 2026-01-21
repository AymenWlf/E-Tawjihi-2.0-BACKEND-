<?php

namespace App\Command;

use App\Entity\TestSession;
use App\Entity\Secteur;
use App\Repository\TestSessionRepository;
use App\Repository\UserRepository;
use App\Repository\SecteurRepository;
use App\Service\SecteurRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-recommendation',
    description: 'Analyse les données du test d\'orientation d\'un utilisateur et les recommandations par secteur'
)]
class AnalyzeRecommendationCommand extends Command
{
    public function __construct(
        private TestSessionRepository $testSessionRepository,
        private UserRepository $userRepository,
        private SecteurRepository $secteurRepository,
        private SecteurRecommendationService $recommendationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::OPTIONAL, 'ID de l\'utilisateur à analyser')
            ->setHelp('Cette commande analyse les données du test d\'orientation et les recommandations par secteur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $userId = $input->getArgument('user_id');
        
        // Si pas d'ID fourni, chercher le premier utilisateur avec un test complété
        if (!$userId) {
            $io->title('Recherche d\'un utilisateur avec un test complété...');
            
            $testSession = $this->entityManager->createQueryBuilder()
                ->select('ts')
                ->from(TestSession::class, 'ts')
                ->where('ts.isCompleted = true')
                ->andWhere('ts.testType = :testType')
                ->setParameter('testType', 'orientation')
                ->orderBy('ts.completedAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            
            if (!$testSession) {
                $io->error('Aucun test complété trouvé dans la base de données.');
                return Command::FAILURE;
            }
            
            $userId = $testSession->getUser()->getId();
            $io->success(sprintf('Utilisateur trouvé: ID %d', $userId));
        }
        
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $io->error(sprintf('Utilisateur ID %d non trouvé.', $userId));
            return Command::FAILURE;
        }
        
        $io->title(sprintf('Analyse du test d\'orientation - Utilisateur ID: %d', $userId));
        $io->section('Informations utilisateur');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID', $user->getId()],
                ['Email', $user->getEmail() ?? 'N/A'],
                ['Téléphone', $user->getPhone() ?? 'N/A'],
            ]
        );
        
        // Récupérer le test d'orientation
        $testSession = $this->testSessionRepository->findByUser($userId, 'orientation');
        
        if (!$testSession || !$testSession->isIsCompleted()) {
            $io->error('Aucun test d\'orientation complété trouvé pour cet utilisateur.');
            return Command::FAILURE;
        }
        
        $io->section('Informations du test');
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Session ID', $testSession->getId()],
                ['Type', $testSession->getTestType()],
                ['Démarré le', $testSession->getStartedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
                ['Complété le', $testSession->getCompletedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
                ['Durée', $testSession->getDuration() ? $testSession->getDuration() . ' secondes' : 'N/A'],
                ['Langue', $testSession->getLanguage() ?? 'N/A'],
            ]
        );
        
        $currentStep = $testSession->getCurrentStep();
        
        // Afficher la structure complète de currentStep
        $io->section('Structure des données (currentStep)');
        $io->writeln('<comment>Clés disponibles:</comment> ' . implode(', ', array_keys($currentStep)));
        $io->newLine();
        
        // Afficher les données RIASEC (essayer plusieurs formats)
        $io->section('Données RIASEC');
        $riasecData = null;
        $riasecScores = null;
        $dominantProfiles = [];
        
        // Essayer différents formats
        if (isset($currentStep['riasec']['riasec']['scores'])) {
            $riasecData = $currentStep['riasec'];
            $riasecScores = $currentStep['riasec']['riasec']['scores'];
            $dominantProfiles = $currentStep['riasec']['riasec']['dominantProfile'] ?? [];
        } elseif (isset($currentStep['riasec']['scores'])) {
            $riasecData = $currentStep['riasec'];
            $riasecScores = $currentStep['riasec']['scores'];
            $dominantProfiles = $currentStep['riasec']['dominantProfile'] ?? [];
        }
        
        if ($riasecScores && is_array($riasecScores)) {
            $io->table(
                ['Profil RIASEC', 'Score'],
                array_map(function($profile, $score) {
                    return [$profile, $score];
                }, array_keys($riasecScores), $riasecScores)
            );
            
            if (!empty($dominantProfiles)) {
                $io->writeln('<info>Profils dominants:</info> ' . implode(', ', $dominantProfiles));
            }
        } else {
            $io->warning('Données RIASEC non disponibles dans le format attendu');
            if (isset($currentStep['riasec'])) {
                $io->writeln('<comment>Structure trouvée:</comment>');
                $io->writeln(json_encode($currentStep['riasec'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Afficher les données de personnalité
        $io->section('Données de personnalité');
        $personalityScores = null;
        $dominantTraits = [];
        
        if (isset($currentStep['personality']['personality']['scores'])) {
            $personalityScores = $currentStep['personality']['personality']['scores'];
            $dominantTraits = $currentStep['personality']['personality']['dominantTraits'] ?? [];
        } elseif (isset($currentStep['personality']['scores'])) {
            $personalityScores = $currentStep['personality']['scores'];
            $dominantTraits = $currentStep['personality']['dominantTraits'] ?? [];
        }
        
        if ($personalityScores && is_array($personalityScores)) {
            $io->table(
                ['Trait', 'Score'],
                array_map(function($trait, $score) {
                    return [$trait, $score];
                }, array_keys($personalityScores), $personalityScores)
            );
            
            if (!empty($dominantTraits)) {
                $io->writeln('<info>Traits dominants:</info> ' . implode(', ', $dominantTraits));
            }
        } else {
            $io->warning('Données de personnalité non disponibles dans le format attendu');
            if (isset($currentStep['personality'])) {
                $io->writeln('<comment>Structure trouvée:</comment>');
                $io->writeln(json_encode($currentStep['personality'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Afficher les données d'aptitude
        $io->section('Données d\'aptitude');
        $overallScore = null;
        $aptitudeScores = null;
        
        if (isset($currentStep['aptitude']['aptitude']['overallScore'])) {
            $overallScore = $currentStep['aptitude']['aptitude']['overallScore'];
            $aptitudeScores = $currentStep['aptitude']['aptitude']['scores'] ?? null;
        } elseif (isset($currentStep['aptitude']['overallScore'])) {
            $overallScore = $currentStep['aptitude']['overallScore'];
            $aptitudeScores = $currentStep['aptitude']['scores'] ?? null;
        }
        
        if ($overallScore !== null) {
            $io->writeln(sprintf('<info>Score global d\'aptitude:</info> %d/50', $overallScore));
            
            if ($aptitudeScores && is_array($aptitudeScores)) {
                $io->table(
                    ['Aptitude', 'Score'],
                    array_map(function($aptitude, $score) {
                        return [$aptitude, $score];
                    }, array_keys($aptitudeScores), $aptitudeScores)
                );
            }
        } else {
            $io->warning('Données d\'aptitude non disponibles dans le format attendu');
            if (isset($currentStep['aptitude'])) {
                $io->writeln('<comment>Structure trouvée:</comment>');
                $io->writeln(json_encode($currentStep['aptitude'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Afficher les données d'intérêts
        $io->section('Données d\'intérêts');
        $fieldInterests = null;
        $topInterests = [];
        
        if (isset($currentStep['interests']['interests']['fieldInterests'])) {
            $fieldInterests = $currentStep['interests']['interests']['fieldInterests'];
            $topInterests = $currentStep['interests']['interests']['topInterests'] ?? [];
        } elseif (isset($currentStep['interests']['fieldInterests'])) {
            $fieldInterests = $currentStep['interests']['fieldInterests'];
            $topInterests = $currentStep['interests']['topInterests'] ?? [];
        }
        
        if ($fieldInterests && is_array($fieldInterests)) {
            $io->table(
                ['Domaine d\'intérêt', 'Score'],
                array_map(function($field, $score) {
                    return [$field, $score];
                }, array_keys($fieldInterests), $fieldInterests)
            );
            
            if (!empty($topInterests)) {
                $io->writeln('<info>Top intérêts:</info> ' . implode(', ', $topInterests));
            }
        } else {
            $io->warning('Données d\'intérêts non disponibles dans le format attendu');
            if (isset($currentStep['interests'])) {
                $io->writeln('<comment>Structure trouvée:</comment>');
                $io->writeln(json_encode($currentStep['interests'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Calculer les recommandations
        $io->section('Calcul des recommandations par secteur');
        
        $scores = $this->recommendationService->calculateRecommendationScores($testSession);
        
        if (empty($scores)) {
            $io->error('Aucun score de recommandation calculé.');
            return Command::FAILURE;
        }
        
        // Récupérer les secteurs
        $secteurs = $this->secteurRepository->findBy(['isActivate' => true]);
        $secteursMap = [];
        foreach ($secteurs as $secteur) {
            $secteursMap[$secteur->getId()] = $secteur;
        }
        
        // Trier les scores par ordre décroissant
        arsort($scores);
        
        $io->writeln(sprintf('<info>Nombre de secteurs analysés:</info> %d', count($scores)));
        $io->newLine();
        
        // Afficher les top 10 secteurs
        $io->section('Top 10 secteurs recommandés');
        $topSecteurs = array_slice($scores, 0, 10, true);
        
        $tableData = [];
        $rank = 1;
        foreach ($topSecteurs as $secteurId => $score) {
            $secteur = $secteursMap[$secteurId] ?? null;
            if ($secteur) {
                $personnalites = $secteur->getPersonnalites() ?? [];
                $softSkills = $secteur->getSoftSkills() ?? [];
                
                $tableData[] = [
                    $rank++,
                    $secteur->getTitre(),
                    $score . '%',
                    implode(', ', array_slice($personnalites, 0, 3)),
                    implode(', ', array_slice($softSkills, 0, 3))
                ];
            }
        }
        
        $io->table(
            ['Rang', 'Secteur', 'Score', 'Personnalités (top 3)', 'Soft Skills (top 3)'],
            $tableData
        );
        
        // Analyse de la logique
        $io->section('Analyse de la logique de calcul');
        
        $io->writeln('<comment>Méthode de calcul:</comment>');
        $io->writeln('Le score de recommandation est calculé à partir de 5 composantes:');
        $io->listing([
            'RIASEC: 30% - Basé sur les profils RIASEC correspondant aux personnalités/softSkills du secteur',
            'Personnalité: 25% - Basé sur les traits de personnalité correspondant au secteur',
            'Aptitudes: 20% - Basé sur le score global d\'aptitude (ajusté pour secteurs techniques)',
            'Intérêts: 15% - Basé sur les domaines d\'intérêt correspondant au secteur',
            'Contraintes: 10% - Basé sur les contraintes (salaire, localisation, etc.)'
        ]);
        
        $io->newLine();
        $io->writeln('<comment>Points forts de la logique:</comment>');
        $io->listing([
            'Utilise les données réelles des secteurs (personnalités, softSkills)',
            'Mapping standardisé entre personnalités/softSkills et profils RIASEC',
            'Pondération différenciée des composantes',
            'Normalisation des scores pour éviter les valeurs uniformes'
        ]);
        
        $io->newLine();
        $io->writeln('<comment>Points d\'amélioration possibles:</comment>');
        $io->listing([
            'Le matching par mots-clés dans les titres de secteurs peut être amélioré',
            'Les scores par défaut à 0 si pas de données peuvent pénaliser certains secteurs',
            'La détection des secteurs techniques pourrait être plus précise',
            'Le matching des domaines d\'intérêt pourrait utiliser des synonymes/aliases'
        ]);
        
        // Afficher un exemple de calcul détaillé pour le premier secteur
        if (!empty($topSecteurs)) {
            $firstSecteurId = array_key_first($topSecteurs);
            $firstSecteur = $secteursMap[$firstSecteurId] ?? null;
            
            if ($firstSecteur) {
                $io->section(sprintf('Exemple de calcul détaillé: %s', $firstSecteur->getTitre()));
                
                $io->writeln('<info>Données du secteur:</info>');
                $io->table(
                    ['Propriété', 'Valeur'],
                    [
                        ['ID', $firstSecteur->getId()],
                        ['Titre', $firstSecteur->getTitre()],
                        ['Personnalités', implode(', ', $firstSecteur->getPersonnalites() ?? [])],
                        ['Soft Skills', implode(', ', $firstSecteur->getSoftSkills() ?? [])],
                    ]
                );
                
                $io->writeln('<info>Score final:</info> ' . $topSecteurs[$firstSecteurId] . '%');
            }
        }
        
        $io->success('Analyse terminée!');
        
        return Command::SUCCESS;
    }
}
