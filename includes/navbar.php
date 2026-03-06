<?php
/**
 * ECE In - Sidebar Navigation (Version Alt)
 */
$pageActive = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Backdrop mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar-nav" id="sidebarNav">
    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="logo-mark">EC</div>
            <div>
                <div class="logo-text">ECE<span>In</span></div>
                <span class="logo-sub">Réseau ECE Paris</span>
            </div>
        </a>
    </div>

    <!-- Nav principale -->
    <div class="sidebar-section">
        <div class="sidebar-section-title">Menu</div>
        <a href="index.php" class="side-link <?= $pageActive === 'index' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i> Accueil
        </a>
        <a href="reseau.php" class="side-link <?= $pageActive === 'reseau' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Mon Réseau
        </a>
        <a href="profil.php" class="side-link <?= $pageActive === 'profil' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> Mon Profil
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Communication</div>
        <a href="notifications.php" class="side-link <?= $pageActive === 'notifications' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> Notifications
            <?php if (isset($nbNotifs) && $nbNotifs > 0): ?>
            <span class="badge-count"><?= $nbNotifs ?></span>
            <?php endif; ?>
        </a>
        <a href="messagerie.php" class="side-link <?= $pageActive === 'messagerie' ? 'active' : '' ?>">
            <i class="bi bi-chat-square-text"></i> Messagerie
            <?php if (isset($nbMessages) && $nbMessages > 0): ?>
            <span class="badge-count"><?= $nbMessages ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Découvrir</div>
        <a href="emplois.php" class="side-link <?= $pageActive === 'emplois' ? 'active' : '' ?>">
            <i class="bi bi-briefcase"></i> Emplois & Stages
        </a>
        <a href="recherche.php" class="side-link <?= $pageActive === 'recherche' ? 'active' : '' ?>">
            <i class="bi bi-search"></i> Recherche
        </a>
        <?php if (isset($userCourant)): ?>
        <a href="profil.php?onglet=cv" class="side-link">
            <i class="bi bi-file-earmark-text"></i> Mon CV
        </a>
        <a href="profil.php?onglet=albums" class="side-link">
            <i class="bi bi-images"></i> Albums Photos
        </a>
        <?php endif; ?>
    </div>

    <?php if (estAdmin()): ?>
    <div class="sidebar-section">
        <div class="sidebar-section-title">Admin</div>
        <a href="admin/index.php" class="side-link" style="color:var(--rose)">
            <i class="bi bi-shield-lock"></i> Administration
        </a>
    </div>
    <?php endif; ?>

    <!-- Profil en bas -->
    <?php if (estConnecte() && isset($userCourant)): ?>
    <div class="sidebar-profile">
        <div class="sidebar-profile-inner" data-bs-toggle="dropdown">
            <img src="<?= h($userCourant['photo']) ?>" alt="">
            <div>
                <div class="sidebar-profile-name"><?= h($userCourant['prenom'] . ' ' . $userCourant['nom']) ?></div>
                <div class="sidebar-profile-role">@<?= h($userCourant['pseudo']) ?></div>
            </div>
            <i class="bi bi-chevron-expand ms-auto" style="color:var(--text-3);font-size:.75rem"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end mb-2" style="min-width:200px">
            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Mon profil</a></li>
            <li><a class="dropdown-item" href="profil.php?onglet=parametres"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" style="color:var(--rose)" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Déconnexion</a></li>
        </ul>
    </div>
    <?php endif; ?>
</aside>

<!-- ===== MAIN WRAPPER ===== -->
<div class="main-content">
    <!-- Page Header -->
    <header class="page-header">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="page-title"><?= h($pageTitle ?? 'ECE In') ?></span>
        </div>
        <div class="search-bar-top position-relative d-none d-md-block">
            <form action="recherche.php" method="GET">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control"
                       placeholder="Rechercher..."
                       value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
            </form>
        </div>
    </header>

    <div class="page-body">
