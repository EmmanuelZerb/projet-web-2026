<?php
/**
 * ECE In - Page de connexion / inscription (Version Alternative - Dark Cyberpunk)
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
            $erreur = 'Pseudo ou email introuvable. Vérifiez vos informations.';
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
        $erreur = 'Seules les adresses email ECE (@edu.ece.fr ou @ece.fr) sont autorisées.';
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
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, date_inscription)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$pseudo, $email, $hash, $nom, $prenom]);
            $succes = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            $mode = 'connexion';
        }
    }
}

$pageTitle = 'Connexion';
include __DIR__ . '/includes/header.php';
?>

<div class="login-page">
    <div class="container-fluid vh-100">
        <div class="row h-100">
            <!-- Panneau gauche (visuel) -->
            <div class="col-lg-6 d-none d-lg-flex login-banner align-items-center justify-content-center">
                <div class="login-banner-content text-center text-white p-5">
                    <div class="logo-ecein logo-ecein-xl mb-4 mx-auto">
                        <span class="logo-ece">ECE</span><span class="logo-in">In</span>
                    </div>
                    <h1 class="fw-bold display-5 mb-3">Bienvenue sur ECE In</h1>
                    <p class="lead opacity-90"><?= SITE_SLOGAN ?></p>
                    <div class="login-features mt-4">
                        <div class="login-feature"><i class="bi bi-people-fill"></i> Connectez-vous avec votre réseau</div>
                        <div class="login-feature"><i class="bi bi-briefcase-fill"></i> Trouvez stages &amp; emplois</div>
                        <div class="login-feature"><i class="bi bi-trophy-fill"></i> Partagez vos réalisations</div>
                        <div class="login-feature"><i class="bi bi-chat-dots-fill"></i> Échangez en temps réel</div>
                    </div>
                </div>
            </div>

            <!-- Panneau droit (formulaire) -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center" style="background:var(--ecein-surface)">
                <div class="login-form-wrapper w-100 px-4 px-md-5">

                    <div class="text-center mb-4 d-lg-none">
                        <div class="logo-ecein logo-ecein-lg mx-auto mb-2">
                            <span class="logo-ece">ECE</span><span class="logo-in">In</span>
                        </div>
                    </div>

                    <ul class="nav nav-pills nav-justified mb-4" id="loginTabs">
                        <li class="nav-item">
                            <button class="nav-link <?= $mode === 'connexion' ? 'active' : '' ?>"
                                    id="tab-connexion" type="button"
                                    onclick="switchMode('connexion')">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $mode === 'inscription' ? 'active' : '' ?>"
                                    id="tab-inscription" type="button"
                                    onclick="switchMode('inscription')">
                                <i class="bi bi-person-plus me-1"></i>S'inscrire
                            </button>
                        </li>
                    </ul>

                    <?php if ($erreur): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= h($erreur) ?></div>
                    <?php endif; ?>
                    <?php if ($succes): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= h($succes) ?></div>
                    <?php endif; ?>

                    <!-- FORMULAIRE CONNEXION -->
                    <div id="form-connexion" <?= $mode !== 'connexion' ? 'style="display:none"' : '' ?>>
                        <h4 class="fw-bold mb-4" style="color:var(--ecein-text)">Connexion à ECE In</h4>
                        <form method="POST" action="login.php" novalidate>
                            <input type="hidden" name="action" value="connexion">

                            <div class="mb-3">
                                <label for="pseudo" class="form-label fw-semibold">Pseudo ou Email ECE</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person" style="color:var(--ecein-cyan)"></i></span>
                                    <input type="text" class="form-control" id="pseudo" name="pseudo"
                                           placeholder="votre.pseudo ou email@edu.ece.fr"
                                           value="<?= h($_POST['pseudo'] ?? '') ?>" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="mot_de_passe" class="form-label fw-semibold">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock" style="color:var(--ecein-cyan)"></i></span>
                                    <input type="password" class="form-control" id="mot_de_passe"
                                           name="mot_de_passe" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('mot_de_passe')">
                                        <i class="bi bi-eye" id="eye-mot_de_passe"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                            </button>

                            <div class="text-center mt-3">
                                <small style="color:var(--ecein-muted)">
                                    Identifiants de test : pseudo <strong style="color:var(--ecein-cyan)">jdupont</strong>, mdp <strong style="color:var(--ecein-cyan)">password</strong>
                                </small>
                            </div>
                        </form>
                    </div>

                    <!-- FORMULAIRE INSCRIPTION -->
                    <div id="form-inscription" <?= $mode !== 'inscription' ? 'style="display:none"' : '' ?>>
                        <h4 class="fw-bold mb-4" style="color:var(--ecein-text)">Créer votre compte ECE In</h4>
                        <form method="POST" action="login.php?mode=inscription" novalidate>
                            <input type="hidden" name="action" value="inscription">

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="prenom" class="form-label fw-semibold">Prénom</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom"
                                           placeholder="Jean" value="<?= h($_POST['prenom'] ?? '') ?>" required>
                                </div>
                                <div class="col-6">
                                    <label for="nom" class="form-label fw-semibold">Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                           placeholder="Dupont" value="<?= h($_POST['nom'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pseudo_ins" class="form-label fw-semibold">Pseudo</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="color:var(--ecein-cyan)">@</span>
                                    <input type="text" class="form-control" id="pseudo_ins" name="pseudo"
                                           placeholder="jean.dupont" value="<?= h($_POST['pseudo'] ?? '') ?>" required>
                                </div>
                                <div class="form-text">Lettres, chiffres, tirets, underscores (3-50 caractères)</div>
                            </div>

                            <div class="mb-3">
                                <label for="email_ins" class="form-label fw-semibold">Email ECE</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope" style="color:var(--ecein-cyan)"></i></span>
                                    <input type="email" class="form-control" id="email_ins" name="email"
                                           placeholder="prenom.nom@edu.ece.fr"
                                           value="<?= h($_POST['email'] ?? '') ?>" required>
                                </div>
                                <div class="form-text">Uniquement les emails @edu.ece.fr ou @ece.fr</div>
                            </div>

                            <div class="mb-3">
                                <label for="mdp_ins" class="form-label fw-semibold">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock" style="color:var(--ecein-cyan)"></i></span>
                                    <input type="password" class="form-control" id="mdp_ins"
                                           name="mot_de_passe" placeholder="Min. 8 caractères" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('mdp_ins')">
                                        <i class="bi bi-eye" id="eye-mdp_ins"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="mdp_ins2" class="form-label fw-semibold">Confirmer le mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill" style="color:var(--ecein-cyan)"></i></span>
                                    <input type="password" class="form-control" id="mdp_ins2"
                                           name="mot_de_passe2" placeholder="Répétez le mot de passe" required>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="cgu" required>
                                <label class="form-check-label small" for="cgu">
                                    J'accepte les <a href="#">conditions d'utilisation</a> de ECE In
                                </label>
                            </div>

                            <button type="submit" class="btn btn-ecein-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-person-plus me-2"></i>Créer mon compte
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchMode(mode) {
    document.getElementById('form-connexion').style.display = mode === 'connexion' ? 'block' : 'none';
    document.getElementById('form-inscription').style.display = mode === 'inscription' ? 'block' : 'none';
    document.getElementById('tab-connexion').classList.toggle('active', mode === 'connexion');
    document.getElementById('tab-inscription').classList.toggle('active', mode === 'inscription');
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById('eye-' + inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
