<?php

namespace App\Command;

use App\Repository\TestSessionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-user-test-data',
    description: 'Analyse les données de test pour un utilisateur'
)]
class AnalyzeUserTestDataCommand extends Command
{
    public function __construct(
        private TestSessionRepository $testSessionRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'utilisateur')
            ->setHelp('Analyse les données de test pour un utilisateur spécifique ou tous les utilisateurs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        
        if ($email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $io->error("Utilisateur non trouvé avec l'email: $email");
                return Command::FAILURE;
            }
            $this->analyzeUser($user, $io);
        } else {
            // Analyser tous les utilisateurs avec des sessions de test
            $sessions = $this->testSessionRepository->findAll();
            $users = [];
            foreach ($sessions as $session) {
                $userId = $session->getUser()->getId();
                if (!isset($users[$userId])) {
                    $users[$userId] = $session->getUser();
                }
            }
            
            $io->title('Analyse des données de test pour tous les utilisateurs');
            $io->writeln(sprintf('Nombre d\'utilisateurs avec des tests: %d', count($users)));
            
            foreach ($users as $user) {
                $this->analyzeUser($user, $io);
                $io->newLine();
                $io->writeln(str_repeat('=', 80));
                $io->newLine();
            }
        }
        
        return Command::SUCCESS;
    }
    
    private function analyzeUser($user, SymfonyStyle $io): void
    {
        $io->section(sprintf('Utilisateur: %s (ID: %d)', $user->getEmail(), $user->getId()));
        
        // Récupérer toutes les sessions de test
        $sessions = $this->testSessionRepository->findBy(['user' => $user]);
        
        if (empty($sessions)) {
            $io->warning('Aucune session de test trouvée pour cet utilisateur');
            return;
        }
        
        $io->writeln(sprintf('Nombre de sessions: %d', count($sessions)));
        
        foreach ($sessions as $session) {
            $io->writeln('');
            $io->writeln(sprintf('<info>Session ID: %d</info>', $session->getId()));
            
            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['Type de test', $session->getTestType()],
                    ['Démarré le', $session->getStartedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
                    ['Complété le', $session->getCompletedAt()?->format('Y-m-d H:i:s') ?? 'N/A'],
                    ['Durée (secondes)', $session->getDuration() ?? 'N/A'],
                    ['Langue', $session->getLanguage() ?? 'N/A'],
                    ['Est complété', $session->isIsCompleted() ? 'Oui' : 'Non'],
                    ['Nombre de questions', $session->getTotalQuestions() ?? 0],
                ]
            );
            
            // Analyser currentStep
            $currentStep = $session->getCurrentStep();
            if (!empty($currentStep)) {
                $io->writeln('<info>Données dans currentStep:</info>');
                
                // Étapes complétées
                $completedSteps = $currentStep['completedSteps'] ?? [];
                $io->writeln(sprintf('  - Étapes complétées (%d): %s', 
                    count($completedSteps), 
                    implode(', ', $completedSteps)
                ));
                
                // Étapes suivies
                $steps = $currentStep['steps'] ?? [];
                $io->writeln(sprintf('  - Étapes suivies (%d): %s', 
                    count($steps), 
                    implode(', ', $steps)
                ));
                
                // Étape actuelle
                $currentStepName = $currentStep['currentStep'] ?? 'N/A';
                $io->writeln(sprintf('  - Étape actuelle: %s', $currentStepName));
                
                // Analyser les données de chaque étape
                $io->writeln('<info>Données par étape:</info>');
                $allExpectedSteps = [
                    'personalInfo',
                    'riasec',
                    'personality',
                    'aptitude',
                    'interests',
                    'career',
                    'constraints',
                    'languages'
                ];
                
                foreach ($allExpectedSteps as $stepName) {
                    $stepData = $currentStep[$stepName] ?? null;
                    if ($stepData) {
                        $hasData = is_array($stepData) && !empty($stepData);
                        $dataKeys = $hasData ? array_keys($stepData) : [];
                        $io->writeln(sprintf('  - %s: %s (clés: %s)', 
                            $stepName,
                            $hasData ? '✓ Données présentes' : '✗ Aucune donnée',
                            !empty($dataKeys) ? implode(', ', array_slice($dataKeys, 0, 5)) . (count($dataKeys) > 5 ? '...' : '') : 'N/A'
                        ));
                        
                        // Afficher quelques détails pour certaines étapes
                        if ($stepName === 'riasec' && isset($stepData['riasec']['scores'])) {
                            $scores = $stepData['riasec']['scores'];
                            $io->writeln(sprintf('    Scores RIASEC: %s', json_encode($scores, JSON_PRETTY_PRINT)));
                        }
                        
                        if ($stepName === 'personality' && isset($stepData['personality']['scores'])) {
                            $scores = $stepData['personality']['scores'];
                            $io->writeln(sprintf('    Scores Personnalité: %s', json_encode($scores, JSON_PRETTY_PRINT)));
                        }
                    } else {
                        $io->writeln(sprintf('  - %s: ✗ Aucune donnée', $stepName));
                    }
                }
            } else {
                $io->warning('currentStep est vide');
            }
            
            // Analyser les métadonnées
            $metadata = $session->getMetadata();
            if (!empty($metadata)) {
                $io->writeln('<info>Métadonnées:</info>');
                $io->writeln(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            
            // Compter les réponses
            $answers = $session->getAnswers();
            $io->writeln(sprintf('<info>Nombre de réponses enregistrées: %d</info>', count($answers)));
            
            if (count($answers) > 0) {
                $answersByStep = [];
                foreach ($answers as $answer) {
                    $stepNum = $answer->getStepNumber();
                    if (!isset($answersByStep[$stepNum])) {
                        $answersByStep[$stepNum] = 0;
                    }
                    $answersByStep[$stepNum]++;
                }
                $io->writeln('Réponses par étape:');
                foreach ($answersByStep as $stepNum => $count) {
                    $io->writeln(sprintf('  - Étape %d: %d réponses', $stepNum, $count));
                }
            }
        }
    }
}
