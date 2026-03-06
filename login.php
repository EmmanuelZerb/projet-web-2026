<?php
/**
 * ECE In - Page de connexion / inscription (Version Alt - Centered Glass)
 */
require_once __DIR__ . '/config/config.php';

if (estConnecte()) {
    redirect('index.php');
}

$erreur    = '';
$succes    = '';
$mode      = $_GET['mode'] ?? 'connexion';

// ===================== TRAITEMENT CONNEXION =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'connexion') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pdo    = getDB();

    if (empty($pseudo) && empty($email)) {
        $erreur = 'Veuillez saisir votre pseudo ou votre adresse email.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM utilisateurs
            WHERE (pseudo = ? OR email = ?) AND actif = 1
            LIMIT 1
        ");
        $stmt->execute([$pseudo ?: $email, $email ?: $pseudo]);
        $utilisateur = $stmt->fetch();

        if (!$utilisateur) {
            $erreur = 'Pseudo ou email introuvable.';
        } elseif (!password_verify($_POST['mot_de_passe'] ?? '', $utilisateur['mot_de_passe'])) {
            $erreur = 'Mot de passe incorrect.';
        } else {
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['pseudo']         = $utilisateur['pseudo'];
            $_SESSION['role']           = $utilisateur['role'];
            $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")
                ->execute([$utilisateur['id']]);
            redirect('index.php');
        }
    }
}

// ===================== TRAITEMENT INSCRIPTION =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $mdp2   = $_POST['mot_de_passe2'] ?? '';
    $pdo    = getDB();

    if (empty($nom) || empty($prenom) || empty($pseudo) || empty($email) || empty($mdp)) {
        $erreur = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (!str_ends_with($email, '@edu.ece.fr') && !str_ends_with($email, '@ece.fr')) {
        $erreur = 'Seules les adresses @edu.ece.fr ou @ece.fr sont autorisées.';
    } elseif (strlen($mdp) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } elseif (!preg_match('/^[a-z0-9_\-]{3,50}$/i', $pseudo)) {
        $erreur = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores (3-50 caractères).';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $erreur = 'Ce pseudo ou email est déjà utilisé.';
        } else {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            $pdo->prepare("
                INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, date_inscription)
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([$pseudo, $email, $hash, $nom, $prenom]);
            $succes = 'Compte créé ! Vous pouvez vous connecter.';
            $mode = 'connexion';
        }
    }
}

$pageTitle = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111118">
    <title>Connexion - <?= SITE_NOM ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-bg"></div>
    <div class="login-bg-grid"></div>

    <div class="login-card" style="animation: fadeUp .6s ease-out">
        <!-- Logo -->
        <div class="login-logo-row">
            <div class="logo-mark" style="width:44px;height:44px;font-size:.95rem;border-radius:12px">EC</div>
            <div>
                <div class="logo-text" style="font-size:1.35rem">ECE<span>In</span></div>
            </div>
        </div>
        <p class="login-tagline"><?= SITE_SLOGAN ?></p>

        <!-- Tabs -->
        <div class="d-flex gap-2 mb-4">
            <button class="btn flex-fill <?= $mode === 'connexion' ? 'btn-ecein-primary' : 'btn-light' ?>"
                    id="tab-connexion" onclick="switchMode('connexion')">
                Connexion
            </button>
            <button class="btn flex-fill <?= $mode === 'inscription' ? 'btn-ecein-primary' : 'btn-light' ?>"
                    id="tab-inscription" onclick="switchMode('inscription')">
                S'inscrire
            </button>
        </div>

        <!-- Alertes -->
        <?php if ($erreur): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
        <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
        <?php endif; ?>

        <!-- ===== FORM CONNEXION ===== -->
        <div id="form-connexion" <?= $mode !== 'connexion' ? 'style="display:none"' : '' ?>>
            <form method="POST" action="login.php" novalidate>
                <input type="hidden" name="action" value="connexion">

                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo ou Email ECE</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at" style="color:var(--accent)"></i></span>
                        <input type="text" class="form-control" id="pseudo" name="pseudo"
                               placeholder="pseudo ou email@edu.ece.fr"
                               value="<?= h($_POST['pseudo'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key" style="color:var(--accent)"></i></span>
                        <input type="password" class="form-control" id="mot_de_passe"
                               name="mot_de_passe" placeholder="••••••••" required>
                        <button class="btn btn-light" type="button" onclick="togglePassword('mot_de_passe')">
                            <i class="bi bi-eye" id="eye-mot_de_passe"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                    Se connecter <i class="bi bi-arrow-right ms-1"></i>
                </button>

                <div class="text-center mt-3">
                    <small style="color:var(--text-3)">
                        Test : <strong style="color:var(--accent)">jdupont</strong> / <strong style="color:var(--accent)">password</strong>
                    </small>
                </div>
            </form>
        </div>

        <!-- ===== FORM INSCRIPTION ===== -->
        <div id="form-inscription" <?= $mode !== 'inscription' ? 'style="display:none"' : '' ?>>
            <form method="POST" action="login.php?mode=inscription" novalidate>
                <input type="hidden" name="action" value="inscription">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-control" name="prenom"
                               placeholder="Jean" value="<?= h($_POST['prenom'] ?? '') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom"
                               placeholder="Dupont" value="<?= h($_POST['nom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pseudo</label>
                    <div class="input-group">
                        <span class="input-group-text" style="color:var(--accent)">@</span>
                        <input type="text" class="form-control" name="pseudo"
                               placeholder="jean.dupont" value="<?= h($_POST['pseudo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email ECE</label>
                    <input type="email" class="form-control" name="email"
                           placeholder="prenom.nom@edu.ece.fr" value="<?= h($_POST['email'] ?? '') ?>" required>
                    <div class="form-text">@edu.ece.fr ou @ece.fr uniquement</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" name="mot_de_passe"
                               placeholder="Min. 8 car." required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Confirmation</label>
                        <input type="password" class="form-control" name="mot_de_passe2"
                               placeholder="Répéter" required>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="cgu" required>
                    <label class="form-check-label small" for="cgu" style="color:var(--text-3)">
                        J'accepte les <a href="#">conditions d'utilisation</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                    Créer mon compte <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    document.getElementById('form-connexion').style.display = mode === 'connexion' ? 'block' : 'none';
    document.getElementById('form-inscription').style.display = mode === 'inscription' ? 'block' : 'none';
    const tc = document.getElementById('tab-connexion');
    const ti = document.getElementById('tab-inscription');
    tc.className = 'btn flex-fill ' + (mode === 'connexion' ? 'btn-ecein-primary' : 'btn-light');
    ti.className = 'btn flex-fill ' + (mode === 'inscription' ? 'btn-ecein-primary' : 'btn-light');
}
function togglePassword(id) {
    const i = document.getElementById(id);
    const e = document.getElementById('eye-' + id);
    if (i.type === 'password') { i.type = 'text'; e.classList.replace('bi-eye', 'bi-eye-slash'); }
    else { i.type = 'password'; e.classList.replace('bi-eye-slash', 'bi-eye'); }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
