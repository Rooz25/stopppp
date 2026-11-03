<?php
// The bottom of every page. Closes the <main> that header.php opened.
?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= e(url('index.php')) ?>">
                <span class="brand-mark">AA</span>
                <span class="brand-text">KOOK<span>!</span></span>
            </a>
            <p>Samen sneller en makkelijker koken met wat je al in huis hebt.</p>
        </div>

        <nav class="footer-links" aria-label="Footer">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <a href="<?= e(url('recepten.php')) ?>">Recepten</a>
            <a href="<?= e(url('toevoegen.php')) ?>">Recept delen</a>
            <a href="<?= e(url('profiel.php')) ?>">Profiel</a>
        </nav>
    </div>

    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> AA KOOK! &middot; Stop de ontkoking</span>
        <span>Gemaakt voor iedereen die net iets vaker zelf wil koken.</span>
    </div>
</footer>

<script src="<?= e(url('script.js')) ?>" defer></script>
</body>
</html>
