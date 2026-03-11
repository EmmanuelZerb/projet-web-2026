<?php
/**
 * ECE In - Page Mon Réseau
 * Gestion des connexions entre utilisateurs (comme LinkedIn)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = 'Mon Réseau';

// On récupère les amis avec un CASE WHEN : dans la table connexions, l'ami peut être demandeur_id ou destinataire_id
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

// Les invitations en attente qu'on doit accepter ou refuser
$stmtDemandes = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre, c.id AS connexion_id, c.date_demande
    FROM connexions c
    JOIN utilisateurs u ON u.id = c.demandeur_id
    WHERE c.destinataire_id = ? AND c.statut = 'en_attente'
    ORDER BY c.date_demande DESC
");
$stmtDemandes->execute([$userId]);
$demandes = $stmtDemandes->fetchAll();

// ===== Demandes envoyées =====
$stmtEnvoyees = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.photo, u.titre, c.id AS connexion_id
    FROM connexions c
    JOIN utilisateurs u ON u.id = c.destinataire_id
    WHERE c.demandeur_id = ? AND c.statut = 'en_attente'
");
$stmtEnvoyees->execute([$userId]);
$envoyees = $stmtEnvoyees->fetchAll();

// Requête SQL un peu complexe : on cherche les amis d'amis qu'on connaît pas encore.
// Le COUNT(*) donne le nombre d'amis en commun pour trier par pertinence
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

<div class="container-xl py-4">
    <div class="row g-4">

        <!-- ===== COLONNE GAUCHE : Menu latéral réseau ===== -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Mon Réseau</div>
                <div class="list-group list-group-flush">
                    <a href="#section-amis" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span><i class="bi bi-people me-2"></i>Connexions</span>
                        <span class="badge bg-primary rounded-pill"><?= count($amis) ?></span>
                    </a>
                    <a href="#section-demandes" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span><i class="bi bi-person-plus me-2"></i>Invitations reçues</span>
                        <?php if (count($demandes) > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= count($demandes) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#section-envoyees" class="list-group-item list-group-item-action">
                        <i class="bi bi-send me-2"></i>Invitations envoyées
                    </a>
                    <a href="#section-suggestions" class="list-group-item list-group-item-action">
                        <i class="bi bi-lightbulb me-2"></i>Suggestions
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== COLONNE PRINCIPALE ===== -->
        <div class="col-lg-9">

            <!-- INVITATIONS REÇUES -->
            <?php if (!empty($demandes)): ?>
            <section id="section-demandes" class="mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-person-plus text-primary me-2"></i>
                    Invitations reçues
                    <span class="badge bg-danger ms-2"><?= count($demandes) ?></span>
                </h5>
                <div class="row g-3">
                    <?php foreach ($demandes as $d): ?>
                    <div class="col-sm-6 col-md-4" id="demande-<?= $d['connexion_id'] ?>">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <a href="utilisateur.php?id=<?= $d['id'] ?>">
                                    <img src="<?= h(photo($d['photo'] ?? null)) ?>" alt=""
                                         class="rounded-circle mb-2" width="72" height="72" style="object-fit:cover">
                                    <h6 class="fw-bold mb-0"><?= h($d['prenom'] . ' ' . $d['nom']) ?></h6>
                                </a>
                                <p class="text-muted small mb-1"><?= h($d['titre'] ?? '') ?></p>
                                <p class="text-muted" style="font-size:.75rem">
                                    Invitation reçue le <?= formatDateFr($d['date_demande']) ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-ecein-primary btn-sm flex-fill"
                                            onclick="repondreInvitation(<?= $d['connexion_id'] ?>, 'accepter', this)">
                                        Accepter
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm flex-fill"
                                            onclick="repondreInvitation(<?= $d['connexion_id'] ?>, 'refuser', this)">
                                        Refuser
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- MES CONNEXIONS -->
            <section id="section-amis" class="mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-people text-success me-2"></i>
                    Mes connexions (<?= count($amis) ?>)
                </h5>
                <?php if (empty($amis)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 mb-3 d-block opacity-25"></i>
                        <p>Vous n'avez pas encore de connexions. Explorez les suggestions ci-dessous !</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($amis as $ami): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="card shadow-sm h-100 card-ami">
                            <div class="card-body">
                                <a href="utilisateur.php?id=<?= $ami['id'] ?>" class="d-flex align-items-center gap-3 text-decoration-none text-dark mb-2">
                                    <img src="<?= h(photo($ami['photo'] ?? null)) ?>" alt=""
                                         class="rounded-circle" width="60" height="60" style="object-fit:cover">
                                    <div>
                                        <div class="fw-bold"><?= h($ami['prenom'] . ' ' . $ami['nom']) ?></div>
                                        <div class="text-muted small">@<?= h($ami['pseudo']) ?></div>
                                        <div class="text-muted" style="font-size:.75rem"><?= h(substr($ami['titre'] ?? '', 0, 50)) ?></div>
                                    </div>
                                </a>
                                <?php if ($ami['localisation']): ?>
                                <p class="text-muted mb-2" style="font-size:.78rem">
                                    <i class="bi bi-geo-alt me-1"></i><?= h($ami['localisation']) ?>
                                </p>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <a href="messagerie.php?contact=<?= $ami['id'] ?>"
                                       class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="bi bi-chat me-1"></i>Message
                                    </a>
                                    <a href="utilisateur.php?id=<?= $ami['id'] ?>"
                                       class="btn btn-sm btn-light flex-fill">
                                        <i class="bi bi-person me-1"></i>Profil
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- INVITATIONS ENVOYÉES -->
            <?php if (!empty($envoyees)): ?>
            <section id="section-envoyees" class="mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-send text-secondary me-2"></i>
                    Invitations envoyées (<?= count($envoyees) ?>)
                </h5>
                <div class="row g-3">
                    <?php foreach ($envoyees as $e): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body d-flex align-items-center gap-3">
                                <img src="<?= h(photo($e['photo'] ?? null)) ?>" alt=""
                                     class="rounded-circle" width="50" height="50" style="object-fit:cover">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small"><?= h($e['prenom'] . ' ' . $e['nom']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= h($e['titre'] ?? '') ?></div>
                                    <span class="badge bg-warning text-dark mt-1">En attente</span>
                                </div>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="annulerInvitation(<?= $e['connexion_id'] ?>, this)"
                                        title="Annuler l'invitation">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- SUGGESTIONS -->
            <?php if (!empty($suggestions)): ?>
            <section id="section-suggestions" class="mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-lightbulb text-warning me-2"></i>
                    Personnes que vous pourriez connaître
                </h5>
                <div class="row g-3">
                    <?php foreach ($suggestions as $sugg): ?>
                    <div class="col-sm-6 col-md-4" id="sugg-<?= $sugg['id'] ?>">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <a href="utilisateur.php?id=<?= $sugg['id'] ?>">
                                    <img src="<?= h(photo($sugg['photo'] ?? null)) ?>" alt=""
                                         class="rounded-circle mb-2" width="72" height="72" style="object-fit:cover">
                                    <h6 class="fw-bold mb-0"><?= h($sugg['prenom'] . ' ' . $sugg['nom']) ?></h6>
                                </a>
                                <p class="text-muted small mb-0"><?= h(substr($sugg['titre'] ?? '', 0, 60)) ?></p>
                                <p class="text-muted mb-2" style="font-size:.75rem">
                                    <i class="bi bi-people me-1"></i>
                                    <?= $sugg['amis_communs'] ?> ami<?= $sugg['amis_communs'] > 1 ? 's' : '' ?> en commun
                                </p>
                                <button class="btn btn-outline-primary btn-sm w-100"
                                        onclick="envoyerDemande(<?= $sugg['id'] ?>, this)">
                                    <i class="bi bi-person-plus me-1"></i>Se connecter
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
