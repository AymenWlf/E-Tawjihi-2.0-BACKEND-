<?php

namespace App\Command;

use App\Entity\Secteur;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-secteurs-keywords',
    description: 'Remplit automatiquement les mots-clés des secteurs pour améliorer la recherche'
)]
class PopulateSecteursKeywordsCommand extends Command
{
    // Mapping des codes de secteurs vers leurs mots-clés
    private const SECTEUR_KEYWORDS = [
        'ENVIRONNEMENT' => ['environnement', 'écologie', 'développement durable', 'protection environnement', 'vert', 'green', 'écologique', 'durable', 'climat'],
        'BATIMENT' => ['bâtiment', 'construction', 'architecture', 'génie civil', 'travaux publics', 'BTP', 'immobilier', 'urbanisme', 'construction'],
        'INDUSTRIE' => ['industrie', 'industriel', 'manufacturing', 'production', 'usine', 'fabrication', 'ingénierie industrielle'],
        'AGRICULTURE' => ['agriculture', 'agronomie', 'agroalimentaire', 'agro', 'ferme', 'rural', 'élevage', 'cultures'],
        'CULTURE' => ['culture', 'arts', 'artistique', 'patrimoine', 'musée', 'théâtre', 'spectacle', 'création artistique'],
        'SHS' => ['sciences humaines', 'sciences sociales', 'sociologie', 'psychologie', 'histoire', 'géographie', 'anthropologie', 'philosophie'],
        'DEFENSE' => ['défense', 'sécurité', 'armée', 'militaire', 'gendarmerie', 'police', 'sécurité nationale'],
        'JEUX_VIDEO' => ['jeux vidéo', 'gaming', 'game', 'ludique', 'interactive', 'numérique', 'e-sport', 'développement jeu'],
        'SPORT' => ['sport', 'athlétisme', 'éducation physique', 'coaching', 'entraînement', 'fitness', 'sportif'],
        'TOURISME' => ['tourisme', 'hôtellerie', 'restauration', 'voyage', 'accueil', 'touristique', 'hospitality', 'gastronomie'],
        'PECHE_MARITIME' => ['pêche', 'maritime', 'océan', 'mer', 'aquaculture', 'pisciculture', 'halieutique', 'navigation'],
        'SCIENCES_POLITIQUES' => ['sciences politiques', 'politique', 'gouvernement', 'droit public', 'administration', 'diplomatie', 'relations internationales'],
        'SANTE' => ['santé', 'médical', 'médecine', 'soins', 'pharmacie', 'infirmier', 'paramédical', 'biologie médicale'],
        'EDUCATION' => ['éducation', 'enseignement', 'pédagogie', 'formation', 'école', 'professeur', 'enseignant', 'apprentissage'],
        'INFORMATIQUE' => ['informatique', 'IT', 'technologie', 'tech', 'développement', 'programmation', 'software', 'computer', 'système information'],
        'TECHNOLOGIE' => ['technologie', 'tech', 'informatique', 'IT', 'innovation', 'digital', 'numérique', 'high tech', 'ingénierie'],
        'COMMERCE' => ['commerce', 'business', 'vente', 'marketing', 'commercial', 'trading', 'négoce', 'distribution'],
        'FINANCE' => ['finance', 'banque', 'banquier', 'comptabilité', 'gestion', 'financement', 'investissement', 'économie'],
        'DROIT' => ['droit', 'juridique', 'avocat', 'justice', 'législation', 'juriste', 'jurisprudence', 'notaire'],
        'JURIDIQUE' => ['juridique', 'droit', 'avocat', 'justice', 'législation', 'juriste', 'jurisprudence', 'notaire', 'judiciaire'],
        'ARTS' => ['arts', 'art', 'artistique', 'création', 'design', 'beaux-arts', 'arts plastiques', 'arts appliqués'],
        'COMMUNICATION' => ['communication', 'médias', 'journalisme', 'presse', 'audiovisuel', 'radio', 'télévision', 'multimédia'],
        'MARKETING' => ['marketing', 'publicité', 'communication', 'publicité', 'promotion', 'marketing digital', 'e-marketing'],
        'RESSOURCES_HUMAINES' => ['ressources humaines', 'RH', 'recrutement', 'gestion personnel', 'talent', 'carrière', 'développement RH'],
        'TRANSPORT' => ['transport', 'logistique', 'logistique transport', 'transportation', 'logistics', 'supply chain', 'chaîne logistique'],
        'LOGISTIQUE' => ['logistique', 'transport', 'supply chain', 'chaîne logistique', 'distribution', 'entreposage', 'gestion stock'],
        'HOTELLERIE' => ['hôtellerie', 'hotel', 'hospitality', 'réception', 'accueil', 'gestion hôtel', 'tourism'],
        'RESTAURATION' => ['restauration', 'cuisine', 'gastronomie', 'chef', 'cuisinier', 'food service', 'restaurant', 'hôtellerie restauration'],
        'SERVICES_PUBLICS' => ['services publics', 'administration', 'fonction publique', 'service public', 'gouvernement', 'administration publique'],
        'ENTREPRENEURIAT' => ['entrepreneuriat', 'entrepreneur', 'startup', 'création entreprise', 'business', 'innovation', 'création d\'entreprise'],
        'RECHERCHE' => ['recherche', 'scientifique', 'R&D', 'innovation', 'laboratoire', 'scientifique', 'développement recherche'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecteurRepository $secteurRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forcer la mise à jour même si des keywords existent déjà')
            ->addOption('secteur-id', null, InputOption::VALUE_OPTIONAL, 'Traiter uniquement un secteur spécifique par ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Remplissage automatique des mots-clés des secteurs');

        $secteurId = $input->getOption('secteur-id');
        if ($secteurId) {
            $secteur = $this->secteurRepository->find($secteurId);
            $secteurs = $secteur ? [$secteur] : [];
        } else {
            $secteurs = $this->secteurRepository->findAll();
        }

        if (empty($secteurs)) {
            $io->warning('Aucun secteur trouvé');
            return Command::FAILURE;
        }

        $force = $input->getOption('force');
        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        $io->progressStart(count($secteurs));

        foreach ($secteurs as $secteur) {
            $io->progressAdvance();

            // Skip si déjà des keywords et pas en mode force
            if (!$force && $secteur->getKeywords() && !empty($secteur->getKeywords())) {
                $skipped++;
                continue;
            }

            $code = $secteur->getCode();
            $keywords = null;

            // Vérifier le mapping direct
            if (isset(self::SECTEUR_KEYWORDS[$code])) {
                $keywords = self::SECTEUR_KEYWORDS[$code];
            } else {
                // Générer des keywords basés sur le titre et le code
                $keywords = $this->generateKeywordsFromTitre($secteur->getTitre(), $code);
                $notFound++;
            }

            if ($keywords && !empty($keywords)) {
                $secteur->setKeywords($keywords);
                $this->entityManager->flush();
                $updated++;
                $io->success(sprintf('Keywords ajoutés pour "%s" : %s', $secteur->getTitre(), implode(', ', $keywords)));
            } else {
                $io->warning(sprintf('Aucun keyword généré pour "%s" (code: %s)', $secteur->getTitre(), $code));
            }
        }

        $io->progressFinish();
        $io->newLine();

        $message = sprintf(
            'Terminé ! %d secteurs mis à jour, %d ignorés',
            $updated,
            $skipped
        );

        if ($notFound > 0) {
            $message .= sprintf(', %d secteurs avec keywords générés automatiquement', $notFound);
        }

        $io->success($message);

        return Command::SUCCESS;
    }

    /**
     * Génère des keywords à partir du titre du secteur
     */
    private function generateKeywordsFromTitre(string $titre, string $code): array
    {
        $keywords = [];

        // Normaliser le titre (minuscules, enlever accents)
        $titreLower = mb_strtolower($titre);
        
        // Extraire les mots significatifs (ignorer articles, prépositions)
        $stopWords = ['et', 'de', 'la', 'le', 'les', 'des', 'du', 'en', 'pour', 'avec', 'dans', '&'];
        $words = preg_split('/[\s-]+/', $titreLower);
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 2;
        });

        // Ajouter les mots significatifs
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) > 2) {
                $keywords[] = $word;
            }
        }

        // Ajouter le code en minuscules si différent
        $codeLower = strtolower($code);
        if (!in_array($codeLower, $keywords)) {
            $keywords[] = $codeLower;
        }

        return array_unique($keywords);
    }
}
