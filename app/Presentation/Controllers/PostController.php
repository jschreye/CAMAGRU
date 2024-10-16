<?php

namespace Presentation\Controllers;

use Camagru\Infrastructure\Services\PostService;

class PostController {
    private $PostService;

    public function __construct() {
        $this->PostService = new PostService();
    }



    public function Index() {
        $posts = $this->PostService->GetMyImage();

        renderView(__DIR__ . '/../Views/Shared/Layout.php', [
            'view' => __DIR__ . '/../Views/Post/index.php',
            'posts' => $posts
        ]);
        exit();
    }
    public function ImageRegister(string $image) {
        $this->PostService->ImageRegister($image);

        header('Location: /');
        exit(); 
    }

    public function SavePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // protection csrf
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $errors[] = 'Erreur : jeton CSRF invalide.';
                renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                    'view' => __DIR__ . '/../Views/Post/index.php',
                    'errors' => $errors  // Passer les erreurs à la vue
                ]);
                exit();
            }

            // Vérifier si les champs sont bien envoyés
            if (isset($_POST['photo']) && isset($_POST['sticker'])) {
                $photoData = $_POST['photo'];  // Photo capturée en base64
                $stickerData = $_POST['sticker'];  // Sticker en base64
                $errors = [];
                $maxImageSize = 5 * 1024 * 1024; // 5 Mo
                
                $errors = $this->PostService->ValidatePost($photoData, $stickerData, $maxImageSize);

                // Si des erreurs existent, les afficher à l'utilisateur
                if (!empty($errors)) {
                    renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                        'view' => __DIR__ . '/../Views/Post/index.php',
                        'errors' => $errors
                    ]);
                    exit();
                }
                // Appeler le service pour fusionner et sauvegarder l'image
                $result = $this->PostService->mergeAndSaveImage(photoData: $photoData, stickerData: $stickerData);
                unset($_SESSION['csrf_token']);
                if ($result) {
                    // Rediriger vers une page de succès ou afficher un message de succès
                    header('Location: /');
                    exit(); // Terminer l'exécution après la redirection
                } else {
                    // Rediriger vers une page d'erreur ou afficher un message d'erreur
                    header('Location: /post/error');
                    exit(); // Terminer l'exécution après la redirection
                }
            } else {
                // Gérer le cas où les données sont manquantes
                echo "Erreur : données photo ou sticker manquantes.";
            }
        } else {
            // Gérer le cas où la requête n'est pas en POST
            echo "Méthode de requête invalide.";
        }
    }

    public function delete()
    {
        // Vérifier si la requête est de type POST et si l'ID de la photo est fourni
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $postId = $_POST['id'];
            $userId = $_SESSION['user']['id']; // L'ID de l'utilisateur connecté
            
            if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur : jeton CSRF invalide ou non défini.'
                ]);
                exit();
            }
            // Appeler le service pour supprimer la photo
            $success = $this->PostService->deletePhoto($postId, $userId);
            $newCsrfToken = GenerateCsrfToken();
            if ($success) {
                // Retourner une réponse JSON en cas de succès
                echo json_encode([
                    'success' => true,
                    'csrf_token' => $newCsrfToken 
                    ]);
            } else {
                // En cas d'échec, retourner une réponse JSON avec un message d'erreur
                echo json_encode(['success' => false, 'message' => 'La suppression a échoué.']);
            }
        } else {
            // Si la requête n'est pas valide, retourner une réponse JSON avec une erreur
            echo json_encode(['success' => false, 'message' => 'Requête non valide ou ID manquant.']);
        }
    }
}
