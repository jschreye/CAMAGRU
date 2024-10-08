<?php

namespace Camagru\Infrastructure\Services;

use Camagru\Core\Models\CommentModel;
use Camagru\Core\Models\LikeModel;
use Camagru\Core\Models\PostModel;
class HomeService
{
    private $CommentModel;
    private $LikeModel;
    private $PostModel;
    public function __construct()
    {
        $this->CommentModel = new CommentModel();
        $this->LikeModel = new LikeModel();
        $this->PostModel = new PostModel();
    }

    public function AddComment($post_id, $comment)
    {
        return $this->CommentModel->SaveComment($post_id, $comment);
    }
    public function LikePost($post_id, $userId)
    {
        return $this->LikeModel->LikePost($post_id, $userId);
    }
    public function GetPostOwner($post_id)
    {
        return $this->PostModel->GetPostOwner($post_id);
    }

    public function GetLikeCount($post_id)
    {
        return $this->LikeModel->GetLikeCount($post_id);
    }

    public function validationCommentaire($comment)
    {
        $errors = [];
        if (empty($comment))
        {
            $errors[] = 'Le commentaire ne doit pas être vide.';
        }
        if (strlen(string: $comment) > 50)
        {
            $errors[] = 'Le commentaire ne doit pas contenir plus de 50 caractères.';
        }
        return $errors;
    }
}