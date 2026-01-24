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

class ExternalApiController extends AbstractController
{
    #[Route('/api/external/user/create', name: 'external_user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            // Parser les données JSON
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => [
                        'phone' => 'Le numéro de téléphone est requis',
                        'password' => 'Le mot de passe est requis'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation des champs requis
            $errors = [];
            if (empty($data['phone']) || !is_string($data['phone']) || trim($data['phone']) === '') {
                $errors['phone'] = 'Le numéro de téléphone est requis';
            }
            
            if (empty($data['password']) || !is_string($data['password']) || trim($data['password']) === '') {
                $errors['password'] = 'Le mot de passe est requis';
            }

            if (!empty($errors)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Nettoyer le numéro de téléphone (enlever les espaces)
            $phone = preg_replace('/\s+/', '', trim($data['phone']));

            // Validation optionnelle du format du numéro de téléphone (06 ou 07 suivi de 8 chiffres)
            if (!preg_match('/^(06|07)\d{8}$/', $phone)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => [
                        'phone' => 'Le numéro de téléphone doit commencer par 06 ou 07 et contenir 10 chiffres'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['phone' => $phone]);
            
            if ($existingUser) {
                // L'utilisateur existe, mettre à jour le mot de passe
                $logger->info('Mise à jour du mot de passe pour l\'utilisateur existant', [
                    'user_id' => $existingUser->getId(),
                    'phone' => $phone
                ]);

                // Hasher le nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($existingUser, $data['password']);
                $existingUser->setPassword($hashedPassword);

                $em->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Mot de passe mis à jour avec succès',
                    'data' => [
                        'id' => $existingUser->getId(),
                        'phone' => $existingUser->getPhone(),
                        'updated' => true
                    ]
                ], Response::HTTP_OK);
            }

            // Créer un nouvel utilisateur
            $logger->info('Création d\'un nouvel utilisateur via API externe', ['phone' => $phone]);

            $user = new User();
            $user->setPhone($phone);
            
            // Générer un email basé sur le téléphone (comme dans register)
            $email = preg_replace('/[^0-9+]/', '', $phone) . '@e-tawjihi.ma';
            $user->setEmail($email);
            
            // Hasher le mot de passe avant de le stocker
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            
            // Définir les rôles par défaut
            $user->setRoles(['ROLE_USER']);
            $user->setIsSetup(false);

            $em->persist($user);
            $em->flush();

            $logger->info('Utilisateur créé avec succès via API externe', [
                'user_id' => $user->getId(),
                'phone' => $phone
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => [
                    'id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'created' => true
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            $logger->error('Erreur lors de la création/mise à jour de l\'utilisateur via API externe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
