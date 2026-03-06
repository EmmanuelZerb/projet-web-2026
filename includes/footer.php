<?php
/**
 * ECE In - Pied de page (Version Alt - Sidebar Layout)
 */
?>
    </div><!-- /.page-body -->
</div><!-- /.main-content -->

<!-- ===================== FOOTER ===================== -->
<footer class="footer-ecein">
    <div class="footer-top">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="logo-mark" style="width:28px;height:28px;font-size:.7rem;border-radius:7px">EC</div>
                    <span class="fw-semibold" style="color:var(--text)">ECE In</span>
                </div>
                <p class="small" style="color:var(--text-3)">
                    Le réseau social professionnel de la communauté ECE Paris.
                </p>
                <div class="d-flex gap-3 mt-2">
                    <a href="#" style="color:var(--text-3)"><i class="bi bi-linkedin"></i></a>
                    <a href="#" style="color:var(--text-3)"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" style="color:var(--text-3)"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div class="col-sm-6 col-lg-2">
                <h6 class="fw-semibold mb-2" style="color:var(--accent);font-size:.78rem">Navigation</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="reseau.php">Réseau</a></li>
                    <li><a href="emplois.php">Emplois</a></li>
                </ul>
            </div>

            <div class="col-sm-6 col-lg-2">
                <h6 class="fw-semibold mb-2" style="color:var(--accent);font-size:.78rem">ECE Paris</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="https://www.ece.fr" target="_blank" rel="noopener">Site officiel</a></li>
                    <li><a href="https://www.omneseducation.com" target="_blank" rel="noopener">Omnes Education</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h6 class="fw-semibold mb-2" style="color:var(--accent);font-size:.78rem">Contact</h6>
                <ul class="list-unstyled footer-contact">
                    <li><i class="bi bi-geo-alt-fill"></i><span><?= SITE_ADRESSE ?></span></li>
                    <li><i class="bi bi-telephone-fill"></i><a href="tel:<?= str_replace(' ', '', SITE_TEL) ?>"><?= SITE_TEL ?></a></li>
                    <li><i class="bi bi-envelope-fill"></i><a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="mb-0 small" style="color:var(--text-3)">
                &copy; <?= date('Y') ?> ECE In – Projet Web ING2 2026
            </p>
            <div class="d-flex gap-3">
                <a href="#" class="small" style="color:var(--text-3)">Mentions légales</a>
                <a href="#" class="small" style="color:var(--text-3)">CGU</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

<?php if (isset($pageScript) && $pageScript === 'messagerie.js'): ?>
<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
<?php endif; ?>
<?php if (isset($pageScript)): ?>
<script src="assets/js/<?= $pageScript ?>"></script>
<?php endif; ?>

<script>
function toggleSidebar() {
    document.getElementById('sidebarNav').classList.toggle('open');
    document.getElementById('sidebarBackdrop').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebarNav').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('show');
}
</script>

</body>
</html>
