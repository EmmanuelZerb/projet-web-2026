<?php
/**
 * ECE In - Page Emplois / Stages / Alternances
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$pageTitle = 'Emplois & Stages';

// Filtres
$filtreType      = $_GET['type'] ?? '';
$filtreRecherche = trim($_GET['q'] ?? '');

// Construire la requête avec filtres
$conditions = ['e.actif = 1'];
$params = [];

if ($filtreType) {
    $conditions[] = 'e.type = ?';
    $params[] = $filtreType;
}
if ($filtreRecherche) {
    $conditions[] = '(e.titre LIKE ? OR e.entreprise LIKE ? OR e.description LIKE ?)';
    $params[] = "%$filtreRecherche%";
    $params[] = "%$filtreRecherche%";
    $params[] = "%$filtreRecherche%";
}

$where = implode(' AND ', $conditions);

$stmtEmplois = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom, u.photo
    FROM emplois e
    JOIN utilisateurs u ON u.id = e.publie_par
    WHERE $where
    ORDER BY e.date_publication DESC
");
$stmtEmplois->execute($params);
$emplois = $stmtEmplois->fetchAll();

// Traitement du formulaire de publication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'publier_emploi') {
        $pdo->prepare("
            INSERT INTO emplois (publie_par, titre, entreprise, description, type, lieu, salaire,
                date_debut, date_limite, lien_candidature, contact_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            trim($_POST['titre'] ?? ''),
            trim($_POST['entreprise'] ?? ''),
            trim($_POST['description'] ?? ''),
            $_POST['type_emploi'] ?? 'stage',
            trim($_POST['lieu'] ?? ''),
            trim($_POST['salaire'] ?? ''),
            !empty($_POST['date_debut']) ? $_POST['date_debut'] : null,
            !empty($_POST['date_limite']) ? $_POST['date_limite'] : null,
            trim($_POST['lien_candidature'] ?? ''),
            trim($_POST['contact_email'] ?? ''),
        ]);
        redirect('emplois.php');
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-xl py-4">
    <!-- En-tête de page -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-briefcase me-2 text-warning"></i>Emplois & Opportunités</h4>
            <p class="text-muted mb-0">Stages, alternances, CDI, CDD – Trouvez votre prochaine opportunité</p>
        </div>
        <?php if (estAdmin()): ?>
        <button class="btn btn-ecein-primary" data-bs-toggle="modal" data-bs-target="#modalPublierEmploi">
            <i class="bi bi-plus-lg me-2"></i>Publier une offre
        </button>
        <?php endif; ?>
    </div>

    <div class="row g-4">

        <!-- ===== FILTRES (sidebar) ===== -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-bold">
                    <i class="bi bi-funnel me-2"></i>Filtres
                </div>
                <div class="card-body">
                    <form method="GET" action="emplois.php">
                        <!-- Recherche -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Mots-clés</label>
                            <input type="search" name="q" class="form-control form-control-sm"
                                   placeholder="Titre, entreprise..." value="<?= h($filtreRecherche) ?>">
                        </div>

                        <!-- Type -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Type de contrat</label>
                            <?php
                            $types = [
                                ''            => 'Tous',
                                'stage'       => 'Stage',
                                'alternance'  => 'Alternance',
                                'cdi'         => 'CDI',
                                'cdd'         => 'CDD',
                                'freelance'   => 'Freelance',
                                'benevole'    => 'Bénévolat',
                            ];
                            foreach ($types as $val => $label): ?>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="type"
                                       id="type-<?= $val ?: 'all' ?>" value="<?= $val ?>"
                                       <?= $filtreType === $val ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="type-<?= $val ?: 'all' ?>">
                                    <?= $label ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="btn btn-ecein-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i>Rechercher
                        </button>
                        <?php if ($filtreType || $filtreRecherche): ?>
                        <a href="emplois.php" class="btn btn-light btn-sm w-100 mt-2">
                            <i class="bi bi-x me-1"></i>Réinitialiser
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Conseils candidature -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Conseil</h6>
                    <p class="text-muted small mb-2">Complétez votre profil ECE In pour augmenter vos chances !</p>
                    <a href="profil.php?onglet=cv" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-file-person me-1"></i>Mon CV
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== LISTE DES OFFRES ===== -->
        <div class="col-lg-9">
            <!-- Barre de recherche mobile -->
            <form class="d-lg-none mb-3" method="GET" action="emplois.php">
                <div class="input-group">
                    <input type="search" name="q" class="form-control"
                           placeholder="Rechercher..." value="<?= h($filtreRecherche) ?>">
                    <button type="submit" class="btn btn-ecein-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Compteur de résultats -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">
                    <strong><?= count($emplois) ?></strong> offre<?= count($emplois) > 1 ? 's' : '' ?> trouvée<?= count($emplois) > 1 ? 's' : '' ?>
                    <?= $filtreType ? 'pour <strong>' . h($filtreType) . '</strong>' : '' ?>
                    <?= $filtreRecherche ? 'pour « <strong>' . h($filtreRecherche) . '</strong> »' : '' ?>
                </p>
            </div>

            <!-- Offres -->
            <?php if (empty($emplois)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-briefcase fs-1 mb-3 d-block opacity-25"></i>
                    <p>Aucune offre ne correspond à votre recherche.</p>
                    <a href="emplois.php" class="btn btn-outline-primary">Voir toutes les offres</a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($emplois as $emploi): ?>
            <div class="card shadow-sm mb-3 emploi-card" id="emploi-<?= $emploi['id'] ?>">
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-start">
                        <!-- Logo entreprise -->
                        <div class="entreprise-logo bg-light rounded d-flex align-items-center
                                    justify-content-center flex-shrink-0" style="width:56px;height:56px">
                            <i class="bi bi-building fs-4 text-muted"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-0"><?= h($emploi['titre']) ?></h6>
                                    <div class="fw-semibold text-primary small"><?= h($emploi['entreprise']) ?></div>
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <?php
                                    $badgeColors = [
                                        'stage'      => 'bg-info text-white',
                                        'alternance' => 'bg-warning text-dark',
                                        'cdi'        => 'bg-success text-white',
                                        'cdd'        => 'bg-secondary text-white',
                                        'freelance'  => 'bg-purple text-white',
                                        'benevole'   => 'bg-light text-dark border',
                                    ];
                                    $badgeClass = $badgeColors[$emploi['type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= h(ucfirst($emploi['type'])) ?></span>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-3 my-2 text-muted small">
                                <?php if ($emploi['lieu']): ?>
                                <span><i class="bi bi-geo-alt me-1"></i><?= h($emploi['lieu']) ?></span>
                                <?php endif; ?>
                                <?php if ($emploi['salaire']): ?>
                                <span><i class="bi bi-cash me-1"></i><?= h($emploi['salaire']) ?></span>
                                <?php endif; ?>
                                <?php if ($emploi['date_debut']): ?>
                                <span><i class="bi bi-calendar me-1"></i>Dès le <?= formatDateFr($emploi['date_debut']) ?></span>
                                <?php endif; ?>
                                <?php if ($emploi['date_limite']): ?>
                                <span class="text-danger">
                                    <i class="bi bi-clock me-1"></i>Limite : <?= formatDateFr($emploi['date_limite']) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-muted small mb-3"><?= nl2br(h(substr($emploi['description'], 0, 200))) ?>...</p>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <?php if ($emploi['lien_candidature']): ?>
                                <a href="<?= h($emploi['lien_candidature']) ?>" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-ecein-primary">
                                    <i class="bi bi-send me-1"></i>Candidater
                                </a>
                                <?php endif; ?>
                                <?php if ($emploi['contact_email']): ?>
                                <a href="mailto:<?= h($emploi['contact_email']) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-envelope me-1"></i>Contacter
                                </a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-light" onclick="this.classList.toggle('active')"
                                        title="Sauvegarder">
                                    <i class="bi bi-bookmark"></i>
                                </button>
                                <span class="text-muted ms-auto" style="font-size:.75rem">
                                    Publié par <?= h($emploi['prenom'] . ' ' . $emploi['nom']) ?> ·
                                    <?= tempsEcoule($emploi['date_publication']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== MODAL : Publier une offre (Admin) ===== -->
<div class="modal fade" id="modalPublierEmploi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-briefcase me-2"></i>Publier une offre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="publier_emploi">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Titre du poste *</label>
                            <input type="text" name="titre" class="form-control" required
                                   placeholder="Développeur Full-Stack (Stage)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Type *</label>
                            <select name="type_emploi" class="form-select" required>
                                <option value="stage">Stage</option>
                                <option value="alternance">Alternance</option>
                                <option value="cdi">CDI</option>
                                <option value="cdd">CDD</option>
                                <option value="freelance">Freelance</option>
                                <option value="benevole">Bénévolat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Entreprise *</label>
                            <input type="text" name="entreprise" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lieu</label>
                            <input type="text" name="lieu" class="form-control" placeholder="Paris, France">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Salaire / Rémunération</label>
                            <input type="text" name="salaire" class="form-control" placeholder="1200€/mois">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date de début</label>
                            <input type="date" name="date_debut" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date limite</label>
                            <input type="date" name="date_limite" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description *</label>
                            <textarea name="description" class="form-control" rows="5" required
                                      placeholder="Décrivez le poste, les missions, les compétences requises..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lien de candidature</label>
                            <input type="url" name="lien_candidature" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email de contact</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="rh@entreprise.fr">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary">
                        <i class="bi bi-send me-2"></i>Publier l'offre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
