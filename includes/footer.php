<?php
/**
 * ECE In - Pied de page HTML commun
 * Pied de page commun : contact, Google Maps, liens utiles, scripts JS
 */
?>
<!-- Infos de contact de l'ECE (adresse, téléphone, email) comme demandé dans le sujet -->
<!-- ===================== FOOTER ===================== -->
<footer class="footer-ecein mt-5">
    <div class="footer-top">
        <div class="container-xl">
            <div class="row g-4">
                <!-- Logo & Description -->
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-ecein logo-ecein-sm">
                            <span class="logo-ece">ECE</span><span class="logo-in">In</span>
                        </div>
                        <span class="text-white fw-semibold">Réseau ECE Paris</span>
                    </div>
                    <p class="text-white-50 small">
                        Le réseau social professionnel de la communauté ECE Paris.
                        Connectez-vous, partagez vos réalisations et développez votre carrière.
                    </p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-white-50 fs-5"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-white-50 fs-5"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-white-50 fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white-50 fs-5"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="col-sm-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Navigation</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="reseau.php">Mon Réseau</a></li>
                        <li><a href="profil.php">Mon Profil</a></li>
                        <li><a href="notifications.php">Notifications</a></li>
                        <li><a href="messagerie.php">Messagerie</a></li>
                        <li><a href="emplois.php">Emplois</a></li>
                    </ul>
                </div>

                <!-- Ressources -->
                <div class="col-sm-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">ECE Paris</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="https://www.ece.fr" target="_blank" rel="noopener">Site officiel ECE</a></li>
                        <li><a href="https://www.omneseducation.com" target="_blank" rel="noopener">Omnes Education</a></li>
                        <li><a href="emplois.php">Offres d'emploi</a></li>
                        <li><a href="emplois.php?type=stage">Stages</a></li>
                        <li><a href="emplois.php?type=alternance">Alternances</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-4">
                    <h6 class="text-white fw-semibold mb-3">Contact & Localisation</h6>
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
                    <!-- Intégration Google Maps pour montrer la localisation de l'ECE -->
                    <div class="footer-map mt-3">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2625.9!2d2.2855!3d48.8510!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e6701b4f128b93%3A0x83e483bd8e2bff0d!2sECE%20Paris%20-%20%C3%89cole%20d&#39;ing%C3%A9nieurs!5e0!3m2!1sfr!2sfr!4v1709500000000"
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
                <p class="mb-0 text-white-50 small">
                    &copy; <?= date('Y') ?> ECE In – Réseau social professionnel ECE Paris.
                    Projet Web ING2 2026.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white-50 small">Mentions légales</a>
                    <a href="#" class="text-white-50 small">Confidentialité</a>
                    <a href="#" class="text-white-50 small">CGU</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- ===================== FIN FOOTER ===================== -->

<!-- Modal global pour partager des publications (utilisé sur toutes les pages) -->
<?php if (isset($userCourant)): ?>
<div class="modal fade" id="modalPartager" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-share me-2"></i>Partager la publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="partage-post-id" value="">

                <div class="d-flex gap-3 mb-3">
                    <img src="<?= h(photo($userCourant['photo'] ?? null)) ?>" alt="" class="rounded-circle flex-shrink-0" width="40" height="40" style="object-fit:cover">
                    <textarea id="partage-commentaire" class="form-control border-0 bg-light" rows="3"
                              placeholder="Ajouter un commentaire..." style="resize:none"></textarea>
                </div>

                <div class="partage-preview">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img id="partage-preview-avatar" src="" alt="" class="rounded-circle" width="28" height="28" style="object-fit:cover">
                        <span id="partage-preview-auteur" class="fw-semibold small"></span>
                    </div>
                    <p id="partage-preview-contenu" class="small text-muted mb-0"></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btn-partager" class="btn btn-ecein-primary px-4" onclick="envoyerPartage()">
                    <i class="bi bi-share me-2"></i>Partager
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- On charge jQuery, Bootstrap JS, notre script principal, et PeerJS uniquement sur la messagerie -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS personnalisé -->
<script src="assets/js/main.js?v=3"></script>

<?php if (isset($pageScript) && $pageScript === 'messagerie.js'): ?>
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<?php endif; ?>
<?php if (isset($pageScript)): ?>
<script src="assets/js/<?= $pageScript ?>"></script>
<?php endif; ?>

</body>
</html>
