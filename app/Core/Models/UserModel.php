<?php

namespace Camagru\Core\Models;

use PDO;
use Camagru\Core\Data\Connection;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getDBConnection();
    }

    /**
     * Récupère le dernier utilisateur ajouté à la base de données.
     */
    public function GetUser(): ?array
    {
        // On récupère l'userId depuis la session
        $userId = $_SESSION['user']['id'];
        // Préparation de la requête SQL pour récupérer les likes correspondant à l'id utilisateur
        $stmt = $this->db->prepare('SELECT username, email, notif FROM users WHERE id = :id');
        
        // Liaison de la variable :id à l'userId
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        // Exécution de la requête
        $stmt->execute();

        // Récupération des résultats sous forme de tableau associatif
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function Login(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function RegisterUser($username, $password, $email)
    {
        // Hash the password pour plus de sécurité
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
        // Générer un jeton unique pour la validation par email
        $verificationToken = bin2hex(random_bytes(50));

        // Insérer les données dans la base de données avec le jeton de validation et `is_verified` à FALSE
        $stmt = $this->db->prepare('INSERT INTO users (username, password, email, verification_token, is_verified) VALUES (:username, :password, :email, :token, FALSE)');
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $hashedPassword);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':token', $verificationToken); // Liaison du jeton de validation
    
        $stmt->execute();
    
        // Retourner le jeton de validation pour l'envoi de l'email
        return $verificationToken;
    }

    public function isUsernameTaken(string $username): bool {

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetchColumn() > 0; // Renvoie true si le nom d'utilisateur est pris
        
    }

    public function findUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Sauvegarder le token de réinitialisation
    public function storeResetToken($email, $token)
    {
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valide pendant 1 heure
        $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_token_expiration = :expiration WHERE email = :email");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiration', $expiration);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    }

    // Vérifier le token de réinitialisation
    public function verifyResetToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expiration > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function ResetPassword($id, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    // Met à jour le nom d'utilisateur
    public function UpdateUsername($userId, $username) {
        $stmt = $this->db->prepare('UPDATE users SET username = :username WHERE id = :id');
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Met à jour l'email
    public function UpdateEmail($userId, $email) {
        $stmt = $this->db->prepare('UPDATE users SET email = :email WHERE id = :id');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Met à jour le mot de passe
    public function UpdatePassword($userId, $hashedPassword) {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function UpdateNotif($userId, $notif) {
        $stmt = $this->db->prepare('UPDATE users SET notif = :notif WHERE id = :id');
        $stmt->bindParam(':notif', $notif, PDO::PARAM_BOOL); // Utilisation de PDO::PARAM_BOOL pour les booleans
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function UpdateCommentsUsername($userId, $newUsername) {
        // Mettre à jour le nom d'utilisateur dans la table des commentaires pour tous les commentaires de cet utilisateur
        $stmt = $this->db->prepare('UPDATE commentaire SET username = :newUsername WHERE user_id = :userId');
        $stmt->bindParam(':newUsername', $newUsername);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function findUserByToken($token) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE verification_token = :token AND is_verified = FALSE');
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function verifyUser($userId) {
        $stmt = $this->db->prepare('UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = :id');
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
}