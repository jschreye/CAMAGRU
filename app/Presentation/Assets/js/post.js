// Initialiser les éléments HTML
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const captureButton = document.getElementById('capture');
const resetButton = document.getElementById('reset');
const uploadPhotoButton = document.getElementById('uploadPhotoButton');
const fileInput = document.getElementById('fileInput');
const photoInput = document.getElementById('photo'); // Champ caché pour la photo capturée
let context = canvas.getContext('2d');
let photoCaptured = false; // Variable pour vérifier si la photo est capturée
let selectedStickers = []; // Liste des stickers sélectionnés avec leurs positions

// Fonction pour mettre à jour la disponibilité des stickers
function updateStickerAvailability() {
    if (photoCaptured) {
        document.querySelectorAll('.sticker-preview').forEach(sticker => {
            sticker.classList.remove('disabled');
        });
    } else {
        document.querySelectorAll('.sticker-preview').forEach(sticker => {
            sticker.classList.add('disabled');
        });
    }
}

// Activer la webcam ou afficher le bouton d'upload si refus ou absence de webcam
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
        // Afficher le bouton de capture si la caméra est activée
        captureButton.style.display = "inline-block";
        // Masquer le bouton d'upload
        uploadPhotoButton.style.display = "none";
    })
    .catch(err => {
        // Afficher le bouton pour uploader une photo si pas de webcam ou refus d'accès
        uploadPhotoButton.style.display = "inline-block";
        // Masquer le bouton de capture
        captureButton.style.display = "none";
    });

// Fonction pour capturer la photo
captureButton.addEventListener('click', () => {
    if (!photoCaptured) {
        // Capture l'image de la vidéo dans le canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/png'); // Convertir l'image en base64
        photoInput.value = dataURL; // Mettre la photo capturée dans un champ caché
        photoCaptured = true; // Indiquer que la photo est capturée

        // Redessiner les stickers sélectionnés (au cas où)
        redrawStickers();
        // Mettre à jour la disponibilité des stickers
        updateStickerAvailability();
    }
});

// Fonction pour uploader une photo
uploadPhotoButton.addEventListener('click', () => {
    fileInput.click(); // Ouvrir la boîte de dialogue pour choisir une photo
});

// Gérer la sélection d'une image uploadée
fileInput.addEventListener('change', function (event) {
    const file = event.target.files[0];
    const reader = new FileReader();

    reader.onload = function (e) {
        const img = new Image();
        img.src = e.target.result;

        img.onload = function () {
            // Dessiner l'image uploadée dans le canvas
            context.drawImage(img, 0, 0, canvas.width, canvas.height);
            const dataURL = canvas.toDataURL('image/png'); // Convertir l'image en base64
            photoInput.value = dataURL; // Mettre la photo uploadée dans le champ caché
            photoCaptured = true; // Marquer la photo comme capturée

            // Redessiner les stickers sélectionnés (au cas où)
            redrawStickers();
            // Mettre à jour la disponibilité des stickers
            updateStickerAvailability();
        };
    };
    reader.readAsDataURL(file);
});

// Réinitialiser la capture et la sélection des stickers
resetButton.addEventListener('click', () => {
    context.clearRect(0, 0, canvas.width, canvas.height); // Effacer le canvas
    photoCaptured = false; // Réinitialiser l'état de capture de la photo
    selectedStickers = []; // Réinitialiser les stickers sélectionnés

    // Supprimer la sélection visuelle des stickers
    document.querySelectorAll('.sticker-preview').forEach(sticker => {
        sticker.classList.remove('sticker-selected');
    });

    // Mettre à jour la disponibilité des stickers
    updateStickerAvailability();
});

// Fonction pour convertir une image URL en base64
function getBase64Image(url, callback) {
    const img = new Image();
    img.crossOrigin = 'Anonymous'; // Pour éviter les problèmes de CORS
    img.onload = function () {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const dataURL = canvas.toDataURL('image/png');
        callback(dataURL);
    };
    img.onerror = function () {
        console.error('Impossible de charger l\'image : ' + url);
        callback(null);
    };
    img.src = url;
}

// Gérer la sélection et la désélection des stickers
document.querySelectorAll('.sticker-preview').forEach(sticker => {
    sticker.addEventListener('click', function () {
        if (!photoCaptured) {
            alert("Vous devez d'abord capturer ou uploader une photo pour sélectionner un sticker.");
            return;
        }

        const stickerSrc = this.dataset.stickerSrc;

        // Vérifier si le sticker est déjà sélectionné
        const index = selectedStickers.findIndex(s => s.originalSrc === stickerSrc);

        if (index !== -1) {
            // Si déjà sélectionné, le retirer
            selectedStickers.splice(index, 1);
            this.classList.remove('sticker-selected'); // Retirer l'effet visuel
            redrawStickers();
        } else {
            // Sinon, convertir le sticker en base64 et l'ajouter
            getBase64Image(stickerSrc, function(base64StickerSrc) {
                if (base64StickerSrc) {
                    const img = new Image();
                    img.src = base64StickerSrc;
                    img.onload = function() {
                        const width = img.width * 0.5; // 50% de la largeur originale
                        const height = img.height * 0.5; // 50% de la hauteur originale

                        const newSticker = {
                            src: base64StickerSrc,
                            originalSrc: stickerSrc,
                            x: canvas.width - width - 10, // Position proche du bord droit avec marge de 10px
                            y: canvas.height - height - 10, // Position proche du bord bas avec marge de 10px
                            width: width,
                            height: height
                        };
                        selectedStickers.push(newSticker);
                        sticker.classList.add('sticker-selected'); // Ajouter l'effet visuel

                        // Redessiner les stickers
                        redrawStickers();
                    };
                } else {
                    alert("Erreur lors du chargement du sticker.");
                }
            });
        }
    });
});

// Fonction pour redessiner les stickers sur le canvas
function redrawStickers() {
    // Effacer le canvas
    context.clearRect(0, 0, canvas.width, canvas.height);

    // Redessiner la photo capturée
    const img = new Image();
    img.src = photoInput.value; // Base64 de la photo capturée/uploadée
    img.onload = () => {
        context.drawImage(img, 0, 0, canvas.width, canvas.height);

        // Dessiner chaque sticker sélectionné
        selectedStickers.forEach(sticker => {
            const stickerImg = new Image();
            stickerImg.src = sticker.src; // Maintenant en base64
            stickerImg.onload = () => {
                const x = Math.min(sticker.x, canvas.width - sticker.width - 10);
                const y = Math.min(sticker.y, canvas.height - sticker.height - 10);

                context.drawImage(stickerImg, x, y, sticker.width, sticker.height);
            };
        });
    };
}

// Gérer la soumission du formulaire
document.getElementById('photoForm').addEventListener('submit', function (event) {
    // Vérifier que la photo est capturée avant de soumettre
    if (!photoCaptured) {
        event.preventDefault(); // Empêcher la soumission
        alert("Veuillez capturer ou uploader une photo avant de soumettre.");
        return;
    }

    // Ajouter les stickers sélectionnés au formulaire uniquement s'ils existent
    if (selectedStickers.length > 0) {
        const stickersData = selectedStickers.map(sticker => ({
            src: sticker.src,
            x: sticker.x,
            y: sticker.y,
            width: sticker.width,
            height: sticker.height
        }));

        const stickersInput = document.createElement('input');
        stickersInput.type = 'hidden';
        stickersInput.name = 'stickers';
        stickersInput.value = JSON.stringify(stickersData); // Convertir les stickers en JSON
        this.appendChild(stickersInput);
    }

    // Debug : Afficher les données soumises dans la console
    console.log({
        photo: photoInput.value, // La photo capturée en base64
        stickers: selectedStickers // Les stickers sous forme de tableau
    });
});

function confirmDeletion(element) {
    // Obtenir l'ID du post depuis l'attribut data-id
    const postId = element.getAttribute('data-id');
    
    // Obtenir le jeton CSRF depuis l'input caché
    const csrfToken = element.querySelector('input[name="csrf_token"]').value;
    
    // Afficher une boîte de confirmation
    const confirmation = confirm("Êtes-vous sûr de vouloir supprimer ce post ?");
    
    if (confirmation) {
        // Envoyer une requête POST pour supprimer le post
        fetch('/post/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: postId,
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer l'élément du DOM
                const postCard = element.closest('.card.mb-2');
                if (postCard) {
                    postCard.remove();
                }
                alert("Post supprimé avec succès !");
            } else {
                alert("Erreur lors de la suppression : " + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression du post:', error);
            alert("Une erreur est survenue lors de la suppression du post.");
        });
    }
}