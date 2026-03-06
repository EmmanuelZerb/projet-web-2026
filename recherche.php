<?php
/**
 * ECE In - Page de recherche (Version Alt)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$q      = trim($_GET['q'] ?? '');
$pageTitle = 'Recherche' . ($q ? ' : ' . $q : '');

$utilisateurs = [];
$publications  = [];
$emplois       = [];

if (!empty($q) && strlen($q) >= 2) {
    $like = "%$q%";

    $stmt = $pdo->prepare("
        SELECT id, nom, prenom, pseudo, photo, titre, localisation
        FROM utilisateurs WHERE actif = 1 AND id != ?
          AND (nom LIKE ? OR prenom LIKE ? OR pseudo LIKE ? OR titre LIKE ?)
        LIMIT 10
    ");
    $stmt->execute([$userId, $like, $like, $like, $like]);
    $utilisateurs = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.*, u.nom, u.prenom, u.photo AS avatar
        FROM publications p JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.visibilite = 'public' AND p.contenu LIKE ?
        ORDER BY p.date_publication DESC LIMIT 10
    ");
    $stmt->execute([$like]);
    $publications = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT * FROM emplois WHERE actif = 1
          AND (titre LIKE ? OR entreprise LIKE ? OR description LIKE ?)
        ORDER BY date_publication DESC LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);
    $emplois = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<?php if (empty($q)): ?>
<div class="glass empty-state">
    <i class="bi bi-search"></i>
    <p>Entrez au moins 2 caractères pour rechercher.</p>
</div>

<?php else: ?>
<h5 class="fw-bold mb-4"><i class="bi bi-search me-2" style="color:var(--accent)"></i>Résultats pour «&nbsp;<?= h($q) ?>&nbsp;»</h5>

<?php if (!empty($utilisateurs)): ?>
<h6 class="fw-semibold mb-3" style="color:var(--text-3);font-size:.82rem">Personnes</h6>
<div class="row g-3 mb-4">
    <?php foreach ($utilisateurs as $u): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="glass p-3 text-center">
            <a href="utilisateur.php?id=<?= $u['id'] ?>">
                <img src="<?= h($u['photo']) ?>" alt="" class="rounded-3 mb-2" width="56" height="56" style="object-fit:cover">
                <div class="fw-semibold small"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
            </a>
            <div style="font-size:.72rem;color:var(--text-3)"><?= h($u['titre'] ?? '') ?></div>
            <button class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="envoyerDemande(<?= $u['id'] ?>, this)">
                <i class="bi bi-person-plus me-1"></i>Connecter
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($publications)): ?>
<h6 class="fw-semibold mb-3" style="color:var(--text-3);font-size:.82rem">Publications</h6>
<div class="mb-4">
    <?php foreach ($publications as $post): ?>
    <div class="glass p-3 mb-2">
        <div class="d-flex gap-2 mb-2">
            <img src="<?= h($post['avatar']) ?>" alt="" class="rounded-3" width="34" height="34" style="object-fit:cover">
            <div>
                <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>" class="fw-semibold small"><?= h($post['prenom'] . ' ' . $post['nom']) ?></a>
                <div style="font-size:.68rem;color:var(--text-3)"><?= tempsEcoule($post['date_publication']) ?></div>
            </div>
        </div>
        <p class="small mb-0"><?= nl2br(h(substr($post['contenu'] ?? '', 0, 200))) ?><?= strlen($post['contenu'] ?? '') > 200 ? '...' : '' ?></p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($emplois)): ?>
<h6 class="fw-semibold mb-3" style="color:var(--text-3);font-size:.82rem">Emplois & Stages</h6>
<div class="mb-4">
    <?php foreach ($emplois as $e): ?>
    <div class="glass p-3 mb-2 d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold small"><?= h($e['titre']) ?></div>
            <div style="font-size:.72rem;color:var(--text-3)"><?= h($e['entreprise']) ?> · <?= h($e['lieu'] ?? '') ?></div>
        </div>
        <a href="emplois.php#emploi-<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($utilisateurs) && empty($publications) && empty($emplois)): ?>
<div class="glass empty-state">
    <i class="bi bi-search"></i>
    <p>Aucun résultat pour «&nbsp;<?= h($q) ?>&nbsp;»</p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
