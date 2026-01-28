<?php

namespace App\Command;

use App\Service\OldEtawjihiClientService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-check-old-clients',
    description: 'Test check-old-clients: appelle l’API old (localhost:8000 en dev) et affiche si les numéros sont clients.',
)]
class TestCheckOldClientsCommand extends Command
{
    public function __construct(
        private OldEtawjihiClientService $oldClientService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'tel',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Numéro(s) à tester (ex: 0614369090)',
            ['0614369090']
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tel = $input->getArgument('tel');
        $tel = \is_array($tel) ? $tel : [$tel];
        $tel = array_values(array_filter(array_map('trim', $tel)));

        if ($tel === []) {
            $io->error('Fournissez au moins un numéro (ex: php bin/console app:test-check-old-clients 0614369090)');
            return Command::FAILURE;
        }

        $io->title('Test check-old-clients');
        $io->writeln('Numéros : ' . implode(', ', $tel));
        $io->newLine();

        $data = $this->oldClientService->checkClients($tel);

        foreach ($data as $num => $client) {
            if ($client === null) {
                $io->writeln(sprintf('<comment>%s</comment> → <fg=red>Non client</>', $num));
                continue;
            }
            $io->writeln(sprintf('<comment>%s</comment> → <fg=green>Client</>', $num));
            $io->writeln(sprintf('  Contrat: %s | Nom: %s %s | Total payé: %s', 
                $client['numeroContrat'] ?? '-',
                $client['prenom'] ?? '',
                $client['nom'] ?? '',
                isset($client['totalPaye']) ? (string) $client['totalPaye'] . ' (totalPaye)' : '-'
            ));
            if (!empty($client['services']) && \is_array($client['services'])) {
                $svc = array_map(fn ($s) => ($s['nom'] ?? '') . ' (totalPaye: ' . ($s['totalPaye'] ?? '') . ')', $client['services']);
                $io->writeln('  Services: ' . implode(' ; ', $svc));
            }
            $io->newLine();
        }

        $io->success('Terminé.');
        return Command::SUCCESS;
    }
}
