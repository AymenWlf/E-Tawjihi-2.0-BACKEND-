<?php

namespace App\Command;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:populate-articles',
    description: 'Populate articles with mock data',
)]
class PopulateArticlesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'truncate',
            't',
            InputOption::VALUE_NONE,
            'Truncate existing articles before populating'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Populating Articles with Mock Data');

        // Option pour vider la table
        if ($input->getOption('truncate')) {
            $io->warning('Truncating existing articles...');
            $this->em->createQuery('DELETE FROM App\Entity\Article')->execute();
            $this->em->flush();
            $io->success('Existing articles deleted.');
        }

        // Données mock des articles
        $articlesData = $this->getArticlesData();

        $io->progressStart(count($articlesData));

        $created = 0;
        $skipped = 0;

        foreach ($articlesData as $data) {
            // Vérifier si l'article existe déjà
            $existing = $this->em->getRepository(Article::class)->findOneBy(['slug' => $data['slug']]);
            
            if ($existing) {
                $io->progressAdvance();
                $skipped++;
                continue;
            }

            $article = new Article();
            $article->setTitre($data['titre']);
            $article->setSlug($data['slug']);
            $article->setDescription($data['description'] ?? null);
            $article->setContenu($data['contenu'] ?? null);
            $article->setImageCouverture($data['imageCouverture'] ?? null);
            $article->setCategorie($data['categorie'] ?? null);
            $article->setCategories($data['categories'] ?? null);
            $article->setTags($data['tags'] ?? null);
            $article->setAuteur($data['auteur'] ?? null);
            $article->setDatePublication($data['datePublication'] ? new \DateTime($data['datePublication']) : null);
            $article->setStatus($data['status'] ?? 'Publié');
            $article->setFeatured($data['featured'] ?? false);
            $article->setMetaTitle($data['metaTitle'] ?? null);
            $article->setMetaDescription($data['metaDescription'] ?? null);
            $article->setMetaKeywords($data['metaKeywords'] ?? null);
            $article->setOgImage($data['ogImage'] ?? null);
            $article->setOgTitle($data['ogTitle'] ?? null);
            $article->setOgDescription($data['ogDescription'] ?? null);
            $article->setCanonicalUrl($data['canonicalUrl'] ?? null);
            $article->setSchemaType($data['schemaType'] ?? 'BlogPosting');
            $article->setNoIndex($data['noIndex'] ?? false);
            $article->setTempsLecture($data['tempsLecture'] ?? null);
            $article->setVues($data['vues'] ?? 0);
            $article->setIsActivate($data['isActivate'] ?? true);
            $article->setIsComplet($data['isComplet'] ?? true);

            $this->em->persist($article);
            $created++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Successfully populated %d articles! (%d skipped)', $created, $skipped));

        return Command::SUCCESS;
    }

    private function getArticlesData(): array
    {
        return [
            [
                'titre' => 'Comment bien choisir sa filière après le bac ?',
                'slug' => 'comment-choisir-filiere-apres-bac',
                'description' => 'Un guide complet pour aider les bacheliers à faire le bon choix de filière d\'études supérieures en fonction de leurs intérêts, compétences et objectifs de carrière.',
                'contenu' => '<h2>Introduction</h2><p>Le choix de la filière après le baccalauréat est une décision cruciale qui déterminera votre parcours professionnel. Ce guide vous aidera à faire le bon choix.</p><h2>1. Connaître vos intérêts</h2><p>La première étape consiste à identifier vos domaines d\'intérêt. Qu\'est-ce qui vous passionne ? Quels sont les sujets qui vous motivent ?</p><h2>2. Évaluer vos compétences</h2><p>Il est important d\'être honnête avec soi-même concernant ses forces et faiblesses académiques.</p><h2>3. Explorer les débouchés</h2><p>Renseignez-vous sur les opportunités professionnelles offertes par chaque filière.</p><h2>Conclusion</h2><p>Le choix de votre filière doit être le résultat d\'une réflexion approfondie sur vos aspirations personnelles et professionnelles.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1920&h=1080&fit=crop',
                'categorie' => 'Orientation',
                'categories' => ['Orientation', 'Conseils'],
                'tags' => ['Orientation', 'Baccalauréat', 'Conseils', 'Études'],
                'auteur' => 'Ahmed Alami',
                'datePublication' => '2024-12-10',
                'status' => 'Publié',
                'featured' => true,
                'metaTitle' => 'Comment bien choisir sa filière après le bac ? | Guide complet E-TAWJIHI',
                'metaDescription' => 'Découvrez notre guide complet pour choisir la bonne filière après le bac. Conseils d\'orientation, évaluation de vos compétences et exploration des débouchés professionnels.',
                'metaKeywords' => 'orientation, filière, baccalauréat, études supérieures, choix filière, conseils orientation, débouchés professionnels, E-TAWJIHI',
                'ogImage' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&h=630&fit=crop',
                'ogTitle' => 'Comment bien choisir sa filière après le bac ?',
                'ogDescription' => 'Un guide complet pour aider les bacheliers à faire le bon choix de filière d\'études supérieures.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/comment-choisir-filiere-apres-bac',
                'schemaType' => 'BlogPosting',
                'noIndex' => false,
                'tempsLecture' => 5,
                'vues' => 1250,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Top 10 des meilleures écoles d\'ingénierie au Maroc',
                'slug' => 'top-10-meilleures-ecoles-ingenierie-maroc',
                'description' => 'Découvrez le classement des meilleures écoles d\'ingénierie au Maroc en 2024, avec leurs spécialités, leurs débouchés et leurs conditions d\'admission.',
                'contenu' => '<h2>Introduction</h2><p>Le Maroc compte de nombreuses écoles d\'ingénierie de renom qui forment les futurs ingénieurs du pays. Voici notre sélection des 10 meilleures.</p><h2>1. École Mohammadia d\'Ingénieurs (EMI)</h2><p>L\'EMI est l\'une des plus prestigieuses écoles d\'ingénierie au Maroc, offrant une formation d\'excellence dans plusieurs spécialités.</p><h2>2. École Hassania des Travaux Publics (EHTP)</h2><p>Spécialisée dans le génie civil et les travaux publics, l\'EHTP forme des ingénieurs de haut niveau.</p><h2>3. École Nationale Supérieure d\'Informatique et d\'Analyse des Systèmes (ENSIAS)</h2><p>L\'ENSIAS est la référence en matière de formation en informatique et systèmes d\'information.</p><h2>Conclusion</h2><p>Chaque école a ses spécificités. Le choix dépend de vos aspirations et de votre projet professionnel.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1920&h=1080&fit=crop',
                'categorie' => 'Écoles',
                'categories' => ['Écoles', 'Ingénierie'],
                'tags' => ['Écoles', 'Ingénierie', 'Maroc', 'Classement', 'Études'],
                'auteur' => 'Fatima Benali',
                'datePublication' => '2024-12-08',
                'status' => 'Publié',
                'featured' => true,
                'metaTitle' => 'Top 10 des meilleures écoles d\'ingénierie au Maroc 2024 | E-TAWJIHI',
                'metaDescription' => 'Découvrez le classement complet des meilleures écoles d\'ingénierie au Maroc avec leurs spécialités, débouchés et conditions d\'admission.',
                'metaKeywords' => 'écoles ingénierie, Maroc, EMI, EHTP, ENSIAS, classement écoles, études ingénierie',
                'ogImage' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1200&h=630&fit=crop',
                'ogTitle' => 'Top 10 des meilleures écoles d\'ingénierie au Maroc',
                'ogDescription' => 'Découvrez le classement des meilleures écoles d\'ingénierie au Maroc en 2024.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/top-10-meilleures-ecoles-ingenierie-maroc',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 8,
                'vues' => 3200,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Les bourses d\'études disponibles au Maroc',
                'slug' => 'bourses-etudes-disponibles-maroc',
                'description' => 'Guide complet sur les différentes bourses d\'études disponibles pour les étudiants marocains, avec les critères d\'éligibilité et les démarches à suivre.',
                'contenu' => '<h2>Introduction</h2><p>Les bourses d\'études représentent une aide précieuse pour de nombreux étudiants. Voici un guide complet sur les bourses disponibles au Maroc.</p><h2>1. Bourses de l\'État</h2><p>Le gouvernement marocain propose plusieurs programmes de bourses pour les étudiants méritants.</p><h2>2. Bourses des établissements</h2><p>De nombreux établissements offrent leurs propres programmes de bourses.</p><h2>3. Bourses internationales</h2><p>Plusieurs organisations internationales proposent des bourses pour les étudiants marocains.</p><h2>Conclusion</h2><p>N\'hésitez pas à postuler pour les bourses qui correspondent à votre profil.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1920&h=1080&fit=crop',
                'categorie' => 'Financement',
                'categories' => ['Financement', 'Conseils'],
                'tags' => ['Bourses', 'Financement', 'Études', 'Aide financière'],
                'auteur' => 'Karim Idrissi',
                'datePublication' => '2024-12-05',
                'status' => 'Publié',
                'featured' => false,
                'metaTitle' => 'Les bourses d\'études disponibles au Maroc | Guide complet E-TAWJIHI',
                'metaDescription' => 'Découvrez toutes les bourses d\'études disponibles pour les étudiants marocains : bourses de l\'État, établissements et organisations internationales.',
                'metaKeywords' => 'bourses études, financement études, aide financière, Maroc, étudiants',
                'ogImage' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&h=630&fit=crop',
                'ogTitle' => 'Les bourses d\'études disponibles au Maroc',
                'ogDescription' => 'Guide complet sur les bourses d\'études disponibles pour les étudiants marocains.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/bourses-etudes-disponibles-maroc',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 6,
                'vues' => 890,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Comment réussir son concours d\'entrée en école d\'ingénieurs ?',
                'slug' => 'reussir-concours-entree-ecole-ingenieurs',
                'description' => 'Conseils pratiques et stratégies pour réussir les concours d\'entrée dans les grandes écoles d\'ingénieurs au Maroc.',
                'contenu' => '<h2>Introduction</h2><p>Les concours d\'entrée en école d\'ingénieurs sont très sélectifs. Voici nos conseils pour maximiser vos chances de réussite.</p><h2>1. Préparation efficace</h2><p>Une bonne préparation est la clé du succès. Organisez votre temps de révision.</p><h2>2. Maîtriser les fondamentaux</h2><p>Les concours testent vos connaissances de base en mathématiques, physique et sciences.</p><h2>3. Gérer le stress</h2><p>Le jour J, il est important de rester calme et confiant.</p><h2>Conclusion</h2><p>Avec une bonne préparation et de la détermination, vous pouvez réussir votre concours.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1920&h=1080&fit=crop',
                'categorie' => 'Conseils',
                'categories' => ['Conseils', 'Concours'],
                'tags' => ['Concours', 'Ingénierie', 'Préparation', 'Conseils'],
                'auteur' => 'Sara Amrani',
                'datePublication' => '2024-12-03',
                'status' => 'Publié',
                'featured' => true,
                'metaTitle' => 'Comment réussir son concours d\'entrée en école d\'ingénieurs ? | E-TAWJIHI',
                'metaDescription' => 'Conseils pratiques et stratégies pour réussir les concours d\'entrée dans les grandes écoles d\'ingénieurs au Maroc.',
                'metaKeywords' => 'concours ingénieurs, préparation concours, école ingénieurs, Maroc, conseils',
                'ogImage' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1200&h=630&fit=crop',
                'ogTitle' => 'Comment réussir son concours d\'entrée en école d\'ingénieurs ?',
                'ogDescription' => 'Conseils pratiques et stratégies pour réussir les concours d\'entrée dans les grandes écoles d\'ingénieurs.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/reussir-concours-entree-ecole-ingenieurs',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 7,
                'vues' => 2100,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Les métiers les plus demandés au Maroc en 2024',
                'slug' => 'metiers-plus-demandes-maroc-2024',
                'description' => 'Découvrez les secteurs et métiers qui recrutent le plus au Maroc en 2024, avec les compétences requises et les perspectives d\'avenir.',
                'contenu' => '<h2>Introduction</h2><p>Le marché de l\'emploi au Maroc évolue constamment. Voici les métiers les plus demandés en 2024.</p><h2>1. Secteur de l\'informatique</h2><p>Les métiers du numérique sont en forte croissance : développeurs, data scientists, experts en cybersécurité.</p><h2>2. Secteur de l\'ingénierie</h2><p>Les ingénieurs restent très demandés, notamment en génie civil, électrique et industriel.</p><h2>3. Secteur de la finance</h2><p>Les métiers de la finance et de la comptabilité offrent de nombreuses opportunités.</p><h2>Conclusion</h2><p>Choisissez une filière qui correspond à vos intérêts et aux besoins du marché.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1920&h=1080&fit=crop',
                'categorie' => 'Emploi',
                'categories' => ['Emploi', 'Métiers'],
                'tags' => ['Emploi', 'Métiers', 'Maroc', '2024', 'Débouchés'],
                'auteur' => 'Youssef Alaoui',
                'datePublication' => '2024-12-01',
                'status' => 'Publié',
                'featured' => false,
                'metaTitle' => 'Les métiers les plus demandés au Maroc en 2024 | E-TAWJIHI',
                'metaDescription' => 'Découvrez les secteurs et métiers qui recrutent le plus au Maroc en 2024 avec les compétences requises.',
                'metaKeywords' => 'métiers Maroc, emploi Maroc, débouchés professionnels, marché emploi, 2024',
                'ogImage' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&h=630&fit=crop',
                'ogTitle' => 'Les métiers les plus demandés au Maroc en 2024',
                'ogDescription' => 'Découvrez les secteurs et métiers qui recrutent le plus au Maroc en 2024.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/metiers-plus-demandes-maroc-2024',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 6,
                'vues' => 1800,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Guide complet des études à l\'étranger',
                'slug' => 'guide-etudes-etranger',
                'description' => 'Tout ce qu\'il faut savoir pour étudier à l\'étranger : procédures, bourses, visas et conseils pratiques.',
                'contenu' => '<h2>Introduction</h2><p>Étudier à l\'étranger est une expérience enrichissante. Voici un guide complet pour vous aider dans vos démarches.</p><h2>1. Choisir sa destination</h2><p>Plusieurs pays accueillent les étudiants marocains : France, Canada, Belgique, etc.</p><h2>2. Procédures administratives</h2><p>Les démarches varient selon le pays : visas, équivalences de diplômes, etc.</p><h2>3. Financement</h2><p>Plusieurs options de financement existent : bourses, prêts étudiants, etc.</p><h2>Conclusion</h2><p>Bien préparer votre projet d\'études à l\'étranger est essentiel pour réussir.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1920&h=1080&fit=crop',
                'categorie' => 'Études à l\'étranger',
                'categories' => ['Études à l\'étranger', 'Conseils'],
                'tags' => ['Études étranger', 'International', 'Visa', 'Bourses'],
                'auteur' => 'Nadia Tazi',
                'datePublication' => '2024-11-28',
                'status' => 'Publié',
                'featured' => false,
                'metaTitle' => 'Guide complet des études à l\'étranger | E-TAWJIHI',
                'metaDescription' => 'Tout ce qu\'il faut savoir pour étudier à l\'étranger : procédures, bourses, visas et conseils pratiques.',
                'metaKeywords' => 'études étranger, international, visa, bourses, études supérieures',
                'ogImage' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1200&h=630&fit=crop',
                'ogTitle' => 'Guide complet des études à l\'étranger',
                'ogDescription' => 'Tout ce qu\'il faut savoir pour étudier à l\'étranger.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/guide-etudes-etranger',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 10,
                'vues' => 1500,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Les avantages des études en alternance',
                'slug' => 'avantages-etudes-alternance',
                'description' => 'Découvrez les avantages de l\'alternance : formation pratique, rémunération et insertion professionnelle facilitée.',
                'contenu' => '<h2>Introduction</h2><p>L\'alternance combine formation théorique et expérience professionnelle. Voici ses avantages.</p><h2>1. Expérience professionnelle</h2><p>L\'alternance vous permet d\'acquérir une expérience concrète en entreprise.</p><h2>2. Rémunération</h2><p>Vous êtes rémunéré pendant votre formation, ce qui facilite votre autonomie financière.</p><h2>3. Insertion professionnelle</h2><p>Les alternants ont généralement plus de facilités à trouver un emploi après leur formation.</p><h2>Conclusion</h2><p>L\'alternance est une excellente option pour ceux qui souhaitent allier théorie et pratique.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1920&h=1080&fit=crop',
                'categorie' => 'Formation',
                'categories' => ['Formation', 'Conseils'],
                'tags' => ['Alternance', 'Formation', 'Emploi', 'Expérience'],
                'auteur' => 'Mehdi Bensaid',
                'datePublication' => '2024-11-25',
                'status' => 'Publié',
                'featured' => false,
                'metaTitle' => 'Les avantages des études en alternance | E-TAWJIHI',
                'metaDescription' => 'Découvrez les avantages de l\'alternance : formation pratique, rémunération et insertion professionnelle facilitée.',
                'metaKeywords' => 'alternance, formation, emploi, expérience professionnelle',
                'ogImage' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1200&h=630&fit=crop',
                'ogTitle' => 'Les avantages des études en alternance',
                'ogDescription' => 'Découvrez les avantages de l\'alternance pour votre formation.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/avantages-etudes-alternance',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 5,
                'vues' => 1100,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Comment préparer son dossier de candidature ?',
                'slug' => 'preparer-dossier-candidature',
                'description' => 'Conseils pour préparer un dossier de candidature solide et convaincant pour intégrer l\'établissement de votre choix.',
                'contenu' => '<h2>Introduction</h2><p>Un bon dossier de candidature est essentiel pour intégrer l\'établissement de vos rêves. Voici nos conseils.</p><h2>1. Lettre de motivation</h2><p>Rédigez une lettre de motivation personnalisée et convaincante.</p><h2>2. CV et parcours</h2><p>Mettez en valeur vos compétences et votre parcours académique.</p><h2>3. Recommandations</h2><p>Les lettres de recommandation peuvent renforcer votre dossier.</p><h2>Conclusion</h2><p>Un dossier bien préparé augmente vos chances d\'être accepté.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1920&h=1080&fit=crop',
                'categorie' => 'Conseils',
                'categories' => ['Conseils', 'Candidature'],
                'tags' => ['Candidature', 'Dossier', 'Conseils', 'Admission'],
                'auteur' => 'Laila Cherkaoui',
                'datePublication' => '2024-11-22',
                'status' => 'Publié',
                'featured' => false,
                'metaTitle' => 'Comment préparer son dossier de candidature ? | E-TAWJIHI',
                'metaDescription' => 'Conseils pour préparer un dossier de candidature solide et convaincant pour intégrer l\'établissement de votre choix.',
                'metaKeywords' => 'dossier candidature, admission, conseils, établissement',
                'ogImage' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'ogTitle' => 'Comment préparer son dossier de candidature ?',
                'ogDescription' => 'Conseils pour préparer un dossier de candidature solide.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/preparer-dossier-candidature',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 4,
                'vues' => 950,
                'isActivate' => true,
                'isComplet' => true,
            ],
            [
                'titre' => 'Les filières les plus prometteuses en 2024',
                'slug' => 'filieres-plus-prometteuses-2024',
                'description' => 'Découvrez les filières d\'études qui offrent les meilleures perspectives d\'emploi et de carrière en 2024.',
                'contenu' => '<h2>Introduction</h2><p>Certaines filières offrent de meilleures perspectives d\'emploi que d\'autres. Voici notre sélection.</p><h2>1. Informatique et numérique</h2><p>Les métiers du numérique sont en pleine expansion.</p><h2>2. Ingénierie</h2><p>Les ingénieurs restent très demandés dans tous les secteurs.</p><h2>3. Commerce et management</h2><p>Les métiers du commerce et de la gestion offrent de nombreuses opportunités.</p><h2>Conclusion</h2><p>Choisissez une filière qui correspond à vos intérêts et aux besoins du marché.</p>',
                'imageCouverture' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920&h=1080&fit=crop',
                'categorie' => 'Orientation',
                'categories' => ['Orientation', 'Filières'],
                'tags' => ['Filières', 'Orientation', 'Emploi', '2024'],
                'auteur' => 'Omar El Fassi',
                'datePublication' => '2024-11-20',
                'status' => 'Publié',
                'featured' => true,
                'metaTitle' => 'Les filières les plus prometteuses en 2024 | E-TAWJIHI',
                'metaDescription' => 'Découvrez les filières d\'études qui offrent les meilleures perspectives d\'emploi et de carrière en 2024.',
                'metaKeywords' => 'filières, orientation, emploi, perspectives, 2024',
                'ogImage' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200&h=630&fit=crop',
                'ogTitle' => 'Les filières les plus prometteuses en 2024',
                'ogDescription' => 'Découvrez les filières d\'études les plus prometteuses en 2024.',
                'canonicalUrl' => 'https://e-tawjihi.ma/blog/filieres-plus-prometteuses-2024',
                'schemaType' => 'Article',
                'noIndex' => false,
                'tempsLecture' => 6,
                'vues' => 2400,
                'isActivate' => true,
                'isComplet' => true,
            ],
        ];
    }
}
