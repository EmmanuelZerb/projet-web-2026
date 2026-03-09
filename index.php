<?php
/**
 * ECE In - Page d'Accueil (Fil d'actualité)
 */
require_once __DIR__ . '/config/config.php';
requireConnexion();

$pdo         = getDB();
$userId      = $_SESSION['utilisateur_id'];
$userCourant = getUtilisateurConnecte();
$pageTitle   = 'Accueil';
$pageScript  = 'posts.js';

// ===================== RÉCUPÉRER LES PUBLICATIONS =====================
// Publications publiques + publications des amis
$stmtPosts = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre,
           (SELECT COUNT(*) FROM reactions r WHERE r.publication_id = p.id) AS nb_reactions,
           (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS nb_commentaires,
           (SELECT type FROM reactions r2 WHERE r2.publication_id = p.id AND r2.utilisateur_id = :uid) AS ma_reaction,
           po.contenu AS original_contenu, po.fichier AS original_fichier,
           uo.prenom AS original_prenom, uo.nom AS original_nom
    FROM publications p
    JOIN utilisateurs u ON u.id = p.utilisateur_id
    LEFT JOIN publications po ON po.id = p.publication_originale_id
    LEFT JOIN utilisateurs uo ON uo.id = po.utilisateur_id
    WHERE p.visibilite = 'public'
       OR p.utilisateur_id = :uid2
       OR (p.visibilite = 'amis' AND p.utilisateur_id IN (
            SELECT CASE WHEN demandeur_id = :uid3 THEN destinataire_id ELSE demandeur_id END
            FROM connexions
            WHERE (demandeur_id = :uid4 OR destinataire_id = :uid5) AND statut = 'accepte'
       ))
    ORDER BY p.date_publication DESC
    LIMIT 30
");
$stmtPosts->execute([
    ':uid'  => $userId,
    ':uid2' => $userId,
    ':uid3' => $userId,
    ':uid4' => $userId,
    ':uid5' => $userId,
]);
$publications = $stmtPosts->fetchAll();

// ===================== ÉVÉNEMENT DE LA SEMAINE =====================
$stmtEvt = $pdo->prepare("
    SELECT e.*, u.nom, u.prenom
    FROM evenements e
    JOIN utilisateurs u ON u.id = e.organisateur_id
    WHERE e.date_debut >= NOW() AND e.date_debut <= DATE_ADD(NOW(), INTERVAL 14 DAY)
    ORDER BY e.est_officiel DESC, e.date_debut ASC
    LIMIT 3
");
$stmtEvt->execute();
$evenements = $stmtEvt->fetchAll();

// ===================== SUGGESTIONS DE CONNEXIONS =====================
$stmtSugg = $pdo->prepare("
    SELECT u.id, u.nom, u.prenom, u.pseudo, u.photo, u.titre
    FROM utilisateurs u
    WHERE u.id != :uid
      AND u.actif = 1
      AND u.id NOT IN (
          SELECT CASE WHEN demandeur_id = :uid2 THEN destinataire_id ELSE demandeur_id END
          FROM connexions
          WHERE (demandeur_id = :uid3 OR destinataire_id = :uid4)
      )
    ORDER BY RAND()
    LIMIT 5
");
$stmtSugg->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId]);
$suggestions = $stmtSugg->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-xl py-4">
    <div class="row g-4">

        <!-- ===== COLONNE GAUCHE : Profil rapide ===== -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card card-profil-rapide mb-3">
                <!-- Image de fond -->
                <div class="profil-bg" style="background-image:url('<?= h($userCourant['image_fond']) ?>')"></div>
                <div class="card-body text-center pt-0">
                    <img src="<?= h($userCourant['photo']) ?>" alt="Avatar"
                         class="rounded-circle profil-avatar border border-3 border-white">
                    <h6 class="fw-bold mt-2 mb-0"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></h6>
                    <p class="text-muted small mb-2"><?= h($userCourant['titre'] ?? 'Membre ECE In') ?></p>
                    <a href="profil.php" class="btn btn-sm btn-outline-primary w-100">Voir mon profil</a>
                </div>
                <div class="card-footer text-center py-2">
                    <small class="text-muted">
                        <i class="bi bi-eye me-1"></i>Profil vu par <strong>0</strong> personnes
                    </small>
                </div>
            </div>

            <!-- Raccourcis -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <a href="profil.php?onglet=cv" class="sidebar-link">
                        <i class="bi bi-file-earmark-person"></i> Mon CV
                    </a>
                    <a href="emplois.php" class="sidebar-link">
                        <i class="bi bi-briefcase"></i> Offres d'emploi
                    </a>
                    <a href="profil.php?onglet=albums" class="sidebar-link">
                        <i class="bi bi-images"></i> Mes albums
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== COLONNE CENTRE : Fil d'actualité ===== -->
        <div class="col-lg-6">

            <!-- Événement de la semaine -->
            <?php if (!empty($evenements)): ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-ecein text-white fw-bold">
                    <i class="bi bi-calendar-event me-2"></i>Événements à venir
                </div>
                <div id="carouselEvenements" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($evenements as $idx => $evt): ?>
                        <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                            <div class="p-4">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="evenement-date-badge text-center">
                                        <div class="evt-mois"><?= strtoupper(date('M', strtotime($evt['date_debut']))) ?></div>
                                        <div class="evt-jour"><?= date('d', strtotime($evt['date_debut'])) ?></div>
                                    </div>
                                    <div>
                                        <?php if ($evt['est_officiel']): ?>
                                        <span class="badge bg-ecein mb-1">Officiel ECE</span>
                                        <?php endif; ?>
                                        <h6 class="fw-bold mb-1"><?= h($evt['titre']) ?></h6>
                                        <p class="text-muted small mb-1">
                                            <i class="bi bi-geo-alt me-1"></i><?= h($evt['lieu'] ?? 'Lieu non précisé') ?>
                                        </p>
                                        <p class="small mb-0"><?= h(substr($evt['description'] ?? '', 0, 120)) ?>...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($evenements) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselEvenements" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselEvenements" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Zone de publication -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <img src="<?= h($userCourant['photo']) ?>" alt="" class="rounded-circle" width="44" height="44" style="object-fit:cover">
                        <button class="btn btn-light border w-100 text-start text-muted rounded-pill px-4"
                                data-bs-toggle="modal" data-bs-target="#modalPublier">
                            Quoi de neuf, <?= h($userCourant['prenom']) ?> ?
                        </button>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-around">
                        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="photo">
                            <i class="bi bi-image text-success fs-5"></i>
                            <span class="d-none d-sm-inline">Photo</span>
                        </button>
                        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="video">
                            <i class="bi bi-camera-video text-danger fs-5"></i>
                            <span class="d-none d-sm-inline">Vidéo</span>
                        </button>
                        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="evenement">
                            <i class="bi bi-calendar-plus text-warning fs-5"></i>
                            <span class="d-none d-sm-inline">Événement</span>
                        </button>
                        <button class="btn btn-sm btn-light d-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#modalPublier" data-type="cv">
                            <i class="bi bi-file-person text-info fs-5"></i>
                            <span class="d-none d-sm-inline">CV</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fil de publications -->
            <div id="fil-publications">
                <?php if (empty($publications)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-chat-square-dots fs-1 mb-3 d-block opacity-25"></i>
                        <p>Aucune publication pour l'instant. Commencez à partager !</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php foreach ($publications as $post): ?>
                <?php include __DIR__ . '/includes/post_card.php'; ?>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- ===== COLONNE DROITE : Suggestions ===== -->
        <div class="col-lg-3 d-none d-lg-block">
            <!-- Suggestions de connexions -->
            <?php if (!empty($suggestions)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-transparent fw-semibold border-0 pb-0">
                    <i class="bi bi-person-plus me-2 text-primary"></i>Personnes à connaître
                </div>
                <div class="card-body pt-2">
                    <?php foreach ($suggestions as $sugg): ?>
                    <div class="suggestion-card d-flex align-items-center gap-2 py-2">
                        <a href="utilisateur.php?id=<?= $sugg['id'] ?>">
                            <img src="<?= h($sugg['photo']) ?>" alt=""
                                 class="rounded-circle" width="44" height="44" style="object-fit:cover">
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <a href="utilisateur.php?id=<?= $sugg['id'] ?>" class="text-decoration-none text-dark">
                                <div class="fw-semibold small text-truncate"><?= h($sugg['prenom'] . ' ' . $sugg['nom']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;line-height:1.2"><?= h(substr($sugg['titre'] ?? 'Membre ECE', 0, 40)) ?></div>
                            </a>
                        </div>
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-2"
                                onclick="envoyerDemande(<?= $sugg['id'] ?>, this)"
                                title="Se connecter">
                            <i class="bi bi-person-plus"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Emplois récents -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent fw-semibold border-0 pb-0">
                    <i class="bi bi-briefcase me-2 text-warning"></i>Offres récentes
                </div>
                <div class="card-body pt-2">
                    <?php
                    $stmtEmplois = $pdo->prepare("SELECT * FROM emplois WHERE actif = 1 ORDER BY date_publication DESC LIMIT 3");
                    $stmtEmplois->execute();
                    foreach ($stmtEmplois->fetchAll() as $emploi): ?>
                    <div class="py-2 border-bottom">
                        <a href="emplois.php#emploi-<?= $emploi['id'] ?>" class="text-decoration-none text-dark">
                            <div class="fw-semibold small"><?= h($emploi['titre']) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= h($emploi['entreprise']) ?></div>
                            <span class="badge badge-type-emploi-<?= $emploi['type'] ?> mt-1"><?= h(ucfirst($emploi['type'])) ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <a href="emplois.php" class="btn btn-sm btn-light w-100 mt-2">Voir toutes les offres</a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ===== MODAL : Publier ===== -->
<div class="modal fade" id="modalPublier" tabindex="-1">
    <div class="modal-dialog modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Créer une publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPublier" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="publier">
                    <input type="hidden" name="type" id="post-type" value="statut">

                    <!-- Type de publication -->
                    <div class="btn-group mb-3 w-100" role="group">
                        <input type="radio" class="btn-check" name="type_post" id="type-statut" value="statut" checked>
                        <label class="btn btn-outline-secondary" for="type-statut"><i class="bi bi-pencil me-1"></i>Statut</label>

                        <input type="radio" class="btn-check" name="type_post" id="type-photo" value="photo">
                        <label class="btn btn-outline-success" for="type-photo"><i class="bi bi-image me-1"></i>Photo</label>

                        <input type="radio" class="btn-check" name="type_post" id="type-video" value="video">
                        <label class="btn btn-outline-danger" for="type-video"><i class="bi bi-camera-video me-1"></i>Vidéo</label>

                        <input type="radio" class="btn-check" name="type_post" id="type-cv" value="cv">
                        <label class="btn btn-outline-info" for="type-cv"><i class="bi bi-file-person me-1"></i>CV</label>
                    </div>

                    <!-- Contenu texte -->
                    <div class="d-flex gap-3 mb-3">
                        <img src="<?= h($userCourant['photo']) ?>" alt="" class="rounded-circle" width="44" height="44" style="object-fit:cover">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small mb-1"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></div>
                            <select name="visibilite" class="form-select form-select-sm d-inline-block w-auto mb-2">
                                <option value="public"><i class="bi bi-globe"></i> Public</option>
                                <option value="amis">Amis</option>
                                <option value="prive">Privé</option>
                            </select>
                        </div>
                    </div>

                    <textarea name="contenu" class="form-control border-0 fs-5" rows="4"
                              placeholder="Que souhaitez-vous partager ?" style="resize:none"></textarea>

                    <!-- Upload fichier -->
                    <div id="zone-upload" class="mt-3" style="display:none">
                        <div class="upload-zone text-center p-4 border-2 border-dashed rounded" id="dropzone">
                            <i class="bi bi-cloud-upload fs-2 text-muted"></i>
                            <p class="text-muted mt-2">Glissez-déposez votre fichier ici ou</p>
                            <label class="btn btn-sm btn-outline-primary">
                                Choisir un fichier
                                <input type="file" name="fichier" id="input-fichier" class="d-none">
                            </label>
                            <div id="preview-fichier" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Informations supplémentaires -->
                    <div class="row g-2 mt-3">
                        <div class="col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="lieu" class="form-control" placeholder="Lieu">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-emoji-smile"></i></span>
                                <input type="text" name="humeur" class="form-control" placeholder="Ce que vous ressentez...">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-ecein-primary px-4">
                        <i class="bi bi-send me-2"></i>Publier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
