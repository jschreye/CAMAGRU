<?php

namespace Camagru\Infrastructure\Services;

use Camagru\Core\Models\PostModel;

class PostService
{
    private $PostModel;

    public function __construct()
    {
        $this->PostModel = new PostModel();
    }

    public function ImageRegister(string $imageContent): ?array
    {
        return $this->PostModel->ImageRegister($imageContent);
    }

    public function GetAllImage(): array
    {
        return $this->PostModel->getAllImages();
    }
    
    public function mergeAndSaveImage($photoData, $stickersData): bool {
        // Décoder l'image capturée en base64
        $photoData = str_replace('data:image/png;base64,', '', $photoData);
        $photoData = base64_decode($photoData);
        
        if (!$photoData) {
            echo "Erreur lors du décodage de l'image capturée.";
            return false;
        }

        // Charger l'image capturée
        $photoImage = imagecreatefromstring($photoData);
        if (!$photoImage) {
            echo "Erreur lors de la création de l'image capturée.";
            return false;
        }

        // Obtenir les dimensions de l'image capturée
        $photoWidth = imagesx($photoImage);
        $photoHeight = imagesy($photoImage);

        // Traiter chaque sticker s'il y en a
        if (!empty($stickersData)) {
            foreach ($stickersData as $sticker) {
                // Vérifier que toutes les clés nécessaires existent
                if (!isset($sticker['src'], $sticker['x'], $sticker['y'], $sticker['width'], $sticker['height'])) {
                    echo "Erreur : données du sticker incomplètes.";
                    continue; // Passer au sticker suivant
                }

                // Décoder le sticker en base64
                $stickerSrc = $sticker['src'];
                // Assurez-vous que le sticker est en base64, sinon encodez-le
                if (strpos($stickerSrc, 'data:image') === 0) {
                    $stickerSrc = preg_replace('#^data:image/\w+;base64,#i', '', $stickerSrc);
                }
                $stickerData = base64_decode($stickerSrc);

                if (!$stickerData) {
                    echo "Erreur lors du décodage d'un sticker.";
                    continue; // Passer au sticker suivant
                }

                $stickerImageResource = imagecreatefromstring($stickerData);
                if (!$stickerImageResource) {
                    echo "Erreur lors de la création de l'image du sticker.";
                    continue; // Passer au sticker suivant
                }

                // Dimensions et position du sticker
                $stickerWidth = $sticker['width'];  // Largeur redimensionnée
                $stickerHeight = $sticker['height']; // Hauteur redimensionnée
                $x = $sticker['x']; // Position X
                $y = $sticker['y']; // Position Y

                // Créer une nouvelle ressource d'image pour le sticker redimensionné
                $resizedSticker = imagecreatetruecolor($stickerWidth, $stickerHeight);

                // Maintenir la transparence du sticker
                imagealphablending($resizedSticker, false);
                imagesavealpha($resizedSticker, true);

                // Redimensionner le sticker
                imagecopyresampled(
                    $resizedSticker,          // Destination (nouvelle image)
                    $stickerImageResource,    // Source (image d'origine)
                    0, 0,                     // Coordonnées de la destination
                    0, 0,                     // Coordonnées de la source
                    $stickerWidth,            // Largeur redimensionnée
                    $stickerHeight,           // Hauteur redimensionnée
                    imagesx($stickerImageResource), // Largeur originale
                    imagesy($stickerImageResource)  // Hauteur originale
                );

                // Fusionner le sticker avec la photo
                imagecopy($photoImage, $resizedSticker, $x, $y, 0, 0, $stickerWidth, $stickerHeight);

                // Libérer les ressources mémoire pour le sticker
                imagedestroy($resizedSticker);
                imagedestroy($stickerImageResource);
            }
        }

        // Enregistrer l'image fusionnée en mémoire
        ob_start();
        imagepng($photoImage); // Convertir en PNG
        $mergedImageData = ob_get_clean(); // Récupérer les données binaires

        if (!$mergedImageData) {
            echo "Erreur lors de la fusion de l'image.";
            return false;
        }

        // Sauvegarder l'image en base de données via le modèle
        $result = $this->PostModel->saveImageToDatabase($mergedImageData);

        // Libérer les ressources mémoire
        imagedestroy($photoImage);

        return $result;
    }

    public function GetMyImage(): array
    {
        return $this->PostModel->getImagesByUserId();
    }

    public function GetPostsPaginated($postsPerPage, $offset)
    {
        return $this->PostModel->GetPostsPaginated($postsPerPage,$offset);
    }
    public function GetTotalPosts()
    {
        return $this->PostModel->GetTotalPosts();
    }

    public function deletePhoto($postId, $userId)
    {
        return $this->PostModel->deletePost($postId,$userId);
    }

    public function ValidatePost($photoData, $stickerData, $maxImageSize)
    {
        $errors = [];
        if (empty($photoData)) {
            $errors[] = "Erreur : aucune photo sélectionnée.";
        } else {
            $base64Prefix = substr($photoData, 0, strpos($photoData, ','));

            // Vérifier le type d'image avec les préfixes spécifiques
            if (strpos($base64Prefix, 'data:image/png') === false && strpos($base64Prefix, 'data:image/jpeg') === false) {
                $errors[] = "Erreur : seuls les fichiers PNG et JPEG sont autorisés.";
            }

            // Calculer la taille réelle de l'image décodée à partir du base64
            $photoSizeInBytes = (int)(strlen(base64_decode(explode(',', $photoData)[1])));
            
            // Vérifier la taille de la photo
            if ($photoSizeInBytes > $maxImageSize) {
                $errors[] = "Erreur : la taille de la photo dépasse la limite autorisée de 5 Mo.";
            }
        }

        // Rendre les stickers optionnels
        if (!empty($stickerData)) {
            // Valider les stickers
            foreach ($stickerData as $sticker) {
                if (!isset($sticker['src'], $sticker['x'], $sticker['y'], $sticker['width'], $sticker['height'])) {
                    $errors[] = "Erreur : données du sticker incomplètes.";
                    break; // Arrêter la validation après la première erreur
                }
            }
        }

        return $errors;
    }
}