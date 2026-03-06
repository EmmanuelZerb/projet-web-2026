<?php
/**
 * ECE In - Page Mon Réseau (Version Alt)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = 'Mon Réseau';

$stmtAmis = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre, u.localisation, c.date_reponse
    FROM connexions c
    JOIN utilisateurs u ON u.id = CASE
        WHEN c.demandeur_id = ? THEN c.destinataire_id
        ELSE c.demandeur_id
    END
    WHERE (c.demandeur_id = ? OR c.destinataire_id = ?)
      AND c.statut = 'accepte'
    ORDER BY u.nom, u.prenom
");
$stmtAmis->execute([$userId, $userId, $userId]);
$amis = $stmtAmis->fetchAll();

$stmtDemandes = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre, c.id AS connexion_id, c.date_demande
    FROM connexions c
    JOIN utilisateurs u ON u.id = c.demandeur_id
    WHERE c.destinataire_id = ? AND c.statut = 'en_attente'
    ORDER BY c.date_demande DESC
");
$stmtDemandes->execute([$userId]);
$demandes = $stmtDemandes->fetchAll();

$stmtEnvoyees = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, u.titre, c.id AS connexion_id
    FROM connexions c
    JOIN utilisateurs u ON u.id = c.destinataire_id
    WHERE c.demandeur_id = ? AND c.statut = 'en_attente'
");
$stmtEnvoyees->execute([$userId]);
$envoyees = $stmtEnvoyees->fetchAll();

$stmtSuggestions = $pdo->prepare("
    SELECT DISTINCT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre,
        COUNT(*) as amis_communs
    FROM connexions c1
    JOIN connexions c2 ON (
        (c2.demandeur_id = CASE WHEN c1.demandeur_id = :uid THEN c1.destinataire_id ELSE c1.demandeur_id END
         OR c2.destinataire_id = CASE WHEN c1.demandeur_id = :uid2 THEN c1.destinataire_id ELSE c1.demandeur_id END)
        AND c2.statut = 'accepte'
    )
    JOIN utilisateurs u ON u.id = CASE
        WHEN c2.demandeur_id = CASE WHEN c1.demandeur_id = :uid3 THEN c1.destinataire_id ELSE c1.demandeur_id END
        THEN c2.destinataire_id
        ELSE c2.demandeur_id
    END
    WHERE (c1.demandeur_id = :uid4 OR c1.destinataire_id = :uid5)
      AND c1.statut = 'accepte'
      AND u.id != :uid6
      AND u.id NOT IN (
          SELECT CASE WHEN demandeur_id = :uid7 THEN destinataire_id ELSE demandeur_id END
          FROM connexions WHERE (demandeur_id = :uid8 OR destinataire_id = :uid9)
      )
    GROUP BY u.id
    ORDER BY amis_communs DESC
    LIMIT 12
");
$stmtSuggestions->execute([
    ':uid'  => $userId, ':uid2' => $userId, ':uid3' => $userId,
    ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId,
    ':uid7' => $userId, ':uid8' => $userId, ':uid9' => $userId,
]);
$suggestions = $stmtSuggestions->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Invitations reçues -->
<?php if (!empty($demandes)): ?>
<section class="mb-4">
    <h6 class="fw-bold mb-3">
        <i class="bi bi-person-plus me-2" style="color:var(--accent)"></i>
        Invitations reçues
        <span class="badge bg-danger ms-1"><?= count($demandes) ?></span>
    </h6>
    <div class="row g-3">
        <?php foreach ($demandes as $d): ?>
        <div class="col-sm-6 col-md-4" id="demande-<?= $d['connexion_id'] ?>">
            <div class="glass p-3 text-center h-100">
                <a href="utilisateur.php?id=<?= $d['id'] ?>">
                    <img src="<?= h($d['photo']) ?>" alt="" class="rounded-3 mb-2" width="72" height="72" style="object-fit:cover">
                    <h6 class="fw-bold mb-0 small"><?= h($d['prenom'] . ' ' . $d['nom']) ?></h6>
                </a>
                <p class="small mb-1" style="color:var(--text-3)"><?= h($d['titre'] ?? '') ?></p>
                <p style="font-size:.7rem;color:var(--text-3)">Reçue le <?= formatDateFr($d['date_demande']) ?></p>
                <div class="d-flex gap-2">
                    <button class="btn btn-ecein-primary btn-sm flex-fill" onclick="repondreInvitation(<?= $d['connexion_id'] ?>, 'accepter', this)">Accepter</button>
                    <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="repondreInvitation(<?= $d['connexion_id'] ?>, 'refuser', this)">Refuser</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Mes connexions -->
<section class="mb-4">
    <h6 class="fw-bold mb-3">
        <i class="bi bi-people me-2" style="color:var(--emerald)"></i>
        Mes connexions (<?= count($amis) ?>)
    </h6>
    <?php if (empty($amis)): ?>
    <div class="glass empty-state">
        <i class="bi bi-people"></i>
        <p>Vous n'avez pas encore de connexions. Explorez les suggestions ci-dessous !</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($amis as $ami): ?>
        <div class="col-sm-6 col-md-4">
            <div class="glass p-3 h-100 card-ami">
                <a href="utilisateur.php?id=<?= $ami['id'] ?>" class="d-flex align-items-center gap-3 text-decoration-none mb-2" style="color:var(--text)">
                    <img src="<?= h($ami['photo']) ?>" alt="" class="rounded-3" width="56" height="56" style="object-fit:cover">
                    <div>
                        <div class="fw-bold small"><?= h($ami['prenom'] . ' ' . $ami['nom']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-3)">@<?= h($ami['pseudo']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-3)"><?= h(substr($ami['titre'] ?? '', 0, 45)) ?></div>
                    </div>
                </a>
                <?php if ($ami['localisation']): ?>
                <p class="mb-2" style="font-size:.72rem;color:var(--text-3)"><i class="bi bi-geo-alt me-1"></i><?= h($ami['localisation']) ?></p>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <a href="messagerie.php?contact=<?= $ami['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-chat me-1"></i>Message</a>
                    <a href="utilisateur.php?id=<?= $ami['id'] ?>" class="btn btn-sm btn-light flex-fill"><i class="bi bi-person me-1"></i>Profil</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Invitations envoyées -->
<?php if (!empty($envoyees)): ?>
<section class="mb-4">
    <h6 class="fw-bold mb-3">
        <i class="bi bi-send me-2" style="color:var(--text-3)"></i>
        Invitations envoyées (<?= count($envoyees) ?>)
    </h6>
    <div class="row g-3">
        <?php foreach ($envoyees as $e): ?>
        <div class="col-sm-6 col-md-4">
            <div class="glass p-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= h($e['photo']) ?>" alt="" class="rounded-3" width="46" height="46" style="object-fit:cover">
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= h($e['prenom'] . ' ' . $e['nom']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-3)"><?= h($e['titre'] ?? '') ?></div>
                        <span class="badge bg-warning mt-1" style="font-size:.6rem;color:#000">En attente</span>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="annulerInvitation(<?= $e['connexion_id'] ?>, this)" title="Annuler">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Suggestions -->
<?php if (!empty($suggestions)): ?>
<section class="mb-4">
    <h6 class="fw-bold mb-3">
        <i class="bi bi-lightbulb me-2" style="color:var(--accent)"></i>
        Personnes que vous pourriez connaître
    </h6>
    <div class="row g-3">
        <?php foreach ($suggestions as $sugg): ?>
        <div class="col-sm-6 col-md-4" id="sugg-<?= $sugg['id'] ?>">
            <div class="glass p-3 text-center h-100">
                <a href="utilisateur.php?id=<?= $sugg['id'] ?>">
                    <img src="<?= h($sugg['photo']) ?>" alt="" class="rounded-3 mb-2" width="68" height="68" style="object-fit:cover">
                    <h6 class="fw-bold mb-0 small"><?= h($sugg['prenom'] . ' ' . $sugg['nom']) ?></h6>
                </a>
                <p class="small mb-0" style="color:var(--text-3)"><?= h(substr($sugg['titre'] ?? '', 0, 55)) ?></p>
                <p class="mb-2" style="font-size:.72rem;color:var(--text-3)">
                    <i class="bi bi-people me-1"></i><?= $sugg['amis_communs'] ?> ami<?= $sugg['amis_communs'] > 1 ? 's' : '' ?> en commun
                </p>
                <button class="btn btn-outline-primary btn-sm w-100" onclick="envoyerDemande(<?= $sugg['id'] ?>, this)">
                    <i class="bi bi-person-plus me-1"></i>Se connecter
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
