<?php

namespace App\Service;

use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Entity\Universite;
use App\Entity\Campus;
use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;

class MigrationService
{
    private Filesystem $filesystem;
    private string $oldUploadsPath;
    private string $newUploadsPath;

    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        string $kernelProjectDir
    ) {
        $this->filesystem = new Filesystem();
        $this->oldUploadsPath = $kernelProjectDir . '/public/old_uploads';
        $this->newUploadsPath = $kernelProjectDir . '/public/uploads';
    }

    /**
     * Migre un établissement depuis un ancien format vers le nouveau
     * 
     * @param array $oldData Données de l'ancien système
     * @return Establishment|null
     */
    public function migrateEstablishment(array $oldData): ?Establishment
    {
        try {
            $establishment = new Establishment();

            // Mapping direct des attributs simples
            $mapping = $this->getEstablishmentMapping();
            foreach ($mapping as $oldKey => $newKey) {
                if (isset($oldData[$oldKey])) {
                    $setter = 'set' . ucfirst($newKey);
                    if (method_exists($establishment, $setter)) {
                        $value = $this->transformValue($oldKey, $oldData[$oldKey], 'establishment');
                        $establishment->$setter($value);
                    }
                }
            }

            // Génération du slug si manquant
            if (!$establishment->getSlug() && $establishment->getNom()) {
                $slug = $this->slugger->slug($establishment->getNom())->lower();
                $establishment->setSlug((string) $slug);
            }

            // Migration des fichiers
            $this->migrateEstablishmentFiles($establishment, $oldData);

            // Migration des relations (Campus, etc.)
            if (isset($oldData['campus']) && is_array($oldData['campus'])) {
                foreach ($oldData['campus'] as $campusData) {
                    $campus = $this->migrateCampus($campusData, $establishment);
                    if ($campus) {
                        $establishment->addCampus($campus);
                    }
                }
            }

            // Conversion des types JSON
            $this->convertJsonFields($establishment, $oldData);

            return $establishment;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la migration de l\'établissement', [
                'oldData' => $oldData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Migre une filière depuis un ancien format vers le nouveau
     * 
     * @param array $oldData Données de l'ancien système
     * @param Establishment|null $establishment Établissement parent (peut être null si à rechercher)
     * @return Filiere|null
     */
    public function migrateFiliere(array $oldData, ?Establishment $establishment = null): ?Filiere
    {
        try {
            $filiere = new Filiere();

            // Mapping direct des attributs simples
            $mapping = $this->getFiliereMapping();
            foreach ($mapping as $oldKey => $newKey) {
                if (isset($oldData[$oldKey])) {
                    $setter = 'set' . ucfirst($newKey);
                    if (method_exists($filiere, $setter)) {
                        $value = $this->transformValue($oldKey, $oldData[$oldKey], 'filiere');
                        $filiere->$setter($value);
                    }
                }
            }

            // Lier à l'établissement
            if (!$establishment && isset($oldData['establishment_id'])) {
                $establishment = $this->em->getRepository(Establishment::class)->find($oldData['establishment_id']);
            }
            if (!$establishment && isset($oldData['etablissement_id'])) {
                $establishment = $this->em->getRepository(Establishment::class)->find($oldData['etablissement_id']);
            }

            if (!$establishment) {
                $this->logger->warning('Établissement non trouvé pour la filière', ['filiere' => $oldData]);
                return null;
            }

            $filiere->setEstablishment($establishment);

            // Génération du slug si manquant
            if (!$filiere->getSlug() && $filiere->getNom()) {
                $slug = $this->slugger->slug($filiere->getNom())->lower();
                // Ajouter l'ID de l'établissement pour éviter les collisions
                $baseSlug = (string) $slug;
                $counter = 1;
                while ($this->slugExists($baseSlug, 'filiere')) {
                    $baseSlug = (string) $slug . '-' . $counter;
                    $counter++;
                }
                $filiere->setSlug($baseSlug);
            }

            // Migration des fichiers
            $this->migrateFiliereFiles($filiere, $oldData);

            // Migration des relations Campus
            if (isset($oldData['campus_ids']) && is_array($oldData['campus_ids'])) {
                foreach ($oldData['campus_ids'] as $campusId) {
                    $campus = $this->em->getRepository(Campus::class)->find($campusId);
                    if ($campus) {
                        $filiere->addCampus($campus);
                    }
                }
            }

            // Conversion des types JSON
            $this->convertFiliereJsonFields($filiere, $oldData);

            return $filiere;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la migration de la filière', [
                'oldData' => $oldData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Migre une université depuis un ancien format vers le nouveau
     * 
     * @param array $oldData Données de l'ancien système
     * @return Universite|null
     */
    public function migrateUniversite(array $oldData): ?Universite
    {
        try {
            $universite = new Universite();

            // Mapping direct des attributs simples
            $mapping = $this->getUniversiteMapping();
            foreach ($mapping as $oldKey => $newKey) {
                if (isset($oldData[$oldKey])) {
                    $setter = 'set' . ucfirst($newKey);
                    if (method_exists($universite, $setter)) {
                        $value = $this->transformValue($oldKey, $oldData[$oldKey], 'universite');
                        $universite->$setter($value);
                    }
                }
            }

            // Migration du logo
            if (isset($oldData['logo']) && $oldData['logo']) {
                $newLogoPath = $this->copyFile($oldData['logo'], 'universites', 'logo');
                if ($newLogoPath) {
                    $universite->setLogo($newLogoPath);
                }
            }

            return $universite;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la migration de l\'université', [
                'oldData' => $oldData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Migre un campus
     */
    private function migrateCampus(array $oldData, Establishment $establishment): ?Campus
    {
        try {
            $campus = new Campus();
            $campus->setNom($oldData['nom'] ?? 'Campus');
            $campus->setEstablishment($establishment);

            // Lier à la ville (City)
            if (isset($oldData['city_id'])) {
                $city = $this->em->getRepository(City::class)->find($oldData['city_id']);
                if ($city) {
                    $campus->setCity($city);
                }
            } elseif (isset($oldData['ville'])) {
                // Rechercher la ville par nom
                $city = $this->em->getRepository(City::class)->findOneBy(['titre' => $oldData['ville']]);
                if ($city) {
                    $campus->setCity($city);
                }
            }

            // Autres attributs
            if (isset($oldData['quartier'])) {
                $campus->setQuartier($oldData['quartier']);
            }
            if (isset($oldData['adresse'])) {
                $campus->setAdresse($oldData['adresse']);
            }
            if (isset($oldData['codePostal'])) {
                $campus->setCodePostal($oldData['codePostal']);
            }
            if (isset($oldData['telephone'])) {
                $campus->setTelephone($oldData['telephone']);
            }
            if (isset($oldData['email'])) {
                $campus->setEmail($oldData['email']);
            }
            if (isset($oldData['mapUrl'])) {
                $campus->setMapUrl($oldData['mapUrl']);
            }
            if (isset($oldData['ordre'])) {
                $campus->setOrdre($oldData['ordre']);
            }

            return $campus;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la migration du campus', [
                'oldData' => $oldData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mapping des attributs Establishment
     */
    private function getEstablishmentMapping(): array
    {
        return [
            // Direct mapping (same name)
            'nom' => 'nom',
            'sigle' => 'sigle',
            'nomArabe' => 'nomArabe',
            'nom_arabe' => 'nomArabe',
            'type' => 'type',
            'ville' => 'ville',
            'villes' => 'villes',
            'pays' => 'pays',
            'universite' => 'universite',
            'description' => 'description',
            'email' => 'email',
            'telephone' => 'telephone',
            'siteWeb' => 'siteWeb',
            'site_web' => 'siteWeb',
            'adresse' => 'adresse',
            'codePostal' => 'codePostal',
            'code_postal' => 'codePostal',
            'facebook' => 'facebook',
            'instagram' => 'instagram',
            'twitter' => 'twitter',
            'linkedin' => 'linkedin',
            'youtube' => 'youtube',
            'nbEtudiants' => 'nbEtudiants',
            'nb_etudiants' => 'nbEtudiants',
            'nbFilieres' => 'nbFilieres',
            'nb_filieres' => 'nbFilieres',
            'anneeCreation' => 'anneeCreation',
            'annee_creation' => 'anneeCreation',
            'accreditationEtat' => 'accreditationEtat',
            'accreditation_etat' => 'accreditationEtat',
            'concours' => 'concours',
            'echangeInternational' => 'echangeInternational',
            'echange_international' => 'echangeInternational',
            'anneesEtudes' => 'anneesEtudes',
            'annees_etudes' => 'anneesEtudes',
            'dureeEtudesMin' => 'dureeEtudesMin',
            'duree_etudes_min' => 'dureeEtudesMin',
            'dureeEtudesMax' => 'dureeEtudesMax',
            'duree_etudes_max' => 'dureeEtudesMax',
            'fraisScolariteMin' => 'fraisScolariteMin',
            'frais_scolarite_min' => 'fraisScolariteMin',
            'fraisScolariteMax' => 'fraisScolariteMax',
            'frais_scolarite_max' => 'fraisScolariteMax',
            'fraisInscriptionMin' => 'fraisInscriptionMin',
            'frais_inscription_min' => 'fraisInscriptionMin',
            'fraisInscriptionMax' => 'fraisInscriptionMax',
            'frais_inscription_max' => 'fraisInscriptionMax',
            'diplomesDelivres' => 'diplomesDelivres',
            'diplomes_delivres' => 'diplomesDelivres',
            'bacObligatoire' => 'bacObligatoire',
            'bac_obligatoire' => 'bacObligatoire',
            'slug' => 'slug',
            'metaTitle' => 'metaTitle',
            'meta_title' => 'metaTitle',
            'metaDescription' => 'metaDescription',
            'meta_description' => 'metaDescription',
            'metaKeywords' => 'metaKeywords',
            'meta_keywords' => 'metaKeywords',
            'ogImage' => 'ogImage',
            'og_image' => 'ogImage',
            'canonicalUrl' => 'canonicalUrl',
            'canonical_url' => 'canonicalUrl',
            'schemaType' => 'schemaType',
            'schema_type' => 'schemaType',
            'noIndex' => 'noIndex',
            'no_index' => 'noIndex',
            'isActive' => 'isActive',
            'is_active' => 'isActive',
            'isRecommended' => 'isRecommended',
            'is_recommended' => 'isRecommended',
            'isSponsored' => 'isSponsored',
            'is_sponsored' => 'isSponsored',
            'isFeatured' => 'isFeatured',
            'is_featured' => 'isFeatured',
            'videoUrl' => 'videoUrl',
            'video_url' => 'videoUrl',
            'status' => 'status',
            'isComplet' => 'isComplet',
            'is_complet' => 'isComplet',
            'hasDetailPage' => 'hasDetailPage',
            'has_detail_page' => 'hasDetailPage',
            'eTawjihiInscription' => 'eTawjihiInscription',
            'e_tawjihi_inscription' => 'eTawjihiInscription',
            'bacType' => 'bacType',
            'bac_type' => 'bacType',
            'filieresAcceptees' => 'filieresAcceptees',
            'filieres_acceptees' => 'filieresAcceptees',
            'combinaisonsBacMission' => 'combinaisonsBacMission',
            'combinaisons_bac_mission' => 'combinaisonsBacMission',
            'secteursIds' => 'secteursIds',
            'secteurs_ids' => 'secteursIds',
            'filieresIds' => 'filieresIds',
            'filieres_ids' => 'filieresIds',
            'createdAt' => 'createdAt',
            'created_at' => 'createdAt',
            'updatedAt' => 'updatedAt',
            'updated_at' => 'updatedAt',
        ];
    }

    /**
     * Mapping des attributs Filiere
     */
    private function getFiliereMapping(): array
    {
        return [
            'nom' => 'nom',
            'nomArabe' => 'nomArabe',
            'nom_arabe' => 'nomArabe',
            'titre' => 'nom',
            'titreArabe' => 'nomArabe',
            'slug' => 'slug',
            'description' => 'description',
            'diplome' => 'diplome',
            'domaine' => 'domaine',
            'langueEtudes' => 'langueEtudes',
            'langue_etudes' => 'langueEtudes',
            'fraisScolarite' => 'fraisScolarite',
            'frais_scolarite' => 'fraisScolarite',
            'fraisAnnuels' => 'fraisScolarite',
            'fraisInscription' => 'fraisInscription',
            'frais_inscription' => 'fraisInscription',
            'concours' => 'concours',
            'nbPlaces' => 'nbPlaces',
            'nb_places' => 'nbPlaces',
            'nombreAnnees' => 'nombreAnnees',
            'nombre_annees' => 'nombreAnnees',
            'duree' => 'nombreAnnees',
            'typeEcole' => 'typeEcole',
            'type_ecole' => 'typeEcole',
            'bacCompatible' => 'bacCompatible',
            'bac_compatible' => 'bacCompatible',
            'bacType' => 'bacType',
            'bac_type' => 'bacType',
            'filieresAcceptees' => 'filieresAcceptees',
            'filieres_acceptees' => 'filieresAcceptees',
            'combinaisonsBacMission' => 'combinaisonsBacMission',
            'combinaisons_bac_mission' => 'combinaisonsBacMission',
            'recommandee' => 'recommandee',
            'isRecommended' => 'recommandee',
            'is_recommended' => 'recommandee',
            'metier' => 'metier',
            'debouches' => 'metier',
            'objectifs' => 'objectifs',
            'programme' => 'programme',
            'videoUrl' => 'videoUrl',
            'video_url' => 'videoUrl',
            'reconnaissance' => 'reconnaissance',
            'echangeInternational' => 'echangeInternational',
            'echange_international' => 'echangeInternational',
            'metaTitle' => 'metaTitle',
            'meta_title' => 'metaTitle',
            'metaDescription' => 'metaDescription',
            'meta_description' => 'metaDescription',
            'metaKeywords' => 'metaKeywords',
            'meta_keywords' => 'metaKeywords',
            'ogImage' => 'ogImage',
            'og_image' => 'ogImage',
            'canonicalUrl' => 'canonicalUrl',
            'canonical_url' => 'canonicalUrl',
            'schemaType' => 'schemaType',
            'schema_type' => 'schemaType',
            'noIndex' => 'noIndex',
            'no_index' => 'noIndex',
            'isActive' => 'isActive',
            'is_active' => 'isActive',
            'isSponsored' => 'isSponsored',
            'is_sponsored' => 'isSponsored',
            'createdAt' => 'createdAt',
            'created_at' => 'createdAt',
            'updatedAt' => 'updatedAt',
            'updated_at' => 'updatedAt',
        ];
    }

    /**
     * Mapping des attributs Universite
     */
    private function getUniversiteMapping(): array
    {
        return [
            'nom' => 'nom',
            'sigle' => 'sigle',
            'nomArabe' => 'nomArabe',
            'nom_arabe' => 'nomArabe',
            'ville' => 'ville',
            'region' => 'region',
            'pays' => 'pays',
            'type' => 'type',
            'description' => 'description',
            'siteWeb' => 'siteWeb',
            'site_web' => 'siteWeb',
            'email' => 'email',
            'telephone' => 'telephone',
            'isActive' => 'isActive',
            'is_active' => 'isActive',
            'createdAt' => 'createdAt',
            'created_at' => 'createdAt',
            'updatedAt' => 'updatedAt',
            'updated_at' => 'updatedAt',
        ];
    }

    /**
     * Transforme une valeur selon le type attendu
     */
    private function transformValue(string $oldKey, $value, string $entityType)
    {
        // Conversion de types
        if (in_array($oldKey, ['concours', 'accreditationEtat', 'accreditation_etat', 'echangeInternational', 'echange_international', 'bacObligatoire', 'bac_obligatoire', 'noIndex', 'no_index', 'isActive', 'is_active', 'isRecommended', 'is_recommended', 'isSponsored', 'is_sponsored', 'isFeatured', 'is_featured', 'isComplet', 'is_complet', 'hasDetailPage', 'has_detail_page', 'eTawjihiInscription', 'e_tawjihi_inscription', 'bacCompatible', 'bac_compatible', 'recommandee'])) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if (in_array($oldKey, ['nbEtudiants', 'nb_etudiants', 'nbFilieres', 'nb_filieres', 'anneeCreation', 'annee_creation', 'anneesEtudes', 'annees_etudes', 'dureeEtudesMin', 'duree_etudes_min', 'dureeEtudesMax', 'duree_etudes_max', 'nbPlaces', 'nb_places', 'ordre'])) {
            return is_numeric($value) ? (int) $value : null;
        }

        if (in_array($oldKey, ['fraisScolariteMin', 'frais_scolarite_min', 'fraisScolariteMax', 'frais_scolarite_max', 'fraisInscriptionMin', 'frais_inscription_min', 'fraisInscriptionMax', 'frais_inscription_max', 'fraisScolarite', 'frais_scolarite', 'fraisInscription', 'frais_inscription'])) {
            return is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
        }

        // Conversion de dates
        if (in_array($oldKey, ['createdAt', 'created_at', 'updatedAt', 'updated_at'])) {
            if ($value instanceof \DateTime) {
                return $value;
            }
            if (is_string($value)) {
                try {
                    return new \DateTime($value);
                } catch (\Exception $e) {
                    return new \DateTime();
                }
            }
            return new \DateTime();
        }

        return $value;
    }

    /**
     * Convertit les champs JSON pour Establishment
     */
    private function convertJsonFields(Establishment $establishment, array $oldData): void
    {
        // Villes
        if (isset($oldData['villes']) && !is_array($oldData['villes'])) {
            $villes = is_string($oldData['villes']) ? json_decode($oldData['villes'], true) : [];
            $establishment->setVilles(is_array($villes) ? $villes : null);
        }

        // Diplômes
        if (isset($oldData['diplomesDelivres']) || isset($oldData['diplomes_delivres'])) {
            $diplomes = $oldData['diplomesDelivres'] ?? $oldData['diplomes_delivres'] ?? null;
            if (!is_array($diplomes) && is_string($diplomes)) {
                $diplomes = json_decode($diplomes, true);
            }
            $establishment->setDiplomesDelivres(is_array($diplomes) ? $diplomes : null);
        }

        // Filières acceptées
        if (isset($oldData['filieresAcceptees']) || isset($oldData['filieres_acceptees'])) {
            $filieres = $oldData['filieresAcceptees'] ?? $oldData['filieres_acceptees'] ?? null;
            if (!is_array($filieres) && is_string($filieres)) {
                $filieres = json_decode($filieres, true);
            }
            $establishment->setFilieresAcceptees(is_array($filieres) ? $filieres : null);
        }

        // Combinaisons Bac Mission
        if (isset($oldData['combinaisonsBacMission']) || isset($oldData['combinaisons_bac_mission'])) {
            $combinaisons = $oldData['combinaisonsBacMission'] ?? $oldData['combinaisons_bac_mission'] ?? null;
            if (!is_array($combinaisons) && is_string($combinaisons)) {
                $combinaisons = json_decode($combinaisons, true);
            }
            $establishment->setCombinaisonsBacMission(is_array($combinaisons) ? $combinaisons : null);
        }

        // Secteurs IDs
        if (isset($oldData['secteursIds']) || isset($oldData['secteurs_ids'])) {
            $secteurs = $oldData['secteursIds'] ?? $oldData['secteurs_ids'] ?? null;
            if (!is_array($secteurs) && is_string($secteurs)) {
                $secteurs = json_decode($secteurs, true);
            }
            if (is_array($secteurs)) {
                $secteurs = array_map('intval', $secteurs);
            }
            $establishment->setSecteursIds(is_array($secteurs) ? $secteurs : null);
        }

        // Filières IDs
        if (isset($oldData['filieresIds']) || isset($oldData['filieres_ids'])) {
            $filieres = $oldData['filieresIds'] ?? $oldData['filieres_ids'] ?? null;
            if (!is_array($filieres) && is_string($filieres)) {
                $filieres = json_decode($filieres, true);
            }
            if (is_array($filieres)) {
                $filieres = array_map('intval', $filieres);
            }
            $establishment->setFilieresIds(is_array($filieres) ? $filieres : null);
        }

        // Documents
        if (isset($oldData['documents'])) {
            $documents = is_array($oldData['documents']) ? $oldData['documents'] : 
                        (is_string($oldData['documents']) ? json_decode($oldData['documents'], true) : null);
            if (is_array($documents)) {
                // Migrer les fichiers de documents
                $migratedDocs = [];
                foreach ($documents as $doc) {
                    if (isset($doc['url']) || isset($doc['filePath'])) {
                        $oldPath = $doc['url'] ?? $doc['filePath'];
                        $newPath = $this->copyFile($oldPath, 'establishments', 'documents');
                        if ($newPath) {
                            $doc['url'] = $newPath;
                            $migratedDocs[] = $doc;
                        }
                    } else {
                        $migratedDocs[] = $doc;
                    }
                }
                $establishment->setDocuments($migratedDocs);
            }
        }

        // Photos
        if (isset($oldData['photos'])) {
            $photos = is_array($oldData['photos']) ? $oldData['photos'] : 
                     (is_string($oldData['photos']) ? json_decode($oldData['photos'], true) : null);
            if (is_array($photos)) {
                // Migrer les fichiers de photos
                $migratedPhotos = [];
                foreach ($photos as $photo) {
                    if (is_string($photo)) {
                        $newPath = $this->copyFile($photo, 'establishments', 'photos');
                        if ($newPath) {
                            $migratedPhotos[] = ['url' => $newPath];
                        }
                    } elseif (isset($photo['url']) || isset($photo['filePath'])) {
                        $oldPath = $photo['url'] ?? $photo['filePath'];
                        $newPath = $this->copyFile($oldPath, 'establishments', 'photos');
                        if ($newPath) {
                            $photo['url'] = $newPath;
                            $migratedPhotos[] = $photo;
                        }
                    } else {
                        $migratedPhotos[] = $photo;
                    }
                }
                $establishment->setPhotos($migratedPhotos);
            }
        }
    }

    /**
     * Convertit les champs JSON pour Filiere
     */
    private function convertFiliereJsonFields(Filiere $filiere, array $oldData): void
    {
        // Similaire à convertJsonFields mais pour Filiere
        // Filières acceptées
        if (isset($oldData['filieresAcceptees']) || isset($oldData['filieres_acceptees'])) {
            $filieres = $oldData['filieresAcceptees'] ?? $oldData['filieres_acceptees'] ?? null;
            if (!is_array($filieres) && is_string($filieres)) {
                $filieres = json_decode($filieres, true);
            }
            $filiere->setFilieresAcceptees(is_array($filieres) ? $filieres : null);
        }

        // Combinaisons Bac Mission
        if (isset($oldData['combinaisonsBacMission']) || isset($oldData['combinaisons_bac_mission'])) {
            $combinaisons = $oldData['combinaisonsBacMission'] ?? $oldData['combinaisons_bac_mission'] ?? null;
            if (!is_array($combinaisons) && is_string($combinaisons)) {
                $combinaisons = json_decode($combinaisons, true);
            }
            $filiere->setCombinaisonsBacMission(is_array($combinaisons) ? $combinaisons : null);
        }

        // Metier/Debouches
        if (isset($oldData['metier']) || isset($oldData['debouches'])) {
            $metier = $oldData['metier'] ?? $oldData['debouches'] ?? null;
            if (!is_array($metier) && is_string($metier)) {
                $metier = json_decode($metier, true);
            }
            $filiere->setMetier(is_array($metier) ? $metier : null);
        }

        // Objectifs
        if (isset($oldData['objectifs'])) {
            $objectifs = is_array($oldData['objectifs']) ? $oldData['objectifs'] : 
                        (is_string($oldData['objectifs']) ? json_decode($oldData['objectifs'], true) : null);
            $filiere->setObjectifs(is_array($objectifs) ? $objectifs : null);
        }

        // Programme
        if (isset($oldData['programme'])) {
            $programme = is_array($oldData['programme']) ? $oldData['programme'] : 
                        (is_string($oldData['programme']) ? json_decode($oldData['programme'], true) : null);
            $filiere->setProgramme(is_array($programme) ? $programme : null);
        }

        // Documents
        if (isset($oldData['documents'])) {
            $documents = is_array($oldData['documents']) ? $oldData['documents'] : 
                        (is_string($oldData['documents']) ? json_decode($oldData['documents'], true) : null);
            if (is_array($documents)) {
                $migratedDocs = [];
                foreach ($documents as $doc) {
                    if (isset($doc['url']) || isset($doc['filePath'])) {
                        $oldPath = $doc['url'] ?? $doc['filePath'];
                        $newPath = $this->copyFile($oldPath, 'filieres', 'documents');
                        if ($newPath) {
                            $doc['url'] = $newPath;
                            $migratedDocs[] = $doc;
                        }
                    } else {
                        $migratedDocs[] = $doc;
                    }
                }
                $filiere->setDocuments($migratedDocs);
            }
        }

        // Photos
        if (isset($oldData['photos'])) {
            $photos = is_array($oldData['photos']) ? $oldData['photos'] : 
                     (is_string($oldData['photos']) ? json_decode($oldData['photos'], true) : null);
            if (is_array($photos)) {
                $migratedPhotos = [];
                foreach ($photos as $photo) {
                    if (is_string($photo)) {
                        $newPath = $this->copyFile($photo, 'filieres', 'photos');
                        if ($newPath) {
                            $migratedPhotos[] = ['url' => $newPath];
                        }
                    } elseif (isset($photo['url']) || isset($photo['filePath'])) {
                        $oldPath = $photo['url'] ?? $photo['filePath'];
                        $newPath = $this->copyFile($oldPath, 'filieres', 'photos');
                        if ($newPath) {
                            $photo['url'] = $newPath;
                            $migratedPhotos[] = $photo;
                        }
                    } else {
                        $migratedPhotos[] = $photo;
                    }
                }
                $filiere->setPhotos($migratedPhotos);
            }
        }
    }

    /**
     * Migre les fichiers d'un établissement
     */
    private function migrateEstablishmentFiles(Establishment $establishment, array $oldData): void
    {
        // Logo
        if (isset($oldData['logo']) && $oldData['logo']) {
            $newLogoPath = $this->copyFile($oldData['logo'], 'establishments', 'logo');
            if ($newLogoPath) {
                $establishment->setLogo($newLogoPath);
            }
        }

        // Image de couverture
        if (isset($oldData['imageCouverture']) && $oldData['imageCouverture']) {
            $newCoverPath = $this->copyFile($oldData['imageCouverture'], 'establishments', 'cover');
            if ($newCoverPath) {
                $establishment->setImageCouverture($newCoverPath);
            }
        } elseif (isset($oldData['image_couverture']) && $oldData['image_couverture']) {
            $newCoverPath = $this->copyFile($oldData['image_couverture'], 'establishments', 'cover');
            if ($newCoverPath) {
                $establishment->setImageCouverture($newCoverPath);
            }
        }

        // OG Image
        if (isset($oldData['ogImage']) && $oldData['ogImage']) {
            $newOgPath = $this->copyFile($oldData['ogImage'], 'establishments', 'og');
            if ($newOgPath) {
                $establishment->setOgImage($newOgPath);
            }
        } elseif (isset($oldData['og_image']) && $oldData['og_image']) {
            $newOgPath = $this->copyFile($oldData['og_image'], 'establishments', 'og');
            if ($newOgPath) {
                $establishment->setOgImage($newOgPath);
            }
        }
    }

    /**
     * Migre les fichiers d'une filière
     */
    private function migrateFiliereFiles(Filiere $filiere, array $oldData): void
    {
        // Image de couverture
        if (isset($oldData['imageCouverture']) && $oldData['imageCouverture']) {
            $newCoverPath = $this->copyFile($oldData['imageCouverture'], 'filieres', 'cover');
            if ($newCoverPath) {
                $filiere->setImageCouverture($newCoverPath);
            }
        } elseif (isset($oldData['image_couverture']) && $oldData['image_couverture']) {
            $newCoverPath = $this->copyFile($oldData['image_couverture'], 'filieres', 'cover');
            if ($newCoverPath) {
                $filiere->setImageCouverture($newCoverPath);
            }
        }

        // OG Image
        if (isset($oldData['ogImage']) && $oldData['ogImage']) {
            $newOgPath = $this->copyFile($oldData['ogImage'], 'filieres', 'og');
            if ($newOgPath) {
                $filiere->setOgImage($newOgPath);
            }
        } elseif (isset($oldData['og_image']) && $oldData['og_image']) {
            $newOgPath = $this->copyFile($oldData['og_image'], 'filieres', 'og');
            if ($newOgPath) {
                $filiere->setOgImage($newOgPath);
            }
        }
    }

    /**
     * Copie un fichier depuis l'ancien système vers le nouveau
     * 
     * @param string $oldPath Chemin ancien (peut être relatif ou absolu)
     * @param string $entityType Type d'entité (establishments, filieres, universites)
     * @param string $fileType Type de fichier (logo, cover, og, documents, photos)
     * @return string|null Nouveau chemin relatif ou null si erreur
     */
    private function copyFile(string $oldPath, string $entityType, string $fileType): ?string
    {
        try {
            // Nettoyer le chemin
            $oldPath = trim($oldPath);
            
            // Si c'est déjà une URL absolue, la retourner telle quelle
            if (strpos($oldPath, 'http://') === 0 || strpos($oldPath, 'https://') === 0) {
                return $oldPath;
            }

            // Déterminer le chemin source
            $sourcePath = $oldPath;
            if (strpos($oldPath, '/') !== 0 && strpos($oldPath, $this->oldUploadsPath) !== 0) {
                // Chemin relatif, essayer différents emplacements possibles
                $possiblePaths = [
                    $this->oldUploadsPath . '/' . ltrim($oldPath, '/'),
                    $this->oldUploadsPath . '/' . $entityType . '/' . basename($oldPath),
                    $this->newUploadsPath . '/' . ltrim($oldPath, '/'),
                ];

                foreach ($possiblePaths as $possiblePath) {
                    if ($this->filesystem->exists($possiblePath)) {
                        $sourcePath = $possiblePath;
                        break;
                    }
                }
            }

            // Vérifier que le fichier source existe
            if (!$this->filesystem->exists($sourcePath)) {
                $this->logger->warning('Fichier source non trouvé lors de la migration', [
                    'oldPath' => $oldPath,
                    'sourcePath' => $sourcePath,
                    'entityType' => $entityType,
                    'fileType' => $fileType
                ]);
                return null;
            }

            // Créer le répertoire de destination
            $destinationDir = $this->newUploadsPath . '/' . $entityType . '/' . $fileType;
            $this->filesystem->mkdir($destinationDir, 0755, true);

            // Générer un nom de fichier unique
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $filename = uniqid('migrated_', true) . '.' . $extension;
            $destinationPath = $destinationDir . '/' . $filename;

            // Copier le fichier
            $this->filesystem->copy($sourcePath, $destinationPath, true);

            // Retourner le chemin relatif
            $relativePath = '/uploads/' . $entityType . '/' . $fileType . '/' . $filename;

            $this->logger->info('Fichier migré avec succès', [
                'oldPath' => $oldPath,
                'newPath' => $relativePath,
                'entityType' => $entityType,
                'fileType' => $fileType
            ]);

            return $relativePath;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la copie du fichier', [
                'oldPath' => $oldPath,
                'entityType' => $entityType,
                'fileType' => $fileType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifie si un slug existe déjà
     */
    private function slugExists(string $slug, string $entityType): bool
    {
        if ($entityType === 'establishment') {
            $existing = $this->em->getRepository(Establishment::class)->findOneBy(['slug' => $slug]);
        } elseif ($entityType === 'filiere') {
            $existing = $this->em->getRepository(Filiere::class)->findOneBy(['slug' => $slug]);
        } else {
            return false;
        }

        return $existing !== null;
    }
}
