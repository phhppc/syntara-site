</main>

<footer class="rodape">
    <div class="container">
        <div class="rodape-grid">
            <div class="footer-brand">
                <span class="logo-icon">🎓</span> <?= SITE_NAME ?>
                <p>Sistema de Avaliação Escolar</p>
            </div>
            <div class="footer-links">
                <a href="<?= url('index.php') ?>">Início</a>
                <a href="<?= url('login.php') ?>">Login</a>
                <a href="<?= url('register.php') ?>">Registrar</a>
                <a href="<?= url('denuncia.php') ?>" style="color:#fca5a5;">🛡️ Denúncia Anônima</a>
            </div>
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?> v<?= SITE_VERSION ?></p>
                <p style="margin-top:4px;">Todos os direitos reservados.</p>
            </div>
        </div>
        <div class="rodape-copy">
            <hr>
            <p>Feito com ❤️ para a educação</p>
        </div>
    </div>
</footer>

<div class="toast-container" id="toastContainer"></div>

<script src="<?= url('assets/js/main.js') ?>"></script>

<!-- Sticky header shadow on scroll -->
<script>
window.addEventListener('scroll', function() {
    const header = document.getElementById('header');
    if (header) {
        header.classList.toggle('nav-scrolled', window.scrollY > 20);
    }
});
</script>

</body>
</html>
