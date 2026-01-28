<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Interroge old.e-tawjihi.ma pour vérifier si des numéros sont clients
 * et récupérer leurs infos (contrat, services, tuteur, etc.).
 *
 * Contract old API: POST /api/check-clients, body {"tel": ["06...", ...]}.
 * Réponse possible:
 * - 1 tel:  {"success": true, "data": {"telephone": "06...", ...}} (objet client unique)
 * - N tel:  {"success": true, "data": [{"telephone": "06...", ...}, ...]} (liste d'objets)
 */
class OldEtawjihiClientService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $oldApiUrl = null,
    ) {
        $this->oldApiUrl = 'https://old.e-tawjihi.ma';
    }

    /**
     * Vérifie si les numéros sont clients sur l'ancien système et retourne leurs infos.
     *
     * @param list<string> $tel Liste de numéros (ex: ["0622073449", "0612345678"])
     * @return array<string, array<string, mixed>|null> Map tel -> client data or null
     */
    public function checkClients(array $tel): array
    {
        $tel = array_values(array_unique(array_map('trim', array_filter($tel, fn ($t) => $t !== ''))));
        if ($tel === []) {
            return [];
        }

        if (empty($this->oldApiUrl)) {
            $this->logger->debug('OldEtawjihiClientService: disabled (missing OLD_ETAWJIHI_API_URL)');
            return array_fill_keys($tel, null);
        }

        $url = $this->oldApiUrl . '/api/check-clients';

        $this->logger->info('OldEtawjihiClientService: vérification clients (check-clients)', [
            'url' => $url,
            'tel_count' => \count($tel),
            'tel' => $tel,
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => ['tel' => $tel],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'E-Tawjihi-Backend-apinew/1.0 (check-clients)',
                ],
                'timeout' => 15,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $this->logger->warning('OldEtawjihiClientService: old API returned non-2xx', [
                    'status' => $status,
                    'body' => $response->getContent(false),
                ]);
                return array_fill_keys($tel, null);
            }

            $body = $response->toArray(false);
            $data = $body['data'] ?? null;
            if (!\is_array($data)) {
                $this->logger->warning('OldEtawjihiClientService: missing or invalid data');
                return array_fill_keys($tel, null);
            }

            $byTel = [];
            $isList = array_is_list($data);
            if ($isList) {
                foreach ($data as $item) {
                    if (\is_array($item) && isset($item['telephone']) && (string) $item['telephone'] !== '') {
                        $key = trim((string) $item['telephone']);
                        $byTel[$key] = $item;
                    }
                }
            } else {
                if (isset($data['telephone'])) {
                    $key = trim((string) $data['telephone']);
                    if ($key !== '') {
                        $byTel[$key] = $data;
                    }
                } else {
                    foreach ($data as $k => $v) {
                        if (\is_string($k) && \is_array($v)) {
                            $byTel[trim($k)] = $v;
                        }
                    }
                }
            }

            $result = [];
            foreach ($tel as $t) {
                $key = trim($t);
                $result[$t] = $byTel[$key] ?? null;
            }

            $foundCount = \count(array_filter($result, fn ($v) => $v !== null));
            $this->logger->info('OldEtawjihiClientService: vérification terminée (old.e-tawjihi.ma)', [
                'url' => $url,
                'requested' => \count($tel),
                'clients_found' => $foundCount,
                'tel_found' => array_keys(array_filter($result, fn ($v) => $v !== null)),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->warning('OldEtawjihiClientService: request failed', [
                'error' => $e->getMessage(),
                'tel_count' => \count($tel),
            ]);
            return array_fill_keys($tel, null);
        }
    }
}
