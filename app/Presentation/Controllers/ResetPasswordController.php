<?php

namespace Presentation\Controllers;

use Camagru\Core\Models\UserModel;
use Camagru\Infrastructure\Services\ResetPasswordService;


class ResetPasswordController
{
    private $ResetPasswordService;

    public function __construct() {
        $this->ResetPasswordService = new ResetPasswordService();
    }
    public function resetPassword()
    {
        // Initialiser les erreurs
        $errors = [];
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données du formulaire
            $token = $_POST['token'] ?? '';
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';
    
            // Vérifier le token CSRF
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $errors[] = 'Erreur : jeton CSRF invalide.';
            }
    
            // Vérifier les mots de passe
            if (empty($errors)) {
                $passwordErrors = $this->ResetPasswordService->verificationResetPassword($newPassword, $confirmPassword);
                if (!empty($passwordErrors)) {
                    $errors = array_merge($errors, $passwordErrors);
                }
            }
    
            if (empty($errors)) {
                // Vérifier si le token de réinitialisation est valide
                $userModel = new UserModel();
                $user = $userModel->verifyResetToken($token);
    
                if ($user) {
                    // Mettre à jour le mot de passe (hashé)
                    $userModel->resetPassword($user['id'], $newPassword);
    
                    // Message de succès et redirection
                    $_SESSION['success_message'] = "Mot de passe réinitialisé avec succès.";
                    unset($_SESSION['csrf_token']); // Invalider le CSRF token après succès
                    header('Location: /login');
                    exit();
                } else {
                    $errors[] = "Token invalide ou expiré.";
                }
            }
    
            // Si des erreurs existent, régénérer le CSRF token si nécessaire
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
    
            // Re-rendre la vue avec les erreurs, le token de réinitialisation et le CSRF token
            renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                'view' => __DIR__ . '/../Views/ResetPassword/index.php',
                'errors' => $errors,
                'token' => htmlspecialchars($token),
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        } else {
            // Si la méthode est GET, afficher le formulaire avec le token
            $token = $_GET['token'] ?? '';
    
            // Générer un nouveau CSRF token si nécessaire
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
    
            renderView(__DIR__ . '/../Views/Shared/Layout.php', [
                'view' => __DIR__ . '/../Views/ResetPassword/index.php',
                'token' => htmlspecialchars($token),
                'csrf_token' => $_SESSION['csrf_token']
            ]);
            exit();
        }
    }
}