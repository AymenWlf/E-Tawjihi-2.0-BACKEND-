<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HubSpotRoundRobinService
{
    private const BASE_URL = 'https://api.hubapi.com';
    private string $apiKey;
    private bool $useBearerToken;
    private array $allowedOwnerIds;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        $this->apiKey = $_ENV['HUBSPOT_API_KEY'] ?? '';
        $this->useBearerToken = $this->detectBearerToken($this->apiKey);
        
        // Récupérer les IDs autorisés depuis l'environnement
        $ownerIdsEnv = $_ENV['HUBSPOT_ROUNDROBIN_OWNER_IDS'] ?? '';
        $this->allowedOwnerIds = !empty($ownerIdsEnv) 
            ? array_map('trim', explode(',', $ownerIdsEnv)) 
            : [];
    }

    /**
     * Détecte si la clé est un Private App Token (Bearer) ou une API Key
     */
    private function detectBearerToken(string $apiKey): bool
    {
        if (empty($apiKey)) {
            return false;
        }
        return strpos($apiKey, 'pat-') === 0 || strlen($apiKey) > 50;
    }

    /**
     * Ajoute l'authentification aux options de requête
     */
    private function addAuth(array $options = []): array
    {
        if ($this->useBearerToken) {
            $options['headers'] = array_merge($options['headers'] ?? [], [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]);
        } else {
            $options['query'] = array_merge($options['query'] ?? [], [
                'hapikey' => $this->apiKey,
            ]);
        }
        return $options;
    }

    /**
     * Récupérer tous les propriétaires actifs depuis HubSpot
     */
    public function getActiveOwners(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $options = $this->addAuth([
                'query' => [
                    'archived' => 'false',
                ],
            ]);

            $response = $this->client->request('GET', self::BASE_URL . '/crm/v3/owners', $options);
            $data = $response->toArray();

            return $data['results'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération owners HubSpot: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir le prochain propriétaire en round robin
     */
    public function getNextOwnerId(): ?string
    {
        // 1. Récupérer tous les propriétaires actifs
        $owners = $this->getActiveOwners();

        if (empty($owners)) {
            $this->logger->warning('Aucun propriétaire HubSpot trouvé');
            return null;
        }

        // 2. Filtrer selon HUBSPOT_ROUNDROBIN_OWNER_IDS si configuré
        if (!empty($this->allowedOwnerIds)) {
            $owners = array_filter($owners, function($owner) {
                return in_array($owner['id'], $this->allowedOwnerIds);
            });
            $owners = array_values($owners); // Réindexer
        }

        if (empty($owners)) {
            $this->logger->warning('Aucun propriétaire HubSpot correspondant aux IDs configurés');
            return null;
        }

        // 3. Récupérer le dernier propriétaire assigné
        $lastOwnerId = $this->getLastAssignedOwnerIdFromFile();

        // 4. Calculer le prochain (round robin)
        $lastIndex = false;
        if ($lastOwnerId) {
            $lastIndex = array_search($lastOwnerId, array_column($owners, 'id'));
        }

        if ($lastIndex === false) {
            // Premier tour ou propriétaire non trouvé
            $nextIndex = 0;
        } else {
            // Passer au suivant
            $nextIndex = ($lastIndex + 1) % count($owners);
        }

        $nextOwnerId = $owners[$nextIndex]['id'];

        // 5. Sauvegarder pour la prochaine fois
        $this->saveLastAssignedOwnerIdToFile($nextOwnerId);

        return $nextOwnerId;
    }

    /**
     * Récupérer le dernier propriétaire assigné depuis le fichier
     */
    private function getLastAssignedOwnerIdFromFile(): ?string
    {
        $filePath = $this->getStateFilePath();

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            return $data['lastOwnerId'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lecture état round robin: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sauvegarder le dernier propriétaire assigné dans le fichier
     */
    private function saveLastAssignedOwnerIdToFile(string $ownerId): void
    {
        $filePath = $this->getStateFilePath();

        try {
            // Créer le dossier var s'il n'existe pas
            $varDir = dirname($filePath);
            if (!is_dir($varDir)) {
                mkdir($varDir, 0755, true);
            }

            $data = [
                'lastOwnerId' => $ownerId,
                'updatedAt' => date('Y-m-d H:i:s'),
            ];

            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->logger->error('Erreur sauvegarde état round robin: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir le chemin du fichier d'état
     */
    private function getStateFilePath(): string
    {
        return $this->projectDir . '/var/hubspot_roundrobin_state.json';
    }
}
