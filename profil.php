<?php
/**
 * ECE In - Page Profil (Version Alt - Hero Overlay)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$onglet      = $_GET['onglet'] ?? 'profil';
$pageTitle   = 'Mon Profil';

$stmtForm = $pdo->prepare("SELECT * FROM formations WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtForm->execute([$userId]);
$formations = $stmtForm->fetchAll();

$stmtProj = $pdo->prepare("SELECT * FROM projets WHERE utilisateur_id = ? ORDER BY date_debut DESC");
$stmtProj->execute([$userId]);
$projets = $stmtProj->fetchAll();

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

$stmtAlbums = $pdo->prepare("SELECT * FROM albums WHERE utilisateur_id = ? ORDER BY date_creation DESC");
$stmtAlbums->execute([$userId]);
$albums = $stmtAlbums->fetchAll();

// ===== Traitement POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
$userCourant = getUtilisateurConnecte();
?>

<!-- ===== HERO BANNER ===== -->
<div class="profil-banner mb-4">
    <div class="profil-bg-large"
         style="background-image:url('<?= h($userCourant['image_fond']) ?>')"
         id="bg-image">
        <label class="btn btn-sm" style="cursor:pointer;background:rgba(0,0,0,.5);color:#fff;border-radius:var(--radius-sm)"
               title="Changer la photo de fond">
            <i class="bi bi-camera me-1"></i>Fond
            <form id="form-bg" method="POST" action="api/upload.php" enctype="multipart/form-data" class="d-none">
                <input type="hidden" name="action" value="upload_bg">
                <input type="file" name="background" accept="image/*" onchange="this.form.submit()">
            </form>
        </label>
    </div>
    <div class="profil-banner-overlay"></div>
    <div class="profil-banner-info">
        <div class="position-relative">
            <img src="<?= h($userCourant['photo']) ?>" alt="Avatar" class="profil-avatar" id="avatar-img">
            <label class="btn btn-sm position-absolute bottom-0 end-0"
                   style="width:28px;height:28px;padding:0;cursor:pointer;background:var(--accent);color:#000;border-radius:8px;display:flex;align-items:center;justify-content:center"
                   title="Modifier la photo">
                <i class="bi bi-camera-fill" style="font-size:.7rem"></i>
                <form method="POST" enctype="multipart/form-data" class="d-none">
                    <input type="hidden" name="action" value="upload_avatar">
                    <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()">
                </form>
            </label>
        </div>
        <div>
            <h4 class="fw-bold mb-0 text-white"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></h4>
            <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.85rem"><?= h($userCourant['titre'] ?? 'Membre ECE In') ?></p>
        </div>
    </div>
</div>

<!-- Action buttons -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <button class="btn btn-ecein-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
        <i class="bi bi-pencil me-1"></i>Modifier le profil
    </button>
    <a href="profil.php?onglet=cv" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-file-earmark-person me-1"></i>Mon CV
    </a>
</div>

<!-- ===== TAB NAV (pills instead of tabs) ===== -->
<ul class="nav nav-pills gap-1 mb-4 flex-nowrap overflow-auto pb-1">
    <li class="nav-item"><a class="nav-link <?= $onglet === 'profil' ? 'active' : '' ?>" href="?onglet=profil"><i class="bi bi-person me-1"></i>Profil</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'publications' ? 'active' : '' ?>" href="?onglet=publications"><i class="bi bi-grid me-1"></i>Posts</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'formations' ? 'active' : '' ?>" href="?onglet=formations"><i class="bi bi-mortarboard me-1"></i>Formations</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'projets' ? 'active' : '' ?>" href="?onglet=projets"><i class="bi bi-kanban me-1"></i>Projets</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'albums' ? 'active' : '' ?>" href="?onglet=albums"><i class="bi bi-images me-1"></i>Albums</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'cv' ? 'active' : '' ?>" href="?onglet=cv"><i class="bi bi-file-earmark-pdf me-1"></i>CV</a></li>
    <li class="nav-item"><a class="nav-link <?= $onglet === 'parametres' ? 'active' : '' ?>" href="?onglet=parametres"><i class="bi bi-gear me-1"></i>Paramètres</a></li>
</ul>

<!-- ===== CONTENU ONGLETS ===== -->

<?php if ($onglet === 'profil'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4 mb-3">
            <h6 class="fw-bold mb-3" style="font-size:.85rem">Informations</h6>
            <?php if ($userCourant['biographie']): ?>
            <p class="small" style="color:var(--text-2)"><?= nl2br(h($userCourant['biographie'])) ?></p>
            <hr style="border-color:var(--glass-border)">
            <?php endif; ?>
            <ul class="list-unstyled small mb-0" style="color:var(--text-2)">
                <?php if ($userCourant['localisation']): ?>
                <li class="mb-2"><i class="bi bi-geo-alt me-2" style="color:var(--accent)"></i><?= h($userCourant['localisation']) ?></li>
                <?php endif; ?>
                <?php if ($userCourant['site_web']): ?>
                <li class="mb-2"><i class="bi bi-link-45deg me-2" style="color:var(--accent)"></i>
                    <a href="<?= h($userCourant['site_web']) ?>" target="_blank"><?= h($userCourant['site_web']) ?></a>
                </li>
                <?php endif; ?>
                <?php if ($userCourant['telephone']): ?>
                <li class="mb-2"><i class="bi bi-telephone me-2" style="color:var(--accent)"></i><?= h($userCourant['telephone']) ?></li>
                <?php endif; ?>
                <li class="mb-2"><i class="bi bi-envelope me-2" style="color:var(--accent)"></i><?= h($userCourant['email']) ?></li>
                <li><i class="bi bi-calendar me-2" style="color:var(--accent)"></i>Membre depuis <?= formatDateFr($userCourant['date_inscription']) ?></li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-mortarboard me-2" style="color:var(--accent)"></i>Formations</h6>
                <a href="?onglet=formations" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <?php if (empty($formations)): ?>
            <p class="small" style="color:var(--text-3)">Aucune formation renseignée.</p>
            <?php else: ?>
            <?php foreach (array_slice($formations, 0, 2) as $f): ?>
            <div class="d-flex gap-3 mb-3">
                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent-light);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-building" style="color:var(--accent)"></i>
                </div>
                <div>
                    <div class="fw-semibold small"><?= h($f['diplome']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-3)"><?= h($f['etablissement']) ?></div>
                    <div style="font-size:.72rem;color:var(--text-3)">
                        <?= formatDateFr($f['date_debut']) ?> –
                        <?= $f['en_cours'] ? 'Présent' : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="glass p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="font-size:.85rem"><i class="bi bi-kanban me-2" style="color:var(--teal)"></i>Projets</h6>
                <a href="?onglet=projets" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <?php if (empty($projets)): ?>
            <p class="small" style="color:var(--text-3)">Aucun projet renseigné.</p>
            <?php else: ?>
            <?php foreach (array_slice($projets, 0, 2) as $p): ?>
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold small"><?= h($p['titre']) ?></div>
                    <?php if ($p['en_cours']): ?><span class="badge bg-success" style="font-size:.6rem">En cours</span><?php endif; ?>
                </div>
                <p class="small mb-0" style="color:var(--text-3)"><?= h(substr($p['description'] ?? '', 0, 150)) ?></p>
                <?php if ($p['lien']): ?>
                <a href="<?= h($p['lien']) ?>" target="_blank" class="small"><i class="bi bi-box-arrow-up-right me-1"></i>Voir</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'formations'): ?>
<div class="glass p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard me-2" style="color:var(--accent)"></i>Mes Formations</h6>
        <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalFormation">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </button>
    </div>
    <?php if (empty($formations)): ?>
    <div class="empty-state">
        <i class="bi bi-mortarboard"></i>
        <p>Aucune formation renseignée. Ajoutez votre parcours académique !</p>
    </div>
    <?php else: ?>
    <div class="timeline">
        <?php foreach ($formations as $f): ?>
        <div class="timeline-item">
            <div class="timeline-marker" style="background:var(--accent)"></div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 small"><?= h($f['diplome']) ?></h6>
                        <div class="fw-semibold" style="font-size:.78rem;color:var(--accent)"><?= h($f['etablissement']) ?></div>
                        <?php if ($f['domaine']): ?>
                        <div style="font-size:.75rem;color:var(--text-3)"><?= h($f['domaine']) ?></div>
                        <?php endif; ?>
                        <div style="font-size:.72rem;color:var(--text-3)">
                            <?= formatDateFr($f['date_debut']) ?> –
                            <?= $f['en_cours'] ? '<span class="badge bg-success" style="font-size:.6rem">Présent</span>' : ($f['date_fin'] ? formatDateFr($f['date_fin']) : '') ?>
                            <?php if ($f['lieu']): ?> · <?= h($f['lieu']) ?><?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-light" onclick="supprimerFormation(<?= $f['id'] ?>, this)" title="Supprimer">
                        <i class="bi bi-trash" style="color:var(--rose)"></i>
                    </button>
                </div>
                <?php if ($f['description']): ?>
                <p class="small mt-2 mb-0" style="color:var(--text-3)"><?= nl2br(h($f['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Formation -->
<div class="modal fade" id="modalFormation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Ajouter une formation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_formation">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Établissement *</label><input type="text" name="etablissement" class="form-control" required placeholder="ECE Paris..."></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Diplôme *</label><input type="text" name="diplome" class="form-control" required placeholder="Master, Licence..."></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Domaine</label><input type="text" name="domaine" class="form-control" placeholder="Informatique..."></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Lieu</label><input type="text" name="lieu" class="form-control" placeholder="Paris..."></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-semibold">Date début *</label><input type="date" name="date_debut" class="form-control" required></div>
                        <div class="col-6"><label class="form-label fw-semibold">Date fin</label><input type="date" name="date_fin" class="form-control" id="date_fin_form"></div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="en_cours" id="en_cours"
                               onchange="document.getElementById('date_fin_form').disabled = this.checked">
                        <label class="form-check-label" for="en_cours">Formation en cours</label>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Spécialités..."></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'projets'): ?>
<div class="glass p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-kanban me-2" style="color:var(--teal)"></i>Mes Projets</h6>
        <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalProjet">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </button>
    </div>
    <?php if (empty($projets)): ?>
    <div class="empty-state">
        <i class="bi bi-kanban"></i>
        <p>Aucun projet renseigné. Ajoutez vos réalisations !</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($projets as $p): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 small"><?= h($p['titre']) ?></h6>
                        <?php if ($p['en_cours']): ?><span class="badge bg-success" style="font-size:.6rem">En cours</span><?php endif; ?>
                    </div>
                    <?php if ($p['type']): ?>
                    <span class="badge bg-secondary mb-2" style="font-size:.6rem"><?= h($p['type']) ?></span>
                    <?php endif; ?>
                    <p class="small" style="color:var(--text-3)"><?= nl2br(h($p['description'] ?? '')) ?></p>
                    <?php if ($p['date_debut']): ?>
                    <div style="font-size:.72rem;color:var(--text-3)">
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
                <div class="card-footer text-end">
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

<!-- Modal Projet -->
<div class="modal fade" id="modalProjet" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Ajouter un projet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_projet">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Titre *</label><input type="text" name="titre" class="form-control" required placeholder="Nom du projet"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Type</label>
                        <select name="type_projet" class="form-select">
                            <option value="">Sélectionner...</option>
                            <option value="Projet académique">Projet académique</option>
                            <option value="Projet Erasmus">Projet Erasmus</option>
                            <option value="Projet entreprise">Projet entreprise</option>
                            <option value="Projet personnel">Projet personnel</option>
                            <option value="Association étudiante">Association étudiante</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label fw-semibold">Date début</label><input type="date" name="date_debut" class="form-control"></div>
                        <div class="col-6"><label class="form-label fw-semibold">Date fin</label><input type="date" name="date_fin" class="form-control"></div>
                    </div>
                    <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="en_cours" id="proj_en_cours"><label class="form-check-label" for="proj_en_cours">Projet en cours</label></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Lien</label><input type="url" name="lien" class="form-control" placeholder="https://..."></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'cv'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4">
            <h6 class="fw-bold mb-3" style="font-size:.85rem"><i class="bi bi-file-earmark-person me-2" style="color:var(--accent)"></i>Générer mon CV</h6>
            <p class="small" style="color:var(--text-3)">CV généré automatiquement à partir de vos données de profil.</p>
            <div class="d-grid gap-2">
                <a href="cv/generer_cv.php?format=html" target="_blank" class="btn btn-ecein-primary"><i class="bi bi-file-earmark-code me-2"></i>CV en HTML</a>
                <a href="cv/generer_cv.php?format=pdf" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-2"></i>CV en PDF</a>
                <a href="cv/generer_cv.php?format=xml" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-code me-2"></i>Exporter XML</a>
            </div>
            <hr style="border-color:var(--glass-border)">
            <h6 class="fw-bold mb-2" style="font-size:.82rem">Importer depuis XML</h6>
            <form method="POST" action="api/import_cv.php" enctype="multipart/form-data">
                <div class="mb-2"><input type="file" name="cv_xml" class="form-control form-control-sm" accept=".xml"></div>
                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-upload me-1"></i>Importer</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass p-0 overflow-hidden">
            <div class="p-3" style="border-bottom:1px solid var(--glass-border)">
                <span class="fw-bold" style="font-size:.85rem"><i class="bi bi-eye me-2" style="color:var(--accent)"></i>Aperçu du CV</span>
            </div>
            <div id="cv-apercu">
                <iframe src="cv/generer_cv.php?format=html&preview=1"
                        class="w-100 border-0" style="height:600px;background:var(--bg)" title="Aperçu CV"></iframe>
            </div>
        </div>
    </div>
</div>

<?php elseif ($onglet === 'albums'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Mes Albums Photos</h6>
    <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalAlbum">
        <i class="bi bi-plus-lg me-1"></i>Créer un album
    </button>
</div>
<?php if (empty($albums)): ?>
<div class="glass empty-state">
    <i class="bi bi-images"></i>
    <p>Aucun album photo. Créez votre premier album !</p>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($albums as $album): ?>
    <div class="col-sm-6 col-md-4">
        <div class="glass p-0 overflow-hidden h-100">
            <div style="height:150px;background:var(--bg-3);background-image:url('<?= h($album['couverture'] ?? '') ?>');background-size:cover;background-position:center">
                <?php if (!$album['couverture']): ?>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <i class="bi bi-images fs-1" style="color:var(--text-3);opacity:.25"></i>
                </div>
                <?php endif; ?>
            </div>
            <div class="p-3">
                <h6 class="fw-bold mb-1 small"><?= h($album['nom']) ?></h6>
                <p class="small mb-0" style="color:var(--text-3)"><?= h($album['description'] ?? '') ?></p>
                <p style="font-size:.7rem;color:var(--text-3)"><?= formatDateFr($album['date_creation']) ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($onglet === 'publications'): ?>
<div class="row g-3">
    <?php if (empty($publications)): ?>
    <div class="col-12">
        <div class="glass empty-state">
            <i class="bi bi-chat-square-dots"></i>
            <p>Aucune publication pour l'instant.</p>
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
<div class="glass p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2" style="color:var(--accent)"></i>Paramètres du compte</h6>
    <form method="POST" id="formParametres">
        <input type="hidden" name="action" value="update_profil">
        <div class="row g-3">
            <div class="col-md-12"><label class="form-label fw-semibold">Titre professionnel</label><input type="text" name="titre" class="form-control" value="<?= h($userCourant['titre'] ?? '') ?>" placeholder="Étudiant ING2 · Développeur Full-Stack"></div>
            <div class="col-md-12"><label class="form-label fw-semibold">Biographie</label><textarea name="biographie" class="form-control" rows="4"><?= h($userCourant['biographie'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Localisation</label><input type="text" name="localisation" class="form-control" value="<?= h($userCourant['localisation'] ?? '') ?>" placeholder="Paris, France"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Téléphone</label><input type="tel" name="telephone" class="form-control" value="<?= h($userCourant['telephone'] ?? '') ?>"></div>
            <div class="col-md-12"><label class="form-label fw-semibold">Site web / Portfolio</label><input type="url" name="site_web" class="form-control" value="<?= h($userCourant['site_web'] ?? '') ?>" placeholder="https://monportfolio.fr"></div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-ecein-primary"><i class="bi bi-save me-2"></i>Enregistrer les modifications</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ===== MODAL : Éditer le profil ===== -->
<div class="modal fade" id="modalEditProfil" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Modifier le profil</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Titre professionnel</label><input type="text" name="titre" class="form-control" value="<?= h($userCourant['titre'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Biographie</label><textarea name="biographie" class="form-control" rows="3"><?= h($userCourant['biographie'] ?? '') ?></textarea></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Localisation</label><input type="text" name="localisation" class="form-control" value="<?= h($userCourant['localisation'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Site web</label><input type="url" name="site_web" class="form-control" value="<?= h($userCourant['site_web'] ?? '') ?>"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
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
            <div class="modal-header"><h5 class="modal-title fw-bold">Créer un album</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="api/albums.php">
                <input type="hidden" name="action" value="creer_album">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Nom de l'album *</label><input type="text" name="nom" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
