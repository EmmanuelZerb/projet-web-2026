<?php
/**
 * ECE In - Page profil d'un autre utilisateur
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$profilId    = (int) ($_GET['id'] ?? 0);

// Rediriger vers son propre profil si c'est l'utilisateur connecté
if ($profilId === $userId) {
    redirect('profil.php');
}

// Récupérer l'utilisateur demandé
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ? AND actif = 1");
$stmt->execute([$profilId]);
$profil = $stmt->fetch();

if (!$profil) {
    http_response_code(404);
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="container py-5 text-center"><h3>Utilisateur introuvable</h3><a href="reseau.php" class="btn btn-primary">Retour au réseau</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

$pageTitle = $profil['prenom'] . ' ' . $profil['nom'];

// Statut de connexion
$statut = statutConnexion($userId, $profilId);

// Formations
$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$profilId]);
$formations = $stmtForm->fetchAll();

// Projets
$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$profilId]);
$projets = $stmtProj->fetchAll();

// Publications visibles (publiques + amis si connectés)
$query = "
    SELECT p.id, p.utilisateur_id, p.type, p.contenu, p.fichier, p.fichier_mime,
           p.lieu, p.humeur, p.visibilite, p.publication_originale_id, p.date_publication,
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

<div class="container-xl py-4">

    <!-- ===== BANNIÈRE PROFIL ===== -->
    <div class="card shadow-sm mb-4 overflow-hidden">
        <div class="profil-bg-large" style="background-image:url('<?= h(imageFond($profil['image_fond'] ?? null)) ?>')"></div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-end gap-4 position-relative" style="margin-top:-50px">
                <img src="<?= h(photo($profil['photo'] ?? null)) ?>" alt="Avatar"
                     class="rounded-circle border-4 border-white shadow"
                     width="110" height="110" style="object-fit:cover">
                <div class="flex-grow-1 pb-2">
                    <h3 class="fw-bold mb-0"><?= h($profil['prenom'] . ' ' . $profil['nom']) ?></h3>
                    <p class="text-muted mb-1"><?= h($profil['titre'] ?? '') ?></p>
                    <?php if ($profil['localisation']): ?>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-geo-alt me-1"></i><?= h($profil['localisation']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <!-- Boutons d'action -->
                <div class="d-flex gap-2 flex-wrap pb-2">
                    <?php if ($statut === 'accepte'): ?>
                    <span class="badge bg-success p-2 fs-6">
                        <i class="bi bi-people-fill me-1"></i>Connecté
                    </span>
                    <a href="messagerie.php?contact=<?= $profilId ?>"
                       class="btn btn-ecein-primary">
                        <i class="bi bi-chat me-2"></i>Message
                    </a>
                    <?php elseif ($statut === 'en_attente'): ?>
                    <span class="badge bg-warning text-dark p-2 fs-6">
                        <i class="bi bi-clock me-1"></i>Invitation envoyée
                    </span>
                    <?php else: ?>
                    <button class="btn btn-ecein-primary" onclick="envoyerDemande(<?= $profilId ?>, this)">
                        <i class="bi bi-person-plus me-2"></i>Se connecter
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Infos -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">À propos</h6>
                    <?php if ($profil['biographie']): ?>
                    <p class="small"><?= nl2br(h($profil['biographie'])) ?></p>
                    <hr>
                    <?php endif; ?>
                    <ul class="list-unstyled small mb-0">
                        <?php if ($profil['localisation']): ?>
                        <li class="mb-2"><i class="bi bi-geo-alt text-primary me-2"></i><?= h($profil['localisation']) ?></li>
                        <?php endif; ?>
                        <?php if ($profil['site_web']): ?>
                        <li class="mb-2"><i class="bi bi-link-45deg text-primary me-2"></i>
                            <a href="<?= h($profil['site_web']) ?>" target="_blank"><?= h($profil['site_web']) ?></a>
                        </li>
                        <?php endif; ?>
                        <li><i class="bi bi-calendar text-primary me-2"></i>
                            Membre depuis <?= formatDateFr($profil['date_inscription']) ?>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Formations -->
            <?php if (!empty($formations)): ?>
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><i class="bi bi-mortarboard me-2"></i>Formations</div>
                <div class="card-body">
                    <?php foreach ($formations as $f): ?>
                    <div class="mb-3">
                        <div class="fw-semibold small"><?= h($f['diplome']) ?></div>
                        <div class="text-primary small"><?= h($f['etablissement']) ?></div>
                        <div class="text-muted" style="font-size:.75rem">
                            <?= formatDateFr($f['date_debut']) ?>
                            <?= $f['en_cours'] ? '– Présent' : ($f['date_fin'] ? '– ' . formatDateFr($f['date_fin']) : '') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Publications -->
        <div class="col-lg-8">
            <?php if (empty($publications)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-chat-square-dots fs-1 mb-3 d-block opacity-25"></i>
                    <p>Aucune publication visible.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($publications as $post):
                // Merge infos utilisateur dans le post pour post_card.php
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
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
