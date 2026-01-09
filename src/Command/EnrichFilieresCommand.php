<?php

namespace App\Command;

use App\Entity\Filiere;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:enrich-filieres',
    description: 'Enrich all existing filieres with complete and rich data',
)]
class EnrichFilieresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Enriching All Filieres with Complete Data');

        // Récupérer toutes les filières
        $filieres = $this->em->getRepository(Filiere::class)->findAll();
        
        if (empty($filieres)) {
            $io->warning('No filieres found in database!');
            return Command::FAILURE;
        }

        $io->info(sprintf('Found %d filieres to enrich', count($filieres)));

        // Données enrichies par domaine
        $enrichmentData = $this->getEnrichmentData();

        $io->section('Enriching Filieres...');
        $io->progressStart(count($filieres));
        
        $updated = 0;
        $skipped = 0;

        foreach ($filieres as $filiere) {
            try {
                // Déterminer le domaine de la filière
                $domaine = $filiere->getDomaine() ?: $this->guessDomaineFromName($filiere->getNom());
                
                // Récupérer les données d'enrichissement pour ce domaine
                $data = $enrichmentData[$domaine] ?? $enrichmentData['Général'];
                
                // Enrichir TOUS les champs avec des données complètes (remplace même les données existantes)
                
                // Description enrichie
                $filiere->setDescription($data['description']);

                // Objectifs complets
                $filiere->setObjectifs($data['objectifs']);

                // Programme détaillé
                $filiere->setProgramme($data['programme']);

                // Métiers associés
                $filiere->setMetier($data['metier']);

                // Frais d'inscription
                $filiere->setFraisInscription($data['fraisInscription']);

                // Nombre de places
                $filiere->setNbPlaces($data['nbPlaces']);

                // Reconnaissance
                $filiere->setReconnaissance($data['reconnaissance']);

                // Échange international
                $filiere->setEchangeInternational($data['echangeInternational']);

                // Champs SEO complets
                $filiere->setMetaTitle($data['metaTitle']);
                $filiere->setMetaDescription($data['metaDescription']);
                $filiere->setMetaKeywords($data['metaKeywords']);

                // S'assurer que le slug existe
                if (empty($filiere->getSlug())) {
                    $slug = strtolower($this->slugger->slug($filiere->getNom())->toString());
                    $slugBase = $slug;
                    $counter = 1;
                    while ($this->em->getRepository(Filiere::class)->findOneBy(['slug' => $slug])) {
                        $slug = $slugBase . '-' . $counter;
                        $counter++;
                    }
                    $filiere->setSlug($slug);
                }

                // S'assurer que le domaine est défini
                if (empty($filiere->getDomaine())) {
                    $filiere->setDomaine($domaine);
                }

                // Remplir tous les champs de base
                $filiere->setDiplome($data['diplome']);
                $filiere->setLangueEtudes($data['langueEtudes']);
                $filiere->setTypeEcole($data['typeEcole']);
                $filiere->setNombreAnnees($data['nombreAnnees']);
                $filiere->setFraisScolarite($data['fraisScolarite']);
                
                // S'assurer que le concours est défini
                $filiere->setConcours($data['concours'] ?? false);
                
                // S'assurer que bacCompatible est défini
                if (is_null($filiere->isBacCompatible())) {
                    $filiere->setBacCompatible(true);
                }

                $this->em->persist($filiere);
                $updated++;
                
            } catch (\Exception $e) {
                $io->error(sprintf('Error enriching filiere "%s": %s', $filiere->getNom(), $e->getMessage()));
                $skipped++;
            }
            
            $io->progressAdvance();
        }

        // Flush toutes les modifications
        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('Enriched %d filieres, %d skipped', $updated, $skipped));

        return Command::SUCCESS;
    }

    private function guessDomaineFromName(string $nom): string
    {
        $nomLower = strtolower($nom);
        
        if (str_contains($nomLower, 'informatique') || str_contains($nomLower, 'info') || str_contains($nomLower, 'it') || str_contains($nomLower, 'software')) {
            return 'Informatique & Technologie';
        }
        if (str_contains($nomLower, 'commerce') || str_contains($nomLower, 'gestion') || str_contains($nomLower, 'business') || str_contains($nomLower, 'marketing')) {
            return 'Commerce & Gestion';
        }
        if (str_contains($nomLower, 'ingénieur') || str_contains($nomLower, 'génie')) {
            return 'Ingénierie';
        }
        if (str_contains($nomLower, 'médecine') || str_contains($nomLower, 'santé') || str_contains($nomLower, 'pharmacie')) {
            return 'Santé & Médecine';
        }
        if (str_contains($nomLower, 'droit') || str_contains($nomLower, 'juridique')) {
            return 'Droit & Sciences Politiques';
        }
        if (str_contains($nomLower, 'architecture') || str_contains($nomLower, 'urbanisme')) {
            return 'Architecture & Urbanisme';
        }
        if (str_contains($nomLower, 'communication') || str_contains($nomLower, 'média')) {
            return 'Communication & Médias';
        }
        
        return 'Général';
    }

    private function getEnrichmentData(): array
    {
        return [
            'Informatique & Technologie' => [
                'description' => 'Cette filière d\'excellence en informatique et technologies de l\'information forme des professionnels hautement qualifiés capables de concevoir, développer et maintenir des systèmes informatiques complexes. Les étudiants acquièrent une solide base théorique et pratique en programmation, algorithmique, bases de données, réseaux informatiques, intelligence artificielle, cybersécurité et développement web. Le programme intègre des projets pratiques, des stages en entreprise et une approche pédagogique innovante basée sur l\'apprentissage par projets. Les diplômés sont préparés à évoluer dans un secteur en constante mutation et à répondre aux défis technologiques de demain.',
                'objectifs' => [
                    'Maîtriser les fondamentaux de l\'informatique et des technologies de l\'information',
                    'Développer des compétences en programmation et développement logiciel',
                    'Acquérir une expertise en bases de données et systèmes d\'information',
                    'Comprendre les enjeux de la cybersécurité et de la protection des données',
                    'Maîtriser les technologies web modernes et le développement d\'applications',
                    'Développer des compétences en intelligence artificielle et machine learning',
                    'Apprendre à gérer des projets informatiques complexes',
                    'Acquérir une culture d\'innovation et d\'entrepreneuriat technologique'
                ],
                'programme' => [
                    'Année 1' => [
                        'Fondamentaux de l\'informatique',
                        'Algorithmique et programmation',
                        'Mathématiques pour l\'informatique',
                        'Architecture des ordinateurs',
                        'Bases de données',
                        'Langues et communication'
                    ],
                    'Année 2' => [
                        'Programmation orientée objet',
                        'Structures de données',
                        'Réseaux informatiques',
                        'Systèmes d\'exploitation',
                        'Développement web',
                        'Gestion de projet'
                    ],
                    'Année 3' => [
                        'Bases de données avancées',
                        'Développement d\'applications mobiles',
                        'Sécurité informatique',
                        'Intelligence artificielle',
                        'Cloud computing',
                        'Stage en entreprise'
                    ]
                ],
                'metier' => [
                    'Développeur Full Stack',
                    'Ingénieur Logiciel',
                    'Architecte Système',
                    'Chef de Projet IT',
                    'Data Scientist',
                    'Ingénieur Cybersécurité',
                    'Développeur Mobile',
                    'Expert Cloud Computing',
                    'Consultant IT',
                    'Ingénieur DevOps'
                ],
                'fraisInscription' => '2000.00',
                'nbPlaces' => 120,
                'reconnaissance' => 'Reconnu par l\'État',
                'echangeInternational' => true,
                'diplome' => 'Cycle d\'ingénieur',
                'langueEtudes' => 'Français',
                'typeEcole' => 'Privé',
                'nombreAnnees' => '5 ans',
                'fraisScolarite' => '45000.00',
                'metaTitle' => 'Formation en Informatique et Technologies - Cycle d\'Ingénieur',
                'metaDescription' => 'Formation d\'excellence en informatique et technologies de l\'information. Programme complet de 5 ans avec stages en entreprise et débouchés variés dans le secteur IT.',
                'metaKeywords' => 'informatique, ingénierie, développement, programmation, cybersécurité, intelligence artificielle, formation ingénieur'
            ],
            'Commerce & Gestion' => [
                'description' => 'Cette filière prestigieuse en commerce et gestion forme des managers et entrepreneurs capables de diriger des organisations dans un environnement économique complexe et mondialisé. Le programme couvre tous les aspects de la gestion d\'entreprise : marketing, finance, comptabilité, ressources humaines, stratégie, commerce international et entrepreneuriat. Les étudiants développent des compétences managériales, analytiques et décisionnelles grâce à des études de cas, des simulations d\'entreprise et des stages en milieu professionnel. La formation intègre également une dimension internationale avec des échanges académiques et des projets collaboratifs.',
                'objectifs' => [
                    'Maîtriser les fondamentaux de la gestion d\'entreprise',
                    'Développer des compétences en marketing et communication',
                    'Acquérir une expertise en finance et comptabilité',
                    'Comprendre les enjeux du commerce international',
                    'Développer des compétences en leadership et management',
                    'Apprendre à analyser et prendre des décisions stratégiques',
                    'Acquérir une culture entrepreneuriale',
                    'Maîtriser les outils de gestion moderne'
                ],
                'programme' => [
                    'Année 1' => [
                        'Introduction à la gestion',
                        'Comptabilité générale',
                        'Marketing fondamental',
                        'Économie d\'entreprise',
                        'Mathématiques appliquées',
                        'Communication et langues'
                    ],
                    'Année 2' => [
                        'Finance d\'entreprise',
                        'Marketing avancé',
                        'Gestion des ressources humaines',
                        'Commerce international',
                        'Droit des affaires',
                        'Informatique de gestion'
                    ],
                    'Année 3' => [
                        'Stratégie d\'entreprise',
                        'Contrôle de gestion',
                        'Entrepreneuriat',
                        'Éthique des affaires',
                        'Projet de fin d\'études',
                        'Stage en entreprise'
                    ]
                ],
                'metier' => [
                    'Chef de Projet Marketing',
                    'Responsable Commercial',
                    'Contrôleur de Gestion',
                    'Directeur Financier',
                    'Entrepreneur',
                    'Consultant en Management',
                    'Responsable RH',
                    'Chef de Produit',
                    'Analyste Financier',
                    'Directeur Commercial'
                ],
                'fraisInscription' => '2500.00',
                'nbPlaces' => 100,
                'reconnaissance' => 'Reconnu par l\'État',
                'echangeInternational' => true,
                'diplome' => 'Master',
                'langueEtudes' => 'Français',
                'typeEcole' => 'Privé',
                'nombreAnnees' => '5 ans',
                'fraisScolarite' => '50000.00',
                'concours' => false,
                'metaTitle' => 'Formation en Commerce et Gestion - Master en Management',
                'metaDescription' => 'Formation complète en commerce et gestion d\'entreprise. Programme de 5 ans avec stages et débouchés variés dans le secteur du management et de l\'entrepreneuriat.',
                'metaKeywords' => 'commerce, gestion, management, marketing, finance, entrepreneuriat, formation business'
            ],
            'Ingénierie' => [
                'description' => 'Cette filière d\'ingénierie pluridisciplinaire forme des ingénieurs polyvalents capables de concevoir, réaliser et gérer des projets techniques complexes dans divers domaines industriels. Le programme allie théorie et pratique avec une forte composante de projets et d\'expérimentation. Les étudiants acquièrent des compétences en mathématiques appliquées, physique, mécanique, électronique, automatique et gestion de projet. La formation intègre également des aspects liés au développement durable, à l\'innovation et à l\'entrepreneuriat technologique. Les diplômés sont préparés à travailler dans l\'industrie, le BTP, l\'énergie ou les services techniques.',
                'objectifs' => [
                    'Maîtriser les fondamentaux scientifiques et techniques',
                    'Développer des compétences en conception et dimensionnement',
                    'Acquérir une expertise en gestion de projet industriel',
                    'Comprendre les enjeux du développement durable',
                    'Développer des compétences en innovation technologique',
                    'Apprendre à travailler en équipe multidisciplinaire',
                    'Acquérir une culture d\'excellence technique',
                    'Maîtriser les outils de CAO et simulation'
                ],
                'programme' => [
                    'Année 1' => [
                        'Mathématiques et physique',
                        'Mécanique générale',
                        'Résistance des matériaux',
                        'Électricité et électronique',
                        'Informatique industrielle',
                        'Langues et communication'
                    ],
                    'Année 2' => [
                        'Mécanique des fluides',
                        'Automatique et régulation',
                        'Gestion de production',
                        'Qualité et sécurité',
                        'Projet d\'ingénierie',
                        'Stage technique'
                    ],
                    'Année 3' => [
                        'Conception assistée par ordinateur',
                        'Optimisation et simulation',
                        'Management de projet',
                        'Innovation technologique',
                        'Projet de fin d\'études',
                        'Stage ingénieur'
                    ]
                ],
                'metier' => [
                    'Ingénieur de Conception',
                    'Ingénieur de Production',
                    'Ingénieur Qualité',
                    'Chef de Projet Industriel',
                    'Ingénieur R&D',
                    'Consultant Technique',
                    'Ingénieur Maintenance',
                    'Ingénieur Process',
                    'Ingénieur BTP',
                    'Ingénieur Énergie'
                ],
                'fraisInscription' => '2000.00',
                'nbPlaces' => 80,
                'reconnaissance' => 'Reconnu par l\'État',
                'echangeInternational' => true,
                'diplome' => 'Cycle d\'ingénieur',
                'langueEtudes' => 'Français',
                'typeEcole' => 'Privé',
                'nombreAnnees' => '5 ans',
                'fraisScolarite' => '45000.00',
                'concours' => false,
                'metaTitle' => 'Formation d\'Ingénieur - Cycle d\'Ingénieur Pluridisciplinaire',
                'metaDescription' => 'Formation d\'excellence en ingénierie. Programme complet de 5 ans avec projets pratiques et stages en entreprise. Débouchés variés dans l\'industrie et le BTP.',
                'metaKeywords' => 'ingénierie, formation ingénieur, génie industriel, BTP, innovation technologique'
            ],
            'Général' => [
                'description' => 'Cette filière offre une formation complète et polyvalente qui prépare les étudiants à évoluer dans un monde professionnel en constante mutation. Le programme allie connaissances théoriques solides et compétences pratiques, avec un accent particulier sur l\'adaptabilité, l\'innovation et l\'esprit d\'initiative. Les étudiants bénéficient d\'un encadrement de qualité, d\'infrastructures modernes et d\'opportunités de stages et d\'échanges internationaux. La formation vise à développer des professionnels complets, capables de s\'adapter aux défis du marché du travail et de contribuer au développement économique et social.',
                'objectifs' => [
                    'Acquérir des connaissances fondamentales solides',
                    'Développer des compétences pratiques et professionnelles',
                    'Cultiver l\'esprit critique et l\'autonomie',
                    'Favoriser l\'ouverture internationale',
                    'Développer des compétences en communication',
                    'Apprendre à travailler en équipe',
                    'Acquérir une culture professionnelle',
                    'Préparer l\'insertion professionnelle'
                ],
                'programme' => [
                    'Année 1' => [
                        'Fondamentaux disciplinaires',
                        'Méthodologie de travail',
                        'Communication et langues',
                        'Projet d\'initiation',
                        'Stage découverte',
                        'Culture générale'
                    ],
                    'Année 2' => [
                        'Approfondissement disciplinaire',
                        'Projets pratiques',
                        'Stage professionnel',
                        'Séminaires spécialisés',
                        'Préparation à l\'insertion',
                        'Projet personnel'
                    ]
                ],
                'metier' => [
                    'Professionnel polyvalent',
                    'Chef de Projet',
                    'Consultant',
                    'Responsable de Service',
                    'Entrepreneur',
                    'Cadre d\'entreprise'
                ],
                'fraisInscription' => '1500.00',
                'nbPlaces' => 60,
                'reconnaissance' => 'Reconnu par l\'État',
                'echangeInternational' => false,
                'diplome' => 'Licence',
                'langueEtudes' => 'Français',
                'typeEcole' => 'Privé',
                'nombreAnnees' => '3 ans',
                'fraisScolarite' => '35000.00',
                'concours' => false,
                'metaTitle' => 'Formation Complète et Polyvalente',
                'metaDescription' => 'Formation complète et polyvalente préparant à l\'insertion professionnelle. Programme adapté avec stages et débouchés variés.',
                'metaKeywords' => 'formation, études supérieures, insertion professionnelle'
            ]
        ];
    }
}
