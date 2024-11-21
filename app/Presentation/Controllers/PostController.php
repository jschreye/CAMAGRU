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

    // Enregistrer une image
    public function SavePost(): void {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Protection CSRF
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $errors[] = 'Erreur : jeton CSRF invalide.';
                renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                    'view' => __DIR__ . '/../Views/Post/index.php',
                    'errors' => $errors
                ]);
                exit();
            }

            // Debug : Loguer toutes les données POST
            error_log(print_r($_POST, true));

            // Vérifier si les données sont bien envoyées
            if (isset($_POST['photo'])) {
                $photoData = $_POST['photo']; // Photo capturée en base64
                $stickersData = isset($_POST['stickers']) ? json_decode($_POST['stickers'], true) : []; // Liste des stickers envoyés en JSON
                $errors = [];
                $maxImageSize = 5 * 1024 * 1024; // Taille maximale de l'image (5 Mo)

                // Vérification des erreurs JSON pour les stickers
                if (!empty($stickersData) && json_last_error() !== JSON_ERROR_NONE) {
                    echo "Erreur : données stickers mal formées.";
                    exit();
                }

                // Valider les données
                $errors = $this->PostService->ValidatePost($photoData, $stickersData, $maxImageSize);

                // Afficher les erreurs si présentes
                if (!empty($errors)) {
                    renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                        'view' => __DIR__ . '/../Views/Post/index.php',
                        'errors' => $errors
                    ]);
                    exit();
                }

                // Fusionner et sauvegarder l'image
                $result = $this->PostService->mergeAndSaveImage($photoData, $stickersData);

                // Réinitialiser le jeton CSRF
                unset($_SESSION['csrf_token']);

                // Redirection selon le résultat
                if ($result) {
                    header('Location: /');
                    exit();
                } else {
                    echo "Erreur : impossible de sauvegarder l'image.";
                    exit();
                }
            } else {
                echo "Erreur : données photo manquantes.";
                exit();
            }
        } else {
            echo "Méthode de requête invalide.";
            exit();
        }
    }

    public function delete()
    {
        // Vérifier si la requête est de type POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lire le corps de la requête
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Vérifier si l'ID et le jeton CSRF sont fournis
            if (isset($data['id']) && isset($data['csrf_token'])) {
                $postId = $data['id'];
                $csrfToken = $data['csrf_token'];
                $userId = $_SESSION['user']['id']; // L'ID de l'utilisateur connecté
                
                // Vérifier le jeton CSRF
                if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erreur : jeton CSRF invalide ou non défini.'
                    ]);
                    exit();
                }
                
                // Appeler le service pour supprimer la photo
                $success = $this->PostService->deletePhoto($postId, $userId);
                $newCsrfToken = GenerateCsrfToken(); // Regénérer le jeton CSRF
                
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
                // Si l'ID ou le jeton CSRF est manquant, retourner une erreur
                echo json_encode(['success' => false, 'message' => 'ID ou jeton CSRF manquant.']);
            }
        } else {
            // Si la requête n'est pas de type POST, retourner une erreur
            echo json_encode(['success' => false, 'message' => 'Requête non valide.']);
        }
    }
}