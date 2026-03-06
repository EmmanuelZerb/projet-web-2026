<?php
/**
 * ECE In - Page profil d'un autre utilisateur (Version Alt)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$profilId    = (int) ($_GET['id'] ?? 0);

if ($profilId === $userId) {
    redirect('profil.php');
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ? AND actif = 1");
$stmt->execute([$profilId]);
$profil = $stmt->fetch();

if (!$profil) {
    http_response_code(404);
    $pageTitle = 'Introuvable';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="glass empty-state"><i class="bi bi-person-x"></i><h5>Utilisateur introuvable</h5><a href="reseau.php" class="btn btn-ecein-primary mt-2">Retour au réseau</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

$pageTitle = $profil['prenom'] . ' ' . $profil['nom'];
$statut = statutConnexion($userId, $profilId);

$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$profilId]);
$formations = $stmtForm->fetchAll();

$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$profilId]);
$projets = $stmtProj->fetchAll();

$query = "
    SELECT p.*,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
        (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
        (SELECT type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = :uid) AS ma_reaction
    FROM publications p
    WHERE p.utilisateur_id = :pid AND (p.visibilite = 'public'
";
if ($statut === 'accepte') {
    $query .= " OR p.visibilite = 'amis'";
}
$query .= ") ORDER BY p.date_publication DESC LIMIT 20";

$stmtPosts = $pdo->prepare($query);
$stmtPosts->execute([':uid' => $userId, ':pid' => $profilId]);
$publications = $stmtPosts->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner -->
<div class="profil-banner mb-4">
    <div class="profil-bg-large" style="background-image:url('<?= h($profil['image_fond']) ?>')"></div>
    <div class="profil-banner-overlay"></div>
    <div class="profil-banner-info">
        <img src="<?= h($profil['photo']) ?>" alt="Avatar" class="profil-avatar">
        <div>
            <h4 class="fw-bold mb-0 text-white"><?= h($profil['prenom'] . ' ' . $profil['nom']) ?></h4>
            <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.85rem"><?= h($profil['titre'] ?? '') ?></p>
            <?php if ($profil['localisation']): ?>
            <p class="mb-0" style="color:rgba(255,255,255,.5);font-size:.78rem"><i class="bi bi-geo-alt me-1"></i><?= h($profil['localisation']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if ($statut === 'accepte'): ?>
    <span class="badge bg-success p-2"><i class="bi bi-people-fill me-1"></i>Connecté</span>
    <a href="messagerie.php?contact=<?= $profilId ?>" class="btn btn-sm btn-ecein-primary"><i class="bi bi-chat me-1"></i>Message</a>
    <?php elseif ($statut === 'en_attente'): ?>
    <span class="badge p-2" style="background:var(--accent);color:#000"><i class="bi bi-clock me-1"></i>Invitation envoyée</span>
    <?php else: ?>
    <button class="btn btn-sm btn-ecein-primary" onclick="envoyerDemande(<?= $profilId ?>, this)"><i class="bi bi-person-plus me-1"></i>Se connecter</button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4 mb-3">
            <h6 class="fw-bold mb-3" style="font-size:.85rem">À propos</h6>
            <?php if ($profil['biographie']): ?>
            <p class="small" style="color:var(--text-2)"><?= nl2br(h($profil['biographie'])) ?></p>
            <hr style="border-color:var(--glass-border)">
            <?php endif; ?>
            <ul class="list-unstyled small mb-0" style="color:var(--text-2)">
                <?php if ($profil['localisation']): ?>
                <li class="mb-2"><i class="bi bi-geo-alt me-2" style="color:var(--accent)"></i><?= h($profil['localisation']) ?></li>
                <?php endif; ?>
                <?php if ($profil['site_web']): ?>
                <li class="mb-2"><i class="bi bi-link-45deg me-2" style="color:var(--accent)"></i><a href="<?= h($profil['site_web']) ?>" target="_blank"><?= h($profil['site_web']) ?></a></li>
                <?php endif; ?>
                <li><i class="bi bi-calendar me-2" style="color:var(--accent)"></i>Membre depuis <?= formatDateFr($profil['date_inscription']) ?></li>
            </ul>
        </div>

        <?php if (!empty($formations)): ?>
        <div class="glass p-4">
            <h6 class="fw-bold mb-3" style="font-size:.85rem"><i class="bi bi-mortarboard me-2" style="color:var(--accent)"></i>Formations</h6>
            <?php foreach ($formations as $f): ?>
            <div class="mb-3">
                <div class="fw-semibold small"><?= h($f['diplome']) ?></div>
                <div style="font-size:.78rem;color:var(--accent)"><?= h($f['etablissement']) ?></div>
                <div style="font-size:.72rem;color:var(--text-3)">
                    <?= formatDateFr($f['date_debut']) ?>
                    <?= $f['en_cours'] ? '– Présent' : ($f['date_fin'] ? '– ' . formatDateFr($f['date_fin']) : '') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <?php if (empty($publications)): ?>
        <div class="glass empty-state">
            <i class="bi bi-chat-square-dots"></i>
            <p>Aucune publication visible.</p>
        </div>
        <?php else: ?>
        <?php foreach ($publications as $post):
            $post['avatar'] = $profil['photo'];
            $post['prenom'] = $profil['prenom'];
            $post['nom']    = $profil['nom'];
            $post['pseudo'] = $profil['pseudo'];
            $post['user_titre'] = $profil['titre'] ?? '';
        ?>
        <?php include __DIR__ . '/includes/post_card.php'; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
