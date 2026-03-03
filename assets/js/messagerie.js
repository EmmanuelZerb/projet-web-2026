/**
 * ECE In - Messagerie + Appels Audio/Vidéo
 * Chat temps réel via polling AJAX
 * Appels via WebRTC (PeerJS)
 */

$(document).ready(function () {

    // ===================== SYSTÈME D'APPELS (WebRTC + PeerJS) =====================

    let peer = null;
    let currentCall = null;
    let localStream = null;
    let callTimerInterval = null;
    let callSeconds = 0;
    let currentCallId = null;
    let currentCallType = null;
    let isMuted = false;
    let isCameraOff = false;
    let callPollingInterval = null;
    let callStatusInterval = null;
    let ringTimeout = null;
    let audioCtx = null;

    if (typeof USER_ID !== 'undefined' && typeof Peer !== 'undefined') {
        initPeer();
        startCallPolling();
    }

    function initPeer() {
        try {
            peer = new Peer('ecein_' + USER_ID, {
                debug: 0,
                config: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                }
            });

            peer.on('open', function () {
                console.log('[ECE In] Connecté au serveur d\'appels');
            });

            peer.on('call', function (call) {
                window._incomingPeerCall = call;
            });

            peer.on('disconnected', function () {
                if (peer && !peer.destroyed) peer.reconnect();
            });

            peer.on('error', function (err) {
                console.warn('[ECE In] PeerJS:', err.type);
                if (err.type === 'peer-unavailable') {
                    afficherToast('L\'utilisateur n\'est pas joignable actuellement.', 'warning');
                    terminerAppelUI();
                } else if (err.type === 'unavailable-id') {
                    setTimeout(function () {
                        if (peer) peer.destroy();
                        peer = new Peer('ecein_' + USER_ID + '_' + Date.now() % 10000, { debug: 0 });
                    }, 2000);
                }
            });
        } catch (e) {
            console.warn('[ECE In] PeerJS non disponible');
        }
    }

    function startCallPolling() {
        callPollingInterval = setInterval(function () {
            if (currentCallId) return;
            $.get('api/calls.php', { action: 'verifier' }, function (res) {
                if (res.succes && res.appel) {
                    afficherAppelEntrant(res.appel);
                }
            });
        }, 2000);
    }

    // ===== LANCER UN APPEL =====

    window.lancerAppel = function (type) {
        if (!peer || peer.disconnected) {
            afficherToast('Connexion au serveur d\'appels en cours, réessayez.', 'warning');
            return;
        }
        if (!CONTACT_ID || currentCallId) return;

        currentCallType = type;
        showCallOverlay('outgoing', type);

        $.post('api/calls.php', {
            action: 'initier',
            conversation_id: CONV_ID,
            receveur_id: CONTACT_ID,
            type: type
        }, function (res) {
            if (res.succes) {
                currentCallId = res.appel_id;
                startRingtone();

                callStatusInterval = setInterval(function () {
                    $.get('api/calls.php', { action: 'statut', appel_id: currentCallId }, function (r) {
                        if (!r.succes) return;
                        if (r.statut === 'en_cours') {
                            clearInterval(callStatusInterval);
                            callStatusInterval = null;
                            stopRingtone();
                            demarrerAppelWebRTC(type, true);
                        } else if (r.statut === 'refuse' || r.statut === 'termine' || r.statut === 'manque') {
                            clearInterval(callStatusInterval);
                            callStatusInterval = null;
                            stopRingtone();
                            afficherToast('Appel refusé ou non répondu.', 'info');
                            terminerAppelUI();
                        }
                    });
                }, 1500);

                setTimeout(function () {
                    if (callStatusInterval) {
                        clearInterval(callStatusInterval);
                        callStatusInterval = null;
                        if (currentCallId) {
                            $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
                            stopRingtone();
                            afficherToast('Pas de réponse.', 'info');
                            terminerAppelUI();
                        }
                    }
                }, 30000);
            } else {
                afficherToast(res.message || 'Erreur lors de l\'appel.', 'danger');
                terminerAppelUI();
            }
        });
    };

    // ===== APPEL ENTRANT =====

    function afficherAppelEntrant(appel) {
        if (currentCallId) return;
        currentCallId = appel.id;
        currentCallType = appel.type;

        $('#call-avatar').attr('src', appel.photo || 'assets/images/default_avatar.png');
        $('#call-name').text(appel.prenom + ' ' + appel.nom);
        showCallOverlay('incoming', appel.type);
        startRingtone();
    }

    window.accepterAppel = function () {
        if (!currentCallId) return;
        stopRingtone();

        $.post('api/calls.php', { action: 'accepter', appel_id: currentCallId }, function (res) {
            if (res.succes) {
                demarrerAppelWebRTC(currentCallType, false);
            }
        });
    };

    window.refuserAppel = function () {
        if (!currentCallId) return;
        stopRingtone();
        $.post('api/calls.php', { action: 'refuser', appel_id: currentCallId });
        terminerAppelUI();
    };

    window.annulerAppel = function () {
        if (!currentCallId) return;
        stopRingtone();
        if (callStatusInterval) {
            clearInterval(callStatusInterval);
            callStatusInterval = null;
        }
        $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        terminerAppelUI();
    };

    window.terminerAppel = function () {
        if (!currentCallId) return;
        $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        if (currentCall) currentCall.close();
        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        terminerAppelUI();
    };

    // ===== WEBRTC =====

    function demarrerAppelWebRTC(type, isInitiator) {
        var constraints = { audio: true, video: type === 'video' };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (stream) {
                localStream = stream;
                showCallOverlay('active', type);
                startCallTimer();

                if (type === 'video') {
                    var localVideo = document.getElementById('local-video');
                    localVideo.srcObject = stream;
                    localVideo.style.display = 'block';
                }

                if (isInitiator) {
                    var remotePeerId = 'ecein_' + CONTACT_ID;
                    currentCall = peer.call(remotePeerId, stream);
                    if (currentCall) {
                        handleCallStream(currentCall, type);
                    } else {
                        afficherToast('Impossible de joindre l\'utilisateur.', 'warning');
                        terminerAppelCleanup();
                    }
                } else {
                    if (window._incomingPeerCall) {
                        window._incomingPeerCall.answer(stream);
                        currentCall = window._incomingPeerCall;
                        handleCallStream(currentCall, type);
                        window._incomingPeerCall = null;
                    } else {
                        // Fallback: wait briefly for peer call
                        var waitCount = 0;
                        var waitPeer = setInterval(function () {
                            waitCount++;
                            if (window._incomingPeerCall) {
                                clearInterval(waitPeer);
                                window._incomingPeerCall.answer(stream);
                                currentCall = window._incomingPeerCall;
                                handleCallStream(currentCall, type);
                                window._incomingPeerCall = null;
                            } else if (waitCount > 10) {
                                clearInterval(waitPeer);
                                afficherToast('Connexion audio/vidéo en attente...', 'info');
                            }
                        }, 500);
                    }
                }
            })
            .catch(function (err) {
                console.error('[ECE In] Erreur média:', err);
                afficherToast('Impossible d\'accéder au micro/caméra. Vérifiez les permissions du navigateur.', 'danger');
                if (currentCallId) {
                    $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
                }
                terminerAppelUI();
            });
    }

    function handleCallStream(call, type) {
        call.on('stream', function (remoteStream) {
            if (type === 'video') {
                var remoteVideo = document.getElementById('remote-video');
                remoteVideo.srcObject = remoteStream;
                remoteVideo.style.display = 'block';
                $('#call-info').addClass('call-info-mini');
            } else {
                var audioEl = document.getElementById('remote-audio');
                if (!audioEl) {
                    audioEl = document.createElement('audio');
                    audioEl.id = 'remote-audio';
                    audioEl.autoplay = true;
                    document.body.appendChild(audioEl);
                }
                audioEl.srcObject = remoteStream;
            }
        });

        call.on('close', function () {
            afficherToast('Appel terminé.', 'info');
            terminerAppelCleanup();
        });

        call.on('error', function () {
            afficherToast('Erreur durant l\'appel.', 'danger');
            terminerAppelCleanup();
        });
    }

    function terminerAppelCleanup() {
        if (currentCallId) {
            $.post('api/calls.php', { action: 'terminer', appel_id: currentCallId });
        }
        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        terminerAppelUI();
    }

    // ===== CONTRÔLES D'APPEL =====

    window.toggleMute = function () {
        if (!localStream) return;
        isMuted = !isMuted;
        localStream.getAudioTracks().forEach(function (t) { t.enabled = !isMuted; });
        var $btn = $('#btn-mute');
        $btn.toggleClass('active', isMuted);
        $btn.find('i').attr('class', isMuted ? 'bi bi-mic-mute-fill' : 'bi bi-mic-fill');
    };

    window.toggleCamera = function () {
        if (!localStream) return;
        isCameraOff = !isCameraOff;
        localStream.getVideoTracks().forEach(function (t) { t.enabled = !isCameraOff; });
        var $btn = $('#btn-camera');
        $btn.toggleClass('active', isCameraOff);
        $btn.find('i').attr('class', isCameraOff ? 'bi bi-camera-video-off-fill' : 'bi bi-camera-video-fill');
        document.getElementById('local-video').style.display = isCameraOff ? 'none' : 'block';
    };

    // ===== UI APPELS =====

    function showCallOverlay(mode, type) {
        var $overlay = $('#call-overlay');
        $overlay.fadeIn(200);

        if (mode !== 'incoming' && typeof CONTACT_NAME !== 'undefined') {
            $('#call-avatar').attr('src', CONTACT_PHOTO || 'assets/images/default_avatar.png');
            $('#call-name').text(CONTACT_NAME);
        }

        $('#call-controls-active, #call-controls-incoming, #call-controls-outgoing').hide();

        if (mode === 'incoming') {
            $('#call-status').text(type === 'video' ? 'Appel vidéo entrant...' : 'Appel audio entrant...');
            $('#call-controls-incoming').show();
            $overlay.addClass('call-ringing');
        } else if (mode === 'outgoing') {
            $('#call-status').text('Appel en cours...');
            $('#call-controls-outgoing').show();
            $overlay.removeClass('call-ringing');
        } else if (mode === 'active') {
            $('#call-status').text('En communication');
            $('#call-timer').show();
            $('#call-controls-active').show();
            $overlay.removeClass('call-ringing');
            if (type === 'video') {
                $('#btn-camera').show();
            }
        }
    }

    function terminerAppelUI() {
        stopCallTimer();
        stopRingtone();

        if (localStream) {
            localStream.getTracks().forEach(function (t) { t.stop(); });
            localStream = null;
        }
        if (currentCall) {
            currentCall.close();
            currentCall = null;
        }

        currentCallId = null;
        currentCallType = null;
        isMuted = false;
        isCameraOff = false;

        if (callStatusInterval) {
            clearInterval(callStatusInterval);
            callStatusInterval = null;
        }

        var $overlay = $('#call-overlay');
        $overlay.fadeOut(200).removeClass('call-ringing');
        $('#call-timer').hide().text('00:00');
        $('#call-status').text('');
        $('#remote-video, #local-video').hide();

        var rv = document.getElementById('remote-video');
        var lv = document.getElementById('local-video');
        if (rv) rv.srcObject = null;
        if (lv) lv.srcObject = null;

        $('#call-info').removeClass('call-info-mini');
        $('#btn-mute').removeClass('active').find('i').attr('class', 'bi bi-mic-fill');
        $('#btn-camera').removeClass('active').hide().find('i').attr('class', 'bi bi-camera-video-fill');

        var remoteAudio = document.getElementById('remote-audio');
        if (remoteAudio) remoteAudio.remove();
    }

    // ===== TIMER =====

    function startCallTimer() {
        callSeconds = 0;
        updateTimerDisplay();
        callTimerInterval = setInterval(function () {
            callSeconds++;
            updateTimerDisplay();
        }, 1000);
    }

    function stopCallTimer() {
        if (callTimerInterval) {
            clearInterval(callTimerInterval);
            callTimerInterval = null;
        }
        callSeconds = 0;
    }

    function updateTimerDisplay() {
        var m = String(Math.floor(callSeconds / 60)).padStart(2, '0');
        var s = String(callSeconds % 60).padStart(2, '0');
        $('#call-timer').text(m + ':' + s);
    }

    // ===== SONNERIE =====

    function startRingtone() {
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            playRingTone();
        } catch (e) {
            // Audio API not available
        }
    }

    function playRingTone() {
        if (!audioCtx || audioCtx.state === 'closed') return;

        var now = audioCtx.currentTime;

        function beep(startTime, freq, duration) {
            var osc = audioCtx.createOscillator();
            var gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.12, startTime);
            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
            osc.start(startTime);
            osc.stop(startTime + duration);
        }

        beep(now, 440, 0.25);
        beep(now + 0.35, 520, 0.25);

        ringTimeout = setTimeout(function () {
            if (audioCtx && currentCallId) {
                playRingTone();
            }
        }, 2000);
    }

    function stopRingtone() {
        if (ringTimeout) {
            clearTimeout(ringTimeout);
            ringTimeout = null;
        }
        if (audioCtx) {
            try { audioCtx.close(); } catch (e) {}
            audioCtx = null;
        }
    }

    // ===================== MESSAGERIE (CHAT) =====================

    if (typeof CONV_ID === 'undefined' || CONV_ID === 0) return;

    scrollBas();

    var dernierMsgId = getDernierMsgId();

    // ===== ENVOI DE MESSAGES =====

    $('#formMessage').on('submit', function (e) {
        e.preventDefault();
        var $input  = $('#input-message');
        var contenu = $input.val().trim();
        if (!contenu) return;

        var $btn = $('#btn-envoyer');
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
                dernierMsgId = parseInt(res.message.id);
            } else {
                afficherToast(res.message, 'danger');
            }
        }).always(function () {
            $btn.prop('disabled', false);
            $input.focus();
        });
    });

    $('#input-message').on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#formMessage').submit();
        }
    });

    // ===== POLLING (nouveaux messages) =====

    var intervalPolling = setInterval(function () {
        $.get('api/messages.php', {
            action:          'polling',
            conversation_id: CONV_ID,
            dernier_id:      dernierMsgId,
        }, function (res) {
            if (res.succes && res.messages.length > 0) {
                res.messages.forEach(function (msg) {
                    if (msg.expediteur_id != USER_ID) {
                        ajouterMessage(msg);
                    }
                    dernierMsgId = Math.max(dernierMsgId, parseInt(msg.id));
                });
                scrollBas();
            }
        });
    }, 3000);

    $(window).on('beforeunload', function () {
        clearInterval(intervalPolling);
        if (callPollingInterval) clearInterval(callPollingInterval);
        if (currentCallId) {
            navigator.sendBeacon('api/calls.php', new URLSearchParams({
                action: 'terminer',
                appel_id: currentCallId
            }));
        }
    });

    // ===== FONCTIONS UTILITAIRES CHAT =====

    function ajouterMessage(msg) {
        var estMoi = msg.expediteur_id == USER_ID;
        var html = '<div class="message-wrapper ' + (estMoi ? 'message-moi' : 'message-autre') + ' mb-3" data-msg-id="' + msg.id + '">';

        if (!estMoi) {
            html += '<img src="' + escapeHtml(msg.avatar) + '" alt="" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover">';
        }

        html += '<div class="message-bulle ' + (estMoi ? 'message-bubble-moi' : 'message-bubble-autre') + '">';
        html += '<div>' + escapeHtml(msg.contenu) + '</div>';
        html += '<div class="message-time">' + tempsEcoule(msg.date_envoi) + '</div>';
        html += '</div>';

        if (estMoi) {
            html += '<img src="' + escapeHtml(msg.avatar) + '" alt="" class="rounded-circle ms-2" width="32" height="32" style="object-fit:cover">';
        }

        html += '</div>';

        var $chatMessages = $('#chat-messages');
        $chatMessages.find('.text-muted').closest('div.text-center').remove();
        $chatMessages.append(html);
    }

    function scrollBas() {
        var el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    function getDernierMsgId() {
        var maxId = 0;
        document.querySelectorAll('#chat-messages .message-wrapper[data-msg-id]').forEach(function (el) {
            var id = parseInt(el.getAttribute('data-msg-id'));
            if (id > maxId) maxId = id;
        });
        return maxId;
    }

});
