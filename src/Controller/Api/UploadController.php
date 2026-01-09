<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/upload', name: 'api_upload_')]
class UploadController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        private SluggerInterface $slugger,
        private string $uploadsDirectory
    ) {
    }

    /**
     * Upload d'une image ou d'un fichier
     */
    #[Route('/file', name: 'file', methods: ['POST'])]
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            /** @var UploadedFile $file */
            $file = $request->files->get('file');

            if (!$file) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucun fichier fourni'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du type MIME
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WEBP, PDF'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation de la taille
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Fichier trop volumineux. Taille maximale: 5MB'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder les informations avant le déplacement
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Type de fichier (logo, cover, brochure, etc.)
            $type = $request->request->get('type', 'general');
            
            // Génération du nom de fichier unique
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->guessExtension();
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            // Dossier de destination
            $destinationDir = $this->uploadsDirectory . '/' . $type;
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }

            // Déplacement du fichier
            $file->move($destinationDir, $newFilename);

            // URL publique du fichier
            $publicUrl = '/uploads/' . $type . '/' . $newFilename;

            return $this->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'data' => [
                    'filename' => $newFilename,
                    'url' => $publicUrl,
                    'type' => $type,
                    'size' => $fileSize,
                    'mimeType' => $mimeType
                ]
            ], Response::HTTP_CREATED);

        } catch (FileException $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du fichier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload multiple de fichiers
     */
    #[Route('/files', name: 'files', methods: ['POST'])]
    public function uploadFiles(Request $request): JsonResponse
    {
        try {
            $files = $request->files->get('files');

            if (!$files || !is_array($files)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Aucun fichier fourni'
                ], Response::HTTP_BAD_REQUEST);
            }

            $uploadedFiles = [];
            $errors = [];

            foreach ($files as $index => $file) {
                if (!$file instanceof UploadedFile) {
                    $errors[] = "Fichier invalide à l'index $index";
                    continue;
                }

                // Validation du type MIME
                if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                    $errors[] = "Type de fichier non autorisé pour {$file->getClientOriginalName()}";
                    continue;
                }

                // Validation de la taille
                if ($file->getSize() > self::MAX_FILE_SIZE) {
                    $errors[] = "Fichier trop volumineux: {$file->getClientOriginalName()}";
                    continue;
                }

                try {
                    // Sauvegarder les informations avant le déplacement
                    $fileSize = $file->getSize();
                    $mimeType = $file->getMimeType();

                    $type = $request->request->get('type', 'general');
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $extension = $file->guessExtension();
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

                    $destinationDir = $this->uploadsDirectory . '/' . $type;
                    if (!is_dir($destinationDir)) {
                        mkdir($destinationDir, 0755, true);
                    }

                    $file->move($destinationDir, $newFilename);

                    $uploadedFiles[] = [
                        'filename' => $newFilename,
                        'url' => '/uploads/' . $type . '/' . $newFilename,
                        'type' => $type,
                        'size' => $fileSize,
                        'mimeType' => $mimeType
                    ];
                } catch (FileException $e) {
                    $errors[] = "Erreur pour {$file->getClientOriginalName()}: " . $e->getMessage();
                }
            }

            $response = [
                'success' => count($uploadedFiles) > 0,
                'message' => count($uploadedFiles) . ' fichier(s) uploadé(s) avec succès',
                'data' => $uploadedFiles
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            return $this->json($response, count($uploadedFiles) > 0 ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload des fichiers: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Suppression d'un fichier
     */
    #[Route('/file', name: 'delete_file', methods: ['DELETE'])]
    public function deleteFile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $url = $data['url'] ?? null;

            if (!$url) {
                return $this->json([
                    'success' => false,
                    'message' => 'URL du fichier requise'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Extraire le chemin relatif depuis l'URL
            $relativePath = str_replace('/uploads/', '', parse_url($url, PHP_URL_PATH));
            $filePath = $this->uploadsDirectory . '/' . $relativePath;

            if (!file_exists($filePath)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Suppression du fichier
            if (!unlink($filePath)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression du fichier'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
