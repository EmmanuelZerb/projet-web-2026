<?php
/**
 * ECE In - Page Messagerie
 * Page de messagerie privée avec chat en temps réel et appels audio/vidéo (WebRTC)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$pageTitle   = 'Messagerie';
$pageScript  = 'messagerie.js';

// Si on arrive via ?contact=X, on crée ou récupère la conversation avec cette personne
$contactId = (int) ($_GET['contact'] ?? 0);
if ($contactId > 0 && $contactId !== $userId) {
    // Vérifier si une conversation 1-à-1 existe déjà
    $stmtCheck = $pdo->prepare("
        SELECT c.id FROM conversations c
        JOIN conversation_participants p1 ON p1.conversation_id = c.id AND p1.utilisateur_id = :uid
        JOIN conversation_participants p2 ON p2.conversation_id = c.id AND p2.utilisateur_id = :cid
        WHERE c.est_groupe = 0
        LIMIT 1
    ");
    $stmtCheck->execute([':uid' => $userId, ':cid' => $contactId]);
    $convExistante = $stmtCheck->fetchColumn();

    if (!$convExistante) {
        // Créer la conversation
        $pdo->prepare("INSERT INTO conversations (est_groupe) VALUES (0)")->execute();
        $convId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO conversation_participants (conversation_id, utilisateur_id) VALUES (?, ?), (?, ?)")
            ->execute([$convId, $userId, $convId, $contactId]);
        $convActiveId = $convId;
    } else {
        $convActiveId = $convExistante;
    }
}

// Conversation active
$convActiveId = $convActiveId ?? (int) ($_GET['conv'] ?? 0);

// Requête un peu complexe avec plein de sous-requêtes : dernier message, nb de non-lus, infos du contact pour chaque conversation
$stmtConvs = $pdo->prepare("
    SELECT c.id, c.nom, c.est_groupe, c.date_creation,
        (
            SELECT m.contenu FROM messages m WHERE m.conversation_id = c.id
            ORDER BY m.date_envoi DESC LIMIT 1
        ) AS dernier_message,
        (
            SELECT m.date_envoi FROM messages m WHERE m.conversation_id = c.id
            ORDER BY m.date_envoi DESC LIMIT 1
        ) AS date_dernier_message,
        (
            SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id
            AND m.expediteur_id != :uid AND m.lu = 0
        ) AS nb_non_lus,
        (
            SELECT u2.prenom FROM conversation_participants p2
            JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id
            WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid2
            LIMIT 1
        ) AS contact_prenom,
        (
            SELECT u2.nom FROM conversation_participants p2
            JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id
            WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid3
            LIMIT 1
        ) AS contact_nom,
        (
            SELECT u2.photo FROM conversation_participants p2
            JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id
            WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid4
            LIMIT 1
        ) AS contact_photo,
        (
            SELECT u2.id FROM conversation_participants p2
            JOIN utilisateurs u2 ON u2.id = p2.utilisateur_id
            WHERE p2.conversation_id = c.id AND p2.utilisateur_id != :uid5
            LIMIT 1
        ) AS contact_id
    FROM conversations c
    JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.utilisateur_id = :uid6
    ORDER BY date_dernier_message DESC, c.date_creation DESC
");
$stmtConvs->execute([
    ':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId,
    ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId,
]);
$conversations = $stmtConvs->fetchAll();

// On vérifie d'abord que l'user fait partie de la conversation (sécurité), puis on charge les messages et on les marque comme lus
$messages = [];
$contactActif = null;
if ($convActiveId > 0) {
    // Vérifier que l'utilisateur fait partie de cette conversation
    $stmtVerif = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?");
    $stmtVerif->execute([$convActiveId, $userId]);
    if ($stmtVerif->fetchColumn()) {
        // Récupérer les messages
        $stmtMsgs = $pdo->prepare("
            SELECT m.*, u.nom, u.prenom, u.photo AS avatar
            FROM messages m
            JOIN utilisateurs u ON u.id = m.expediteur_id
            WHERE m.conversation_id = ?
            ORDER BY m.date_envoi ASC
        ");
        $stmtMsgs->execute([$convActiveId]);
        $messages = $stmtMsgs->fetchAll();

        // Marquer comme lus
        $pdo->prepare("UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ?")
            ->execute([$convActiveId, $userId]);

        // Info du contact
        $stmtContact = $pdo->prepare("
            SELECT u.* FROM conversation_participants cp
            JOIN utilisateurs u ON u.id = cp.utilisateur_id
            WHERE cp.conversation_id = ? AND cp.utilisateur_id != ?
            LIMIT 1
        ");
        $stmtContact->execute([$convActiveId, $userId]);
        $contactActif = $stmtContact->fetch();
    }
}

// Liste des amis pour le modal "nouvelle conversation"
$stmtAmis = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, u.titre
    FROM connexions c
    JOIN utilisateurs u ON u.id = CASE
        WHEN c.demandeur_id = ? THEN c.destinataire_id ELSE c.demandeur_id
    END
    WHERE (c.demandeur_id = ? OR c.destinataire_id = ?) AND c.statut = 'accepte'
    ORDER BY u.nom
");
$stmtAmis->execute([$userId, $userId, $userId]);
$amis = $stmtAmis->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-xl py-4">
    <div class="card shadow-sm messagerie-wrapper">
        <div class="row g-0" style="min-height:600px">

            <!-- ===== LISTE DES CONVERSATIONS ===== -->
            <div class="col-md-4 col-lg-3 border-end">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2"></i>Messagerie</h6>
                    <button class="btn btn-sm btn-ecein-primary rounded-circle"
                            data-bs-toggle="modal" data-bs-target="#modalNouvelleConv"
                            title="Nouvelle conversation">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <!-- Recherche de conversations -->
                <div class="p-2 border-bottom">
                    <input type="search" class="form-control form-control-sm rounded-pill"
                           placeholder="Rechercher..." id="recherche-conv"
                           oninput="filtrerConversations(this.value)">
                </div>

                <!-- Liste des conversations -->
                <div class="liste-conversations" style="overflow-y:auto;max-height:540px">
                    <?php if (empty($conversations)): ?>
                    <div class="text-center p-4 text-muted small">
                        <i class="bi bi-chat fs-2 d-block mb-2 opacity-25"></i>
                        Aucune conversation
                    </div>
                    <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <a href="messagerie.php?conv=<?= $conv['id'] ?>"
                       class="d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none conversation-item
                              <?= $convActiveId == $conv['id'] ? 'bg-light' : '' ?>">
                        <div class="position-relative flex-shrink-0">
                            <?php if ($conv['est_groupe']): ?>
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white"
                                 style="width:46px;height:46px">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <?php else: ?>
                            <img src="<?= h(photo($conv['contact_photo'] ?? null)) ?>"
                                 alt="" class="rounded-circle" width="46" height="46" style="object-fit:cover">
                            <?php endif; ?>
                            <?php if ($conv['nb_non_lus'] > 0): ?>
                            <span class="badge bg-primary rounded-pill position-absolute top-0 end-0"
                                  style="font-size:.65rem"><?= $conv['nb_non_lus'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold small <?= $conv['nb_non_lus'] > 0 ? 'fw-bold' : '' ?>">
                                    <?= $conv['est_groupe'] ? h($conv['nom'] ?? 'Groupe') : h($conv['contact_prenom'] . ' ' . $conv['contact_nom']) ?>
                                </span>
                                <?php if ($conv['date_dernier_message']): ?>
                                <span class="text-muted" style="font-size:.7rem">
                                    <?= tempsEcoule($conv['date_dernier_message']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted text-truncate" style="font-size:.78rem">
                                <?= h(substr($conv['dernier_message'] ?? 'Démarrer la conversation...', 0, 50)) ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== FENÊTRE DE CHAT ===== -->
            <div class="col-md-8 col-lg-9 d-flex flex-column">

                <?php if ($convActiveId > 0 && $contactActif): ?>
                <!-- En-tête du chat -->
                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                    <img src="<?= h(photo($contactActif['photo'] ?? null)) ?>" alt=""
                         class="rounded-circle" width="42" height="42" style="object-fit:cover">
                    <div>
                        <div class="fw-bold"><?= h($contactActif['prenom'] . ' ' . $contactActif['nom']) ?></div>
                        <div class="text-muted small"><?= h($contactActif['titre'] ?? '') ?></div>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-sm btn-light rounded-circle" onclick="lancerAppel('audio')" title="Appel audio">
                            <i class="bi bi-telephone-fill text-success"></i>
                        </button>
                        <button class="btn btn-sm btn-light rounded-circle" onclick="lancerAppel('video')" title="Appel vidéo">
                            <i class="bi bi-camera-video-fill text-primary"></i>
                        </button>
                        <a href="utilisateur.php?id=<?= $contactActif['id'] ?>"
                           class="btn btn-sm btn-light rounded-circle" title="Voir le profil">
                            <i class="bi bi-person"></i>
                        </a>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages flex-grow-1 p-3" id="chat-messages" style="overflow-y:auto">
                    <?php foreach ($messages as $msg): ?>
                    <?php $estMoi = $msg['expediteur_id'] == $userId; ?>
                    <div class="message-wrapper <?= $estMoi ? 'message-moi' : 'message-autre' ?> mb-3" data-msg-id="<?= $msg['id'] ?>">
                        <?php if (!$estMoi): ?>
                        <img src="<?= h(photo($msg['avatar'] ?? null)) ?>" alt="" class="rounded-circle me-2"
                             width="32" height="32" style="object-fit:cover">
                        <?php endif; ?>
                        <div class="message-bulle <?= $estMoi ? 'message-bubble-moi' : 'message-bubble-autre' ?>">
                            <div><?= nl2br(h($msg['contenu'])) ?></div>
                            <div class="message-time"><?= tempsEcoule($msg['date_envoi']) ?></div>
                        </div>
                        <?php if ($estMoi): ?>
                        <img src="<?= h(photo($userCourant['photo'] ?? null)) ?>" alt="" class="rounded-circle ms-2"
                             width="32" height="32" style="object-fit:cover">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-dots fs-1 mb-3 d-block opacity-25"></i>
                        <p>Démarrez la conversation avec <?= h($contactActif['prenom']) ?> !</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Zone de saisie -->
                <div class="p-3 border-top bg-light">
                    <form id="formMessage" class="d-flex gap-2">
                        <input type="hidden" name="action" value="envoyer">
                        <input type="hidden" name="conversation_id" value="<?= $convActiveId ?>">
                        <div class="input-group">
                            <button type="button" class="btn btn-light border" title="Emoji">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <input type="text" name="contenu" id="input-message" class="form-control"
                                   placeholder="Écrivez votre message..." autocomplete="off">
                            <button type="submit" class="btn btn-ecein-primary" id="btn-envoyer">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <?php else: ?>
                <!-- Écran d'accueil messagerie -->
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted p-4">
                    <i class="bi bi-chat-dots-fill fs-1 mb-3 opacity-25"></i>
                    <h5 class="fw-semibold">Vos messages</h5>
                    <p class="text-center small">
                        Sélectionnez une conversation ou démarrez-en une nouvelle
                    </p>
                    <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalNouvelleConv">
                        <i class="bi bi-plus-lg me-2"></i>Nouvelle conversation
                    </button>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ===== MODAL : Nouvelle conversation ===== -->
<div class="modal fade" id="modalNouvelleConv" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nouvelle conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="search" class="form-control mb-3" placeholder="Rechercher parmi vos connexions..."
                       oninput="filtrerAmis(this.value)">
                <div class="liste-amis-modal" style="max-height:350px;overflow-y:auto">
                    <?php foreach ($amis as $ami): ?>
                    <a href="messagerie.php?contact=<?= $ami['id'] ?>"
                       class="d-flex align-items-center gap-3 p-2 rounded text-decoration-none text-dark
                              ami-item hover-bg mb-1">
                        <img src="<?= h(photo($ami['photo'] ?? null)) ?>" alt=""
                             class="rounded-circle" width="42" height="42" style="object-fit:cover">
                        <div>
                            <div class="fw-semibold"><?= h($ami['prenom'] . ' ' . $ami['nom']) ?></div>
                            <div class="text-muted small"><?= h($ami['titre'] ?? '') ?></div>
                        </div>
                        <i class="bi bi-arrow-right ms-auto text-muted"></i>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($amis)): ?>
                    <p class="text-muted text-center small">Aucune connexion disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- L'overlay HTML pour les appels audio/vidéo, avec les boutons accepter/refuser/raccrocher -->
<div id="call-overlay" class="call-overlay" style="display:none">
    <div class="call-overlay-bg"></div>
    <div class="call-content">
        <video id="remote-video" autoplay playsinline></video>
        <video id="local-video" autoplay playsinline muted class="local-video"></video>
        <div id="call-info" class="call-info">
            <img id="call-avatar" src="<?= DEFAULT_AVATAR ?>" alt="" class="call-avatar">
            <h4 id="call-name" class="call-name"></h4>
            <p id="call-status" class="call-status"></p>
            <p id="call-timer" class="call-timer" style="display:none">00:00</p>
        </div>
        <div class="call-controls">
            <div id="call-controls-active" style="display:none">
                <button class="call-btn call-btn-muted" onclick="toggleMute()" id="btn-mute" title="Couper le micro">
                    <i class="bi bi-mic-fill"></i>
                </button>
                <button class="call-btn call-btn-muted" onclick="toggleCamera()" id="btn-camera" title="Couper la caméra" style="display:none">
                    <i class="bi bi-camera-video-fill"></i>
                </button>
                <button class="call-btn call-btn-danger" onclick="terminerAppel()" title="Raccrocher">
                    <i class="bi bi-telephone-x-fill"></i>
                </button>
            </div>
            <div id="call-controls-incoming" style="display:none">
                <button class="call-btn call-btn-success call-btn-ring" onclick="accepterAppel()" title="Décrocher">
                    <i class="bi bi-telephone-fill"></i>
                </button>
                <button class="call-btn call-btn-danger" onclick="refuserAppel()" title="Refuser">
                    <i class="bi bi-telephone-x-fill"></i>
                </button>
            </div>
            <div id="call-controls-outgoing" style="display:none">
                <button class="call-btn call-btn-danger" onclick="annulerAppel()" title="Annuler">
                    <i class="bi bi-telephone-x-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// On passe les données PHP au JavaScript via des constantes globales (nécessaire pour le polling AJAX)
const chatEl = document.getElementById('chat-messages');
if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;

const CONV_ID = <?= $convActiveId ?>;
const USER_ID = <?= $userId ?>;
<?php if ($contactActif): ?>
const CONTACT_ID = <?= (int) $contactActif['id'] ?>;
const CONTACT_NAME = <?= json_encode($contactActif['prenom'] . ' ' . $contactActif['nom']) ?>;
const CONTACT_PHOTO = <?= json_encode($contactActif['photo']) ?>;
<?php else: ?>
const CONTACT_ID = 0;
const CONTACT_NAME = '';
const CONTACT_PHOTO = 'assets/images/default_avatar.png';
<?php endif; ?>

function filtrerConversations(q) {
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
    });
}

function filtrerAmis(q) {
    document.querySelectorAll('.ami-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
