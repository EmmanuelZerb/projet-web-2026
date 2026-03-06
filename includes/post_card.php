<?php
/**
 * ECE In - Carte de publication (Version Alt - Glassmorphism)
 */
if (!isset($post)) return;
?>
<div class="post-card" id="post-<?= $post['id'] ?>">
    <div class="p-3">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex gap-3">
                <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>">
                    <img src="<?= h($post['avatar']) ?>" alt=""
                         class="rounded-3" width="44" height="44"
                         style="object-fit:cover;border:2px solid var(--accent)">
                </a>
                <div>
                    <a href="utilisateur.php?id=<?= $post['utilisateur_id'] ?>"
                       class="fw-semibold text-decoration-none" style="color:var(--text);font-size:.9rem">
                        <?= h($post['prenom'] . ' ' . $post['nom']) ?>
                    </a>
                    <?php if ($post['humeur']): ?>
                    <span style="color:var(--text-3);font-size:.8rem"> – se sent <em><?= h($post['humeur']) ?></em></span>
                    <?php endif; ?>
                    <div class="d-flex align-items-center gap-2" style="font-size:.72rem;color:var(--text-3)">
                        <span><?= tempsEcoule($post['date_publication']) ?></span>
                        <?php if ($post['lieu']): ?>
                        <span><i class="bi bi-geo-alt me-1"></i><?= h($post['lieu']) ?></span>
                        <?php endif; ?>
                        <span>
                            <?php if ($post['visibilite'] === 'public'): ?><i class="bi bi-globe2"></i>
                            <?php elseif ($post['visibilite'] === 'amis'): ?><i class="bi bi-people"></i>
                            <?php else: ?><i class="bi bi-lock"></i><?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($post['utilisateur_id'] == ($userId ?? 0)): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-light rounded-3" data-bs-toggle="dropdown" style="padding:.25rem .45rem">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" onclick="modifierPost(<?= $post['id'] ?>)"><i class="bi bi-pencil me-2"></i>Modifier</button></li>
                    <li><button class="dropdown-item" style="color:var(--rose)" onclick="supprimerPost(<?= $post['id'] ?>)"><i class="bi bi-trash me-2"></i>Supprimer</button></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contenu -->
        <?php if ($post['contenu']): ?>
        <p class="post-contenu mb-3" style="font-size:.88rem"><?= nl2br(h($post['contenu'])) ?></p>
        <?php endif; ?>

        <!-- Partage -->
        <?php if ($post['type'] === 'partage' && $post['original_contenu']): ?>
        <div class="rounded-3 p-3 mb-3" style="background:var(--bg-3);border:1px solid var(--glass-border)">
            <small style="color:var(--text-3)">Partagé de <?= h($post['original_prenom'] . ' ' . $post['original_nom']) ?></small>
            <p class="mb-0 mt-1 small"><?= nl2br(h(substr($post['original_contenu'], 0, 200))) ?>...</p>
        </div>
        <?php endif; ?>

        <!-- Média -->
        <?php if ($post['fichier']): ?>
        <div class="mb-3">
            <?php
            $ext = strtolower(pathinfo($post['fichier'], PATHINFO_EXTENSION));
            $videoExts = ['mp4', 'webm', 'ogg'];
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            ?>
            <?php if (in_array($ext, $imageExts)): ?>
            <img src="<?= h($post['fichier']) ?>" alt="Photo"
                 class="img-fluid w-100 post-image"
                 style="max-height:400px;object-fit:cover;cursor:pointer;border-radius:var(--radius-sm)"
                 onclick="ouvrirMedia(this.src)">
            <?php elseif (in_array($ext, $videoExts)): ?>
            <video controls class="w-100" style="max-height:400px;border-radius:var(--radius-sm)">
                <source src="<?= h($post['fichier']) ?>">
            </video>
            <?php elseif ($ext === 'pdf'): ?>
            <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:var(--bg-3)">
                <i class="bi bi-file-earmark-pdf fs-2" style="color:var(--rose)"></i>
                <div>
                    <div class="fw-semibold">Curriculum Vitæ</div>
                    <a href="<?= h($post['fichier']) ?>" target="_blank" class="btn btn-sm btn-outline-danger mt-1">
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Compteurs -->
        <?php if ($post['nb_reactions'] > 0 || $post['nb_commentaires'] > 0): ?>
        <div class="d-flex justify-content-between small mb-2 pb-2" style="border-bottom:1px solid var(--glass-border);color:var(--text-3)">
            <?php if ($post['nb_reactions'] > 0): ?>
            <span>👍 <?= $post['nb_reactions'] ?> réaction<?= $post['nb_reactions'] > 1 ? 's' : '' ?></span>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($post['nb_commentaires'] > 0): ?>
            <button class="btn btn-link btn-sm p-0 text-decoration-none" style="color:var(--text-3)"
                    onclick="afficherCommentaires(<?= $post['id'] ?>)">
                <?= $post['nb_commentaires'] ?> commentaire<?= $post['nb_commentaires'] > 1 ? 's' : '' ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-light flex-fill reaction-btn <?= $post['ma_reaction'] ? 'active' : '' ?>"
                    onclick="reagir(<?= $post['id'] ?>, 'jaime', this)">
                <i class="bi bi-hand-thumbs-up<?= $post['ma_reaction'] === 'jaime' ? '-fill' : '' ?>"></i>
                <span class="d-none d-sm-inline ms-1">J'aime</span>
            </button>
            <button class="btn btn-sm btn-light flex-fill" onclick="afficherCommentaires(<?= $post['id'] ?>)">
                <i class="bi bi-chat"></i>
                <span class="d-none d-sm-inline ms-1">Commenter</span>
            </button>
            <button class="btn btn-sm btn-light flex-fill" onclick="partager(<?= $post['id'] ?>)">
                <i class="bi bi-share"></i>
                <span class="d-none d-sm-inline ms-1">Partager</span>
            </button>
        </div>

        <!-- Commentaires -->
        <div class="commentaires-section mt-3" id="commentaires-<?= $post['id'] ?>" style="display:none">
            <div class="commentaires-liste" id="liste-commentaires-<?= $post['id'] ?>"></div>
            <div class="d-flex gap-2 mt-2">
                <img src="<?= h($userCourant['photo'] ?? 'assets/images/default_avatar.png') ?>" alt=""
                     class="rounded-3" width="30" height="30" style="object-fit:cover">
                <div class="flex-grow-1 position-relative">
                    <input type="text" class="form-control form-control-sm"
                           placeholder="Écrire un commentaire..."
                           style="border-radius:var(--radius-sm);padding-right:2.5rem"
                           id="input-commentaire-<?= $post['id'] ?>"
                           onkeypress="if(event.key==='Enter') envoyerCommentaire(<?= $post['id'] ?>)">
                    <button class="btn btn-sm position-absolute end-0 top-50 translate-middle-y pe-2"
                            onclick="envoyerCommentaire(<?= $post['id'] ?>)">
                        <i class="bi bi-send" style="color:var(--accent)"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
