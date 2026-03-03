/**
 * ECE In - Script messagerie
 * Gestion du chat en temps réel via polling AJAX
 */

$(document).ready(function () {

    if (typeof CONV_ID === 'undefined' || CONV_ID === 0) return;

    // Scroll vers le bas au chargement
    scrollBas();

    // Enregistrer l'ID du dernier message
    let dernierMsgId = getDernierMsgId();

    // ===================== ENVOI DE MESSAGES =====================

    $('#formMessage').on('submit', function (e) {
        e.preventDefault();
        const $input  = $('#input-message');
        const contenu = $input.val().trim();
        if (!contenu) return;

        const $btn = $('#btn-envoyer');
        $btn.prop('disabled', true);

        $.post('api/messages.php', {
            action:          'envoyer',
            conversation_id: CONV_ID,
            contenu:         contenu,
        }, function (res) {
            if (res.succes) {
                $input.val('');
                ajouterMessage(res.message);
                scrollBas();
                dernierMsgId = res.message.id;
            } else {
                afficherToast(res.message, 'danger');
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // Envoyer avec Entrée, Shift+Entrée pour nouvelle ligne
    $('#input-message').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#formMessage').submit();
        }
    });

    // ===================== POLLING (nouveaux messages) =====================

    const intervalPolling = setInterval(function () {
        $.get('api/messages.php', {
            action:          'polling',
            conversation_id: CONV_ID,
            dernier_id:      dernierMsgId,
        }, function (res) {
            if (res.succes && res.messages.length > 0) {
                res.messages.forEach(function (msg) {
                    // Ne pas ajouter si c'est un message qu'on vient d'envoyer
                    if (msg.expediteur_id != USER_ID) {
                        ajouterMessage(msg);
                    }
                    dernierMsgId = Math.max(dernierMsgId, msg.id);
                });
                scrollBas();
            }
        });
    }, 3000); // Toutes les 3 secondes

    // Arrêter le polling quand on quitte la page
    $(window).on('beforeunload', function () {
        clearInterval(intervalPolling);
    });

    // ===================== FONCTIONS UTILITAIRES =====================

    /**
     * Ajoute un message dans le chat
     */
    function ajouterMessage(msg) {
        const estMoi = msg.expediteur_id == USER_ID;
        const html = `
        <div class="message-wrapper ${estMoi ? 'message-moi' : 'message-autre'} mb-3">
            ${!estMoi ? `<img src="${escapeHtml(msg.avatar)}" alt="" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover">` : ''}
            <div class="message-bulle ${estMoi ? 'message-bubble-moi' : 'message-bubble-autre'}">
                <div>${escapeHtml(msg.contenu)}</div>
                <div class="message-time">${tempsEcoule(msg.date_envoi)}</div>
            </div>
            ${estMoi ? `<img src="${escapeHtml(msg.avatar)}" alt="" class="rounded-circle ms-2" width="32" height="32" style="object-fit:cover">` : ''}
        </div>`;

        const $chatMessages = $('#chat-messages');
        // Retirer le message "démarrer la conversation" si présent
        $chatMessages.find('.text-muted').closest('div.text-center').remove();
        $chatMessages.append(html);
    }

    /**
     * Scroll vers le bas du chat
     */
    function scrollBas() {
        const el = document.getElementById('chat-messages');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    /**
     * Récupère l'ID du dernier message affiché
     */
    function getDernierMsgId() {
        const messages = document.querySelectorAll('#chat-messages .message-wrapper');
        if (!messages.length) return 0;
        // L'ID est dans l'attribut data ou on utilise 0 comme point de départ
        return 0; // Le polling récupèrera uniquement les nouveaux
    }

});
