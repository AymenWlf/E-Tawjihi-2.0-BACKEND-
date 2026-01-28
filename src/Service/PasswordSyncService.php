<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Synchronise le mot de passe "mot de passe oublié" vers un autre backend.
 * Appelé après mdp_oublie : envoie tel + mdp pour que l'autre backend mette à jour le même utilisateur.
 */
class PasswordSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $syncBackendUrl = null,
    ) {
        $this->syncBackendUrl = $syncBackendUrl ? rtrim($syncBackendUrl, '/') : null;
    }

    /**
     * Envoie le téléphone et le nouveau mot de passe (en clair) vers l'autre backend.
     * Non bloquant : en cas d'échec HTTP, on log et on ne lève pas d'exception.
     *
     * @param string $phone   Numéro au format local (ex: 0612345678)
     * @param string $plainPassword Mot de passe en clair à appliquer côté autre backend
     * @return bool True si la synchronisation a réussi, false sinon
     */
    public function syncPassword(string $phone, string $plainPassword): bool
    {
        if (empty($this->syncBackendUrl)) {
            $this->logger->debug('PasswordSyncService: sync disabled (missing SYNC_PASSWORD_BACKEND_URL)');
            return false;
        }

        $url = $this->syncBackendUrl . '/api/sync_password';

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'phone' => $phone,
                    'password' => $plainPassword,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('PasswordSyncService: password synced to other backend', [
                    'phone' => $phone,
                    'status' => $statusCode,
                ]);
                return true;
            }

            $this->logger->warning('PasswordSyncService: other backend returned non-2xx', [
                'phone' => $phone,
                'status' => $statusCode,
                'body' => $response->getContent(false),
            ]);
            return false;
        } catch (\Throwable $e) {
            $this->logger->warning('PasswordSyncService: sync failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
