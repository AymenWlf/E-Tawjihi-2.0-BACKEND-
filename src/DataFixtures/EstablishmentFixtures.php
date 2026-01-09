<?php

namespace App\DataFixtures;

use App\Entity\Establishment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EstablishmentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. EMSI - École Marocaine des Sciences de l'Ingénieur (Privé)
        $emsi = new Establishment();
        $emsi->setNom('École Marocaine des Sciences de l\'Ingénieur');
        $emsi->setNomArabe('المدرسة المغربية لعلوم المهندس');
        $emsi->setSigle('EMSI');
        $emsi->setType('Privé');
        $emsi->setVille('Casablanca');
        $emsi->setPays('Maroc');
        $emsi->setVilles(['Casablanca', 'Rabat', 'Marrakech', 'Tanger']);
        $emsi->setUniversite('Honoris United Universities');
        $emsi->setDescription($this->getEmsiDescription());
        $emsi->setEmail('contact@emsi.ma');
        $emsi->setTelephone('+212522272727');
        $emsi->setSiteWeb('https://www.emsi.ma');
        $emsi->setAdresse('Rue Abou Kacem Echabi, Casablanca');
        $emsi->setCodePostal('20100');
        $emsi->setNbEtudiants(5000);
        $emsi->setNbFilieres(15);
        $emsi->setAnneeCreation(1986);
        $emsi->setAnneesEtudes(5);
        $emsi->setAccreditationEtat(true);
        $emsi->setConcours(true);
        $emsi->setEchangeInternational(true);
        $emsi->setBacObligatoire(true);
        $emsi->setLogo('/uploads/logos/emsi-logo.png');
        $emsi->setImageCouverture('/uploads/covers/emsi-cover.jpg');
        $emsi->setVideoUrl('https://www.youtube.com/embed/dQw4w9WgXcQ');
        $emsi->setFacebook('https://www.facebook.com/EMSIMaroc');
        $emsi->setInstagram('https://www.instagram.com/emsi_maroc');
        $emsi->setLinkedin('https://www.linkedin.com/school/emsi');
        $emsi->setTwitter('https://twitter.com/EMSI_Maroc');
        $emsi->setYoutube('https://www.youtube.com/@EMSIMaroc');
        $emsi->setSlug('emsi-ecole-marocaine-sciences-ingenieur');
        $emsi->setMetaTitle('EMSI - École Marocaine des Sciences de l\'Ingénieur | Formation Ingénieur Maroc');
        $emsi->setMetaDescription('EMSI offre des formations d\'excellence en ingénierie et technologies. 4 campus au Maroc. Diplômes reconnus par l\'État.');
        $emsi->setMetaKeywords('EMSI, école ingénieur Maroc, formation informatique, génie civil, Casablanca, Rabat');
        $emsi->setOgImage('/uploads/covers/emsi-og.jpg');
        $emsi->setCanonicalUrl('https://etawjihi.ma/etablissements/emsi-ecole-marocaine-sciences-ingenieur');
        $emsi->setSchemaType('EducationalOrganization');
        $emsi->setNoIndex(false);
        $emsi->setIsActive(true);
        $emsi->setIsRecommended(true);
        $emsi->setIsSponsored(true);
        $emsi->setIsFeatured(true);
        $emsi->setStatus('Publié');
        $emsi->setIsComplet(true);
        $emsi->setHasDetailPage(true);
        $emsi->setCampus([
            [
                'name' => 'Campus Casablanca',
                'city' => 'Casablanca',
                'district' => 'Maarif',
                'address' => 'Rue Abou Kacem Echabi',
                'postalCode' => '20100',
                'phone' => '+212522272727',
                'email' => 'casa@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3323.8!2d-7.6177!3d33.5731'
            ],
            [
                'name' => 'Campus Rabat',
                'city' => 'Rabat',
                'district' => 'Agdal',
                'address' => 'Avenue des FAR',
                'postalCode' => '10000',
                'phone' => '+212537777777',
                'email' => 'rabat@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3307.5!2d-6.8498!3d33.9716'
            ],
            [
                'name' => 'Campus Marrakech',
                'city' => 'Marrakech',
                'district' => 'Guéliz',
                'address' => 'Avenue Mohammed VI',
                'postalCode' => '40000',
                'phone' => '+212524434343',
                'email' => 'marrakech@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3396.8!2d-8.0089!3d31.6295'
            ],
            [
                'name' => 'Campus Tanger',
                'city' => 'Tanger',
                'district' => 'Centre Ville',
                'address' => 'Boulevard Mohammed V',
                'postalCode' => '90000',
                'phone' => '+212539949494',
                'email' => 'tanger@emsi.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3240.2!2d-5.8324!3d35.7595'
            ]
        ]);
        $emsi->setDocuments([
            [
                'name' => 'Brochure de présentation',
                'url' => '/uploads/brochures/emsi-brochure-2024.pdf',
                'type' => 'pdf',
                'description' => 'Découvrez tous nos programmes'
            ]
        ]);

        $manager->persist($emsi);

        // 2. EST - École Supérieure de Technologie (Public)
        $est = new Establishment();
        $est->setNom('École Supérieure de Technologie de Casablanca');
        $est->setNomArabe('المدرسة العليا للتكنولوجيا بالدار البيضاء');
        $est->setSigle('EST Casa');
        $est->setType('Public');
        $est->setVille('Casablanca');
        $est->setPays('Maroc');
        $est->setVilles(['Casablanca']);
        $est->setUniversite('Université Hassan II de Casablanca');
        $est->setDescription($this->getEstDescription());
        $est->setEmail('est@uh2c.ac.ma');
        $est->setTelephone('+212522230000');
        $est->setSiteWeb('https://www.estc.ma');
        $est->setAdresse('Km 7, Route d\'El Jadida, Casablanca');
        $est->setCodePostal('20230');
        $est->setNbEtudiants(3500);
        $est->setNbFilieres(12);
        $est->setAnneeCreation(1992);
        $est->setAnneesEtudes(3);
        $est->setAccreditationEtat(true);
        $est->setConcours(true);
        $est->setEchangeInternational(true);
        $est->setBacObligatoire(true);
        $est->setLogo('/uploads/logos/est-casa-logo.png');
        $est->setImageCouverture('/uploads/covers/est-casa-cover.jpg');
        $est->setVideoUrl('https://www.youtube.com/embed/dQw4w9WgXcQ');
        $est->setFacebook('https://www.facebook.com/ESTCasablanca');
        $est->setInstagram('https://www.instagram.com/est_casablanca');
        $est->setLinkedin('https://www.linkedin.com/school/est-casablanca');
        $est->setYoutube('https://www.youtube.com/@ESTCasablanca');
        $est->setSlug('est-casablanca-ecole-superieure-technologie');
        $est->setMetaTitle('EST Casablanca - École Supérieure de Technologie | Formation DUT & Licence');
        $est->setMetaDescription('EST Casablanca propose des DUT et Licences Professionnelles en Génie Informatique, Électrique, Mécanique. Formation publique gratuite.');
        $est->setMetaKeywords('EST Casablanca, DUT Maroc, Licence Professionnelle, Génie Informatique, école publique, Université Hassan II');
        $est->setOgImage('/uploads/covers/est-casa-og.jpg');
        $est->setCanonicalUrl('https://etawjihi.ma/etablissements/est-casablanca-ecole-superieure-technologie');
        $est->setSchemaType('EducationalOrganization');
        $est->setNoIndex(false);
        $est->setIsActive(true);
        $est->setIsRecommended(true);
        $est->setIsSponsored(false);
        $est->setIsFeatured(false);
        $est->setStatus('Publié');
        $est->setIsComplet(true);
        $est->setHasDetailPage(true);
        $est->setCampus([
            [
                'name' => 'Campus Principal',
                'city' => 'Casablanca',
                'district' => 'Route d\'El Jadida',
                'address' => 'Km 7, Route d\'El Jadida',
                'postalCode' => '20230',
                'phone' => '+212522230000',
                'email' => 'contact@estc.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3325.2!2d-7.6489!3d33.5489'
            ]
        ]);
        $est->setDocuments([
            [
                'name' => 'Guide de l\'étudiant 2024',
                'url' => '/uploads/brochures/est-casa-guide-2024.pdf',
                'type' => 'pdf',
                'description' => 'Toutes les informations pour votre inscription'
            ]
        ]);

        $manager->persist($est);

        // 3. ERSSM - École Royale du Service de Santé Militaire (Militaire)
        $erssm = new Establishment();
        $erssm->setNom('École Royale du Service de Santé Militaire');
        $erssm->setNomArabe('المدرسة الملكية لمصلحة الصحة العسكرية');
        $erssm->setSigle('ERSSM');
        $erssm->setType('Militaire');
        $erssm->setVille('Rabat');
        $erssm->setPays('Maroc');
        $erssm->setVilles(['Rabat']);
        $erssm->setUniversite('Forces Armées Royales');
        $erssm->setDescription($this->getErسsmDescription());
        $erssm->setEmail('erssm@far.ma');
        $erssm->setTelephone('+212537769000');
        $erssm->setSiteWeb('https://www.far.ma/erssm');
        $erssm->setAdresse('Rabat Instituts, Rabat');
        $erssm->setCodePostal('10100');
        $erssm->setNbEtudiants(400);
        $erssm->setNbFilieres(8);
        $erssm->setAnneeCreation(1966);
        $erssm->setAnneesEtudes(7);
        $erssm->setAccreditationEtat(true);
        $erssm->setConcours(true);
        $erssm->setEchangeInternational(false);
        $erssm->setBacObligatoire(true);
        $erssm->setLogo('/uploads/logos/erssm-logo.png');
        $erssm->setImageCouverture('/uploads/covers/erssm-cover.jpg');
        $erssm->setVideoUrl('https://www.youtube.com/embed/dQw4w9WgXcQ');
        $erssm->setFacebook('https://www.facebook.com/ERSSM.Officiel');
        $erssm->setYoutube('https://www.youtube.com/@ERSSM_Officiel');
        $erssm->setSlug('erssm-ecole-royale-service-sante-militaire');
        $erssm->setMetaTitle('ERSSM - École Royale du Service de Santé Militaire | Médecine Militaire Maroc');
        $erssm->setMetaDescription('ERSSM forme des médecins militaires au Maroc. Formation gratuite avec solde. Concours sélectif. 7 ans d\'études.');
        $erssm->setMetaKeywords('ERSSM, médecine militaire, école militaire Maroc, concours ERSSM, Forces Armées Royales');
        $erssm->setOgImage('/uploads/covers/erssm-og.jpg');
        $erssm->setCanonicalUrl('https://etawjihi.ma/etablissements/erssm-ecole-royale-service-sante-militaire');
        $erssm->setSchemaType('EducationalOrganization');
        $erssm->setNoIndex(false);
        $erssm->setIsActive(true);
        $erssm->setIsRecommended(true);
        $erssm->setIsSponsored(false);
        $erssm->setIsFeatured(true);
        $erssm->setStatus('Publié');
        $erssm->setIsComplet(true);
        $erssm->setHasDetailPage(true);
        $erssm->setCampus([
            [
                'name' => 'Campus Principal',
                'city' => 'Rabat',
                'district' => 'Rabat Instituts',
                'address' => 'Avenue des FAR, Rabat Instituts',
                'postalCode' => '10100',
                'phone' => '+212537769000',
                'email' => 'contact@erssm.ma',
                'mapUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3308.1!2d-6.8626!3d33.9856'
            ]
        ]);
        $erssm->setDocuments([
            [
                'name' => 'Dossier de candidature 2024',
                'url' => '/uploads/brochures/erssm-candidature-2024.pdf',
                'type' => 'pdf',
                'description' => 'Conditions et modalités d\'inscription'
            ],
            [
                'name' => 'Notice d\'information',
                'url' => '/uploads/brochures/erssm-notice-2024.pdf',
                'type' => 'pdf',
                'description' => 'Informations sur le concours et la formation'
            ]
        ]);

        $manager->persist($erssm);

        $manager->flush();
    }

    private function getEmsiDescription(): string
    {
        return '<h3>🎓 EMSI - Leader de l\'enseignement supérieur privé au Maroc</h3>
<p>L\'<strong>École Marocaine des Sciences de l\'Ingénieur (EMSI)</strong> est une institution d\'enseignement supérieur privé de référence au Maroc, créée en 1986.</p>';
    }

    private function getEstDescription(): string
    {
        return '<h3>🎓 EST Casablanca - Formation Technologique d\'Excellence</h3>
<p>L\'<strong>École Supérieure de Technologie de Casablanca (EST Casa)</strong> est un établissement public d\'enseignement supérieur rattaché à l\'<strong>Université Hassan II de Casablanca</strong>.</p>';
    }

    private function getErسsmDescription(): string
    {
        return '<h3>🎖️ ERSSM - Former les Médecins Militaires de Demain</h3>
<p>L\'<strong>École Royale du Service de Santé Militaire (ERSSM)</strong> est une institution militaire d\'enseignement supérieur médical créée en 1966.</p>';
    }
}
