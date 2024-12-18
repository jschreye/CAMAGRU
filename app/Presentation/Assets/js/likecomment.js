// Gestion des "likes"
document.querySelectorAll('.like-button').forEach(button => {
    button.addEventListener('click', function() {
        const form = this.closest('form');
        const postId = form.getAttribute('data-post-id');
        const likeCountElement = form.querySelector('.like-count');
        const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput.value;

        fetch('/post/like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le nombre de likes
                likeCountElement.innerHTML = `${data.likes} <svg xmlns="http://www.w3.org/2000/svg" style="color: red;" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icon-tabler-filled icon-tabler-heart"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.979 3.074a6 6 0 0 1 4.988 1.425l.037 .033l.034 -.03a6 6 0 0 1 4.733 -1.44l.246 .036a6 6 0 0 1 3.364 10.008l-.18 .185l-.048 .041l-7.45 7.379a1 1 0 0 1 -1.313 .082l-.094 -.082l-7.493 -7.422a6 6 0 0 1 3.176 -10.215z" /></svg>`;
            } else {
                console.error(data.message);  // Gérer l'erreur
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

// Gestion des commentaires
document.querySelectorAll('.comment-form').forEach(form => {
    form.addEventListener('submit', function(event) {
        event.preventDefault();  // Empêcher la soumission classique du formulaire

        const postId = form.getAttribute('data-post-id');
        const commentInput = form.querySelector('input[name="comment"]');
        const commentList = document.getElementById(`comment-list-${postId}`);
        const errorMessage = form.querySelector('.error-message');
        const commentText = commentInput.value.trim();
        const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput.value;

        // Vérifier si le conteneur d'erreur existe
        if (!errorMessage) {
            return;
        }

        // Validation simple (optionnel)
        if (commentText === "") {
            errorMessage.textContent = "Le commentaire ne peut pas être vide.";
            errorMessage.style.display = 'block';
            return;
        }

        // Envoi de la requête POST via Fetch API
        fetch('/post/add_comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&comment=${encodeURIComponent(commentText)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Créer un nouvel élément <li> pour le commentaire
                const newComment = document.createElement('li');
                newComment.classList.add('list-group-item');
                newComment.innerHTML = `<strong>${data.username}:</strong> ${data.comment}`;
                
                // Ajouter le nouveau commentaire à la liste
                commentList.appendChild(newComment);

                // Réinitialiser le champ de texte du commentaire
                commentInput.value = '';
                errorMessage.style.display = 'none'; // Cacher le message d'erreur en cas de succès
            } else {
                // Afficher un message d'erreur si la requête échoue
                errorMessage.textContent = data.errors ? data.errors.join(', ') : (data.message || 'Une erreur est survenue.');
                errorMessage.style.display = 'block';
            }
        })
        .catch(() => {
            errorMessage.textContent = 'Erreur de connexion ou de réponse. Veuillez réessayer.';
            errorMessage.style.display = 'block';
        });
    });
});