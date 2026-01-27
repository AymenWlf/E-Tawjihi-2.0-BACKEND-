<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HubSpotService
{
    private const BASE_URL = 'https://api.hubapi.com';
    private string $apiKey;
    private bool $useBearerToken;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger
    ) {
        $this->apiKey = $_ENV['HUBSPOT_API_KEY'] ?? '';
        $this->useBearerToken = $this->detectBearerToken($this->apiKey);
    }

    /**
     * Détecte si la clé est un Private App Token (Bearer) ou une API Key
     */
    private function detectBearerToken(string $apiKey): bool
    {
        if (empty($apiKey)) {
            return false;
        }
        // Private App Token commence par "pat-" ou est > 50 caractères
        return strpos($apiKey, 'pat-') === 0 || strlen($apiKey) > 50;
    }

    /**
     * Vérifie si HubSpot est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Ajoute l'authentification aux options de requête
     */
    private function addAuth(array $options = []): array
    {
        if ($this->useBearerToken) {
            // Private App Token - Bearer Token
            $options['headers'] = array_merge($options['headers'] ?? [], [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]);
        } else {
            // API Key - hapikey dans query
            $options['query'] = array_merge($options['query'] ?? [], [
                'hapikey' => $this->apiKey,
            ]);
        }
        return $options;
    }

    /**
     * Recherche un contact par téléphone
     */
    public function findContactByPhone(string $phone): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $cleanedPhone = $this->cleanPhoneNumber($phone);
            
            $options = $this->addAuth([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'filterGroups' => [
                        [
                            'filters' => [
                                [
                                    'propertyName' => 'phone',
                                    'operator' => 'EQ',
                                    'value' => $cleanedPhone,
                                ],
                            ],
                        ],
                    ],
                    'properties' => [
                        'firstname',
                        'lastname',
                        'phone',
                        'est_tuteut',
                        'statut_de_traitement',
                        'niveau_detude',
                        'filiere',
                        'createdate',
                        'derniere_date_de_generation',
                        'source_du_lead',
                        'source_du_lead_2',
                        'source_du_lead_3',
                        'hubspot_owner_id',
                    ],
                ],
            ]);

            $response = $this->client->request('POST', self::BASE_URL . '/crm/v3/objects/contacts/search', $options);
            $data = $response->toArray();

            if (!empty($data['results'])) {
                return $data['results'][0];
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche contact HubSpot: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer un nouveau contact
     */
    public function createContact(array $contactData, ?string $ownerId = null): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $properties = $this->mapFormDataToHubSpotProperties($contactData);
            
            if ($ownerId) {
                $properties['hubspot_owner_id'] = $ownerId;
            }

            // Toujours "Nouveau" pour les nouveaux contacts
            $properties['statut_de_traitement'] = 'Nouveau';
            $properties['derniere_date_de_generation'] = $this->getMoroccoDateTime();

            $options = $this->addAuth([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'properties' => $properties,
                ],
            ]);

            $response = $this->client->request('POST', self::BASE_URL . '/crm/v3/objects/contacts', $options);
            $data = $response->toArray();

            $this->logger->info('Contact HubSpot créé - ID: ' . ($data['id'] ?? 'unknown'));
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur création contact HubSpot: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour un contact existant
     */
    public function updateContact(string $contactId, array $updateData): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $properties = $this->mapFormDataToHubSpotProperties($updateData);
            
            // Gestion des sources en cascade
            if (!empty($updateData['source'])) {
                try {
                    $currentContact = $this->getContactPropertiesForUpdate($contactId);
                    if ($currentContact) {
                        $currentSource = $currentContact['properties']['source_du_lead'] ?? '';
                        $currentSource2 = $currentContact['properties']['source_du_lead_2'] ?? '';
                        $currentSource3 = $currentContact['properties']['source_du_lead_3'] ?? '';

                        $mappedSource = $this->mapSourceToHubSpot($updateData['source']);
                        $mappedSource2 = $this->mapSourceToHubSpotForSource2($updateData['source']);

                        // Logique de remplissage en cascade
                        if (!empty($currentSource) && trim($currentSource) !== '') {
                            if (empty($currentSource2) || trim($currentSource2) === '') {
                                $properties['source_du_lead_2'] = $mappedSource2;
                            } elseif (!empty($currentSource2) && trim($currentSource2) !== '') {
                                if (empty($currentSource3) || trim($currentSource3) === '') {
                                    $properties['source_du_lead_3'] = $mappedSource2;
                                }
                            }
                        } else {
                            $properties['source_du_lead'] = $mappedSource;
                            $properties['source_du_lead_2'] = $mappedSource2;
                        }
                    }
                } catch (\Exception $e) {
                    // Ne pas bloquer la mise à jour si la gestion des sources échoue
                }
            }

            // Toujours mettre à jour la date de génération
            $properties['derniere_date_de_generation'] = $this->getMoroccoDateTime();

            $options = $this->addAuth([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'properties' => $properties,
                ],
            ]);

            $response = $this->client->request('PATCH', self::BASE_URL . '/crm/v3/objects/contacts/' . $contactId, $options);
            $data = $response->toArray();

            $this->logger->info('Contact HubSpot mis à jour - ID: ' . $contactId);
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur mise à jour contact HubSpot: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchroniser un lead (création ou mise à jour automatique)
     */
    public function syncLeadToHubSpot(array $formData, ?string $ownerId = null, $request = null): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $phone = $formData['telephone'] ?? null;
        if (!$phone) {
            return null;
        }

        // Extraire le nom adset depuis formData ou request
        $adsetName = null;
        if (!empty($formData['adset_name'])) {
            $adsetName = $formData['adset_name'];
        } elseif ($request) {
            $type = $request->query->get('type');
            if (empty($type)) {
                $uri = $request->getUri();
                $parsedUrl = parse_url($uri);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    $type = $queryParams['type'] ?? null;
                }
            }
            if (!empty($type)) {
                $adsetName = trim($type);
            }
        }

        if ($adsetName) {
            $formData['adset_name'] = $adsetName;
        }

        // Rechercher le contact existant
        $existingContact = $this->findContactByPhone($phone);

        if ($existingContact) {
            // Mettre à jour le contact existant
            return $this->updateContact($existingContact['id'], $formData);
        } else {
            // Créer un nouveau contact
            return $this->createContact($formData, $ownerId);
        }
    }

    /**
     * Récupérer les propriétés d'un contact pour la mise à jour
     */
    private function getContactPropertiesForUpdate(string $contactId): ?array
    {
        try {
            $options = $this->addAuth([
                'query' => [
                    'properties' => 'source_du_lead,source_du_lead_2,source_du_lead_3,statut_de_traitement',
                ],
            ]);

            $response = $this->client->request('GET', self::BASE_URL . '/crm/v3/objects/contacts/' . $contactId, $options);
            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mapper les données du formulaire vers les propriétés HubSpot
     */
    private function mapFormDataToHubSpotProperties(array $formData): array
    {
        $properties = [];

        // Nom et prénom
        if (!empty($formData['nom_prenom'])) {
            $firstName = $this->extractFirstName($formData['nom_prenom']);
            $lastName = $this->extractLastName($formData['nom_prenom']);
            if ($firstName) {
                $properties['firstname'] = $firstName;
            }
            if ($lastName) {
                $properties['lastname'] = $lastName;
            }
        }

        // Téléphone
        if (!empty($formData['telephone'])) {
            $properties['phone'] = $this->cleanPhoneNumber($formData['telephone']);
        }

        // Email
        if (!empty($formData['email'])) {
            $properties['email'] = $formData['email'];
        }

        // Est tuteur
        if (isset($formData['tuteur_eleve'])) {
            $properties['est_tuteut'] = ($formData['tuteur_eleve'] === 'tuteur');
        }

        // Niveau d'étude
        if (!empty($formData['niveau_etude'])) {
            $mappedNiveau = $this->mapNiveauEtudeToHubSpot($formData['niveau_etude']);
            if ($mappedNiveau) {
                $properties['niveau_detude'] = $mappedNiveau;
            }
        }

        // Filière
        if (!empty($formData['filiere_bac'])) {
            $filiereValue = $this->extractFiliereValue($formData['filiere_bac']);
            if ($filiereValue) {
                $properties['filiere'] = $filiereValue;
            }
        }

        // Type d'école
        if (!empty($formData['type_ecole'])) {
            $properties['type_decole'] = $formData['type_ecole'];
        }

        // Ville
        if (!empty($formData['ville'])) {
            $properties['city'] = is_string($formData['ville']) ? $formData['ville'] : (string)$formData['ville'];
        }

        // Prêt à payer
        if (isset($formData['pret_payer'])) {
            $properties['case_paiement_compris'] = ($formData['pret_payer'] === 'oui');
        }

        // Besoins cochés
        $besoins = $this->extractBesoinsCoches($formData);
        if (!empty($besoins)) {
            $properties['besoins_coches'] = $besoins;
        }

        // Source (pour les nouveaux contacts uniquement)
        if (!empty($formData['source'])) {
            $mappedSource = $this->mapSourceToHubSpot($formData['source']);
            $properties['source_du_lead'] = $mappedSource;
            $mappedSource2 = $this->mapSourceToHubSpotForSource2($formData['source']);
            $properties['source_du_lead_2'] = $mappedSource2;
        }

        // Nom adset
        if (!empty($formData['adset_name'])) {
            $properties['nom_adset'] = trim($formData['adset_name']);
        }

        // Spécialités mission
        if (!empty($formData['specialites_mission'])) {
            $properties['specialites_mission'] = $formData['specialites_mission'];
        }

        return $properties;
    }

    /**
     * Nettoyer le numéro de téléphone
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Supprimer les espaces, tirets, points, parenthèses
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);
        
        // Si commence par +212, remplacer par 0
        if (strpos($phone, '+212') === 0) {
            $phone = '0' . substr($phone, 4);
        }
        
        return $phone;
    }

    /**
     * Extraire le prénom
     */
    private function extractFirstName(string $nomPrenom): string
    {
        $parts = explode(' ', trim($nomPrenom), 2);
        return $parts[0] ?? '';
    }

    /**
     * Extraire le nom
     */
    private function extractLastName(string $nomPrenom): string
    {
        $parts = explode(' ', trim($nomPrenom), 2);
        return $parts[1] ?? '';
    }

    /**
     * Extraire la valeur de la filière
     */
    private function extractFiliereValue(string $filiereBac): ?string
    {
        // Si "mission", retourner "MISSION"
        if (strtolower(trim($filiereBac)) === 'mission') {
            return 'MISSION';
        }

        // Mapping des valeurs complètes
        $filiereMapping = [
            'Sciences Math A / علوم رياضية أ' => 'Sciences Math A',
            'Sciences Math B / علوم رياضية ب' => 'Sciences Math B',
            'Sciences Physique / علوم فيزيائية' => 'Sciences Physique',
            'SVT / علوم الحياة والأرض' => 'SVT',
        ];

        if (isset($filiereMapping[$filiereBac])) {
            return $filiereMapping[$filiereBac];
        }

        // Si contient "/", prendre la partie avant
        if (strpos($filiereBac, ' / ') !== false) {
            $parts = explode(' / ', $filiereBac);
            return trim($parts[0]);
        }

        return trim($filiereBac);
    }

    /**
     * Mapper le niveau d'étude vers HubSpot
     */
    private function mapNiveauEtudeToHubSpot(string $niveauEtude): ?string
    {
        $mapping = [
            '1ère année du bac' => '1ère année Baccalauréat',
            '2ème année du bac' => '2ème année Baccalauréat',
            'BAC+1' => 'BAC+1',
            'BAC+2' => 'BAC+1',
            'BAC+3' => 'BAC+3',
            'BAC+4' => 'BAC+3',
            'Doctorant' => 'Autres',
            'Autre' => 'Autres',
        ];

        $niveauEtudeTrimmed = trim($niveauEtude);
        if (isset($mapping[$niveauEtudeTrimmed])) {
            return $mapping[$niveauEtudeTrimmed];
        }

        // Vérifier si déjà une valeur HubSpot
        $hubspotValues = [
            '1ère année Baccalauréat',
            '2ème année Baccalauréat',
            'BAC+1',
            'BAC+3',
            'Autres',
        ];

        if (in_array($niveauEtudeTrimmed, $hubspotValues)) {
            return $niveauEtudeTrimmed;
        }

        return 'Autres';
    }

    /**
     * Mapper la source vers HubSpot
     */
    private function mapSourceToHubSpot(string $source): string
    {
        $sourceMapping = [
            'Formulaire Maroc' => 'Formulaire web',
            'formulaire-maroc' => 'Formulaire web',
            'google-ads' => 'P',
            'facebook-ads' => 'P',
            'ads' => 'P',
            'instagram' => 'Instagram',
            'whatsapp' => 'Whatsapp',
            'tiktok' => 'Tiktok',
            'partenaire' => 'Partenaire',
            'plateforme' => 'Plateforme/App',
            'app' => 'Plateforme/App',
        ];

        $normalizedSource = strtolower(trim($source));
        if (isset($sourceMapping[$normalizedSource])) {
            return $sourceMapping[$normalizedSource];
        }

        $allowedValues = [
            'Plateforme/App',
            'Formulaire web',
            'P',
            'Instagram',
            'Whatsapp',
            'Tiktok',
            'Partenaire',
        ];

        if (in_array($source, $allowedValues)) {
            return $source;
        }

        return 'Formulaire web';
    }

    /**
     * Mapper la source pour source_du_lead_2
     */
    private function mapSourceToHubSpotForSource2(string $source): string
    {
        $sourceMapping = [
            'Formulaire Maroc' => '(organic) Formulaire web',
            'formulaire-maroc' => '(organic) Formulaire web',
            'google-ads' => '(Ads) Formulaire web',
            'facebook-ads' => '(Ads) Formulaire web',
            'ads' => '(Ads) Formulaire web',
            'instagram' => 'Instagram',
            'whatsapp' => 'Whatsapp',
            'tiktok' => 'Tiktok',
            'partenaire' => 'Partenaire',
            'plateforme' => 'Plateforme/App',
            'app' => 'Plateforme/App',
        ];

        $normalizedSource = strtolower(trim($source));
        if (isset($sourceMapping[$normalizedSource])) {
            return $sourceMapping[$normalizedSource];
        }

        return '(organic) Formulaire web';
    }

    /**
     * Extraire les besoins cochés
     */
    private function extractBesoinsCoches(array $formData): string
    {
        $besoins = [];
        $besoinsMapping = [
            'besoin_orientation' => 'Séances d\'orientation avec un conseiller',
            'besoin_test' => 'Test d\'orientation',
            'besoin_notification' => 'Service de notification d\'ouverture d\'inscription aux écoles',
            'besoin_inscription' => 'Service d\'inscription dans les écoles supérieurs et concours au Maroc',
        ];

        foreach ($besoinsMapping as $key => $label) {
            if (!empty($formData[$key])) {
                $besoins[] = $label;
            }
        }

        return implode(', ', $besoins);
    }

    /**
     * Obtenir la date/heure actuelle au Maroc en UTC
     */
    private function getMoroccoDateTime(): string
    {
        $moroccoTimezone = new \DateTimeZone('Africa/Casablanca');
        $dateTime = new \DateTime('now', $moroccoTimezone);
        $utcTimezone = new \DateTimeZone('UTC');
        $dateTime->setTimezone($utcTimezone);
        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }
}
