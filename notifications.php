<?php
/**
 * ECE In - Page Notifications
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = 'Notifications';

// Marquer toutes les notifications comme lues
$pdo->prepare("UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?")
    ->execute([$userId]);

// Récupérer toutes les notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.nom, u.prenom, u.photo AS avatar_exp
    FROM notifications n
    LEFT JOIN utilisateurs u ON u.id = n.expediteur_id
    WHERE n.utilisateur_id = ?
    ORDER BY n.date_notification DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Récupérer les événements à venir (pour tous)
$stmtEvts = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom, u.photo
    FROM evenements e
    JOIN utilisateurs u ON u.id = e.organisateur_id
    WHERE e.date_debut >= NOW()
    ORDER BY e.est_officiel DESC, e.date_debut ASC
    LIMIT 10
");
$stmtEvts->execute();
$evenementsAvenir = $stmtEvts->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-xl py-4">
    <div class="row g-4">

        <!-- NOTIFICATIONS -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bell me-2"></i>Notifications</h5>
                    <?php if (!empty($notifications)): ?>
                    <button class="btn btn-sm btn-light" onclick="toutMarquerLu()">
                        <i class="bi bi-check2-all me-1"></i>Tout marquer comme lu
                    </button>
                    <?php endif; ?>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 mb-3 d-block opacity-25"></i>
                        <p>Aucune notification pour l'instant.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="list-group-item list-group-item-action py-3
                        <?= !$notif['lue'] ? 'notif-non-lue' : '' ?>">
                        <div class="d-flex gap-3 align-items-start">
                            <!-- Icône de l'expéditeur ou système -->
                            <?php if ($notif['avatar_exp']): ?>
                            <a href="utilisateur.php?id=<?= $notif['expediteur_id'] ?>">
                                <img src="<?= h(photo($notif['avatar_exp'] ?? null)) ?>" alt=""
                                     class="rounded-circle" width="46" height="46" style="object-fit:cover">
                            </a>
                            <?php else: ?>
                            <div class="notif-icone-systeme">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <?php endif; ?>

                            <!-- Contenu -->
                            <div class="flex-grow-1">
                                <?php if ($notif['expediteur_id']): ?>
                                <strong><?= h($notif['prenom'] . ' ' . $notif['nom']) ?></strong>
                                <?php endif; ?>
                                <span class="<?= $notif['expediteur_id'] ? '' : 'fw-semibold' ?>">
                                    <?= h($notif['message']) ?>
                                </span>
                                <div class="text-muted small mt-1">
                                    <?= tempsEcoule($notif['date_notification']) ?>
                                </div>
                            </div>

                            <!-- Icône type -->
                            <div class="notif-type-icon">
                                <?php
                                $icons = [
                                    'demande_ami'  => 'bi-person-plus text-primary',
                                    'ami_accepte'  => 'bi-people-fill text-success',
                                    'reaction'     => 'bi-hand-thumbs-up-fill text-primary',
                                    'commentaire'  => 'bi-chat-dots-fill text-warning',
                                    'partage'      => 'bi-share-fill text-info',
                                    'evenement'    => 'bi-calendar-event-fill text-danger',
                                    'emploi'       => 'bi-briefcase-fill text-success',
                                    'systeme'      => 'bi-info-circle-fill text-secondary',
                                ];
                                $icon = $icons[$notif['type']] ?? 'bi-bell-fill text-muted';
                                ?>
                                <i class="bi <?= $icon ?> fs-5"></i>
                            </div>

                            <?php if ($notif['lien']): ?>
                            <a href="<?= h($notif['lien']) ?>" class="stretched-link"></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ÉVÉNEMENTS À VENIR -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold">
                    <i class="bi bi-calendar-event text-danger me-2"></i>Événements à venir
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($evenementsAvenir)): ?>
                    <div class="list-group-item text-muted small py-3 text-center">
                        Aucun événement planifié
                    </div>
                    <?php else: ?>
                    <?php foreach ($evenementsAvenir as $evt): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex gap-3">
                            <div class="evenement-date-badge evenement-date-badge-sm text-center flex-shrink-0">
                                <div class="evt-mois"><?= strtoupper(date('M', strtotime($evt['date_debut']))) ?></div>
                                <div class="evt-jour"><?= date('d', strtotime($evt['date_debut'])) ?></div>
                            </div>
                            <div>
                                <?php if ($evt['est_officiel']): ?>
                                <span class="badge bg-ecein mb-1">Officiel</span>
                                <?php endif; ?>
                                <div class="fw-semibold small"><?= h($evt['titre']) ?></div>
                                <?php if ($evt['lieu']): ?>
                                <div class="text-muted" style="font-size:.75rem">
                                    <i class="bi bi-geo-alt me-1"></i><?= h($evt['lieu']) ?>
                                </div>
                                <?php endif; ?>
                                <div class="text-muted" style="font-size:.75rem">
                                    Par <?= h($evt['prenom'] . ' ' . $evt['nom']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function toutMarquerLu() {
    $.post('api/notifications.php', { action: 'marquer_lu_tout' }, function() {
        $('.notif-non-lue').removeClass('notif-non-lue');
        $('.badge-notif').remove();
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
