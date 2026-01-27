<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Repository\UserProfileRepository;
use App\Repository\TestSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || !isset($data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Téléphone et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Trouver l'utilisateur par téléphone
        $user = $em->getRepository(User::class)->findOneBy(['phone' => $data['phone']]);
        
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Numéro de téléphone ou mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Générer le token JWT
        $token = $jwtManager->create($user);

        return new JsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'is_setup' => $user->getIsSetup(),
                ],
                'token' => $token
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'is_setup' => $user->getIsSetup(),
                ]
            ]
        ]);
    }

    #[Route('/api/user/profile', name: 'api_user_profile', methods: ['GET'])]
    public function getProfile(
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em,
        UserProfileRepository $profileRepository
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Charger le profil avec la relation ville en utilisant le repository
        $profile = $profileRepository->createQueryBuilder('p')
            ->leftJoin('p.ville', 'v')
            ->addSelect('v')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$profile) {
            error_log('[AuthController::getProfile] Aucun profil trouvé pour l\'utilisateur ID: ' . $user->getId());
            return new JsonResponse([
                'success' => true,
                'data' => null,
                'message' => 'Aucun profil trouvé pour cet utilisateur'
            ]);
        }

        $ville = $profile->getVille();
        
        $profileData = [
                'id' => $profile->getId(),
                'nom' => $profile->getNom(),
                'prenom' => $profile->getPrenom(),
            'email' => $profile->getEmail() ?? $user->getEmail(),
            'telephone' => $user->getPhone(),
                'dateNaissance' => $profile->getDateNaissance() ? $profile->getDateNaissance()->format('Y-m-d') : null,
                'genre' => $profile->getGenre(),
                'ville' => $ville ? [
                    'id' => $ville->getId(),
                    'titre' => $ville->getTitre()
                ] : null,
                'userType' => $profile->getUserType(),
                'niveau' => $profile->getNiveau(),
                'bacType' => $profile->getBacType(),
                'filiere' => $profile->getFiliere(),
                'specialite1' => $profile->getSpecialite1(),
                'specialite2' => $profile->getSpecialite2(),
                'specialite3' => $profile->getSpecialite3(),
                'diplomeEnCours' => $profile->getDiplomeEnCours(),
            'nomEtablissement' => $profile->getNomEtablissement(),
            'typeEcolePrefere' => $profile->getTypeEcolePrefere(),
            'servicesPrefere' => $profile->getServicesPrefere(),
            'tuteur' => $profile->getTuteur(),
            'nomTuteur' => $profile->getNomTuteur(),
            'prenomTuteur' => $profile->getPrenomTuteur(),
            'telTuteur' => $profile->getTelTuteur(),
            'professionTuteur' => $profile->getProfessionTuteur(),
            'adresseTuteur' => $profile->getAdresseTuteur(),
            'consentContact' => $profile->getConsentContact(),
            'planReussiteSteps' => $profile->getPlanReussiteSteps() ?? [],
            'createdAt' => $profile->getCreatedAt() ? $profile->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updatedAt' => $profile->getUpdatedAt() ? $profile->getUpdatedAt()->format('Y-m-d H:i:s') : null
        ];
        
        error_log('[AuthController::getProfile] Profil trouvé pour l\'utilisateur ID: ' . $user->getId() . ', Profil ID: ' . $profile->getId());
        error_log('[AuthController::getProfile] Données du profil: ' . json_encode($profileData));
        
        return new JsonResponse([
            'success' => true,
            'data' => $profileData
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || !isset($data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Téléphone et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['phone' => $data['phone']]);
        if ($existingUser) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce numéro de téléphone est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setPhone($data['phone']);
        // Utiliser le téléphone comme email pour l'identifiant (format: +212XXXXXXXXX@e-tawjihi.ma)
        $email = preg_replace('/[^0-9+]/', '', $data['phone']) . '@e-tawjihi.ma';
        $user->setEmail($email);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // Créer une notification de bienvenue
        try {
            $notificationService = new \App\Service\NotificationService(
                $em,
                $em->getRepository(\App\Entity\Notification::class)
            );
            $notificationService->createWelcomeNotification($user);
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer l'inscription
            error_log('Erreur lors de la création de la notification de bienvenue: ' . $e->getMessage());
        }

        // Générer le token JWT pour le nouvel utilisateur
        $token = $jwtManager->create($user);

        return new JsonResponse([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => [
                'user' => [
                    'id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'is_setup' => $user->getIsSetup(),
                ],
                'token' => $token
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/account/setup', name: 'api_account_setup', methods: ['POST'])]
    public function completeSetup(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): JsonResponse {
        // LOG IMMÉDIAT AVANT TOUT
        error_log('=== COMPLETE SETUP METHOD CALLED ===');
        error_log('Request URI: ' . $request->getRequestUri());
        error_log('Request Method: ' . $request->getMethod());
        error_log('User: ' . ($user ? 'User ID ' . $user->getId() : 'NULL'));
        error_log('Content: ' . $request->getContent());
        
        $logFile = '/tmp/account_setup.log';
        $log = function($message) use ($logFile, $logger) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
            $logger->info($message);
            error_log($message); // Double log pour être sûr
        };
        
        $log("=== ÉTAPE 1: DÉBUT DE LA MÉTHODE ===");
        
        // ÉTAPE 1: Vérifier l'authentification
        if (!$user) {
            $log("ERREUR: Utilisateur non authentifié");
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }
        $log("✓ Utilisateur authentifié - ID: " . $user->getId());

        // ÉTAPE 2: Parser les données
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $log("ERREUR: Aucune donnée reçue");
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucune donnée reçue'
            ], Response::HTTP_BAD_REQUEST);
        }
        $log("✓ Données reçues: " . count($data) . " champs");

        // ÉTAPE 3: Récupérer ou créer le profil
        $log("=== ÉTAPE 3: GESTION DU PROFIL ===");
        $profile = $user->getProfile();
        
        if (!$profile) {
            $log("Création d'un nouveau profil");
            $profile = new \App\Entity\UserProfile();
            $profile->setCreatedAt(new \DateTime());
            $profile->setUpdatedAt(new \DateTime());
            
            // Définir la relation bidirectionnelle
            $profile->setUser($user);
            $user->setProfile($profile);
            
            $log("✓ Profil créé avec user_id: " . $user->getId());
        } else {
            $log("Profil existant trouvé - ID: " . $profile->getId());
            $profile->setUpdatedAt(new \DateTime());
        }

        // ÉTAPE 4: Remplir les données du profil
        $log("=== ÉTAPE 4: REMPLISSAGE DES DONNÉES ===");
        
        try {
            // Informations académiques
            $profile->setUserType($data['userType'] ?? null);
            $profile->setNiveau($data['niveau'] ?? null);
            $profile->setBacType($data['bacType'] ?? null);
            $profile->setFiliere($data['filiere'] ?? null);
            $profile->setSpecialite1($data['specialite1'] ?? null);
            $profile->setSpecialite2($data['specialite2'] ?? null);
            $profile->setSpecialite3($data['specialite3'] ?? null);
            $profile->setDiplomeEnCours($data['diplomeEnCours'] ?? null);
            $profile->setNomEtablissement($data['nomEtablissement'] ?? null);
            $log("✓ Informations académiques définies");

            // Préférences
            $profile->setTypeEcolePrefere(
                !empty($data['typeEcolePrefere']) && is_array($data['typeEcolePrefere']) 
                    ? $data['typeEcolePrefere'] 
                    : null
            );
            $profile->setServicesPrefere(
                !empty($data['servicesPrefere']) && is_array($data['servicesPrefere']) 
                    ? $data['servicesPrefere'] 
                    : null
            );
            $log("✓ Préférences définies");

            // Informations personnelles
            $profile->setNom($data['nom'] ?? null);
            $profile->setPrenom($data['prenom'] ?? null);
            $profile->setGenre($data['genre'] ?? null);
            
            if (!empty($data['email'])) {
                $profile->setEmail($data['email']);
                $user->setEmail($data['email']);
            }
            
            if (!empty($data['dateNaissance'])) {
                try {
                    $profile->setDateNaissance(new \DateTime($data['dateNaissance']));
                } catch (\Exception $e) {
                    $log("ATTENTION: Erreur de date - " . $e->getMessage());
                }
            }
            
            // Ville
            if (!empty($data['ville']) && is_numeric($data['ville'])) {
                $cityId = (int)$data['ville'];
                $log("Recherche de la ville avec ID: " . $cityId);
                $city = $em->getRepository(\App\Entity\City::class)->find($cityId);
                if ($city) {
                    $profile->setVille($city);
                    $log("✓ Ville définie: " . $city->getTitre() . " (ID: " . $city->getId() . ")");
                } else {
                    $log("⚠️ ATTENTION: Ville avec ID " . $cityId . " non trouvée dans la base de données");
                }
            } else {
                $log("⚠️ Ville non définie ou invalide: " . ($data['ville'] ?? 'null') . " (type: " . gettype($data['ville'] ?? null) . ")");
            }
            $log("✓ Informations personnelles définies");

            // Informations tuteur (optionnel)
            $profile->setTuteur($data['tuteur'] ?? null);
            $profile->setNomTuteur($data['nomTuteur'] ?? null);
            $profile->setPrenomTuteur($data['prenomTuteur'] ?? null);
            $profile->setTelTuteur($data['telTuteur'] ?? null);
            $profile->setProfessionTuteur($data['professionTuteur'] ?? null);
            $profile->setAdresseTuteur($data['adresseTuteur'] ?? null);
            $log("✓ Informations tuteur définies");

            // Accord
            $profile->setConsentContact(isset($data['consentContact']) ? (bool)$data['consentContact'] : false);
            $log("✓ Consentement défini");

            // ÉTAPE 5: Marquer le setup comme complété
            $log("=== ÉTAPE 5: MARQUER LE SETUP ===");
            $user->setIsSetup(true);
            $log("✓ is_setup = true");

            // ÉTAPE 6: Vérifier les dates
            $log("=== ÉTAPE 6: VÉRIFICATION DES DATES ===");
            if ($profile->getCreatedAt() === null) {
                $profile->setCreatedAt(new \DateTime());
                $log("ATTENTION: CreatedAt était null, défini manuellement");
            }
            if ($profile->getUpdatedAt() === null) {
                $profile->setUpdatedAt(new \DateTime());
                $log("ATTENTION: UpdatedAt était null, défini manuellement");
            }
            $log("✓ Dates vérifiées");

            // ÉTAPE 7: Persister les entités
            $log("=== ÉTAPE 7: PERSISTENCE ===");
            $log("Vérification avant persist:");
            $log("  - Profile user_id: " . ($profile->getUser() ? $profile->getUser()->getId() : 'NULL'));
            $log("  - Profile createdAt: " . ($profile->getCreatedAt() ? $profile->getCreatedAt()->format('Y-m-d H:i:s') : 'NULL'));
            $log("  - Profile nom: " . ($profile->getNom() ?: 'NULL'));
            $log("  - Profile prenom: " . ($profile->getPrenom() ?: 'NULL'));
            
            $em->persist($profile);
            $em->persist($user);
            $log("✓ Entités persistées dans l'EntityManager");

            // ÉTAPE 8: Flush (sauvegarder en base)
            $log("=== ÉTAPE 8: FLUSH (SAUVEGARDE EN BASE) ===");
            try {
                $em->flush();
                $log("✓ FLUSH RÉUSSI - Données sauvegardées en base");
            } catch (\Doctrine\DBAL\Exception\DriverException $e) {
                $log("ERREUR DBAL: " . $e->getMessage());
                $log("SQL State: " . $e->getSQLState());
                $log("Error Code: " . $e->getCode());
                throw $e;
            } catch (\Exception $e) {
                $log("ERREUR: " . $e->getMessage());
                $log("Classe: " . get_class($e));
                throw $e;
            }

            // ÉTAPE 9: Vérifier la sauvegarde
            $log("=== ÉTAPE 9: VÉRIFICATION ===");
            $em->refresh($profile);
            $em->refresh($user);
            
            $profileId = $profile->getId();
            if ($profileId) {
                $log("✓ Profil sauvegardé avec ID: " . $profileId);
                $log("✓ User is_setup: " . ($user->getIsSetup() ? 'true' : 'false'));
                
                // Vérifier en base
                $savedProfile = $em->getRepository(\App\Entity\UserProfile::class)->find($profileId);
                if ($savedProfile) {
                    $log("✓ Profil vérifié en base - nom: " . ($savedProfile->getNom() ?: 'NULL'));
                } else {
                    $log("ERREUR: Profil non trouvé en base après flush!");
                }
            } else {
                $log("ERREUR: Profile ID est NULL après flush!");
            }

        } catch (\Exception $e) {
            $log("=== ERREUR CAPTURÉE ===");
            $log("Message: " . $e->getMessage());
            $log("Fichier: " . $e->getFile() . ":" . $e->getLine());
            $log("Trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(),
                'error' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null,
                'file' => $this->getParameter('kernel.environment') === 'dev' ? $e->getFile() : null,
                'line' => $this->getParameter('kernel.environment') === 'dev' ? $e->getLine() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // ÉTAPE 10: Retourner la réponse
        $log("=== ÉTAPE 10: RÉPONSE ===");
        $profileData = $profile && $profile->getId() ? [
            'id' => $profile->getId(),
            'nom' => $profile->getNom(),
            'prenom' => $profile->getPrenom(),
            'userType' => $profile->getUserType(),
            'niveau' => $profile->getNiveau(),
        ] : null;
        
        $log("✓ Réponse envoyée avec succès");
        $log("=== FIN DE LA MÉTHODE ===");
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Configuration du compte complétée',
            'data' => [
                'user' => [
                    'id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'is_setup' => $user->getIsSetup(),
                ],
                'profile' => $profileData
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/api/user/profile', name: 'api_user_profile_update', methods: ['PUT'])]
    public function updateProfile(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $logFile = '/tmp/profile_update.log';
        $log = function($message) use ($logFile, $logger) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
            $logger->info($message);
        };

        $log("=== MISE À JOUR DU PROFIL ===");
        $log("User ID: " . $user->getId());

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $log("ERREUR: Aucune donnée reçue");
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucune donnée reçue'
            ], Response::HTTP_BAD_REQUEST);
        }

        $log("Données reçues: " . count($data) . " champs");

        // Récupérer ou créer le profil
        $profile = $user->getProfile();
        if (!$profile) {
            $log("Création d'un nouveau profil");
            $profile = new \App\Entity\UserProfile();
            $profile->setCreatedAt(new \DateTime());
            $profile->setUser($user);
            $user->setProfile($profile);
        } else {
            $log("Profil existant trouvé - ID: " . $profile->getId());
        }
        $profile->setUpdatedAt(new \DateTime());

        try {
            // Informations académiques
            if (isset($data['niveau'])) $profile->setNiveau($data['niveau']);
            if (isset($data['bacType'])) $profile->setBacType($data['bacType']);
            if (isset($data['filiere'])) $profile->setFiliere($data['filiere']);
            if (isset($data['specialite1'])) $profile->setSpecialite1($data['specialite1']);
            if (isset($data['specialite2'])) $profile->setSpecialite2($data['specialite2']);
            if (isset($data['specialite3'])) $profile->setSpecialite3($data['specialite3']);
            if (isset($data['diplomeEnCours'])) $profile->setDiplomeEnCours($data['diplomeEnCours']);
            if (isset($data['nomEtablissement'])) $profile->setNomEtablissement($data['nomEtablissement']);
            if (isset($data['userType'])) $profile->setUserType($data['userType']);

            // Préférences
            if (isset($data['typeEcolePrefere'])) {
                $profile->setTypeEcolePrefere(
                    !empty($data['typeEcolePrefere']) && is_array($data['typeEcolePrefere']) 
                        ? $data['typeEcolePrefere'] 
                        : null
                );
            }
            if (isset($data['servicesPrefere'])) {
                $profile->setServicesPrefere(
                    !empty($data['servicesPrefere']) && is_array($data['servicesPrefere']) 
                        ? $data['servicesPrefere'] 
                        : null
                );
            }

            // Informations personnelles
            if (isset($data['nom'])) $profile->setNom($data['nom']);
            if (isset($data['prenom'])) $profile->setPrenom($data['prenom']);
            if (isset($data['genre'])) $profile->setGenre($data['genre']);
            
            if (isset($data['email']) && !empty($data['email'])) {
                $profile->setEmail($data['email']);
                $user->setEmail($data['email']);
            }
            
            if (isset($data['dateNaissance']) && !empty($data['dateNaissance'])) {
                try {
                    $profile->setDateNaissance(new \DateTime($data['dateNaissance']));
                } catch (\Exception $e) {
                    $log("ATTENTION: Erreur de date - " . $e->getMessage());
                }
            }
            
            // Ville
            if (isset($data['ville']) && !empty($data['ville']) && is_numeric($data['ville'])) {
                $cityId = (int)$data['ville'];
                $log("Recherche de la ville avec ID: " . $cityId);
                $city = $em->getRepository(\App\Entity\City::class)->find($cityId);
                if ($city) {
                    $profile->setVille($city);
                    $log("✓ Ville définie: " . $city->getTitre() . " (ID: " . $city->getId() . ")");
                } else {
                    $log("⚠️ ATTENTION: Ville avec ID " . $cityId . " non trouvée dans la base de données");
                }
            } elseif (isset($data['ville']) && $data['ville'] === null) {
                // Permettre de réinitialiser la ville à null
                $profile->setVille(null);
                $log("✓ Ville réinitialisée à null");
            }

            // Informations tuteur (optionnel)
            if (isset($data['tuteur'])) $profile->setTuteur($data['tuteur']);
            if (isset($data['nomTuteur'])) $profile->setNomTuteur($data['nomTuteur']);
            if (isset($data['prenomTuteur'])) $profile->setPrenomTuteur($data['prenomTuteur']);
            if (isset($data['telTuteur'])) $profile->setTelTuteur($data['telTuteur']);
            if (isset($data['professionTuteur'])) $profile->setProfessionTuteur($data['professionTuteur']);
            if (isset($data['adresseTuteur'])) $profile->setAdresseTuteur($data['adresseTuteur']);
            
            // Accord
            if (isset($data['consentContact'])) {
                $profile->setConsentContact((bool)$data['consentContact']);
            }

            // Persister les changements
            $em->persist($profile);
            $em->persist($user);
            $em->flush();

            $log("✓ Profil mis à jour avec succès");

            // Charger le profil mis à jour avec la relation ville
            $profileRepository = $em->getRepository(\App\Entity\UserProfile::class);
            $updatedProfile = $profileRepository->createQueryBuilder('p')
                ->leftJoin('p.ville', 'v')
                ->addSelect('v')
                ->where('p.id = :id')
                ->setParameter('id', $profile->getId())
                ->getQuery()
                ->getOneOrNullResult();

            $ville = $updatedProfile ? $updatedProfile->getVille() : null;
            
            $profileData = [
                'id' => $updatedProfile->getId(),
                'nom' => $updatedProfile->getNom(),
                'prenom' => $updatedProfile->getPrenom(),
                'email' => $updatedProfile->getEmail(),
                'telephone' => $user->getPhone(),
                'dateNaissance' => $updatedProfile->getDateNaissance() ? $updatedProfile->getDateNaissance()->format('Y-m-d') : null,
                'genre' => $updatedProfile->getGenre(),
                'ville' => $ville ? [
                    'id' => $ville->getId(),
                    'titre' => $ville->getTitre(),
                ] : null,
                'userType' => $updatedProfile->getUserType(),
                'niveau' => $updatedProfile->getNiveau(),
                'bacType' => $updatedProfile->getBacType(),
                'filiere' => $updatedProfile->getFiliere(),
                'specialite1' => $updatedProfile->getSpecialite1(),
                'specialite2' => $updatedProfile->getSpecialite2(),
                'specialite3' => $updatedProfile->getSpecialite3(),
                'diplomeEnCours' => $updatedProfile->getDiplomeEnCours(),
                'nomEtablissement' => $updatedProfile->getNomEtablissement(),
                'typeEcolePrefere' => $updatedProfile->getTypeEcolePrefere(),
                'servicesPrefere' => $updatedProfile->getServicesPrefere(),
                'tuteur' => $updatedProfile->getTuteur(),
                'nomTuteur' => $updatedProfile->getNomTuteur(),
                'prenomTuteur' => $updatedProfile->getPrenomTuteur(),
                'telTuteur' => $updatedProfile->getTelTuteur(),
                'professionTuteur' => $updatedProfile->getProfessionTuteur(),
                'adresseTuteur' => $updatedProfile->getAdresseTuteur(),
                'consentContact' => $updatedProfile->getConsentContact(),
                'createdAt' => $updatedProfile->getCreatedAt() ? $updatedProfile->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'updatedAt' => $updatedProfile->getUpdatedAt() ? $updatedProfile->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ];

            return new JsonResponse([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $profileData
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $log("ERREUR: " . $e->getMessage());
            $log("Stack trace: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/user/plan-reussite/steps', name: 'api_user_plan_reussite_steps', methods: ['POST', 'PUT'])]
    public function updatePlanReussiteSteps(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em,
        TestSessionRepository $testSessionRepository
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['step'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le champ "step" est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $step = $data['step']; // 'reportStepCompleted', 'step3_visited', 'step4_visited', 'step5_visited'

        // Récupérer ou créer le profil
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new \App\Entity\UserProfile();
            $profile->setCreatedAt(new \DateTime());
            $profile->setUser($user);
            $user->setProfile($profile);
        }
        $profile->setUpdatedAt(new \DateTime());

        // Récupérer les étapes existantes ou initialiser un tableau vide
        $steps = $profile->getPlanReussiteSteps() ?? [];
        
        // Vérifier les prérequis selon l'étape
        $canProceed = false;
        $missingPrerequisite = null;
        
        switch ($step) {
            case 'reportStepCompleted':
                // Pour reportStepCompleted, vérifier que le test d'orientation est complété
                $session = $testSessionRepository->findByUser($user->getId(), 'orientation');
                if ($session && $session->isIsCompleted()) {
                    $canProceed = true;
                } else {
                    $missingPrerequisite = 'Le test de diagnostic doit être complété avant de consulter le rapport';
                }
                break;
                
            case 'step3_visited':
                // Pour step3_visited, vérifier que reportStepCompleted est true
                if (isset($steps['reportStepCompleted']) && $steps['reportStepCompleted'] === true) {
                    $canProceed = true;
                } else {
                    $missingPrerequisite = 'Le rapport d\'orientation doit être consulté avant d\'accéder aux secteurs de métiers';
                }
                break;
                
            case 'step4_visited':
                // Pour step4_visited, vérifier que step3_visited est true
                if (isset($steps['step3_visited']) && $steps['step3_visited'] === true) {
                    $canProceed = true;
                } else {
                    $missingPrerequisite = 'Les secteurs de métiers doivent être consultés avant d\'accéder aux établissements';
                }
                break;
                
            case 'step5_visited':
                // Pour step5_visited, vérifier que step4_visited est true
                if (isset($steps['step4_visited']) && $steps['step4_visited'] === true) {
                    $canProceed = true;
                } else {
                    $missingPrerequisite = 'Les établissements doivent être consultés avant d\'accéder aux services';
                }
                break;
                
            default:
                // Pour les autres étapes, permettre la progression
                $canProceed = true;
                break;
        }
        
        // Si les prérequis ne sont pas remplis, retourner une erreur
        if (!$canProceed) {
            return new JsonResponse([
                'success' => false,
                'message' => $missingPrerequisite ?? 'Les étapes précédentes doivent être complétées',
                'data' => [
                    'planReussiteSteps' => $steps,
                    'canProceed' => false,
                    'missingPrerequisite' => $missingPrerequisite
                ]
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Marquer l'étape comme complétée seulement si les prérequis sont remplis
        $steps[$step] = true;
        
        // Si c'est reportStepCompleted, enregistrer aussi la date
        if ($step === 'reportStepCompleted') {
            $steps['reportStepCompletedAt'] = (new \DateTime())->format('Y-m-d\TH:i:s');
        }

        $profile->setPlanReussiteSteps($steps);
        $em->persist($profile);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Étape du plan de réussite mise à jour avec succès',
            'data' => [
                'planReussiteSteps' => $steps,
                'canProceed' => true
            ]
        ]);
    }

    #[Route('/api/user/plan-reussite/steps', name: 'api_user_plan_reussite_steps_get', methods: ['GET'])]
    public function getPlanReussiteSteps(
        #[CurrentUser] ?User $user,
        UserProfileRepository $profileRepository
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $profileRepository->findOneBy(['user' => $user]);
        
        $steps = $profile ? ($profile->getPlanReussiteSteps() ?? []) : [];

        return new JsonResponse([
            'success' => true,
            'data' => [
                'planReussiteSteps' => $steps
            ]
        ]);
    }

    /**
     * RESET PASSWORD WITH MANYCHAT
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/api/mdp_oublie', name: 'api.mdp_oublie', methods: ['POST', 'OPTIONS'])]
    public function mdp_oublie(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): JsonResponse {
        // Gérer les requêtes OPTIONS (preflight CORS)
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $logger->info('mdp_oublie: Request received', [
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('Content-Type'),
            'content_length' => $request->headers->get('Content-Length'),
        ]);

        $data = json_decode($request->getContent(), true) ?? [];
        $response = [
            "password" => null
        ];

        // Récupérer whatsapp_phone depuis le body JSON
        // ManyChat envoie les données dans le body JSON avec la clé whatsapp_phone
        $rawNumber = $data['whatsapp_phone'] ?? null;

        $logger->info('mdp_oublie: Data parsed', [
            'has_whatsapp_phone' => !empty($rawNumber),
            'data_keys' => array_keys($data),
        ]);

        if (empty($rawNumber)) {
            $logger->warning('mdp_oublie: whatsapp_phone missing');
            return new JsonResponse([
                'password' => null,
                'error' => 'whatsapp_phone is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Nettoyage du numéro : suppression de tous les caractères non numériques
        $cleanNumber = preg_replace('/\D+/', '', $rawNumber);

        // Conversion vers le format local
        if (preg_match('/^(?:212|00212)?([5-7]\d{8})$/', $cleanNumber, $matches)) {
            $localNumber = '0' . $matches[1];
        } else {
            $localNumber = $cleanNumber; // Numéro non reconnu, laissé tel quel
        }

        $logger->info('mdp_oublie: Phone processed', [
            'raw' => $rawNumber,
            'clean' => $cleanNumber,
            'local' => $localNumber,
        ]);

        $user = $em->getRepository(User::class)->findOneBy(['phone' => $localNumber]);

        if ($user) {
            $password = $this->generateStrongPassword(15);
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $password
                )
            );
            $em->flush();
            $response['password'] = $password;
            $logger->info('mdp_oublie: Password reset successful', [
                'user_id' => $user->getId(),
                'phone' => $localNumber,
            ]);
        } else {
            $logger->info('mdp_oublie: User not found', [
                'phone' => $localNumber,
            ]);
        }

        // Envoi de la réponse
        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Génère un mot de passe fort
     */
    private function generateStrongPassword(int $length = 15): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $all = $uppercase . $lowercase . $numbers . $special;
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        return str_shuffle($password);
    }
}
