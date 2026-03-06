<?php
/**
 * ECE In - Panel d'administration (Version Alt)
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pdo       = getDB();
$pageTitle = 'Administration';

$stats = [];
$stats['utilisateurs'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn();
$stats['publications']  = $pdo->query("SELECT COUNT(*) FROM publications")->fetchColumn();
$stats['connexions']    = $pdo->query("SELECT COUNT(*) FROM connexions WHERE statut = 'accepte'")->fetchColumn();
$stats['emplois']       = $pdo->query("SELECT COUNT(*) FROM emplois WHERE actif = 1")->fetchColumn();

$stmtUsers = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 10");
$utilisateurs = $stmtUsers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter_utilisateur') {
        $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
        try {
            $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, role) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([trim($_POST['pseudo']), trim($_POST['email']), $hash, trim($_POST['nom']), trim($_POST['prenom']), $_POST['role'] ?? 'auteur']);
            $succes = "Utilisateur créé avec succès.";
        } catch (PDOException $e) {
            $erreur = "Erreur : pseudo ou email déjà utilisé.";
        }
    }

    if ($action === 'supprimer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET actif = 0 WHERE id = ?")->execute([$uid]);
            $succes = "Utilisateur désactivé.";
        } else { $erreur = "Vous ne pouvez pas vous supprimer vous-même."; }
    }

    if ($action === 'activer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        $pdo->prepare("UPDATE utilisateurs SET actif = 1 WHERE id = ?")->execute([$uid]);
        $succes = "Utilisateur activé.";
    }

    if ($action === 'modifier_role') {
        $uid = (int) $_POST['utilisateur_id']; $role = $_POST['role'] ?? 'auteur';
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?")->execute([$role, $uid]);
            $succes = "Rôle modifié.";
        }
    }

    redirect('index.php');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

$filtreActif = $_GET['actif'] ?? 'tous';
$filtreRole  = $_GET['role'] ?? 'tous';

$cond = []; $params = [];
if ($filtreActif === 'actif')   { $cond[] = 'actif = 1'; }
if ($filtreActif === 'inactif') { $cond[] = 'actif = 0'; }
if ($filtreRole !== 'tous')     { $cond[] = 'role = ?'; $params[] = $filtreRole; }

$where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
$stmtUsers = $pdo->prepare("SELECT * FROM utilisateurs $where ORDER BY date_inscription DESC");
$stmtUsers->execute($params);
$utilisateurs = $stmtUsers->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-shield-fill me-2" style="color:var(--rose)"></i>Panel d'administration</h5>
        <p class="mb-0 small" style="color:var(--text-3)">Gestion des utilisateurs et du contenu ECE In</p>
    </div>
    <a href="../index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i>Retour au site</a>
</div>

<?php if (isset($succes)): ?>
<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
<?php endif; ?>
<?php if (isset($erreur)): ?>
<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $statItems = [
        ['label' => 'Utilisateurs', 'val' => $stats['utilisateurs'], 'icon' => 'bi-people-fill', 'color' => 'var(--accent)'],
        ['label' => 'Publications', 'val' => $stats['publications'], 'icon' => 'bi-file-post-fill', 'color' => 'var(--emerald)'],
        ['label' => 'Connexions',   'val' => $stats['connexions'],   'icon' => 'bi-diagram-3-fill', 'color' => 'var(--sky)'],
        ['label' => 'Offres',       'val' => $stats['emplois'],      'icon' => 'bi-briefcase-fill',  'color' => 'var(--rose)'],
    ];
    foreach ($statItems as $s): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="glass p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-2 fw-bold"><?= $s['val'] ?></div>
                    <div style="font-size:.78rem;color:var(--text-3)"><?= $s['label'] ?></div>
                </div>
                <i class="bi <?= $s['icon'] ?> fs-1" style="color:<?= $s['color'] ?>;opacity:.2"></i>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gestion utilisateurs -->
<div class="glass p-0">
    <div class="p-3 d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--glass-border)">
        <span class="fw-bold" style="font-size:.85rem"><i class="bi bi-people me-2" style="color:var(--accent)"></i>Gestion des utilisateurs</span>
        <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterUser">
            <i class="bi bi-person-plus me-1"></i>Ajouter
        </button>
    </div>

    <div class="p-3" style="border-bottom:1px solid var(--glass-border)">
        <div class="d-flex flex-wrap gap-2">
            <a href="?actif=tous" class="btn btn-sm <?= $filtreActif === 'tous' ? 'btn-ecein-primary' : 'btn-light' ?>">Tous</a>
            <a href="?actif=actif" class="btn btn-sm <?= $filtreActif === 'actif' ? 'btn-success' : 'btn-light' ?>">Actifs</a>
            <a href="?actif=inactif" class="btn btn-sm <?= $filtreActif === 'inactif' ? 'btn-outline-danger' : 'btn-light' ?>">Inactifs</a>
            <a href="?role=administrateur" class="btn btn-sm <?= $filtreRole === 'administrateur' ? 'btn-outline-danger' : 'btn-light' ?>">Admins</a>
            <a href="?role=auteur" class="btn btn-sm <?= $filtreRole === 'auteur' ? 'btn-outline-primary' : 'btn-light' ?>">Auteurs</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Inscription</th><th>Statut</th><th class="text-center">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                <tr style="<?= !$u['actif'] ? 'opacity:.5' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= h($u['photo']) ?>" alt="" class="rounded-3" width="34" height="34" style="object-fit:cover">
                            <div>
                                <div class="fw-semibold small"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                                <div style="font-size:.7rem;color:var(--text-3)">@<?= h($u['pseudo']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= h($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'administrateur'): ?>
                        <span class="badge bg-danger" style="font-size:.6rem">Admin</span>
                        <?php else: ?>
                        <span class="badge bg-secondary" style="font-size:.6rem">Auteur</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= formatDateFr($u['date_inscription']) ?></td>
                    <td>
                        <?php if ($u['actif']): ?><span class="badge bg-success" style="font-size:.6rem">Actif</span>
                        <?php else: ?><span class="badge bg-danger" style="font-size:.6rem">Inactif</span><?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="../utilisateur.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-light" title="Profil"><i class="bi bi-eye"></i></a>
                            <?php if ($u['id'] !== $_SESSION['utilisateur_id']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="modifier_role">
                                <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                <select name="role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <option value="auteur" <?= $u['role'] === 'auteur' ? 'selected' : '' ?>>Auteur</option>
                                    <option value="administrateur" <?= $u['role'] === 'administrateur' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="<?= $u['actif'] ? 'supprimer_utilisateur' : 'activer_utilisateur' ?>">
                                <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $u['actif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        onclick="return confirm('Confirmer cette action ?')">
                                    <i class="bi <?= $u['actif'] ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajouter utilisateur -->
<div class="modal fade" id="modalAjouterUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Ajouter un utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_utilisateur">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label fw-semibold">Prénom *</label><input type="text" name="prenom" class="form-control" required></div>
                        <div class="col-6"><label class="form-label fw-semibold">Nom *</label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-12"><label class="form-label fw-semibold">Pseudo *</label><input type="text" name="pseudo" class="form-control" required></div>
                        <div class="col-12"><label class="form-label fw-semibold">Email ECE *</label><input type="email" name="email" class="form-control" required placeholder="prenom.nom@edu.ece.fr"></div>
                        <div class="col-12"><label class="form-label fw-semibold">Mot de passe *</label><input type="password" name="mot_de_passe" class="form-control" required minlength="8"></div>
                        <div class="col-12"><label class="form-label fw-semibold">Rôle</label>
                            <select name="role" class="form-select"><option value="auteur">Auteur</option><option value="administrateur">Administrateur</option></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
