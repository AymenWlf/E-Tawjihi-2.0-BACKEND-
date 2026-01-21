<?php

namespace App\Service;

use App\Entity\TestSession;
use App\Entity\Secteur;
use App\Repository\SecteurRepository;
use Doctrine\ORM\EntityManagerInterface;

class SecteurRecommendationService
{
    private SecteurRepository $secteurRepository;
    private EntityManagerInterface $entityManager;

    // Mapping standardisé entre personnalités/softSkills et profils RIASEC
    private const PERSONNALITE_TO_RIASEC = [
        // Personnalités -> Profils RIASEC
        'sociable' => ['Social'],
        'extraverti' => ['Social', 'Entreprenant'],
        'analytique' => ['Investigateur', 'Realiste'],
        'methodique' => ['Conventionnel', 'Investigateur'],
        'creatif' => ['Artistique'],
        'intuitif' => ['Artistique', 'Social'],
        'independant' => ['Realiste', 'Investigateur', 'Artistique'],
        'organise' => ['Conventionnel'],
        'leader' => ['Entreprenant'],
        'entrepreneur' => ['Entreprenant'],
        'pratique' => ['Realiste'],
        'scientifique' => ['Investigateur'],
        'artiste' => ['Artistique'],
        'relationnel' => ['Social'],
        'innovant' => ['Investigateur', 'Artistique'],
        'ecologiste' => ['Investigateur', 'Social'],
        'responsable' => ['Conventionnel', 'Social']
    ];

    // Mapping softSkills -> Profils RIASEC
    private const SOFTSKILL_TO_RIASEC = [
        'empathie' => ['Social'],
        'communication' => ['Social', 'Entreprenant'],
        'gestion-stress' => ['Social', 'Conventionnel'],
        'rigueur' => ['Conventionnel', 'Investigateur'],
        'patience' => ['Social', 'Conventionnel'],
        'creativite' => ['Artistique'],
        'adaptabilite' => ['Artistique', 'Entreprenant'],
        'travail-equipe' => ['Social'],
        'autonomie' => ['Realiste', 'Investigateur', 'Artistique'],
        'organisation' => ['Conventionnel'],
        'pedagogie' => ['Social'],
        'analytique' => ['Investigateur'],
        'persuasion' => ['Entreprenant', 'Social'],
        'resilience' => ['Entreprenant', 'Realiste'],
        'leadership' => ['Entreprenant'],
        'perseverance' => ['Investigateur', 'Realiste'],
        'intuition' => ['Artistique'],
        'discretion' => ['Conventionnel'],
        'ecoute' => ['Social'],
        'service-public' => ['Social', 'Conventionnel']
    ];

    // Mapping personnalités -> Traits de personnalité du test
    private const PERSONNALITE_TO_PERSONALITY_TRAIT = [
        'sociable' => ['Sociabilité'],
        'extraverti' => ['Sociabilité'],
        'analytique' => ['Organisation'],
        'methodique' => ['Organisation'],
        'creatif' => ['Ouverture'],
        'intuitif' => ['Ouverture'],
        'independant' => ['Organisation'],
        'organise' => ['Organisation'],
        'leader' => ['Leadership'],
        'entrepreneur' => ['Leadership'],
        'pratique' => ['Organisation'],
        'scientifique' => ['Organisation'],
        'artiste' => ['Ouverture'],
        'relationnel' => ['Sociabilité'],
        'innovant' => ['Ouverture'],
        'ecologiste' => ['Ouverture'],
        'responsable' => ['Organisation', 'Leadership']
    ];

    // Mapping softSkills -> Traits de personnalité du test
    private const SOFTSKILL_TO_PERSONALITY_TRAIT = [
        'empathie' => ['Sociabilité'],
        'communication' => ['Sociabilité'],
        'gestion-stress' => ['Gestion du stress'],
        'rigueur' => ['Organisation'],
        'patience' => ['Sociabilité', 'Gestion du stress'],
        'creativite' => ['Ouverture'],
        'adaptabilite' => ['Ouverture'],
        'travail-equipe' => ['Sociabilité'],
        'autonomie' => ['Organisation'],
        'organisation' => ['Organisation'],
        'pedagogie' => ['Sociabilité'],
        'analytique' => ['Organisation'],
        'persuasion' => ['Sociabilité', 'Leadership'],
        'resilience' => ['Gestion du stress'],
        'leadership' => ['Leadership'],
        'perseverance' => ['Gestion du stress'],
        'intuition' => ['Ouverture'],
        'discretion' => ['Organisation'],
        'ecoute' => ['Sociabilité'],
        'service-public' => ['Sociabilité', 'Organisation']
    ];

    public function __construct(
        SecteurRepository $secteurRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->secteurRepository = $secteurRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Calcule les scores de recommandation pour tous les secteurs basés sur le test d'orientation
     * Retourne aussi un indicateur de complétude des données de test
     */
    public function calculateRecommendationScores(?TestSession $testSession): array
    {
        if (!$testSession || !$testSession->isIsCompleted()) {
            return [];
        }

        $secteurs = $this->secteurRepository->findBy(['isActivate' => true]);
        $scores = [];
        $completeness = $this->calculateTestCompleteness($testSession);

        foreach ($secteurs as $secteur) {
            $result = $this->calculateSecteurScore($secteur, $testSession, $completeness);
            $scores[$secteur->getId()] = $result['score'];
        }

        // Trier par score décroissant
        arsort($scores);

        return $scores;
    }

    /**
     * Calcule le score de recommandation pour un secteur spécifique
     * Retourne aussi un indicateur de complétude
     */
    private function calculateSecteurScore(Secteur $secteur, TestSession $testSession, array $completeness): array
    {
        $currentStep = $testSession->getCurrentStep();
        
        $score = 0;
        $maxPossibleScore = 0;
        $weights = [
            'riasec' => 0.30,
            'personality' => 0.25,
            'aptitude' => 0.20,
            'interests' => 0.15,
            'constraints' => 0.10
        ];

        // Extraire les données depuis currentStep
        $riasecData = $this->extractRiasecData($currentStep);
        $personalityData = $this->extractPersonalityData($currentStep);
        $aptitudeData = $this->extractAptitudeData($currentStep);
        $interestsData = $this->extractInterestsData($currentStep);
        $constraintsData = $currentStep['constraints'] ?? null;

        // 1. Score RIASEC (30% du score total)
        $riasecResult = $this->calculateRiasecScore($secteur, $riasecData, $completeness['riasec']);
        $score += (int)($riasecResult['score'] * $weights['riasec']);
        $maxPossibleScore += $riasecResult['maxScore'] * $weights['riasec'];

        // 2. Score Personnalité (25% du score total)
        $personalityResult = $this->calculatePersonalityScore($secteur, $personalityData, $completeness['personality']);
        $score += (int)($personalityResult['score'] * $weights['personality']);
        $maxPossibleScore += $personalityResult['maxScore'] * $weights['personality'];

        // 3. Score Aptitudes (20% du score total)
        $aptitudeResult = $this->calculateAptitudeScore($secteur, $aptitudeData, $completeness['aptitude']);
        $score += (int)($aptitudeResult['score'] * $weights['aptitude']);
        $maxPossibleScore += $aptitudeResult['maxScore'] * $weights['aptitude'];

        // 4. Score Intérêts (15% du score total)
        $interestsResult = $this->calculateInterestsScore($secteur, $interestsData, $completeness['interests']);
        $score += (int)($interestsResult['score'] * $weights['interests']);
        $maxPossibleScore += $interestsResult['maxScore'] * $weights['interests'];

        // 5. Score Contraintes (10% du score total)
        $constraintsResult = $this->calculateConstraintsScore($secteur, $constraintsData, $completeness['constraints']);
        $score += (int)($constraintsResult['score'] * $weights['constraints']);
        $maxPossibleScore += $constraintsResult['maxScore'] * $weights['constraints'];

        // Normaliser le score en fonction du score maximum possible
        if ($maxPossibleScore > 0) {
            $normalizedScore = ($score / $maxPossibleScore) * 100;
        } else {
            $normalizedScore = 0;
        }

        return [
            'score' => min(100, max(0, (int)$normalizedScore)),
            'completeness' => $completeness
        ];
    }

    /**
     * Calcule le niveau de complétude des données de test
     */
    private function calculateTestCompleteness(TestSession $testSession): array
    {
        $currentStep = $testSession->getCurrentStep();
        
        return [
            'riasec' => !empty($currentStep['riasec']),
            'personality' => !empty($currentStep['personality']),
            'aptitude' => !empty($currentStep['aptitude']),
            'interests' => !empty($currentStep['interests']),
            'constraints' => !empty($currentStep['constraints'])
        ];
    }

    /**
     * Extrait les données RIASEC depuis currentStep de TestSession
     */
    private function extractRiasecData(array $currentStep): ?array
    {
        $riasecData = $currentStep['riasec'] ?? null;
        if ($riasecData && !isset($riasecData['riasec']) && isset($riasecData['scores'])) {
            return ['riasec' => $riasecData];
        }
        return $riasecData;
    }

    /**
     * Extrait les données de personnalité depuis currentStep de TestSession
     */
    private function extractPersonalityData(array $currentStep): ?array
    {
        $personalityData = $currentStep['personality'] ?? null;
        if ($personalityData && !isset($personalityData['personality']) && isset($personalityData['scores'])) {
            return ['personality' => $personalityData];
        }
        return $personalityData;
    }

    /**
     * Extrait les données d'aptitude depuis currentStep de TestSession
     */
    private function extractAptitudeData(array $currentStep): ?array
    {
        $aptitudeData = $currentStep['aptitude'] ?? null;
        if ($aptitudeData && !isset($aptitudeData['aptitude']) && isset($aptitudeData['scores'])) {
            return ['aptitude' => $aptitudeData];
        }
        return $aptitudeData;
    }

    /**
     * Extrait les données d'intérêts depuis currentStep de TestSession
     */
    private function extractInterestsData(array $currentStep): ?array
    {
        $interestsData = $currentStep['interests'] ?? null;
        if ($interestsData && !isset($interestsData['interests']) && isset($interestsData['fieldInterests'])) {
            return ['interests' => $interestsData];
        }
        return $interestsData;
    }

    /**
     * Calcule le score basé sur le test RIASEC en utilisant les personnalités et softSkills réels du secteur
     * Utilise une normalisation relative (percentile) plutôt qu'absolue
     */
    private function calculateRiasecScore(Secteur $secteur, ?array $riasecData, bool $hasData): array
    {
        if (!$hasData || !$riasecData || !isset($riasecData['riasec']['scores'])) {
            return ['score' => 0, 'maxScore' => 100]; // Score minimal si pas de données
        }

        $scores = $riasecData['riasec']['scores'] ?? [];
        $dominantProfiles = $riasecData['riasec']['dominantProfile'] ?? [];

        // Trouver les profils RIASEC correspondant au secteur en utilisant personnalites et softSkills
        $secteurRiasecProfiles = $this->getSecteurRiasecProfilesFromData($secteur);

        if (empty($secteurRiasecProfiles)) {
            // Si aucun profil trouvé, utiliser un score minimal basé sur la moyenne générale
            $avgScore = array_sum($scores) / count($scores);
            $maxRealScore = max($scores);
            // Normalisation relative : si max réel est 5, alors 5 = 100%
            $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
            return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
        }

        // Calculer la moyenne des scores des profils correspondants
        $totalScore = 0;
        $count = 0;
        $matchedScores = [];

        foreach ($secteurRiasecProfiles as $profile) {
            $profileKey = $this->normalizeRiasecKey($profile);
            if (isset($scores[$profileKey])) {
                $totalScore += $scores[$profileKey];
                $matchedScores[] = $scores[$profileKey];
                $count++;
            }
        }

        if ($count === 0) {
            $avgScore = array_sum($scores) / count($scores);
            $maxRealScore = max($scores);
            $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
            return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
        }

        $averageScore = $totalScore / $count;

        // Normalisation relative avec différenciation basée sur la variance
        $maxRealScore = max($scores);
        $minRealScore = min($scores);
        $rangeRealScore = $maxRealScore - $minRealScore;
        $avgAllScores = array_sum($scores) / count($scores);

        // Si tous les scores sont identiques, utiliser une différenciation basée sur le nombre de correspondances
        if ($rangeRealScore == 0 || $rangeRealScore < 1) {
            // Tous les scores sont identiques, différencier par le nombre de correspondances
            // Score de base : 40% + (nombre de correspondances * 10%)
            $normalizedScore = 40 + min(30, $count * 10);
        } else {
            // Normalisation relative avec prise en compte de la position par rapport à la moyenne
            // Score normalisé = position relative dans la plage [min, max]
            $normalizedScore = (($averageScore - $minRealScore) / $rangeRealScore) * 100;
            
            // Bonus si au-dessus de la moyenne générale
            if ($averageScore > $avgAllScores) {
                $normalizedScore += 10; // Bonus de 10% si au-dessus de la moyenne
            }
        }

        // Bonus si le profil dominant correspond (augmenté pour mieux différencier)
        $bonus = 0;
        $dominantMatches = 0;
        foreach ($dominantProfiles as $dominant) {
            $normalizedDominant = $this->normalizeRiasecKey($dominant);
            if (in_array($normalizedDominant, array_map([$this, 'normalizeRiasecKey'], $secteurRiasecProfiles))) {
                $dominantMatches++;
            }
        }
        
        // Bonus progressif selon le nombre de profils dominants correspondants
        if ($dominantMatches > 0) {
            $bonus = 20 + ($dominantMatches * 10); // 20% pour 1, 30% pour 2, etc.
        }

        // Bonus supplémentaire si plusieurs profils correspondent (mais pas trop pour éviter l'uniformisation)
        if ($count > 1) {
            $bonus += min(5, ($count - 1) * 2); // +2% par profil supplémentaire, max +5%
        }

        $finalScore = min(100, $normalizedScore + $bonus);
        $maxPossibleScore = 100; // Max toujours 100%

        return [
            'score' => (int)$finalScore,
            'maxScore' => $maxPossibleScore
        ];
    }

    /**
     * Calcule le score basé sur la personnalité en utilisant les données réelles du secteur
     * Utilise une normalisation relative (percentile) plutôt qu'absolue
     */
    private function calculatePersonalityScore(Secteur $secteur, ?array $personalityData, bool $hasData): array
    {
        if (!$hasData || !$personalityData || !isset($personalityData['personality']['scores'])) {
            return ['score' => 0, 'maxScore' => 100];
        }

        $scores = $personalityData['personality']['scores'] ?? [];
        $dominantTraits = $personalityData['personality']['dominantTraits'] ?? [];

        // Trouver les traits correspondant au secteur en utilisant personnalites et softSkills
        $secteurPersonalityTraits = $this->getSecteurPersonalityTraitsFromData($secteur);

        if (empty($secteurPersonalityTraits)) {
            // Score minimal basé sur la moyenne générale avec normalisation relative
            $avgScore = array_sum($scores) / count($scores);
            $maxRealScore = max($scores);
            $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
            return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
        }

        // Calculer la correspondance
        $totalScore = 0;
        $count = 0;
        $matchedScores = [];

        foreach ($secteurPersonalityTraits as $trait) {
            $traitKey = $this->normalizeTraitKey($trait);
            if (isset($scores[$traitKey])) {
                $totalScore += $scores[$traitKey];
                $matchedScores[] = $scores[$traitKey];
                $count++;
            }
        }

        if ($count === 0) {
            $avgScore = array_sum($scores) / count($scores);
            $maxRealScore = max($scores);
            $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
            return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
        }

        $averageScore = $totalScore / $count;

        // Normalisation relative avec différenciation basée sur la variance
        $maxRealScore = max($scores);
        $minRealScore = min($scores);
        $rangeRealScore = $maxRealScore - $minRealScore;
        $avgAllScores = array_sum($scores) / count($scores);

        // Si tous les scores sont identiques, utiliser une différenciation basée sur le nombre de correspondances
        if ($rangeRealScore == 0 || $rangeRealScore < 1) {
            // Tous les scores sont identiques, différencier par le nombre de correspondances
            $normalizedScore = 40 + min(30, $count * 10);
        } else {
            // Normalisation relative avec prise en compte de la position par rapport à la moyenne
            $normalizedScore = (($averageScore - $minRealScore) / $rangeRealScore) * 100;
            
            // Bonus si au-dessus de la moyenne générale
            if ($averageScore > $avgAllScores) {
                $normalizedScore += 10; // Bonus de 10% si au-dessus de la moyenne
            }
        }

        // Bonus si trait dominant correspond (augmenté pour mieux différencier)
        $bonus = 0;
        $dominantMatches = 0;
        foreach ($dominantTraits as $dominant) {
            $normalizedDominant = $this->normalizeTraitKey($dominant);
            if (in_array($normalizedDominant, array_map([$this, 'normalizeTraitKey'], $secteurPersonalityTraits))) {
                $dominantMatches++;
            }
        }
        
        // Bonus progressif selon le nombre de traits dominants correspondants
        if ($dominantMatches > 0) {
            $bonus = 15 + ($dominantMatches * 8); // 15% pour 1, 23% pour 2, etc.
        }

        // Bonus supplémentaire si plusieurs traits correspondent (mais pas trop)
        if ($count > 1) {
            $bonus += min(5, ($count - 1) * 2); // +2% par trait supplémentaire, max +5%
        }

        $finalScore = min(100, $normalizedScore + $bonus);
        $maxPossibleScore = 100; // Max toujours 100%

        return [
            'score' => (int)$finalScore,
            'maxScore' => $maxPossibleScore
        ];
    }

    /**
     * Calcule le score basé sur les aptitudes
     * Utilise une normalisation relative et cappe correctement les scores > 50
     */
    private function calculateAptitudeScore(Secteur $secteur, ?array $aptitudeData, bool $hasData): array
    {
        if (!$hasData || !$aptitudeData || !isset($aptitudeData['aptitude']['scores'])) {
            return ['score' => 0, 'maxScore' => 100];
        }

        $scores = $aptitudeData['aptitude']['scores'] ?? [];
        $overallScore = $aptitudeData['aptitude']['overallScore'] ?? 50;

        // Déterminer si le secteur nécessite des aptitudes techniques basé sur softSkills
        $softSkills = $secteur->getSoftSkills() ?? [];
        $isTechnical = $this->isTechnicalSecteur($softSkills, $secteur->getPersonnalites() ?? []);

        // Normalisation relative : utiliser le score max réel plutôt que 50
        // Si le score peut dépasser 50, on utilise une fonction logarithmique pour éviter > 100%
        $maxPossibleScore = 50; // Score théorique max
        $actualMaxScore = max(array_merge($scores, [$overallScore]));
        
        // Si le score réel dépasse 50, utiliser une normalisation logarithmique
        if ($actualMaxScore > 50) {
            // Utiliser une fonction logarithmique pour normaliser : log(x+1) / log(max+1) * 100
            // Cela évite les scores > 100% tout en préservant la différenciation
            $baseScore = (log($overallScore + 1) / log($actualMaxScore + 1)) * 100;
        } else {
            // Normalisation standard si score <= 50
            $baseScore = ($overallScore / $maxPossibleScore) * 100;
        }

        // Capper à 100% pour éviter les dépassements
        $baseScore = min(100, $baseScore);

        // Ajustement pour secteurs techniques
        if ($isTechnical) {
            // Utiliser une échelle relative pour les secteurs techniques
            $technicalThreshold = $actualMaxScore * 0.6; // 60% du max réel
            $technicalExcellent = $actualMaxScore * 0.8; // 80% du max réel
            
            if ($overallScore < $technicalThreshold) {
                $baseScore *= 0.7; // Pénalité si aptitudes faibles
            } elseif ($overallScore >= $technicalExcellent) {
                $baseScore = min(100, $baseScore * 1.15); // Bonus si aptitudes excellentes
            }
        }

        return [
            'score' => (int)$baseScore,
            'maxScore' => 100
        ];
    }

    /**
     * Calcule le score basé sur les intérêts académiques
     * Utilise les rangs plutôt que les scores absolus pour mieux différencier
     */
    private function calculateInterestsScore(Secteur $secteur, ?array $interestsData, bool $hasData): array
    {
        if (!$hasData || !$interestsData || !isset($interestsData['interests']['fieldInterests'])) {
            return ['score' => 0, 'maxScore' => 100];
        }

        $fieldInterests = $interestsData['interests']['fieldInterests'] ?? [];
        $topInterests = $interestsData['interests']['topInterests'] ?? [];

        // Mapper le secteur vers des domaines d'intérêt basé sur le titre et la description
        $secteurFields = $this->getSecteurFieldsFromData($secteur);

        if (empty($secteurFields)) {
            // Score minimal basé sur la moyenne générale avec normalisation relative
            $avgScore = array_sum($fieldInterests) / count($fieldInterests);
            $maxRealScore = max($fieldInterests);
            $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
            return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
        }

        // Si tous les intérêts ont le même score, utiliser le rang plutôt que le score absolu
        $uniqueScores = array_unique($fieldInterests);
        $useRanking = count($uniqueScores) <= 2; // Si 2 scores uniques ou moins, utiliser le rang

        if ($useRanking) {
            // Trier les intérêts par score décroissant pour obtenir les rangs
            arsort($fieldInterests);
            $rankedInterests = array_keys($fieldInterests);
            
            // Calculer le score basé sur le rang (meilleur rang = meilleur score)
            $totalRankScore = 0;
            $count = 0;
            
            foreach ($secteurFields as $field) {
                foreach ($rankedInterests as $rank => $interestKey) {
                    if ($this->stringsMatch($field, $interestKey)) {
                        // Rang 0 = 100%, rang 1 = 90%, rang 2 = 80%, etc.
                        $rankScore = max(0, 100 - ($rank * 10));
                        $totalRankScore += $rankScore;
                        $count++;
                        break;
                    }
                }
            }
            
            if ($count === 0) {
                return ['score' => 30, 'maxScore' => 100]; // Score minimal
            }
            
            $averageRankScore = $totalRankScore / $count;
            $normalizedScore = $averageRankScore;
        } else {
            // Utiliser les scores absolus avec normalisation relative
            $totalScore = 0;
            $count = 0;
            $matchedScores = [];

            foreach ($secteurFields as $field) {
                foreach ($fieldInterests as $interestKey => $interestValue) {
                    if ($this->stringsMatch($field, $interestKey)) {
                        $totalScore += $interestValue;
                        $matchedScores[] = $interestValue;
                        $count++;
                        break;
                    }
                }
            }

            if ($count === 0) {
                $avgScore = array_sum($fieldInterests) / count($fieldInterests);
                $maxRealScore = max($fieldInterests);
                $normalizedAvg = $maxRealScore > 0 ? ($avgScore / $maxRealScore) * 100 : 50;
                return ['score' => (int)($normalizedAvg * 0.3), 'maxScore' => 100];
            }

            $averageScore = $totalScore / $count;
            $maxRealScore = max($fieldInterests);
            $minRealScore = min($fieldInterests);
            $rangeRealScore = $maxRealScore - $minRealScore;

            // Normalisation relative
            if ($rangeRealScore == 0) {
                $normalizedScore = 50; // Neutre si tous identiques
            } else {
                $normalizedScore = ($averageScore / $maxRealScore) * 100;
            }
        }

        // Bonus si dans les top intérêts (basé sur le rang dans topInterests)
        $bonus = 0;
        $topInterestMatches = [];
        foreach ($topInterests as $rank => $topInterest) {
            foreach ($secteurFields as $field) {
                if ($this->stringsMatch($field, $topInterest)) {
                    $topInterestMatches[] = $rank; // Stocker le rang (0 = 1er, 1 = 2ème, etc.)
                    break;
                }
            }
        }
        
        // Bonus progressif selon le rang dans les top intérêts
        if (!empty($topInterestMatches)) {
            // Meilleur rang (plus petit) = meilleur bonus
            $bestRank = min($topInterestMatches);
            // Rang 0 (1er) = 40%, rang 1 (2ème) = 30%, rang 2 (3ème) = 20%, etc.
            $bonus = max(10, 40 - ($bestRank * 10));
            
            // Bonus supplémentaire si plusieurs top intérêts correspondent
            if (count($topInterestMatches) > 1) {
                $bonus += min(10, (count($topInterestMatches) - 1) * 3); // +3% par match supplémentaire
            }
        }

        // Bonus supplémentaire si plusieurs domaines correspondent (mais modéré)
        if ($count > 1) {
            $bonus += min(10, ($count - 1) * 3); // +3% par domaine supplémentaire, max +10%
        }

        $finalScore = min(100, $normalizedScore + $bonus);

        return [
            'score' => (int)$finalScore,
            'maxScore' => 100
        ];
    }

    /**
     * Calcule le score basé sur les contraintes (salaire, localisation, etc.)
     */
    private function calculateConstraintsScore(Secteur $secteur, ?array $constraintsData, bool $hasData): array
    {
        if (!$hasData || !$constraintsData) {
            return ['score' => 50, 'maxScore' => 100]; // Score neutre si pas de contraintes
        }

        $score = 50; // Score de base
        $maxScore = 100;

        // Vérifier les contraintes de salaire
        $salaireMin = $constraintsData['salaryMin'] ?? null;
        $salaireMax = $constraintsData['salaryMax'] ?? null;
        
        if ($salaireMin !== null) {
            $secteurSalaireMin = $secteur->getSalaireMin() ? (float)$secteur->getSalaireMin() : null;
            if ($secteurSalaireMin && $secteurSalaireMin < $salaireMin) {
                $score -= 20; // Pénalité si salaire minimum du secteur inférieur à l'attente
            } elseif ($secteurSalaireMin && $secteurSalaireMin >= $salaireMin) {
                $score += 10; // Bonus si salaire minimum respecté
            }
        }

        return [
            'score' => min(100, max(0, $score)),
            'maxScore' => $maxScore
        ];
    }

    /**
     * Retourne les profils RIASEC correspondant à un secteur en utilisant personnalites et softSkills
     */
    private function getSecteurRiasecProfilesFromData(Secteur $secteur): array
    {
        $profiles = [];
        
        // Utiliser les personnalités du secteur
        $personnalites = $secteur->getPersonnalites() ?? [];
        foreach ($personnalites as $personnalite) {
            $personnaliteLower = strtolower(trim($personnalite));
            if (isset(self::PERSONNALITE_TO_RIASEC[$personnaliteLower])) {
                $profiles = array_merge($profiles, self::PERSONNALITE_TO_RIASEC[$personnaliteLower]);
            }
        }

        // Utiliser les softSkills du secteur
        $softSkills = $secteur->getSoftSkills() ?? [];
        foreach ($softSkills as $softSkill) {
            $softSkillLower = strtolower(trim($softSkill));
            if (isset(self::SOFTSKILL_TO_RIASEC[$softSkillLower])) {
                $profiles = array_merge($profiles, self::SOFTSKILL_TO_RIASEC[$softSkillLower]);
            }
        }

        return array_unique($profiles);
    }

    /**
     * Retourne les traits de personnalité correspondant à un secteur en utilisant personnalites et softSkills
     */
    private function getSecteurPersonalityTraitsFromData(Secteur $secteur): array
    {
        $traits = [];
        
        // Utiliser les personnalités du secteur
        $personnalites = $secteur->getPersonnalites() ?? [];
        foreach ($personnalites as $personnalite) {
            $personnaliteLower = strtolower(trim($personnalite));
            if (isset(self::PERSONNALITE_TO_PERSONALITY_TRAIT[$personnaliteLower])) {
                $traits = array_merge($traits, self::PERSONNALITE_TO_PERSONALITY_TRAIT[$personnaliteLower]);
            }
        }

        // Utiliser les softSkills du secteur
        $softSkills = $secteur->getSoftSkills() ?? [];
        foreach ($softSkills as $softSkill) {
            $softSkillLower = strtolower(trim($softSkill));
            if (isset(self::SOFTSKILL_TO_PERSONALITY_TRAIT[$softSkillLower])) {
                $traits = array_merge($traits, self::SOFTSKILL_TO_PERSONALITY_TRAIT[$softSkillLower]);
            }
        }

        return array_unique($traits);
    }

    /**
     * Retourne les domaines d'intérêt correspondant à un secteur basé sur le titre et la description
     */
    private function getSecteurFieldsFromData(Secteur $secteur): array
    {
        $fields = [];
        $titre = strtolower($secteur->getTitre() ?? '');
        $description = strtolower($secteur->getDescription() ?? '');

        // Mapping basé sur les mots-clés dans le titre et la description
        $keywordsMapping = [
            'santé' => ['Médecine', 'Santé', 'Biologie'],
            'sante' => ['Médecine', 'Santé', 'Biologie'],
            'technologie' => ['Informatique', 'Technologie', 'Programmation'],
            'tech' => ['Informatique', 'Technologie', 'Programmation'],
            'éducation' => ['Enseignement', 'Éducation', 'Pédagogie'],
            'education' => ['Enseignement', 'Éducation', 'Pédagogie'],
            'finance' => ['Économie', 'Finance', 'Commerce'],
            'juridique' => ['Droit', 'Juridique'],
            'droit' => ['Droit', 'Juridique'],
            'arts' => ['Arts', 'Design', 'Créativité'],
            'créatif' => ['Arts', 'Design', 'Créativité'],
            'creatif' => ['Arts', 'Design', 'Créativité'],
            'communication' => ['Communication', 'Médias'],
            'médias' => ['Communication', 'Médias'],
            'medias' => ['Communication', 'Médias'],
            'commerce' => ['Commerce', 'Économie'],
            'marketing' => ['Marketing', 'Commerce'],
            'rh' => ['Ressources Humaines', 'Management'],
            'ressources humaines' => ['Ressources Humaines', 'Management'],
            'transport' => ['Logistique', 'Transport'],
            'logistique' => ['Logistique', 'Transport'],
            'hôtellerie' => ['Tourisme', 'Hôtellerie'],
            'hotellerie' => ['Tourisme', 'Hôtellerie'],
            'restauration' => ['Tourisme', 'Hôtellerie'],
            'public' => ['Administration', 'Services Publics'],
            'entrepreneuriat' => ['Entrepreneuriat', 'Management'],
            'recherche' => ['Sciences', 'Recherche', 'Mathématiques', 'Physique', 'Chimie'],
            'sciences' => ['Sciences', 'Mathématiques', 'Physique', 'Chimie'],
            'ingénierie' => ['Ingénierie', 'Mathématiques', 'Physique'],
            'ingenierie' => ['Ingénierie', 'Mathématiques', 'Physique']
        ];

        foreach ($keywordsMapping as $keyword => $mappedFields) {
            if (strpos($titre, $keyword) !== false || strpos($description, $keyword) !== false) {
                $fields = array_merge($fields, $mappedFields);
            }
        }

        return array_unique($fields);
    }

    /**
     * Détermine si un secteur est technique basé sur ses softSkills et personnalités
     */
    private function isTechnicalSecteur(array $softSkills, array $personnalites): bool
    {
        $technicalIndicators = ['rigueur', 'analytique', 'technique', 'pratique', 'methodique', 'scientifique'];
        
        $allAttributes = array_merge(
            array_map('strtolower', $softSkills),
            array_map('strtolower', $personnalites)
        );

        foreach ($technicalIndicators as $indicator) {
            if (in_array($indicator, $allAttributes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare deux chaînes de manière flexible (insensible à la casse, avec variations)
     */
    private function stringsMatch(string $str1, string $str2): bool
    {
        $str1Lower = strtolower(trim($str1));
        $str2Lower = strtolower(trim($str2));
        
        // Correspondance exacte
        if ($str1Lower === $str2Lower) {
            return true;
        }
        
        // Correspondance partielle
        if (strpos($str1Lower, $str2Lower) !== false || strpos($str2Lower, $str1Lower) !== false) {
            return true;
        }
        
        // Variations communes
        $variations = [
            'informatique' => ['technologie', 'tech', 'programmation', 'it'],
            'médecine' => ['santé', 'sante', 'medical'],
            'sciences' => ['scientifique', 'science'],
            'commerce' => ['business', 'economie', 'économie'],
            'arts' => ['art', 'design', 'créatif', 'creatif'],
            'enseignement' => ['éducation', 'education', 'pedagogie']
        ];
        
        foreach ($variations as $key => $values) {
            if (($str1Lower === $key && in_array($str2Lower, $values)) ||
                ($str2Lower === $key && in_array($str1Lower, $values))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Normalise une clé RIASEC
     */
    private function normalizeRiasecKey(string $key): string
    {
        $normalized = [
            'Réaliste' => 'Realiste',
            'réaliste' => 'Realiste',
            'Investigateur' => 'Investigateur',
            'investigateur' => 'Investigateur',
            'Artistique' => 'Artistique',
            'artistique' => 'Artistique',
            'Social' => 'Social',
            'social' => 'Social',
            'Entreprenant' => 'Entreprenant',
            'entreprenant' => 'Entreprenant',
            'Conventionnel' => 'Conventionnel',
            'conventionnel' => 'Conventionnel'
        ];

        return $normalized[$key] ?? $key;
    }

    /**
     * Normalise une clé de trait de personnalité
     */
    private function normalizeTraitKey(string $key): string
    {
        $normalized = [
            'Ouverture' => 'Ouverture',
            'ouverture' => 'Ouverture',
            'Organisation' => 'Organisation',
            'organisation' => 'Organisation',
            'Sociabilité' => 'Sociabilité',
            'sociabilité' => 'Sociabilité',
            'sociabilite' => 'Sociabilité',
            'Gestion du stress' => 'Gestion du stress',
            'gestion du stress' => 'Gestion du stress',
            'gestion-stress' => 'Gestion du stress',
            'Leadership' => 'Leadership',
            'leadership' => 'Leadership'
        ];

        return $normalized[$key] ?? $key;
    }
}
