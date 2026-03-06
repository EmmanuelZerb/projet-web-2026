<?php
/**
 * ECE In - Page Profil (Vous)
 * Affiche le profil de l'utilisateur connecté avec ses formations, projets, CV
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$onglet      = $_GET['onglet'] ?? 'profil';
$pageTitle   = 'Mon Profil';

// ===== Formations =====
$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$userId]);
$formations = $stmtForm->fetchAll();

// ===== Projets =====
$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$userId]);
$projets = $stmtProj->fetchAll();

// ===== Publications de l'utilisateur =====
$stmtPosts = $pdo->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
        (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires
    FROM publications p
    WHERE p.utilisateur_id = ?
    ORDER BY p.date_publication DESC
");
$stmtPosts->execute([$userId]);
$publications = $stmtPosts->fetchAll();

// ===== Albums =====
$stmtAlbums = $pdo->prepare("SELECT * FROM albums WHERE utilisateur_id = ? ORDER BY date_creation DESC");
$stmtAlbums->execute([$userId]);
$albums = $stmtAlbums->fetchAll();

// ===== Traitement AJAX des mises à jour de profil =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Mettre à jour les infos de profil
    if ($action === 'update_profil') {
        $champs = ['titre', 'biographie', 'localisation', 'site_web', 'telephone'];
        $values = [];
        $params = [];
        foreach ($champs as $champ) {
            if (isset($_POST[$champ])) {
                $values[] = "$champ = ?";
                $params[] = trim($_POST[$champ]);
            }
        }
        if (!empty($values)) {
            $params[] = $userId;
            $pdo->prepare("UPDATE utilisateurs SET " . implode(', ', $values) . " WHERE id = ?")
                ->execute($params);
        }
        header('Location: profil.php');
        exit();
    }

    // Upload avatar
    if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nom  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest = UPLOAD_PATH . 'avatars/' . $nom;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $chemin = 'uploads/avatars/' . $nom;
                $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")
                    ->execute([$chemin, $userId]);
            }
        }
        header('Location: profil.php');
        exit();
    }

    // Ajouter formation
    if ($action === 'ajouter_formation') {
        $pdo->prepare("
            INSERT INTO formations (utilisateur_id, etablissement, diplome, domaine, date_debut, date_fin, en_cours, description, lieu)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($_POST['etablissement'] ?? ''),
            trim($_POST['diplome'] ?? ''),
            trim($_POST['domaine'] ?? ''),
            $_POST['date_debut'] ?? null,
            !empty($_POST['date_fin']) ? $_POST['date_fin'] : null,
            isset($_POST['en_cours']) ? 1 : 0,
            trim($_POST['description'] ?? ''),
            trim($_POST['lieu'] ?? ''),
        ]);
        header('Location: profil.php?onglet=formations');
        exit();
    }

    // Ajouter projet
    if ($action === 'ajouter_projet') {
        $pdo->prepare("
            INSERT INTO projets (utilisateur_id, titre, description, date_debut, date_fin, en_cours, lien, type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($_POST['titre'] ?? ''),
            trim($_POST['description'] ?? ''),
            !empty($_POST['date_debut']) ? $_POST['date_debut'] : null,
            !empty($_POST['date_fin']) ? $_POST['date_fin'] : null,
            isset($_POST['en_cours']) ? 1 : 0,
            trim($_POST['lien'] ?? ''),
            trim($_POST['type_projet'] ?? ''),
        ]);
        header('Location: profil.php?onglet=projets');
        exit();
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';

// Recharger l'utilisateur après d'éventuelles mises à jour
$userCourant = getUtilisateurConnecte();
?>

<div class="container-xl py-4">

    <!-- ===== BANNIÈRE PROFIL ===== -->
    <div class="card shadow-sm mb-4 overflow-hidden">
        <!-- Image de fond -->
        <div class="profil-bg-large"
             style="background-image:url('<?= h($userCourant['image_fond']) ?>')"
             id="bg-image">
            <label class="btn btn-sm opacity-75 position-absolute bottom-0 end-0 m-2"
                   style="cursor:pointer;background:var(--ecein-surface-2)" title="Changer la photo de fond">
                <i class="bi bi-camera"></i>
                <form id="form-bg" method="POST" action="api/upload.php" enctype="multipart/form-data" class="d-none">
                    <input type="hidden" name="action" value="upload_bg">
                    <input type="file" name="background" accept="image/*" onchange="this.form.submit()">
                </form>
            </label>
        </div>

        <div class="card-body">
            <div class="d-flex flex-wrap align-items-end gap-4 position-relative" style="margin-top:-50px">
                <!-- Avatar -->
                <div class="position-relative">
                    <img src="<?= h($userCourant['photo']) ?>" alt="Avatar"
                         class="rounded-circle border border-4 shadow" id="avatar-img"
                         width="110" height="110" style="object-fit:cover;border-color:var(--ecein-primary) !important">
                    <label class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0"
                           style="width:32px;height:32px;padding:0;cursor:pointer" title="Modifier la photo">
                        <i class="bi bi-camera-fill small"></i>
                        <form method="POST" enctype="multipart/form-data" class="d-none">
                            <input type="hidden" name="action" value="upload_avatar">
                            <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()">
                        </form>
                    </label>
                </div>

                <!-- Informations principales -->
                <div class="flex-grow-1 pb-2">
                    <h3 class="fw-bold mb-0">
                        <?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?>
                    </h3>
                    <p class="text-muted mb-1"><?= h($userCourant['titre'] ?? 'Ajouter un titre') ?></p>
                    <?php if ($userCourant['localisation']): ?>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-geo-alt me-1"></i><?= h($userCourant['localisation']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2 flex-wrap pb-2">
                    <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
                        <i class="bi bi-pencil me-2"></i>Modifier le profil
                    </button>
                    <a href="profil.php?onglet=cv" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-person me-2"></i>Mon CV
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation onglets -->
        <div class="card-footer bg-transparent pt-0">
            <ul class="nav nav-tabs border-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'profil' ? 'active' : '' ?>" href="?onglet=profil">
                        <i class="bi bi-person me-1"></i>Profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'publications' ? 'active' : '' ?>" href="?onglet=publications">
                        <i class="bi bi-grid me-1"></i>Publications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'formations' ? 'active' : '' ?>" href="?onglet=formations">
                        <i class="bi bi-mortarboard me-1"></i>Formations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'projets' ? 'active' : '' ?>" href="?onglet=projets">
                        <i class="bi bi-kanban me-1"></i>Projets
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'albums' ? 'active' : '' ?>" href="?onglet=albums">
                        <i class="bi bi-images me-1"></i>Albums
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'cv' ? 'active' : '' ?>" href="?onglet=cv">
                        <i class="bi bi-file-earmark-pdf me-1"></i>CV
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $onglet === 'parametres' ? 'active' : '' ?>" href="?onglet=parametres">
                        <i class="bi bi-gear me-1"></i>Paramètres
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ===== CONTENU SELON ONGLET ===== -->

    <?php if ($onglet === 'profil'): ?>
    <!-- ONGLET PROFIL -->
    <div class="row g-4">
        <div class="col-lg-4">
            <!-- Informations de contact -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Informations</h6>
                    <?php if ($userCourant['biographie']): ?>
                    <p class="small"><?= nl2br(h($userCourant['biographie'])) ?></p>
                    <hr>
                    <?php endif; ?>
                    <ul class="list-unstyled small mb-0">
                        <?php if ($userCourant['localisation']): ?>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2" style="color:var(--ecein-cyan)"></i><?= h($userCourant['localisation']) ?></li>
                        <?php endif; ?>
                        <?php if ($userCourant['site_web']): ?>
                        <li class="mb-2"><i class="bi bi-link-45deg me-2" style="color:var(--ecein-cyan)"></i>
                            <a href="<?= h($userCourant['site_web']) ?>" target="_blank" rel="noopener"><?= h($userCourant['site_web']) ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if ($userCourant['telephone']): ?>
                        <li class="mb-2"><i class="bi bi-telephone me-2" style="color:var(--ecein-cyan)"></i><?= h($userCourant['telephone']) ?></li>
                        <?php endif; ?>
                        <li class="mb-2"><i class="bi bi-envelope me-2" style="color:var(--ecein-cyan)"></i><?= h($userCourant['email']) ?></li>
                        <li><i class="bi bi-calendar me-2" style="color:var(--ecein-cyan)"></i>
                            Membre depuis <?= formatDateFr($userCourant['date_inscription']) ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <!-- Formations récentes -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-mortarboard me-2"></i>Formations</span>
                    <a href="?onglet=formations" class="btn btn-sm" style="background:var(--ecein-surface-2)">Tout voir</a>
                </div>
                <div class="card-body">
                    <?php if (empty($formations)): ?>
                    <p class="text-muted small">Aucune formation renseignée.</p>
                    <?php else: ?>
                    <?php foreach (array_slice($formations, 0, 2) as $f): ?>
                    <div class="d-flex gap-3 mb-3">
                        <div class="formation-icon"><i class="bi bi-building fs-4" style="color:var(--ecein-cyan)"></i></div>
                        <div>
                            <div class="fw-semibold"><?= h($f['diplome']) ?></div>
                            <div class="text-muted small"><?= h($f['etablissement']) ?></div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= formatDateFr($f['date_debut']) ?> –
                                <?= $f['en_cours'] ? 'Présent' : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Projets récents -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-kanban me-2"></i>Projets</span>
                    <a href="?onglet=projets" class="btn btn-sm" style="background:var(--ecein-surface-2)">Tout voir</a>
                </div>
                <div class="card-body">
                    <?php if (empty($projets)): ?>
                    <p class="text-muted small">Aucun projet renseigné.</p>
                    <?php else: ?>
                    <?php foreach (array_slice($projets, 0, 2) as $p): ?>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="fw-semibold"><?= h($p['titre']) ?></div>
                            <?php if ($p['en_cours']): ?>
                            <span class="badge bg-success">En cours</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-0"><?= h(substr($p['description'] ?? '', 0, 150)) ?></p>
                        <?php if ($p['lien']): ?>
                        <a href="<?= h($p['lien']) ?>" target="_blank" class="small">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Voir le projet
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($onglet === 'formations'): ?>
    <!-- ONGLET FORMATIONS -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-mortarboard me-2"></i>Mes Formations</span>
            <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalFormation">
                <i class="bi bi-plus-lg me-1"></i>Ajouter
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($formations)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-mortarboard fs-1 mb-3 d-block opacity-25"></i>
                <p>Aucune formation renseignée. Ajoutez votre parcours académique !</p>
            </div>
            <?php else: ?>
            <div class="timeline">
                <?php foreach ($formations as $f): ?>
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content card mb-0 border-0 shadow-none">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-0"><?= h($f['diplome']) ?></h6>
                                <div class="fw-semibold small" style="color:var(--ecein-cyan)"><?= h($f['etablissement']) ?></div>
                                <?php if ($f['domaine']): ?>
                                <div class="text-muted small"><?= h($f['domaine']) ?></div>
                                <?php endif; ?>
                                <div class="text-muted" style="font-size:.78rem">
                                    <?= formatDateFr($f['date_debut']) ?> –
                                    <?= $f['en_cours'] ? '<span class="badge bg-success">Présent</span>' : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                                    <?php if ($f['lieu']): ?> · <?= h($f['lieu']) ?><?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-sm"
                                    style="background:var(--ecein-surface-2)"
                                    onclick="supprimerFormation(<?= $f['id'] ?>, this)"
                                    title="Supprimer">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </div>
                        <?php if ($f['description']): ?>
                        <p class="text-muted small mt-2 mb-0"><?= nl2br(h($f['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Ajouter Formation -->
    <div class="modal fade" id="modalFormation" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold">Ajouter une formation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="ajouter_formation">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Établissement *</label>
                            <input type="text" name="etablissement" class="form-control" required placeholder="ECE Paris, Université Paris...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Diplôme *</label>
                            <input type="text" name="diplome" class="form-control" required placeholder="Master, Licence, Bac+5...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Domaine</label>
                            <input type="text" name="domaine" class="form-control" placeholder="Informatique, Finance...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lieu</label>
                            <input type="text" name="lieu" class="form-control" placeholder="Paris, Lyon...">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Date début *</label>
                                <input type="date" name="date_debut" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Date fin</label>
                                <input type="date" name="date_fin" class="form-control" id="date_fin_form">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="en_cours" id="en_cours"
                                   onchange="document.getElementById('date_fin_form').disabled = this.checked">
                            <label class="form-check-label" for="en_cours">Formation en cours</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Spécialités, mention, activités..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" style="background:var(--ecein-surface-2)" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-ecein-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php elseif ($onglet === 'projets'): ?>
    <!-- ONGLET PROJETS -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-kanban me-2"></i>Mes Projets</span>
            <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalProjet">
                <i class="bi bi-plus-lg me-1"></i>Ajouter
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($projets)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-kanban fs-1 mb-3 d-block opacity-25"></i>
                <p>Aucun projet renseigné. Ajoutez vos réalisations !</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($projets as $p): ?>
                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-bold mb-0"><?= h($p['titre']) ?></h6>
                                <?php if ($p['en_cours']): ?><span class="badge bg-success">En cours</span><?php endif; ?>
                            </div>
                            <?php if ($p['type']): ?>
                            <span class="badge border mb-2" style="background:var(--ecein-surface-2);color:var(--ecein-text)"><?= h($p['type']) ?></span>
                            <?php endif; ?>
                            <p class="text-muted small"><?= nl2br(h($p['description'] ?? '')) ?></p>
                            <?php if ($p['date_debut']): ?>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= formatDateFr($p['date_debut']) ?>
                                <?= $p['date_fin'] ? ' – ' . formatDateFr($p['date_fin']) : '' ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($p['lien']): ?>
                            <a href="<?= h($p['lien']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Voir
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent text-end">
                            <button class="btn btn-sm btn-outline-danger" onclick="supprimerProjet(<?= $p['id'] ?>, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Ajouter Projet -->
    <div class="modal fade" id="modalProjet" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold">Ajouter un projet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="ajouter_projet">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Titre *</label>
                            <input type="text" name="titre" class="form-control" required placeholder="Nom du projet">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="type_projet" class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="Projet académique">Projet académique</option>
                                <option value="Projet Erasmus">Projet Erasmus</option>
                                <option value="Projet entreprise">Projet entreprise</option>
                                <option value="Projet personnel">Projet personnel</option>
                                <option value="Association étudiante">Association étudiante</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Date début</label>
                                <input type="date" name="date_debut" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Date fin</label>
                                <input type="date" name="date_fin" class="form-control">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="en_cours" id="proj_en_cours">
                            <label class="form-check-label" for="proj_en_cours">Projet en cours</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lien (GitHub, site web...)</label>
                            <input type="url" name="lien" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" style="background:var(--ecein-surface-2)" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-ecein-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php elseif ($onglet === 'cv'): ?>
    <!-- ONGLET CV -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-person me-2"></i>Générer mon CV</h6>
                    <p class="text-muted small">Votre CV sera généré automatiquement à partir de vos données de profil.</p>
                    <div class="d-grid gap-2">
                        <a href="cv/generer_cv.php?format=html" target="_blank"
                           class="btn btn-ecein-primary">
                            <i class="bi bi-file-earmark-code me-2"></i>CV en HTML
                        </a>
                        <a href="cv/generer_cv.php?format=pdf" target="_blank"
                           class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf me-2"></i>CV en PDF
                        </a>
                        <a href="cv/generer_cv.php?format=xml" target="_blank"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-file-earmark-code me-2"></i>Exporter XML
                        </a>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-2">Importer depuis XML</h6>
                    <form method="POST" action="api/import_cv.php" enctype="multipart/form-data">
                        <div class="mb-2">
                            <input type="file" name="cv_xml" class="form-control form-control-sm" accept=".xml">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-upload me-1"></i>Importer
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <!-- Aperçu du CV -->
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="bi bi-eye me-2"></i>Aperçu du CV
                </div>
                <div class="card-body" id="cv-apercu">
                    <iframe src="cv/generer_cv.php?format=html&preview=1"
                            class="w-100 border-0" style="height:600px" title="Aperçu CV"></iframe>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($onglet === 'albums'): ?>
    <!-- ONGLET ALBUMS -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Mes Albums Photos</h5>
        <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalAlbum">
            <i class="bi bi-plus-lg me-1"></i>Créer un album
        </button>
    </div>
    <?php if (empty($albums)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-images fs-1 mb-3 d-block opacity-25"></i>
            <p>Aucun album photo. Créez votre premier album !</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($albums as $album): ?>
        <div class="col-sm-6 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="album-cover" style="height:150px;background:var(--ecein-surface-2);background-image:url('<?= h($album['couverture'] ?? '') ?>')">
                    <?php if (!$album['couverture']): ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="bi bi-images fs-1 text-muted opacity-25"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold mb-1"><?= h($album['nom']) ?></h6>
                    <p class="text-muted small mb-0"><?= h($album['description'] ?? '') ?></p>
                    <p class="text-muted" style="font-size:.75rem"><?= formatDateFr($album['date_creation']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php elseif ($onglet === 'publications'): ?>
    <!-- ONGLET PUBLICATIONS -->
    <div class="row g-3">
        <?php if (empty($publications)): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-chat-square-dots fs-1 mb-3 d-block opacity-25"></i>
                    <p>Aucune publication pour l'instant.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($publications as $post): ?>
        <div class="col-12">
            <?php include __DIR__ . '/includes/post_card.php'; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php elseif ($onglet === 'parametres'): ?>
    <!-- ONGLET PARAMÈTRES -->
    <div class="card shadow-sm">
        <div class="card-header fw-bold"><i class="bi bi-gear me-2"></i>Paramètres du compte</div>
        <div class="card-body">
            <form method="POST" id="formParametres">
                <input type="hidden" name="action" value="update_profil">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Titre professionnel</label>
                        <input type="text" name="titre" class="form-control"
                               value="<?= h($userCourant['titre'] ?? '') ?>"
                               placeholder="Étudiant ING2 · Développeur Full-Stack">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Biographie</label>
                        <textarea name="biographie" class="form-control" rows="4"><?= h($userCourant['biographie'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Localisation</label>
                        <input type="text" name="localisation" class="form-control"
                               value="<?= h($userCourant['localisation'] ?? '') ?>"
                               placeholder="Paris, France">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control"
                               value="<?= h($userCourant['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Site web / Portfolio</label>
                        <input type="url" name="site_web" class="form-control"
                               value="<?= h($userCourant['site_web'] ?? '') ?>"
                               placeholder="https://monportfolio.fr">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-ecein-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ===== MODAL : Éditer le profil ===== -->
<div class="modal fade" id="modalEditProfil" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Modifier le profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre professionnel</label>
                        <input type="text" name="titre" class="form-control"
                               value="<?= h($userCourant['titre'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Biographie</label>
                        <textarea name="biographie" class="form-control" rows="3"><?= h($userCourant['biographie'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Localisation</label>
                        <input type="text" name="localisation" class="form-control"
                               value="<?= h($userCourant['localisation'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Site web</label>
                        <input type="url" name="site_web" class="form-control"
                               value="<?= h($userCourant['site_web'] ?? '') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background:var(--ecein-surface-2)" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Album -->
<div class="modal fade" id="modalAlbum" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Créer un album</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/albums.php">
                <input type="hidden" name="action" value="creer_album">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom de l'album *</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background:var(--ecein-surface-2)" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
