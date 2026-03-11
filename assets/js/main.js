/**
 * ECE In - Script JavaScript principal
 * Script JS chargé sur toutes les pages - gère les interactions AJAX globales
 * Utilise jQuery 3.7.1 + Bootstrap 5
 * Projet Web ING2 2026
 */

$(document).ready(function () {

    // ===================== GESTION DES PUBLICATIONS =====================

    /**
     * Afficher/masquer la zone d'upload selon le type de publication
     */
    $('input[name="type_post"]').on('change', function () {
        const type = $(this).val();
        const zoneUpload = $('#zone-upload');
        if (type === 'photo' || type === 'video' || type === 'cv') {
            zoneUpload.slideDown(200);
            $('#input-fichier').attr('accept', getAcceptType(type));
        } else {
            zoneUpload.slideUp(200);
        }
        $('#post-type').val(type);
    });

    function getAcceptType(type) {
        if (type === 'photo')  return 'image/jpeg,image/png,image/gif,image/webp';
        if (type === 'video')  return 'video/mp4,video/webm,video/ogg';
        if (type === 'cv')     return 'application/pdf,.xml';
        return '';
    }

    // Ouvrir le bon type si on clique sur les boutons de la zone de publication
    $('[data-bs-target="#modalPublier"][data-type]').on('click', function () {
        const type = $(this).data('type');
        $(`#type-${type}`).prop('checked', true).trigger('change');
    });

    // ===================== ENVOI DU FORMULAIRE DE PUBLICATION =====================

    $('#formPublier').on('submit', function (e) {
        e.preventDefault();
        const $btn    = $(this).find('[type="submit"]');
        const formData = new FormData(this);
        formData.set('action', 'publier');
        formData.set('type', $('input[name="type_post"]:checked').val() || 'statut');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Publication...');

        $.ajax({
            url:         'api/posts.php',
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.succes) {
                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('modalPublier')).hide();
                    // Recharger la page pour afficher le post
                    location.reload();
                } else {
                    afficherToast(res.message, 'danger');
                }
            },
            error: function () {
                afficherToast('Erreur lors de la publication.', 'danger');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-send me-2"></i>Publier');
            },
        });
    });

    // ===================== APERÇU D'IMAGE AVANT UPLOAD =====================

    $('#input-fichier').on('change', function () {
        const file = this.files[0];
        const preview = $('#preview-fichier');
        preview.empty();

        if (!file) return;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.html(`<img src="${e.target.result}" class="img-fluid rounded" style="max-height:200px">`);
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            preview.html(`<p class="text-success"><i class="bi bi-camera-video me-1"></i>${escapeHtml(file.name)}</p>`);
        } else {
            preview.html(`<p class="text-info"><i class="bi bi-file-earmark me-1"></i>${escapeHtml(file.name)}</p>`);
        }
    });

    // Drag & drop zone
    const $dropzone = $('#dropzone');
    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        if (e.type === 'drop') {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('input-fichier').files = files;
                $('#input-fichier').trigger('change');
            }
        }
    });

});

// ===================== RÉAGIR À UN POST (AJAX) =====================
// Toggle de réaction via AJAX sans rechargement de page

function reagir(postId, typeReaction, btn) {
    $.post('api/posts.php', {
        action:   'reagir',
        post_id:  postId,
        type:     typeReaction,
    }, function (res) {
        if (res.succes) {
            const $btn = $(btn);
            const $icon = $btn.find('i');

            if (res.etat === 'ajoute') {
                $btn.addClass('active text-primary');
                $icon.removeClass('bi-hand-thumbs-up').addClass('bi-hand-thumbs-up-fill');
            } else if (res.etat === 'retire') {
                $btn.removeClass('active text-primary');
                $icon.removeClass('bi-hand-thumbs-up-fill').addClass('bi-hand-thumbs-up');
            }

            // Mettre à jour le compteur dans le post
            const $compteur = $btn.closest('.post-card').find('.reaction-emoji').parent();
            if ($compteur.length && res.nb_reactions > 0) {
                $compteur.html(`<span class="reaction-emoji">👍</span> ${res.nb_reactions} réaction${res.nb_reactions > 1 ? 's' : ''}`);
            }
        }
    });
}

// ===================== AFFICHER / MASQUER COMMENTAIRES =====================

function afficherCommentaires(postId) {
    const $section = $(`#commentaires-${postId}`);

    if ($section.is(':visible')) {
        $section.slideUp(200);
        return;
    }

    // Charger les commentaires via AJAX
    $.get('api/posts.php', { action: 'charger_commentaires', post_id: postId }, function (res) {
        if (res.succes) {
            const $liste = $(`#liste-commentaires-${postId}`);
            $liste.empty();

            if (res.commentaires.length === 0) {
                $liste.html('<p class="text-muted small text-center">Aucun commentaire. Soyez le premier !</p>');
            } else {
                res.commentaires.forEach(function (c) {
                    $liste.append(creerHTMLCommentaire(c));
                });
            }

            $section.slideDown(200);
        }
    });
}

function creerHTMLCommentaire(c) {
    return `
    <div class="commentaire-item d-flex gap-2 mb-2" id="commentaire-${c.id}">
        <img src="${escapeHtml(c.avatar)}" alt="" class="rounded-circle flex-shrink-0"
             width="32" height="32" style="object-fit:cover">
        <div class="commentaire-bulle">
            <div class="auteur">${escapeHtml(c.prenom + ' ' + c.nom)}</div>
            <div class="contenu">${escapeHtml(c.contenu)}</div>
            <div class="temps">${tempsEcoule(c.date_commentaire)}</div>
        </div>
    </div>`;
}

// ===================== ENVOYER UN COMMENTAIRE =====================
// Envoi de commentaires via AJAX

function envoyerCommentaire(postId) {
    const $input   = $(`#input-commentaire-${postId}`);
    const contenu  = $input.val().trim();
    if (!contenu) return;

    $.post('api/posts.php', {
        action:  'commenter',
        post_id: postId,
        contenu: contenu,
    }, function (res) {
        if (res.succes) {
            $input.val('');
            const $liste = $(`#liste-commentaires-${postId}`);
            $liste.find('.text-muted').remove(); // retirer "aucun commentaire"
            $liste.append(creerHTMLCommentaire(res.commentaire));

            // Mettre à jour le compteur
            const $post = $(`#post-${postId}`);
            let $compteur = $post.find('[onclick*="afficherCommentaires"]').first();
            if ($compteur.length) {
                const actuel = parseInt($compteur.text()) || 0;
                const newCount = actuel + 1;
                $compteur.text(`${newCount} commentaire${newCount > 1 ? 's' : ''}`);
            }
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== PARTAGER UN POST =====================

function partager(postId) {
    const $post = $(`#post-${postId}`);
    const auteur = $post.find('.fw-semibold.text-dark').first().text().trim() || 'un membre';
    const extrait = $post.find('.post-contenu').first().text().trim();
    const avatar = $post.find('.rounded-circle').first().attr('src') || '';
    const preview = extrait ? (extrait.length > 120 ? extrait.substring(0, 120) + '...' : extrait) : '';

    $('#partage-post-id').val(postId);
    $('#partage-commentaire').val('');
    $('#partage-preview-avatar').attr('src', avatar);
    $('#partage-preview-auteur').text(auteur);
    $('#partage-preview-contenu').text(preview || 'Publication sans texte');

    const modal = new bootstrap.Modal(document.getElementById('modalPartager'));
    modal.show();

    $('#partage-commentaire').focus();
}

function envoyerPartage() {
    const postId = $('#partage-post-id').val();
    const contenu = $('#partage-commentaire').val().trim();
    const $btn = $('#btn-partager');

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Partage...');

    $.post('api/posts.php', {
        action:  'partager',
        post_id: postId,
        contenu: contenu,
    }, function (res) {
        if (res.succes) {
            bootstrap.Modal.getInstance(document.getElementById('modalPartager')).hide();
            afficherToast('Publication partagée !', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            afficherToast(res.message, 'danger');
        }
    }).always(function () {
        $btn.prop('disabled', false).html('<i class="bi bi-share me-2"></i>Partager');
    });
}

// ===================== SUPPRIMER UN POST =====================

function supprimerPost(postId) {
    if (!confirm('Supprimer cette publication définitivement ?')) return;

    $.post('api/posts.php', {
        action:  'supprimer',
        post_id: postId,
    }, function (res) {
        if (res.succes) {
            $(`#post-${postId}`).fadeOut(400, function () { $(this).remove(); });
            afficherToast('Publication supprimée.', 'success');
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== ENVOYER DEMANDE D'AMI =====================
// Envoi / acceptation de demandes d'ami via AJAX

function envoyerDemande(userId, btn) {
    const $btn = $(btn);
    $btn.prop('disabled', true);

    $.post('api/amis.php', {
        action:           'envoyer_demande',
        destinataire_id:  userId,
    }, function (res) {
        if (res.succes) {
            $btn.html('<i class="bi bi-check2 me-1"></i>Invitation envoyée').removeClass('btn-outline-primary').addClass('btn-success');
            afficherToast(res.message, 'success');
        } else {
            $btn.prop('disabled', false);
            afficherToast(res.message, 'warning');
        }
    });
}

// ===================== RÉPONDRE À UNE INVITATION =====================

function repondreInvitation(connexionId, reponse, btn) {
    const $btn = $(btn);
    $btn.prop('disabled', true);

    $.post('api/amis.php', {
        action:       'repondre',
        connexion_id: connexionId,
        reponse:      reponse,
    }, function (res) {
        if (res.succes) {
            $(`#demande-${connexionId}`).fadeOut(400, function () { $(this).remove(); });
            afficherToast(res.message, 'success');
        } else {
            $btn.prop('disabled', false);
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== ANNULER UNE INVITATION =====================

function annulerInvitation(connexionId, btn) {
    if (!confirm('Annuler cette invitation ?')) return;
    const $btn = $(btn);

    $.post('api/amis.php', {
        action:       'annuler',
        connexion_id: connexionId,
    }, function (res) {
        if (res.succes) {
            $btn.closest('.col-sm-6').fadeOut(300, function () { $(this).remove(); });
            afficherToast('Invitation annulée.', 'info');
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== SUPPRIMER UNE FORMATION =====================

function supprimerFormation(id, btn) {
    if (!confirm('Supprimer cette formation ?')) return;
    $.post('api/profil.php', { action: 'supprimer_formation', id: id }, function (res) {
        if (res.succes) {
            $(btn).closest('.timeline-item').fadeOut(300);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== SUPPRIMER UN PROJET =====================

function supprimerProjet(id, btn) {
    if (!confirm('Supprimer ce projet ?')) return;
    $.post('api/profil.php', { action: 'supprimer_projet', id: id }, function (res) {
        if (res.succes) {
            $(btn).closest('.col-md-6').fadeOut(300);
        } else {
            afficherToast(res.message, 'danger');
        }
    });
}

// ===================== OUVRIR MEDIA EN PLEIN ÉCRAN =====================

function ouvrirMedia(src) {
    const modal = `
    <div class="modal fade" id="modalMedia" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-black">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img src="${escapeHtml(src)}" class="img-fluid" style="max-height:80vh">
                </div>
            </div>
        </div>
    </div>`;

    $('#modalMedia').remove();
    $('body').append(modal);
    const m = new bootstrap.Modal(document.getElementById('modalMedia'));
    m.show();
    document.getElementById('modalMedia').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

// ===================== POLLING NOTIFICATIONS =====================

// Vérifier les nouvelles notifications toutes les 30 secondes
setInterval(function () {
    $.get('api/notifications.php', { action: 'compter' }, function (res) {
        if (res.succes && res.count > 0) {
            const $badge = $('.nav-icon-link[href="notifications.php"] .badge-notif');
            if ($badge.length) {
                $badge.text(res.count);
            } else {
                $('.nav-icon-link[href="notifications.php"]').append(
                    `<span class="badge-notif">${res.count}</span>`
                );
            }
        }
    });
}, 30000);

// ===================== UTILITAIRES =====================
// Fonctions helper réutilisées partout

// Système de notifications visuelles (toasts Bootstrap)
/**
 * Affiche un toast Bootstrap
 * @param {string} message
 * @param {string} type (success, danger, warning, info)
 */
function afficherToast(message, type = 'info') {
    const id     = 'toast-' + Date.now();
    const icons  = { success: 'check-circle-fill', danger: 'exclamation-triangle-fill', warning: 'exclamation-circle-fill', info: 'info-circle-fill' };
    const icon   = icons[type] || 'info-circle-fill';

    const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${icon} me-2"></i>${escapeHtml(message)}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;

    let $container = $('#toast-container');
    if (!$container.length) {
        $container = $('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>');
        $('body').append($container);
    }

    $container.append(html);

    // Auto-supprimer après 4 secondes
    setTimeout(() => $(`#${id}`).fadeOut(300, function () { $(this).remove(); }), 4000);
}

/**
 * Échappe les caractères HTML dangereux
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Calcule le temps écoulé (version JS)
 * @param {string} dateStr
 * @returns {string}
 */
function tempsEcoule(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)    return 'À l\'instant';
    if (diff < 3600)  return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    if (diff < 604800) return Math.floor(diff / 86400) + ' j';
    return new Date(dateStr).toLocaleDateString('fr-FR');
}
