<?php

namespace App\Command;

use App\Entity\Campus;
use App\Entity\City;
use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Repository\CityRepository;
use App\Repository\EstablishmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:complete-emsi-data',
    description: 'Remplit complètement l\'école EMSI et une de ses filières avec tous les champs'
)]
class CompleteEMSIDataCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Remplissage complet de l\'école EMSI et de ses filières');

        $establishmentRepository = $this->entityManager->getRepository(Establishment::class);
        $cityRepository = $this->entityManager->getRepository(City::class);

        // Trouver ou créer l'établissement EMSI
        $emsi = $establishmentRepository->findOneBy(['sigle' => 'EMSI']);

        if (!$emsi) {
            $io->warning('EMSI n\'existe pas, création en cours...');
            $emsi = new Establishment();
            $emsi->setNom('École Marocaine des Sciences de l\'Ingénieur');
            $emsi->setSigle('EMSI');
            $emsi->setSlug('emsi-ecole-marocaine-sciences-ingenieur');
        } else {
            $io->info(sprintf('EMSI trouvé (ID: %d), mise à jour en cours...', $emsi->getId()));
        }

        // Remplir tous les champs de l'établissement
        $emsi->setNomArabe('المدرسة المغربية لعلوم المهندس');
        $emsi->setType('Privé');
        $emsi->setVille('Casablanca');
        $emsi->setVilles(['Casablanca', 'Rabat', 'Marrakech', 'Tanger']);
        $emsi->setPays('Maroc');
        $emsi->setUniversite('Honoris United Universities');
        
        // Description complète
        $emsi->setDescription('<h2>À propos d\'EMSI</h2>
<p>L\'<strong>École Marocaine des Sciences de l\'Ingénieur (EMSI)</strong> est une institution d\'enseignement supérieur privé de référence au Maroc, créée en 1986. Elle fait partie du réseau Honoris United Universities, le premier réseau panafricain d\'enseignement supérieur privé.</p>

<h3>Mission</h3>
<p>EMSI a pour mission de former des ingénieurs compétents, innovants et capables de répondre aux défis technologiques et économiques du Maroc et de l\'Afrique. L\'école privilégie une approche pédagogique axée sur la pratique, l\'innovation et l\'entrepreneuriat.</p>

<h3>Points forts</h3>
<ul>
<li><strong>4 campus</strong> au Maroc : Casablanca, Rabat, Marrakech et Tanger</li>
<li><strong>Diplômes reconnus</strong> par l\'État marocain</li>
<li><strong>Partenariats internationaux</strong> avec des universités prestigieuses</li>
<li><strong>Programmes d\'échange</strong> et double diplôme</li>
<li><strong>Insertion professionnelle</strong> : 95% d\'insertion dans les 6 mois</li>
<li><strong>Réseau d\'anciens</strong> de plus de 15 000 ingénieurs</li>
</ul>

<h3>Formations</h3>
<p>EMSI propose des formations d\'ingénieur dans plusieurs spécialités : Génie Informatique, Génie Industriel, Génie Civil, Génie Réseaux et Télécommunications, Génie Énergétique, et bien d\'autres.</p>

<h3>Vie étudiante</h3>
<p>L\'école offre un environnement dynamique avec de nombreux clubs étudiants, activités sportives, événements culturels et projets innovants. Les étudiants bénéficient d\'infrastructures modernes et d\'équipements de pointe.</p>');

        // Images
        $emsi->setLogo('https://cdn.e-tawjihi.ma/establishments/emsi-logo.png');
        $emsi->setImageCouverture('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200&h=600&fit=crop');
        $emsi->setOgImage('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200&h=630&fit=crop');

        // Contact
        $emsi->setEmail('contact@emsi.ma');
        $emsi->setTelephone('0522272727');
        $emsi->setSiteWeb('https://www.emsi.ma');
        $emsi->setAdresse('Boulevard Zerktouni, Casablanca, Maroc');
        $emsi->setCodePostal('20000');

        // Réseaux sociaux
        $emsi->setFacebook('https://www.facebook.com/EMSIMaroc');
        $emsi->setInstagram('https://www.instagram.com/emsi_maroc');
        $emsi->setTwitter('https://twitter.com/EMSI_Maroc');
        $emsi->setLinkedin('https://www.linkedin.com/school/emsi-maroc');
        $emsi->setYoutube('https://www.youtube.com/@EMSIMaroc');

        // Statistiques
        $emsi->setNbEtudiants(8000);
        $emsi->setNbFilieres(12);
        $emsi->setAnneeCreation(1986);

        // Caractéristiques
        $emsi->setAccreditationEtat(true);
        $emsi->setConcours(true);
        $emsi->setEchangeInternational(true);
        $emsi->setBacObligatoire(true);

        // Durée d'études
        $emsi->setDureeEtudesMin(5);
        $emsi->setDureeEtudesMax(5);
        $emsi->setAnneesEtudes(5);

        // Frais
        $emsi->setFraisScolariteMin('45000');
        $emsi->setFraisScolariteMax('65000');
        $emsi->setFraisInscriptionMin('5000');
        $emsi->setFraisInscriptionMax('8000');

        // Diplômes délivrés
        $emsi->setDiplomesDelivres([
            'Diplôme d\'Ingénieur d\'État',
            'Master',
            'Master Spécialisé',
            'MBA'
        ]);

        // SEO
        $emsi->setMetaTitle('EMSI - École Marocaine des Sciences de l\'Ingénieur | Formation Ingénieur Maroc');
        $emsi->setMetaDescription('EMSI offre des formations d\'excellence en ingénierie et technologies. 4 campus au Maroc. Diplômes reconnus par l\'État. Admission sur concours.');
        $emsi->setMetaKeywords('EMSI, école ingénieur Maroc, formation informatique, génie civil, génie industriel, Casablanca, Rabat, Marrakech, Tanger, Honoris');
        $emsi->setCanonicalUrl('https://www.e-tawjihi.ma/ecoles/emsi');
        $emsi->setSchemaType('EducationalOrganization');
        $emsi->setNoIndex(false);

        // Statut et visibilité
        $emsi->setStatus('Publié');
        $emsi->setIsActive(true);
        $emsi->setIsRecommended(true);
        $emsi->setIsSponsored(false);
        $emsi->setIsFeatured(true);
        $emsi->setIsComplet(true);
        $emsi->setHasDetailPage(true);
        $emsi->setETawjihiInscription(true);

        // Compatibilité Bac
        $emsi->setBacType('both');
        $emsi->setFilieresAcceptees([
            'Sciences Mathématiques',
            'Sciences Expérimentales',
            'Sciences et Technologies',
            'Sciences Économiques'
        ]);
        $emsi->setCombinaisonsBacMission([
            ['Mathématiques', 'Physique-Chimie'],
            ['Mathématiques', 'SVT'],
            ['Mathématiques', 'Sciences Économiques et Sociales']
        ]);

        // Vidéo
        $emsi->setVideoUrl('https://www.youtube.com/watch?v=example');

        // Documents
        $emsi->setDocuments([
            [
                'nom' => 'Brochure EMSI 2024',
                'url' => 'https://cdn.e-tawjihi.ma/documents/emsi-brochure-2024.pdf',
                'type' => 'pdf'
            ],
            [
                'nom' => 'Guide d\'admission',
                'url' => 'https://cdn.e-tawjihi.ma/documents/emsi-guide-admission.pdf',
                'type' => 'pdf'
            ]
        ]);

        // Photos
        $emsi->setPhotos([
            'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=600&fit=crop'
        ]);

        $this->entityManager->persist($emsi);

        // Créer les campus
        $campusData = [
            [
                'nom' => 'Campus Casablanca',
                'ville' => 'Casablanca',
                'quartier' => 'Zerktouni',
                'adresse' => 'Boulevard Zerktouni, Casablanca',
                'codePostal' => '20000',
                'telephone' => '0522272727',
                'email' => 'casablanca@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps?q=EMSI+Casablanca',
                'ordre' => 1
            ],
            [
                'nom' => 'Campus Rabat',
                'ville' => 'Rabat',
                'quartier' => 'Agdal',
                'adresse' => 'Avenue Allal El Fassi, Rabat',
                'codePostal' => '10000',
                'telephone' => '0537772727',
                'email' => 'rabat@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps?q=EMSI+Rabat',
                'ordre' => 2
            ],
            [
                'nom' => 'Campus Marrakech',
                'ville' => 'Marrakech',
                'quartier' => 'Gueliz',
                'adresse' => 'Avenue Mohammed VI, Marrakech',
                'codePostal' => '40000',
                'telephone' => '0524442727',
                'email' => 'marrakech@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps?q=EMSI+Marrakech',
                'ordre' => 3
            ],
            [
                'nom' => 'Campus Tanger',
                'ville' => 'Tanger',
                'quartier' => 'Centre',
                'adresse' => 'Boulevard Pasteur, Tanger',
                'codePostal' => '90000',
                'telephone' => '0539992727',
                'email' => 'tanger@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps?q=EMSI+Tanger',
                'ordre' => 4
            ]
        ];

        $campusEntities = [];
        foreach ($campusData as $campusInfo) {
            $city = $cityRepository->findOneBy(['titre' => $campusInfo['ville']]);
            if (!$city) {
                $io->warning(sprintf('Ville %s non trouvée, création d\'un campus sans ville', $campusInfo['ville']));
                continue;
            }

            $campus = new Campus();
            $campus->setNom($campusInfo['nom']);
            $campus->setCity($city);
            $campus->setQuartier($campusInfo['quartier']);
            $campus->setAdresse($campusInfo['adresse']);
            $campus->setCodePostal($campusInfo['codePostal']);
            $campus->setTelephone($campusInfo['telephone']);
            $campus->setEmail($campusInfo['email']);
            $campus->setMapUrl($campusInfo['mapUrl']);
            $campus->setOrdre($campusInfo['ordre']);
            $campus->setEstablishment($emsi);

            $this->entityManager->persist($campus);
            $campusEntities[] = $campus;
        }

        // Trouver ou créer une filière complète : Génie Informatique
        $filiereRepository = $this->entityManager->getRepository(Filiere::class);
        $filiere = $filiereRepository->findOneBy(['slug' => 'genie-informatique-emsi']);
        
        if (!$filiere) {
            $filiere = new Filiere();
            $filiere->setNom('Génie Informatique');
            $filiere->setNomArabe('هندسة المعلوماتية');
            $filiere->setSlug('genie-informatique-emsi');
            $filiere->setEstablishment($emsi);
            $io->info('Création de la filière Génie Informatique...');
        } else {
            $io->info(sprintf('Filière Génie Informatique trouvée (ID: %d), mise à jour en cours...', $filiere->getId()));
        }

        // Description complète
        $filiere->setDescription('<h2>Génie Informatique à EMSI</h2>
<p>La filière <strong>Génie Informatique</strong> à EMSI forme des ingénieurs capables de concevoir, développer et maintenir des systèmes informatiques complexes. Cette formation allie théorie et pratique pour préparer les étudiants aux défis du monde professionnel.</p>

<h3>Objectifs de la formation</h3>
<ul>
<li>Maîtriser les fondamentaux de l\'informatique et des technologies de l\'information</li>
<li>Développer des compétences en programmation, bases de données et réseaux</li>
<li>Apprendre les méthodes de développement logiciel et gestion de projets</li>
<li>Acquérir des compétences en cybersécurité et intelligence artificielle</li>
<li>Développer l\'esprit d\'innovation et d\'entrepreneuriat</li>
</ul>

<h3>Débouchés professionnels</h3>
<p>Les diplômés en Génie Informatique d\'EMSI peuvent exercer dans divers secteurs :</p>
<ul>
<li><strong>Développement logiciel</strong> : Développeur full-stack, architecte logiciel</li>
<li><strong>Cybersécurité</strong> : Expert en sécurité informatique, analyste sécurité</li>
<li><strong>Intelligence Artificielle</strong> : Data scientist, ingénieur machine learning</li>
<li><strong>Gestion de projets</strong> : Chef de projet IT, scrum master</li>
<li><strong>Consulting</strong> : Consultant IT, architecte solutions</li>
</ul>

<h3>Programme de formation</h3>
<p>Le programme s\'étend sur 5 ans et comprend :</p>
<ul>
<li><strong>Cycle préparatoire</strong> (2 ans) : Mathématiques, physique, algorithmique, programmation</li>
<li><strong>Cycle ingénieur</strong> (3 ans) : Spécialisation en informatique, projets, stages</li>
</ul>');

        // Image de couverture
        $filiere->setImageCouverture('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200&h=600&fit=crop');

        // Informations générales
        $filiere->setDiplome('Diplôme d\'Ingénieur d\'État');
        $filiere->setDomaine('Informatique');
        $filiere->setLangueEtudes('Français');
        $filiere->setTypeEcole('Privé');
        $filiere->setNombreAnnees('5 ans');
        $filiere->setReconnaissance('Diplôme reconnu par l\'État marocain');

        // Frais
        $filiere->setFraisScolarite('55000');
        $filiere->setFraisInscription('6000');

        // Admission
        $filiere->setConcours(true);
        $filiere->setNbPlaces(120);
        $filiere->setBacCompatible(true);
        $filiere->setBacType('both');
        $filiere->setFilieresAcceptees([
            'Sciences Mathématiques',
            'Sciences Expérimentales',
            'Sciences et Technologies'
        ]);
        $filiere->setCombinaisonsBacMission([
            ['Mathématiques', 'Physique-Chimie'],
            ['Mathématiques', 'SVT']
        ]);

        // Recommandation
        $filiere->setRecommandee(true);

        // Métier associé
        $filiere->setMetier([
            'titre' => 'Ingénieur en Informatique',
            'description' => 'Conception et développement de solutions informatiques',
            'secteur' => 'Technologie',
            'competences' => [
                'Programmation',
                'Bases de données',
                'Réseaux',
                'Cybersécurité',
                'Gestion de projets'
            ]
        ]);

        // Objectifs
        $filiere->setObjectifs([
            'Former des ingénieurs compétents en informatique',
            'Développer l\'innovation et la créativité',
            'Préparer à l\'insertion professionnelle',
            'Favoriser l\'entrepreneuriat',
            'Promouvoir la recherche et développement'
        ]);

        // Programme détaillé
        $filiere->setProgramme([
            [
                'semestre' => 'Semestre 1',
                'modules' => [
                    'Algorithmique et programmation',
                    'Mathématiques pour l\'ingénieur',
                    'Architecture des ordinateurs',
                    'Systèmes d\'exploitation',
                    'Anglais technique'
                ]
            ],
            [
                'semestre' => 'Semestre 2',
                'modules' => [
                    'Structures de données',
                    'Bases de données',
                    'Réseaux informatiques',
                    'Mathématiques appliquées',
                    'Communication'
                ]
            ],
            [
                'semestre' => 'Semestre 3',
                'modules' => [
                    'Développement web',
                    'Développement mobile',
                    'Intelligence artificielle',
                    'Gestion de projets',
                    'Éthique et déontologie'
                ]
            ],
            [
                'semestre' => 'Semestre 4',
                'modules' => [
                    'Cybersécurité',
                    'Cloud computing',
                    'Big Data',
                    'Projet de fin d\'études',
                    'Stage en entreprise'
                ]
            ]
        ]);

        // Documents
        $filiere->setDocuments([
            [
                'nom' => 'Brochure Génie Informatique',
                'url' => 'https://cdn.e-tawjihi.ma/documents/emsi-genie-info-brochure.pdf',
                'type' => 'pdf'
            ],
            [
                'nom' => 'Programme détaillé',
                'url' => 'https://cdn.e-tawjihi.ma/documents/emsi-genie-info-programme.pdf',
                'type' => 'pdf'
            ]
        ]);

        // Photos
        $filiere->setPhotos([
            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1522542550221-31fd19575a2d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&h=600&fit=crop'
        ]);

        // Vidéo
        $filiere->setVideoUrl('https://www.youtube.com/watch?v=example-genie-info');

        // Échange international
        $filiere->setEchangeInternational(true);

        // SEO
        $filiere->setMetaTitle('Génie Informatique EMSI | Formation Ingénieur Informatique Maroc');
        $filiere->setMetaDescription('Formation d\'ingénieur en Génie Informatique à EMSI. 5 ans de formation, diplôme reconnu, stages en entreprise. Admission sur concours.');
        $filiere->setMetaKeywords('Génie Informatique EMSI, formation ingénieur informatique Maroc, école ingénieur Casablanca, programmation, cybersécurité');
        $filiere->setCanonicalUrl('https://www.e-tawjihi.ma/filieres/genie-informatique-emsi');
        $filiere->setSchemaType('EducationalProgram');
        $filiere->setNoIndex(false);

        // Statut
        $filiere->setIsActive(true);
        $filiere->setIsSponsored(false);

        // Lier la filière aux campus
        foreach ($campusEntities as $campus) {
            $filiere->addCampus($campus);
        }

        $this->entityManager->persist($filiere);
        $this->entityManager->flush();

        $io->success(sprintf(
            'EMSI et la filière Génie Informatique ont été complétés avec succès !\n' .
            '- Établissement: %s (ID: %d)\n' .
            '- Filière: %s (ID: %d)\n' .
            '- Campus: %d créés',
            $emsi->getNom(),
            $emsi->getId(),
            $filiere->getNom(),
            $filiere->getId(),
            count($campusEntities)
        ));

        return Command::SUCCESS;
    }
}
