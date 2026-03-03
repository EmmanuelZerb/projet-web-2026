<?php
/**
 * ECE In - Carte de publication (réutilisable)
 * Variable attendue : $post (tableau de données de la publication)
 */
if (!isset($post)) return;
?>
<div class="card shadow-sm mb-3 post-card" id="post-<?= $post['id'] ?>">
    <div class="card-body">
        <!-- En-tête du post -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex gap-3">
                <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>">
                    <img src="<?= h($post['avatar']) ?>" alt=""
                         class="rounded-circle" width="48" height="48" style="object-fit:cover">
                </a>
                <div>
                    <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>"
                       class="fw-semibold text-dark text-decoration-none">
                        <?= h($post['prenom'] . ' ' . $post['nom']) ?>
                    </a>
                    <?php if ($post['humeur']): ?>
                    <span class="text-muted small"> – se sent <em><?= h($post['humeur']) ?></em></span>
                    <?php endif; ?>
                    <div class="text-muted d-flex align-items-center gap-2" style="font-size:.78rem">
                        <span title="<?= h($post['date_publication']) ?>"><?= tempsEcoule($post['date_publication']) ?></span>
                        <?php if ($post['lieu']): ?>
                        <span><i class="bi bi-geo-alt me-1"></i><?= h($post['lieu']) ?></span>
                        <?php endif; ?>
                        <span>
                            <?php if ($post['visibilite'] === 'public'): ?>
                            <i class="bi bi-globe" title="Public"></i>
                            <?php elseif ($post['visibilite'] === 'amis'): ?>
                            <i class="bi bi-people" title="Amis"></i>
                            <?php else: ?>
                            <i class="bi bi-lock" title="Privé"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Menu options (si propriétaire) -->
            <?php if ($post['utilisateur_id'] == ($userId ?? 0)): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <button class="dropdown-item" onclick="modifierPost(<?= $post['id'] ?>)">
                            <i class="bi bi-pencil me-2"></i>Modifier
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item text-danger" onclick="supprimerPost(<?= $post['id'] ?>)">
                            <i class="bi bi-trash me-2"></i>Supprimer
                        </button>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenu du post -->
        <?php if ($post['contenu']): ?>
        <p class="post-contenu mb-3"><?= nl2br(h($post['contenu'])) ?></p>
        <?php endif; ?>

        <!-- Publication partagée -->
        <?php if ($post['type'] === 'partage' && $post['original_contenu']): ?>
        <div class="post-partage border rounded p-3 mb-3 bg-light">
            <small class="text-muted">Partagé depuis <?= h($post['original_prenom'] . ' ' . $post['original_nom']) ?></small>
            <p class="mb-1 mt-1 small"><?= nl2br(h(substr($post['original_contenu'], 0, 200))) ?>...</p>
        </div>
        <?php endif; ?>

        <!-- Média -->
        <?php if ($post['fichier']): ?>
        <div class="post-media mb-3">
            <?php
            $ext = strtolower(pathinfo($post['fichier'], PATHINFO_EXTENSION));
            $videoExts = ['mp4', 'webm', 'ogg'];
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            ?>
            <?php if (in_array($ext, $imageExts)): ?>
            <img src="<?= h($post['fichier']) ?>" alt="Photo"
                 class="img-fluid rounded w-100 post-image"
                 style="max-height:400px;object-fit:cover;cursor:pointer"
                 onclick="ouvrirMedia(this.src)">
            <?php elseif (in_array($ext, $videoExts)): ?>
            <video controls class="w-100 rounded" style="max-height:400px">
                <source src="<?= h($post['fichier']) ?>">
                Votre navigateur ne supporte pas la lecture vidéo.
            </video>
            <?php elseif ($ext === 'pdf'): ?>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <i class="bi bi-file-earmark-pdf fs-2 text-danger"></i>
                <div>
                    <div class="fw-semibold">Curriculum Vitæ</div>
                    <a href="<?= h($post['fichier']) ?>" target="_blank" class="btn btn-sm btn-outline-danger mt-1">
                        <i class="bi bi-download me-1"></i>Télécharger le CV
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Compteurs de réactions -->
        <?php if ($post['nb_reactions'] > 0 || $post['nb_commentaires'] > 0): ?>
        <div class="d-flex justify-content-between text-muted small mb-2 pb-2 border-bottom">
            <?php if ($post['nb_reactions'] > 0): ?>
            <span>
                <span class="reaction-emoji">👍</span>
                <?= $post['nb_reactions'] ?> réaction<?= $post['nb_reactions'] > 1 ? 's' : '' ?>
            </span>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($post['nb_commentaires'] > 0): ?>
            <button class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                    onclick="afficherCommentaires(<?= $post['id'] ?>)">
                <?= $post['nb_commentaires'] ?> commentaire<?= $post['nb_commentaires'] > 1 ? 's' : '' ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Boutons d'actions -->
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-light flex-fill reaction-btn <?= $post['ma_reaction'] ? 'active text-primary' : '' ?>"
                    onclick="reagir(<?= $post['id'] ?>, 'jaime', this)"
                    title="J'aime">
                <i class="bi bi-hand-thumbs-up<?= $post['ma_reaction'] === 'jaime' ? '-fill' : '' ?>"></i>
                <span class="d-none d-sm-inline ms-1">J'aime</span>
            </button>
            <button class="btn btn-sm btn-light flex-fill"
                    onclick="afficherCommentaires(<?= $post['id'] ?>)"
                    title="Commenter">
                <i class="bi bi-chat"></i>
                <span class="d-none d-sm-inline ms-1">Commenter</span>
            </button>
            <button class="btn btn-sm btn-light flex-fill"
                    onclick="partager(<?= $post['id'] ?>)"
                    title="Partager">
                <i class="bi bi-share"></i>
                <span class="d-none d-sm-inline ms-1">Partager</span>
            </button>
        </div>

        <!-- Section commentaires (masquée par défaut) -->
        <div class="commentaires-section mt-3" id="commentaires-<?= $post['id'] ?>" style="display:none">
            <div class="commentaires-liste" id="liste-commentaires-<?= $post['id'] ?>"></div>
            <div class="d-flex gap-2 mt-2">
                <img src="<?= h($userCourant['photo'] ?? 'assets/images/default_avatar.png') ?>" alt=""
                     class="rounded-circle" width="32" height="32" style="object-fit:cover">
                <div class="flex-grow-1 position-relative">
                    <input type="text" class="form-control form-control-sm rounded-pill"
                           placeholder="Écrire un commentaire..."
                           id="input-commentaire-<?= $post['id'] ?>"
                           onkeypress="if(event.key==='Enter') envoyerCommentaire(<?= $post['id'] ?>)">
                    <button class="btn btn-sm position-absolute end-0 top-50 translate-middle-y pe-3"
                            onclick="envoyerCommentaire(<?= $post['id'] ?>)">
                        <i class="bi bi-send text-primary"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
