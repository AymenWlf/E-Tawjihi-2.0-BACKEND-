<?php

namespace App\Controller\Api;

use App\Entity\Establishment;
use App\Repository\EstablishmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/establishments/{id}/photos', name: 'api_establishment_photos_')]
class EstablishmentPhotoController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EstablishmentRepository $establishmentRepository,
        private SluggerInterface $slugger,
        private string $uploadsDirectory
    ) {
    }

    /**
     * Upload une ou plusieurs photos pour un établissement
     */
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);

        if (!$establishment) {
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $files = $request->files->get('files');
        if (!$files) {
            $files = [$request->files->get('file')];
        }

        if (!$files || (is_array($files) && count($files) === 0) || (is_array($files) && !$files[0])) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun fichier fourni'
            ], Response::HTTP_BAD_REQUEST);
        }

        $uploadedPhotos = [];
        $errors = [];
        $photos = $establishment->getPhotos() ?? [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Validation du type MIME
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                $errors[] = "Type de fichier non autorisé pour {$file->getClientOriginalName()}";
                continue;
            }

            // Validation de la taille
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                $errors[] = "Fichier trop volumineux: {$file->getClientOriginalName()} (max 5MB)";
                continue;
            }

            try {
                // Génération du nom de fichier unique
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $extension = $file->guessExtension();
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

                // Dossier de destination
                $destinationDir = $this->uploadsDirectory . '/establishments/photos';
                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0755, true);
                }

                // Déplacement du fichier
                $file->move($destinationDir, $newFilename);

                // URL publique du fichier
                $publicUrl = '/uploads/establishments/photos/' . $newFilename;

                // Ajouter la photo au tableau
                $photoData = [
                    'id' => uniqid('photo_', true),
                    'url' => $publicUrl,
                    'titre' => $originalFilename,
                    'fileName' => $newFilename,
                    'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s')
                ];

                $photos[] = $photoData;
                $uploadedPhotos[] = $photoData;

            } catch (FileException $e) {
                $errors[] = "Erreur pour {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        // Sauvegarder les photos dans la base de données
        $establishment->setPhotos($photos);
        $this->entityManager->flush();

        $response = [
            'success' => count($uploadedPhotos) > 0,
            'message' => count($uploadedPhotos) . ' photo(s) uploadée(s) avec succès',
            'data' => $uploadedPhotos,
            'totalPhotos' => count($photos)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response, count($uploadedPhotos) > 0 ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);
    }

    /**
     * Met à jour les métadonnées d'une photo
     */
    #[Route('/{photoId}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, string $photoId, Request $request): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);

        if (!$establishment) {
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $photos = $establishment->getPhotos() ?? [];
        $photoFound = false;

        $data = json_decode($request->getContent(), true);

        foreach ($photos as &$photo) {
            if ($photo['id'] === $photoId) {
                if (isset($data['titre'])) {
                    $photo['titre'] = $data['titre'];
                }
                if (isset($data['description'])) {
                    $photo['description'] = $data['description'];
                }
                $photo['updatedAt'] = (new \DateTime())->format('Y-m-d H:i:s');
                $photoFound = true;
                break;
            }
        }

        if (!$photoFound) {
            return $this->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $establishment->setPhotos($photos);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Photo mise à jour avec succès',
            'data' => $photos
        ]);
    }

    /**
     * Supprime une photo
     */
    #[Route('/{photoId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, string $photoId): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);

        if (!$establishment) {
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $photos = $establishment->getPhotos() ?? [];
        $photoFound = false;
        $deletedPhoto = null;

        foreach ($photos as $index => $photo) {
            if ($photo['id'] === $photoId) {
                $deletedPhoto = $photo;
                
                // Supprimer le fichier physique
                $filePath = $this->uploadsDirectory . '/establishments/photos/' . $photo['fileName'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Retirer la photo du tableau
                array_splice($photos, $index, 1);
                $photoFound = true;
                break;
            }
        }

        if (!$photoFound) {
            return $this->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        $establishment->setPhotos($photos);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Photo supprimée avec succès',
            'data' => [
                'remainingPhotos' => count($photos),
                'deletedPhoto' => $deletedPhoto
            ]
        ]);
    }

    /**
     * Récupère toutes les photos d'un établissement
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $id): JsonResponse
    {
        $establishment = $this->establishmentRepository->find($id);

        if (!$establishment) {
            return $this->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $establishment->getPhotos() ?? [],
            'total' => count($establishment->getPhotos() ?? [])
        ]);
    }
}
