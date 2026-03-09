<?php
/**
 * ECE In - Panel d'administration
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$pdo       = getDB();
$pageTitle = 'Administration';

// Statistiques
$stats = [];
$stats['utilisateurs'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn();
$stats['publications']  = $pdo->query("SELECT COUNT(*) FROM publications")->fetchColumn();
$stats['connexions']    = $pdo->query("SELECT COUNT(*) FROM connexions WHERE statut = 'accepte'")->fetchColumn();
$stats['emplois']       = $pdo->query("SELECT COUNT(*) FROM emplois WHERE actif = 1")->fetchColumn();

// Utilisateurs récents
$stmtUsers = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 10");
$utilisateurs = $stmtUsers->fetchAll();

// Actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Ajouter un utilisateur
    if ($action === 'ajouter_utilisateur') {
        $hash = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
        try {
            $pdo->prepare("
                INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, role)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                trim($_POST['pseudo']),
                trim($_POST['email']),
                $hash,
                trim($_POST['nom']),
                trim($_POST['prenom']),
                $_POST['role'] ?? 'auteur',
            ]);
            $succes = "Utilisateur créé avec succès.";
        } catch (PDOException $e) {
            $erreur = "Erreur : pseudo ou email déjà utilisé.";
        }
    }

    // Supprimer un utilisateur
    if ($action === 'supprimer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET actif = 0 WHERE id = ?")
                ->execute([$uid]);
            $succes = "Utilisateur désactivé.";
        } else {
            $erreur = "Vous ne pouvez pas vous supprimer vous-même.";
        }
    }

    // Activer un utilisateur
    if ($action === 'activer_utilisateur') {
        $uid = (int) $_POST['utilisateur_id'];
        $pdo->prepare("UPDATE utilisateurs SET actif = 1 WHERE id = ?")->execute([$uid]);
        $succes = "Utilisateur activé.";
    }

    // Modifier le rôle
    if ($action === 'modifier_role') {
        $uid  = (int) $_POST['utilisateur_id'];
        $role = $_POST['role'] ?? 'auteur';
        if ($uid !== $_SESSION['utilisateur_id']) {
            $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?")
                ->execute([$role, $uid]);
            $succes = "Rôle modifié.";
        }
    }

    redirect('index.php');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// Recharger avec filtres
$filtreActif = $_GET['actif'] ?? 'tous';
$filtreRole  = $_GET['role'] ?? 'tous';

$cond   = [];
$params = [];
if ($filtreActif === 'actif')   { $cond[] = 'actif = 1'; }
if ($filtreActif === 'inactif') { $cond[] = 'actif = 0'; }
if ($filtreRole !== 'tous')     { $cond[] = 'role = ?'; $params[] = $filtreRole; }

$where = $cond ? 'WHERE ' . implode(' AND ', $cond) : '';
$stmtUsers = $pdo->prepare("SELECT * FROM utilisateurs $where ORDER BY date_inscription DESC");
$stmtUsers->execute($params);
$utilisateurs = $stmtUsers->fetchAll();
?>

<div class="container-xl py-4">
    <!-- En-tête admin -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-shield-fill text-danger me-2"></i>Panel d'administration
            </h4>
            <p class="text-muted mb-0">Gestion des utilisateurs et du contenu ECE In</p>
        </div>
        <a href="../index.php" class="btn btn-light">
            <i class="bi bi-arrow-left me-2"></i>Retour au site
        </a>
    </div>

    <?php if (isset($succes)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
    <?php endif; ?>
    <?php if (isset($erreur)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $stats['utilisateurs'] ?></div>
                        <div class="opacity-75">Utilisateurs actifs</div>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $stats['publications'] ?></div>
                        <div class="opacity-75">Publications</div>
                    </div>
                    <i class="bi bi-file-post-fill fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $stats['connexions'] ?></div>
                        <div class="opacity-75">Connexions</div>
                    </div>
                    <i class="bi bi-diagram-3-fill fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 bg-warning text-dark">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $stats['emplois'] ?></div>
                        <div class="opacity-50">Offres d'emploi</div>
                    </div>
                    <i class="bi bi-briefcase-fill fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestion des utilisateurs -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-people me-2"></i>Gestion des utilisateurs</span>
            <button class="btn btn-sm btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalAjouterUser">
                <i class="bi bi-person-plus me-1"></i>Ajouter
            </button>
        </div>

        <!-- Filtres -->
        <div class="card-body border-bottom pb-3">
            <div class="d-flex flex-wrap gap-2">
                <a href="?actif=tous" class="btn btn-sm <?= $filtreActif === 'tous' ? 'btn-secondary' : 'btn-light' ?>">Tous</a>
                <a href="?actif=actif" class="btn btn-sm <?= $filtreActif === 'actif' ? 'btn-success' : 'btn-light' ?>">Actifs</a>
                <a href="?actif=inactif" class="btn btn-sm <?= $filtreActif === 'inactif' ? 'btn-danger' : 'btn-light' ?>">Inactifs</a>
                <a href="?role=administrateur" class="btn btn-sm <?= $filtreRole === 'administrateur' ? 'btn-danger' : 'btn-light' ?>">Admins</a>
                <a href="?role=auteur" class="btn btn-sm <?= $filtreRole === 'auteur' ? 'btn-info' : 'btn-light' ?>">Auteurs</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Inscription</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr class="<?= !$u['actif'] ? 'table-secondary opacity-50' : '' ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= h(photo($u['photo'] ?? null)) ?>" alt=""
                                     class="rounded-circle" width="36" height="36" style="object-fit:cover">
                                <div>
                                    <div class="fw-semibold small"><?= h($u['prenom'] . ' ' . $u['nom']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem">@<?= h($u['pseudo']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= h($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'administrateur'): ?>
                            <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Auteur</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= formatDateFr($u['date_inscription']) ?></td>
                        <td>
                            <?php if ($u['actif']): ?>
                            <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="../utilisateur.php?id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-light" title="Voir profil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($u['id'] !== $_SESSION['utilisateur_id']): ?>
                                <!-- Modifier rôle -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="modifier_role">
                                    <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm d-inline-block w-auto"
                                            onchange="this.form.submit()">
                                        <option value="auteur" <?= $u['role'] === 'auteur' ? 'selected' : '' ?>>Auteur</option>
                                        <option value="administrateur" <?= $u['role'] === 'administrateur' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>

                                <!-- Activer/Désactiver -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action"
                                           value="<?= $u['actif'] ? 'supprimer_utilisateur' : 'activer_utilisateur' ?>">
                                    <input type="hidden" name="utilisateur_id" value="<?= $u['id'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm <?= $u['actif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
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
</div>

<!-- Modal Ajouter utilisateur -->
<div class="modal fade" id="modalAjouterUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ajouter un utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_utilisateur">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Pseudo *</label>
                            <input type="text" name="pseudo" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email ECE *</label>
                            <input type="email" name="email" class="form-control" required
                                   placeholder="prenom.nom@edu.ece.fr">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Mot de passe *</label>
                            <input type="password" name="mot_de_passe" class="form-control" required
                                   minlength="8">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Rôle</label>
                            <select name="role" class="form-select">
                                <option value="auteur">Auteur</option>
                                <option value="administrateur">Administrateur</option>
                            </select>
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
