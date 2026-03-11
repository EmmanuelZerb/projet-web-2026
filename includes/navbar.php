<?php
/**
 * ECE In - Barre de navigation principale
 * Barre de navigation responsive : desktop et mobile (Bootstrap 5)
 */
// Détermine la page active
$pageActive = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- ===================== MOBILE HEADER ===================== -->
<header class="mobile-header d-none">
    <a href="index.php" class="text-decoration-none">
        <div class="logo-ecein">
            <span class="logo-ece">ECE</span><span class="logo-in">In</span>
        </div>
    </a>
    <div class="mobile-search-bar position-relative">
        <form action="recherche.php" method="GET">
            <i class="bi bi-search search-icon"></i>
            <input type="search" name="q" class="form-control"
                   placeholder="Rechercher..."
                   value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
        </form>
    </div>
    <div class="mobile-header-actions">
        <a href="messagerie.php" class="btn position-relative">
            <i class="bi bi-chat-dots-fill"></i>
            <?php if (isset($nbMessages) && $nbMessages > 0): ?>
            <span class="badge-notif"><?= $nbMessages ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<!-- Menu principal avec badges pour les notifications et messages non lus -->
<!-- ===================== NAVBAR DESKTOP ===================== -->
<nav class="navbar navbar-expand-lg navbar-ecein sticky-top">
    <div class="container-xl">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <div class="logo-ecein">
                <span class="logo-ece">ECE</span><span class="logo-in">In</span>
            </div>
            <span class="d-none d-md-inline logo-subtitle">Réseau ECE Paris</span>
        </a>

        <!-- Recherche globale accessible depuis n'importe quelle page -->
        <div class="search-bar d-none d-lg-flex mx-3 flex-grow-1">
            <form class="d-flex w-100" action="recherche.php" method="GET">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="search" name="q" class="form-control border-start-0 ps-0"
                           placeholder="Rechercher des personnes, emplois, événements..."
                           value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
                </div>
            </form>
        </div>

        <!-- Toggle mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-label="Navigation">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Liens de navigation -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">

                <!-- Accueil -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link <?= $pageActive === 'index' ? 'active' : '' ?>"
                       href="index.php" title="Accueil">
                        <i class="bi bi-house-fill"></i>
                        <span class="nav-label">Accueil</span>
                    </a>
                </li>

                <!-- Mon Réseau -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link <?= $pageActive === 'reseau' ? 'active' : '' ?>"
                       href="reseau.php" title="Mon Réseau">
                        <i class="bi bi-people-fill"></i>
                        <span class="nav-label">Mon Réseau</span>
                    </a>
                </li>

                <!-- Vous (Profil) -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link <?= $pageActive === 'profil' ? 'active' : '' ?>"
                       href="profil.php" title="Votre profil">
                        <i class="bi bi-person-fill"></i>
                        <span class="nav-label">Vous</span>
                    </a>
                </li>

                <!-- Notifications -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link position-relative <?= $pageActive === 'notifications' ? 'active' : '' ?>"
                       href="notifications.php" title="Notifications">
                        <i class="bi bi-bell-fill"></i>
                        <?php if (isset($nbNotifs) && $nbNotifs > 0): ?>
                        <span class="badge-notif"><?= $nbNotifs ?></span>
                        <?php endif; ?>
                        <span class="nav-label">Notifications</span>
                    </a>
                </li>

                <!-- Messagerie -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link position-relative <?= $pageActive === 'messagerie' ? 'active' : '' ?>"
                       href="messagerie.php" title="Messagerie">
                        <i class="bi bi-chat-dots-fill"></i>
                        <?php if (isset($nbMessages) && $nbMessages > 0): ?>
                        <span class="badge-notif"><?= $nbMessages ?></span>
                        <?php endif; ?>
                        <span class="nav-label">Messagerie</span>
                    </a>
                </li>

                <!-- Emplois -->
                <li class="nav-item">
                    <a class="nav-link nav-icon-link <?= $pageActive === 'emplois' ? 'active' : '' ?>"
                       href="emplois.php" title="Emplois">
                        <i class="bi bi-briefcase-fill"></i>
                        <span class="nav-label">Emplois</span>
                    </a>
                </li>

                <!-- Divider -->
                <li class="nav-item d-none d-lg-block"><div class="vr mx-1 opacity-25" style="height:32px"></div></li>

                <!-- Menu utilisateur -->
                <?php if (estConnecte() && isset($userCourant)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-1" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= h(photo($userCourant['photo'] ?? null)) ?>" alt="Avatar"
                             class="rounded-circle" width="34" height="34" style="object-fit:cover">
                        <span class="d-none d-lg-inline fw-semibold small"><?= h($userCourant['prenom']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <div class="dropdown-header d-flex align-items-center gap-2 py-2">
                                <img src="<?= h(photo($userCourant['photo'] ?? null)) ?>" alt="" class="rounded-circle" width="40" height="40" style="object-fit:cover">
                                <div>
                                    <div class="fw-semibold"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></div>
                                    <div class="text-muted small"><?= h($userCourant['titre'] ?? '') ?></div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Mon profil</a></li>
                        <li><a class="dropdown-item" href="profil.php?onglet=cv"><i class="bi bi-file-earmark-person me-2"></i>Mon CV</a></li>
                        <li><a class="dropdown-item" href="profil.php?onglet=parametres"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                        <?php if (estAdmin()): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="admin/index.php"><i class="bi bi-shield-fill me-2"></i>Administration</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-ecein-primary btn-sm" href="login.php">Connexion</a>
                </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<!-- ===================== FIN NAVBAR ===================== -->

<!-- Version mobile avec menu hamburger (responsive) -->
<!-- ===================== BOTTOM TAB BAR (MOBILE) ===================== -->
<nav class="bottom-tab-bar d-none" aria-label="Navigation mobile">
    <a href="index.php" class="tab-item <?= $pageActive === 'index' ? 'active' : '' ?>">
        <i class="bi <?= $pageActive === 'index' ? 'bi-house-fill' : 'bi-house' ?>"></i>
        <span>Accueil</span>
    </a>
    <a href="reseau.php" class="tab-item <?= $pageActive === 'reseau' ? 'active' : '' ?>">
        <i class="bi <?= $pageActive === 'reseau' ? 'bi-people-fill' : 'bi-people' ?>"></i>
        <span>Réseau</span>
    </a>
    <div class="tab-item tab-item-publish">
        <button class="publish-btn" data-bs-toggle="modal" data-bs-target="#modalPublier" aria-label="Publier">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
    <a href="notifications.php" class="tab-item <?= $pageActive === 'notifications' ? 'active' : '' ?>">
        <i class="bi <?= $pageActive === 'notifications' ? 'bi-bell-fill' : 'bi-bell' ?>"></i>
        <?php if (isset($nbNotifs) && $nbNotifs > 0): ?>
        <span class="tab-badge"><?= $nbNotifs ?></span>
        <?php endif; ?>
        <span>Notifs</span>
    </a>
    <a href="profil.php" class="tab-item <?= $pageActive === 'profil' ? 'active' : '' ?>">
        <?php if (isset($userCourant) && !empty($userCourant['photo'])): ?>
        <img src="<?= h(photo($userCourant['photo'] ?? null)) ?>" alt="" width="26" height="26"
             style="border-radius:50%;object-fit:cover;border:2px solid <?= $pageActive === 'profil' ? 'var(--ecein-primary)' : 'transparent' ?>">
        <?php else: ?>
        <i class="bi <?= $pageActive === 'profil' ? 'bi-person-fill' : 'bi-person' ?>"></i>
        <?php endif; ?>
        <span>Profil</span>
    </a>
</nav>
