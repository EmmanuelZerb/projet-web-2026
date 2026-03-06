<?php
/**
 * ECE In - Pied de page HTML commun (Version Alternative - Dark Cyberpunk)
 */
?>
<!-- ===================== FOOTER ===================== -->
<footer class="footer-ecein mt-5">
    <div class="footer-top">
        <div class="container-xl">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-ecein logo-ecein-sm">
                            <span class="logo-ece">ECE</span><span class="logo-in">In</span>
                        </div>
                        <span class="fw-semibold" style="color:var(--ecein-text)">Réseau ECE Paris</span>
                    </div>
                    <p class="small" style="color:var(--ecein-muted)">
                        Le réseau social professionnel de la communauté ECE Paris.
                        Connectez-vous, partagez vos réalisations et développez votre carrière.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="fs-5" style="color:var(--ecein-muted)"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="fs-5" style="color:var(--ecein-muted)"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="fs-5" style="color:var(--ecein-muted)"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="fs-5" style="color:var(--ecein-muted)"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <h6 class="fw-semibold mb-3" style="color:var(--ecein-cyan)">Navigation</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="reseau.php">Mon Réseau</a></li>
                        <li><a href="profil.php">Mon Profil</a></li>
                        <li><a href="notifications.php">Notifications</a></li>
                        <li><a href="messagerie.php">Messagerie</a></li>
                        <li><a href="emplois.php">Emplois</a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <h6 class="fw-semibold mb-3" style="color:var(--ecein-cyan)">ECE Paris</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="https://www.ece.fr" target="_blank" rel="noopener">Site officiel ECE</a></li>
                        <li><a href="https://www.omneseducation.com" target="_blank" rel="noopener">Omnes Education</a></li>
                        <li><a href="emplois.php">Offres d'emploi</a></li>
                        <li><a href="emplois.php?type=stage">Stages</a></li>
                        <li><a href="emplois.php?type=alternance">Alternances</a></li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h6 class="fw-semibold mb-3" style="color:var(--ecein-cyan)">Contact & Localisation</h6>
                    <ul class="list-unstyled footer-contact">
                        <li>
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= SITE_ADRESSE ?></span>
                        </li>
                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            <a href="tel:<?= str_replace(' ', '', SITE_TEL) ?>"><?= SITE_TEL ?></a>
                        </li>
                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
                        </li>
                    </ul>
                    <div class="footer-map mt-3">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2625.648!2d2.2938!3d48.8462!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e6703285cb5e9f%3A0x1234567890!2sECE%20Paris!5e0!3m2!1sfr!2sfr!4v1234567890"
                            width="100%" height="120" style="border:0; border-radius:8px;"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container-xl">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <p class="mb-0 small" style="color:var(--ecein-muted)">
                    &copy; <?= date('Y') ?> ECE In – Réseau social professionnel ECE Paris.
                    Projet Web ING2 2026.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="small" style="color:var(--ecein-muted)">Mentions légales</a>
                    <a href="#" class="small" style="color:var(--ecein-muted)">Confidentialité</a>
                    <a href="#" class="small" style="color:var(--ecein-muted)">CGU</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- ===================== FIN FOOTER ===================== -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS personnalisé -->
<script src="assets/js/main.js"></script>

<?php if (isset($pageScript) && $pageScript === 'messagerie.js'): ?>
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<?php endif; ?>
<?php if (isset($pageScript)): ?>
<script src="assets/js/<?= $pageScript ?>"></script>
<?php endif; ?>

</body>
</html>
