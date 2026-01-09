<?php

namespace App\Controller\Api;

use App\Entity\User;
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
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $profile = $user->getProfile();
        
        if (!$profile) {
            return new JsonResponse([
                'success' => true,
                'data' => null
            ]);
        }

        $ville = $profile->getVille();
        
        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $profile->getId(),
                'nom' => $profile->getNom(),
                'prenom' => $profile->getPrenom(),
                'email' => $profile->getEmail(),
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
                'nomEtablissement' => $profile->getNomEtablissement()
            ]
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
                $city = $em->getRepository(\App\Entity\City::class)->find((int)$data['ville']);
                if ($city) {
                    $profile->setVille($city);
                    $log("✓ Ville définie: " . $city->getTitre());
                }
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
}
