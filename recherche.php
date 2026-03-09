<?php
/**
 * ECE In - Page de recherche
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$q      = trim($_GET['q'] ?? '');
$pageTitle = 'Recherche : ' . $q;

$utilisateurs = [];
$publications  = [];
$emplois       = [];

if (!empty($q) && strlen($q) >= 2) {
    $like = "%$q%";

    // Recherche utilisateurs
    $stmt = $pdo->prepare("
        SELECT id, nom, prenom, pseudo, photo, titre, localisation
        FROM utilisateurs
        WHERE actif = 1 AND id != ?
          AND (nom LIKE ? OR prenom LIKE ? OR pseudo LIKE ? OR titre LIKE ?)
        LIMIT 10
    ");
    $stmt->execute([$userId, $like, $like, $like, $like]);
    $utilisateurs = $stmt->fetchAll();

    // Recherche publications
    $stmt = $pdo->prepare("
        SELECT p.id, p.utilisateur_id, p.type, p.contenu, p.fichier, p.fichier_mime,
               p.lieu, p.humeur, p.visibilite, p.publication_originale_id, p.date_publication,
               u.nom, u.prenom, u.photo AS avatar,
               (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
               (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
               NULL AS ma_reaction
        FROM publications p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.visibilite = 'public' AND p.contenu LIKE ?
        ORDER BY p.date_publication DESC LIMIT 10
    ");
    $stmt->execute([$like]);
    $publications = $stmt->fetchAll();

    // Recherche emplois
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

<div class="container-xl py-4">
    <?php if (empty($q)): ?>
    <div class="card shadow-sm text-center py-5">
        <i class="bi bi-search fs-1 text-muted mb-3 d-block opacity-25"></i>
        <p class="text-muted">Entrez au moins 2 caractères pour rechercher.</p>
    </div>

    <?php else: ?>
    <h4 class="fw-bold mb-4">
        <i class="bi bi-search me-2"></i>Résultats pour «&nbsp;<?= h($q) ?>&nbsp;»
    </h4>

    <!-- Utilisateurs -->
    <?php if (!empty($utilisateurs)): ?>
    <h6 class="fw-bold text-muted mb-3">Personnes</h6>
    <div class="row g-3 mb-4">
        <?php foreach ($utilisateurs as $u): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <a href="utilisateur.php?id=<?= $u['id'] ?>">
                        <img src="<?= h(photo($u['photo'] ?? null)) ?>" alt=""
                             class="rounded-circle mb-2" width="60" height="60" style="object-fit:cover">
                        <div class="fw-semibold"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                    </a>
                    <div class="text-muted small"><?= h($u['titre'] ?? '') ?></div>
                    <button class="btn btn-sm btn-outline-primary mt-2 w-100"
                            onclick="envoyerDemande(<?= $u['id'] ?>, this)">
                        <i class="bi bi-person-plus me-1"></i>Connecter
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Publications -->
    <?php if (!empty($publications)): ?>
    <h6 class="fw-bold text-muted mb-3">Publications</h6>
    <div class="mb-4">
        <?php foreach ($publications as $post): ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body">
                <div class="d-flex gap-2 mb-2">
                    <img src="<?= h(photo($post['avatar'] ?? null)) ?>" alt="" class="rounded-circle"
                         width="36" height="36" style="object-fit:cover">
                    <div>
                        <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>"
                           class="fw-semibold small"><?= h($post['prenom'] . ' ' . $post['nom']) ?></a>
                        <div class="text-muted" style="font-size:.72rem"><?= tempsEcoule($post['date_publication']) ?></div>
                    </div>
                </div>
                <p class="small mb-0"><?= nl2br(h(substr($post['contenu'] ?? '', 0, 200))) ?>
                    <?= strlen($post['contenu'] ?? '') > 200 ? '...' : '' ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Emplois -->
    <?php if (!empty($emplois)): ?>
    <h6 class="fw-bold text-muted mb-3">Emplois & Stages</h6>
    <div class="mb-4">
        <?php foreach ($emplois as $e): ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold"><?= h($e['titre']) ?></div>
                    <div class="text-muted small"><?= h($e['entreprise']) ?> · <?= h($e['lieu'] ?? '') ?></div>
                </div>
                <a href="emplois.php#emploi-<?= $e['id'] ?>" class="btn btn-sm btn-outline-warning">Voir</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($utilisateurs) && empty($publications) && empty($emplois)): ?>
    <div class="card shadow-sm text-center py-5">
        <i class="bi bi-search fs-1 text-muted mb-3 d-block opacity-25"></i>
        <p class="text-muted">Aucun résultat pour «&nbsp;<?= h($q) ?>&nbsp;»</p>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
