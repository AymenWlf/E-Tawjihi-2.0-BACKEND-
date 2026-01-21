<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Establishment;
use App\Entity\Filiere;
use App\Entity\Secteur;
use App\Repository\TestSessionRepository;
use App\Repository\SecteurRepository;
use App\Repository\FiliereRepository;
use Psr\Log\LoggerInterface;

class RecommendationService
{
    private const RECOMMENDATION_THRESHOLD = 60; // Score minimum pour être recommandé (0-100)
    private const THRESHOLD_HIGH = 80; // Score élevé

    public function __construct(
        private TestSessionRepository $testSessionRepository,
        private SecteurRepository $secteurRepository,
        private FiliereRepository $filiereRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Calcule le score de recommandation pour un établissement
     * 
     * @param Establishment $establishment
     * @param User $user
     * @return array Structure: ['score' => int, 'factors' => array, 'isRecommended' => bool, 'message' => string]
     */
    public function calculateEstablishmentScore(Establishment $establishment, User $user): array
    {
        // Récupérer la session de test d'orientation complétée
        $testSession = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
        
        if (!$testSession || !$testSession->isIsCompleted()) {
            return [
                'score' => 0,
                'factors' => [],
                'isRecommended' => false,
                'message' => 'Test d\'orientation non complété'
            ];
        }

        $currentStep = $testSession->getCurrentStep();
        $factors = [];
        $totalScore = 0;
        $totalWeight = 0;

        // 1. Test de carrière (Secteurs) - 40% de poids
        if (!empty($currentStep['career']) && !empty($establishment->getSecteursIds())) {
            $careerData = $currentStep['career'];
            $sectorScores = $careerData['sectorScores'] ?? [];
            
            if (!empty($sectorScores)) {
                $establishmentSectors = $establishment->getSecteursIds();
                $sectorMatchScore = 0;
                $matchedCount = 0;

                // Récupérer les secteurs pour obtenir leurs titres
                foreach ($establishmentSectors as $sectorId) {
                    $secteur = $this->secteurRepository->find($sectorId);
                    if ($secteur && isset($sectorScores[$secteur->getTitre()])) {
                        $sectorMatchScore += $sectorScores[$secteur->getTitre()];
                        $matchedCount++;
                    }
                }

                if ($matchedCount > 0) {
                    $avgScore = ($sectorMatchScore / $matchedCount);
                    $weight = 0.40;
                    $contribution = $avgScore * $weight;
                    $totalScore += $contribution;
                    $totalWeight += $weight;

                    $factors['career'] = [
                        'score' => round($avgScore),
                        'weight' => $weight,
                        'contribution' => round($contribution * 100, 2),
                        'details' => [
                            'matchedSectors' => $matchedCount,
                            'totalSectors' => count($establishmentSectors),
                            'averageScore' => round($avgScore, 2)
                        ]
                    ];
                }
            }
        }

        // 2. Intérêts académiques (Domaine/Filières) - 30% de poids
        if (!empty($currentStep['interests']) && !empty($establishment->getFilieresIds())) {
            $interestsData = $currentStep['interests'];
            $categoryScores = $interestsData['categoryScores'] ?? [];
            
            if (!empty($categoryScores)) {
                $establishmentFilieres = $establishment->getFilieresIds();
                $interestMatchScore = 0;
                $matchedCount = 0;

                // Récupérer les filières pour obtenir leurs domaines
                foreach ($establishmentFilieres as $filiereId) {
                    $filiere = $this->filiereRepository->find($filiereId);
                    if ($filiere && $filiere->getDomaine() && isset($categoryScores[$filiere->getDomaine()])) {
                        $categoryData = $categoryScores[$filiere->getDomaine()];
                        $interestValue = is_array($categoryData) ? ($categoryData['interest'] ?? $categoryData['score'] ?? 0) : $categoryData;
                        $interestMatchScore += $interestValue;
                        $matchedCount++;
                    }
                }

                if ($matchedCount > 0) {
                    $avgScore = ($interestMatchScore / $matchedCount);
                    $weight = 0.30;
                    $contribution = $avgScore * $weight;
                    $totalScore += $contribution;
                    $totalWeight += $weight;

                    $factors['interests'] = [
                        'score' => round($avgScore),
                        'weight' => $weight,
                        'contribution' => round($contribution * 100, 2),
                        'details' => [
                            'matchedFilieres' => $matchedCount,
                            'totalFilieres' => count($establishmentFilieres),
                            'averageScore' => round($avgScore, 2)
                        ]
                    ];
                }
            }
        }

        // 3. Contraintes (Budget, Localisation) - 15% de poids
        if (!empty($currentStep['constraints'])) {
            $constraintsData = $currentStep['constraints'];
            $budgetScore = 0;
            
            if (isset($constraintsData['budgetMax']) && $establishment->getFraisScolariteMax() !== null) {
                $budgetMax = (float) $constraintsData['budgetMax'];
                $fraisMax = (float) $establishment->getFraisScolariteMax();
                $fraisMin = (float) ($establishment->getFraisScolariteMin() ?? 0);

                if ($fraisMax <= $budgetMax) {
                    $budgetScore = 100; // Parfait match
                } elseif ($fraisMin <= $budgetMax) {
                    $budgetScore = 50 + (($budgetMax - $fraisMin) / ($fraisMax - $fraisMin)) * 50; // Match partiel
                } else {
                    $budgetScore = max(0, 50 - (($fraisMin - $budgetMax) / $budgetMax) * 50); // Dépassement
                }
            }

            // Localisation (ville)
            $locationScore = 0;
            if (isset($constraintsData['preferredCity']) && !empty($establishment->getVille())) {
                $preferredCity = $constraintsData['preferredCity'];
                if ($establishment->getVille() === $preferredCity || 
                    (is_array($establishment->getVilles()) && in_array($preferredCity, $establishment->getVilles()))) {
                    $locationScore = 100;
                }
            }

            $constraintAvgScore = ($budgetScore + $locationScore) / 2;
            $weight = 0.15;
            $contribution = $constraintAvgScore * $weight;
            $totalScore += $contribution;
            $totalWeight += $weight;

            $factors['constraints'] = [
                'score' => round($constraintAvgScore),
                'weight' => $weight,
                'contribution' => round($contribution * 100, 2),
                'details' => [
                    'budgetScore' => round($budgetScore),
                    'locationScore' => round($locationScore)
                ]
            ];
        }

        // 4. Langues - 10% de poids
        // Note: La méthode getLanguages() n'existe pas encore dans l'entité Establishment
        // Pour le moment, on saute cette partie du calcul
        // TODO: Ajouter un champ languages à l'entité Establishment si nécessaire
        /*
        if (!empty($currentStep['languages']) && method_exists($establishment, 'getLanguages') && !empty($establishment->getLanguages())) {
            $languagesData = $currentStep['languages'];
            $preferredLanguage = $languagesData['preferences']['preferredTeachingLanguage'] ?? null;
            $establishmentLanguages = $establishment->getLanguages() ?? [];

            $languageScore = 0;
            if ($preferredLanguage && is_array($establishmentLanguages) && in_array($preferredLanguage, $establishmentLanguages)) {
                $languageScore = 100;
            } elseif ($preferredLanguage && is_string($establishmentLanguages)) {
                $languageScore = $establishmentLanguages === $preferredLanguage ? 100 : 0;
            }

            if ($languageScore > 0) {
                $weight = 0.10;
                $contribution = $languageScore * $weight;
                $totalScore += $contribution;
                $totalWeight += $weight;

                $factors['languages'] = [
                    'score' => $languageScore,
                    'weight' => $weight,
                    'contribution' => round($contribution * 100, 2),
                    'details' => [
                        'preferredLanguage' => $preferredLanguage,
                        'establishmentLanguages' => $establishmentLanguages
                    ]
                ];
            }
        }
        */

        // 5. RIASEC / Personnalité - 5% de poids
        if (!empty($currentStep['riasec']) && !empty($currentStep['personality'])) {
            // Score basé sur la personnalité (simplifié pour l'instant)
            $personalityScore = 50; // Score par défaut, peut être amélioré
            $weight = 0.05;
            $contribution = $personalityScore * $weight;
            $totalScore += $contribution;
            $totalWeight += $weight;

            $factors['personality'] = [
                'score' => $personalityScore,
                'weight' => $weight,
                'contribution' => round($contribution * 100, 2),
                'details' => []
            ];
        }

        // Normaliser le score final (0-100)
        // Si aucun poids n'a été calculé, essayer de générer un score minimum basé sur les données disponibles
        if ($totalWeight === 0) {
            // Aucun facteur n'a pu être évalué, mais le test est complété
            // Donner un score minimal basé sur la présence de données
            $hasData = !empty($currentStep['career']) || !empty($currentStep['interests']) || 
                      !empty($currentStep['riasec']) || !empty($currentStep['personality']);
            $finalScore = $hasData ? 30 : 0; // Score minimal si on a des données mais pas de correspondances
        } else {
            // Le totalScore est la somme des contributions (score * poids), où score est sur 100
            // Par exemple : career score=100 * 0.4 = 40, interests score=80 * 0.3 = 24, etc.
            // On divise par le poids total pour obtenir une moyenne pondérée sur 100
            // Exemple : totalScore = 40 + 24 + 7.5 + 2.5 = 74, totalWeight = 0.9
            // normalizedScore = 74 / 0.9 = 82.22 (déjà sur échelle 100)
            $finalScore = round($totalScore / $totalWeight);
            
            // Logger pour debug si score anormalement élevé
            if ($finalScore >= 95) {
                $this->logger->warning('Score anormalement élevé détecté', [
                    'establishment_id' => $establishment->getId(),
                    'establishment_name' => $establishment->getNom(),
                    'totalScore' => $totalScore,
                    'totalWeight' => $totalWeight,
                    'finalScore' => $finalScore,
                    'factors' => $factors
                ]);
            }
        }
        $finalScore = max(0, min(100, $finalScore)); // S'assurer que c'est entre 0 et 100

        $isRecommended = $finalScore >= self::RECOMMENDATION_THRESHOLD;

        // Message personnalisé
        $message = $this->generateMessage($finalScore, $factors);

        return [
            'score' => $finalScore,
            'factors' => $factors,
            'isRecommended' => $isRecommended,
            'message' => $message
        ];
    }

    /**
     * Calcule le score de recommandation pour une filière
     * 
     * @param Filiere $filiere
     * @param User $user
     * @return array Structure: ['score' => int, 'factors' => array, 'isRecommended' => bool, 'message' => string]
     */
    public function calculateFiliereScore(Filiere $filiere, User $user): array
    {
        // Récupérer la session de test d'orientation complétée
        $testSession = $this->testSessionRepository->findByUser($user->getId(), 'orientation');
        
        if (!$testSession || !$testSession->isIsCompleted()) {
            return [
                'score' => 0,
                'factors' => [],
                'isRecommended' => false,
                'message' => 'Test d\'orientation non complété'
            ];
        }

        $currentStep = $testSession->getCurrentStep();
        $factors = [];
        $totalScore = 0;
        $totalWeight = 0;

        // 1. Intérêts académiques (Domaine) - 40% de poids
        if (!empty($currentStep['interests']) && !empty($filiere->getDomaine())) {
            $interestsData = $currentStep['interests'];
            $categoryScores = $interestsData['categoryScores'] ?? [];
            $filiereDomain = $filiere->getDomaine();

            if (isset($categoryScores[$filiereDomain])) {
                $categoryData = $categoryScores[$filiereDomain];
                $interestScore = is_array($categoryData) ? ($categoryData['interest'] ?? $categoryData['score'] ?? 0) : $categoryData;
                
                $weight = 0.40;
                $contribution = $interestScore * $weight;
                $totalScore += $contribution;
                $totalWeight += $weight;

                $factors['interests'] = [
                    'score' => round($interestScore),
                    'weight' => $weight,
                    'contribution' => round($contribution * 100, 2),
                    'details' => [
                        'domain' => $filiereDomain,
                        'interestScore' => round($interestScore, 2)
                    ]
                ];
            }
        }

        // 2. Test de carrière (Secteurs) - 30% de poids
        // Note: Les filières n'ont pas encore de relation directe avec les secteurs
        // On peut utiliser le domaine de la filière pour faire une correspondance approximative
        if (!empty($currentStep['career'])) {
            $careerData = $currentStep['career'];
            $sectorScores = $careerData['sectorScores'] ?? [];
            
            if (!empty($sectorScores)) {
                // Pour l'instant, on utilise un score basé sur les secteurs en général
                // Une future amélioration serait d'ajouter une relation filière-secteur
                $avgScore = !empty($sectorScores) ? array_sum($sectorScores) / count($sectorScores) : 0;
                
                if ($avgScore > 0) {
                    $weight = 0.30;
                    $contribution = $avgScore * $weight;
                    $totalScore += $contribution;
                    $totalWeight += $weight;

                    $factors['career'] = [
                        'score' => round($avgScore),
                        'weight' => $weight,
                        'contribution' => round($contribution * 100, 2),
                        'details' => [
                            'averageSectorScore' => round($avgScore, 2),
                            'note' => 'Correspondance basée sur les secteurs en général (relation filière-secteur à implémenter)'
                        ]
                    ];
                }
            }
        }

        // 3. Contraintes (Budget) - 15% de poids
        if (!empty($currentStep['constraints'])) {
            $constraintsData = $currentStep['constraints'];
            $budgetScore = 0;
            
            if (isset($constraintsData['budgetMax']) && $filiere->getFraisScolarite() !== null) {
                $budgetMax = (float) $constraintsData['budgetMax'];
                $frais = (float) $filiere->getFraisScolarite();

                if ($frais <= $budgetMax) {
                    $budgetScore = 100; // Parfait match
                } elseif ($filiere->getFraisInscription() && (float) $filiere->getFraisInscription() <= $budgetMax) {
                    $budgetScore = 70; // Match partiel (frais inscription OK)
                } else {
                    $budgetScore = max(0, 50 - (($frais - $budgetMax) / $budgetMax) * 50); // Dépassement
                }
            }

            if ($budgetScore > 0) {
                $weight = 0.15;
                $contribution = $budgetScore * $weight;
                $totalScore += $contribution;
                $totalWeight += $weight;

                $factors['constraints'] = [
                    'score' => round($budgetScore),
                    'weight' => $weight,
                    'contribution' => round($contribution * 100, 2),
                    'details' => [
                        'budgetScore' => round($budgetScore)
                    ]
                ];
            }
        }

        // 4. Langues - 10% de poids
        if (!empty($currentStep['languages']) && !empty($filiere->getLangueEtudes())) {
            $languagesData = $currentStep['languages'];
            $preferredLanguage = $languagesData['preferences']['preferredTeachingLanguage'] ?? null;
            $filiereLanguage = $filiere->getLangueEtudes();

            $languageScore = 0;
            if ($preferredLanguage && $filiereLanguage === $preferredLanguage) {
                $languageScore = 100;
            }

            if ($languageScore > 0) {
                $weight = 0.10;
                $contribution = $languageScore * $weight;
                $totalScore += $contribution;
                $totalWeight += $weight;

                $factors['languages'] = [
                    'score' => $languageScore,
                    'weight' => $weight,
                    'contribution' => round($contribution * 100, 2),
                    'details' => [
                        'preferredLanguage' => $preferredLanguage,
                        'filiereLanguage' => $filiereLanguage
                    ]
                ];
            }
        }

        // 5. RIASEC / Personnalité - 5% de poids
        if (!empty($currentStep['riasec']) && !empty($currentStep['personality'])) {
            $personalityScore = 50; // Score par défaut
            $weight = 0.05;
            $contribution = $personalityScore * $weight;
            $totalScore += $contribution;
            $totalWeight += $weight;

            $factors['personality'] = [
                'score' => $personalityScore,
                'weight' => $weight,
                'contribution' => round($contribution * 100, 2),
                'details' => []
            ];
        }

        // Normaliser le score final (0-100)
        $finalScore = $totalWeight > 0 ? round(($totalScore / $totalWeight) * 100) : 0;
        $finalScore = max(0, min(100, $finalScore));

        $isRecommended = $finalScore >= self::RECOMMENDATION_THRESHOLD;

        // Message personnalisé
        $message = $this->generateMessage($finalScore, $factors);

        return [
            'score' => $finalScore,
            'factors' => $factors,
            'isRecommended' => $isRecommended,
            'message' => $message
        ];
    }

    /**
     * Génère un message personnalisé basé sur le score et les facteurs
     */
    private function generateMessage(int $score, array $factors): string
    {
        if ($score >= self::THRESHOLD_HIGH) {
            return 'Excellente correspondance avec votre profil !';
        } elseif ($score >= self::RECOMMENDATION_THRESHOLD) {
            return 'Bonne correspondance avec vos intérêts et contraintes.';
        } elseif ($score >= 40) {
            return 'Correspondance modérée. À considérer selon vos priorités.';
        } else {
            return 'Correspondance faible avec votre profil.';
        }
    }
}
