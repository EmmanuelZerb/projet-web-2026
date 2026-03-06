<?php
/**
 * ECE In - Page Notifications (Version Alt)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = 'Notifications';

$pdo->prepare("UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?")
    ->execute([$userId]);

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

<div class="row g-4">
    <div class="col-lg-8">
        <div class="glass p-0">
            <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--glass-border)">
                <h6 class="mb-0 fw-bold" style="font-size:.85rem"><i class="bi bi-bell me-2" style="color:var(--accent)"></i>Notifications</h6>
                <?php if (!empty($notifications)): ?>
                <button class="btn btn-sm btn-light" onclick="toutMarquerLu()">
                    <i class="bi bi-check2-all me-1"></i>Tout marquer comme lu
                </button>
                <?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <p>Aucune notification pour l'instant.</p>
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="list-group-item list-group-item-action py-3 <?= !$notif['lue'] ? 'notif-non-lue' : '' ?>">
                    <div class="d-flex gap-3 align-items-start">
                        <?php if ($notif['avatar_exp']): ?>
                        <a href="utilisateur.php?id=<?= $notif['expediteur_id'] ?>">
                            <img src="<?= h($notif['avatar_exp']) ?>" alt="" class="rounded-3" width="44" height="44" style="object-fit:cover">
                        </a>
                        <?php else: ?>
                        <div class="notif-icone-systeme"><i class="bi bi-bell-fill"></i></div>
                        <?php endif; ?>

                        <div class="flex-grow-1">
                            <?php if ($notif['expediteur_id']): ?>
                            <strong><?= h($notif['prenom'] . ' ' . $notif['nom']) ?></strong>
                            <?php endif; ?>
                            <span class="<?= $notif['expediteur_id'] ? '' : 'fw-semibold' ?>"><?= h($notif['message']) ?></span>
                            <div class="mt-1" style="font-size:.72rem;color:var(--text-3)"><?= tempsEcoule($notif['date_notification']) ?></div>
                        </div>

                        <div>
                            <?php
                            $icons = [
                                'demande_ami'  => 'bi-person-plus',
                                'ami_accepte'  => 'bi-people-fill',
                                'reaction'     => 'bi-hand-thumbs-up-fill',
                                'commentaire'  => 'bi-chat-dots-fill',
                                'partage'      => 'bi-share-fill',
                                'evenement'    => 'bi-calendar-event-fill',
                                'emploi'       => 'bi-briefcase-fill',
                                'systeme'      => 'bi-info-circle-fill',
                            ];
                            $colors = [
                                'demande_ami'  => 'var(--accent)',
                                'ami_accepte'  => 'var(--emerald)',
                                'reaction'     => 'var(--accent)',
                                'commentaire'  => 'var(--accent)',
                                'partage'      => 'var(--sky)',
                                'evenement'    => 'var(--rose)',
                                'emploi'       => 'var(--emerald)',
                                'systeme'      => 'var(--text-3)',
                            ];
                            $icon = $icons[$notif['type']] ?? 'bi-bell-fill';
                            $color = $colors[$notif['type']] ?? 'var(--text-3)';
                            ?>
                            <i class="bi <?= $icon ?> fs-5" style="color:<?= $color ?>"></i>
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

    <div class="col-lg-4">
        <div class="glass p-0" style="position:sticky;top:70px">
            <div class="p-3" style="border-bottom:1px solid var(--glass-border)">
                <span class="fw-bold" style="font-size:.85rem"><i class="bi bi-calendar-event me-2" style="color:var(--rose)"></i>Événements à venir</span>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($evenementsAvenir)): ?>
                <div class="list-group-item small text-center py-3" style="color:var(--text-3)">Aucun événement planifié</div>
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
                            <span class="badge bg-ecein mb-1" style="font-size:.6rem">Officiel</span>
                            <?php endif; ?>
                            <div class="fw-semibold small"><?= h($evt['titre']) ?></div>
                            <?php if ($evt['lieu']): ?>
                            <div style="font-size:.72rem;color:var(--text-3)"><i class="bi bi-geo-alt me-1"></i><?= h($evt['lieu']) ?></div>
                            <?php endif; ?>
                            <div style="font-size:.72rem;color:var(--text-3)">Par <?= h($evt['prenom'] . ' ' . $evt['nom']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
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
