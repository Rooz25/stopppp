<?php
// TIJDELIJKE TESTPAGINA - stap 1, 3 en 4 uit PLAN.md
// Deze pagina controleert of de basisbestanden werken:
//   includes/db.php        -> verbinding met de database
//   includes/auth.php      -> sessie en inlogfuncties
//   includes/functions.php -> e(), url() en de CSRF-token
//   templates/header.php   -> navigatiebalk en style.css
// Hij wordt verwijderd zodra de echte pagina's klaar zijn.

require __DIR__ . '/includes/db.php';   // geeft $pdo
require __DIR__ . '/includes/auth.php'; // start de sessie, laadt functions.php

// De CSRF-token controleren als het testformulier verstuurd is.
// Dit moet BOVEN de header staan, omdat csrf_check() de pagina kan stoppen.
$tokenGoedgekeurd = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $tokenGoedgekeurd = true;
}

// Recepten ophalen met een prepared statement.
$statement = $pdo->prepare('SELECT title, prep_time FROM recipes ORDER BY id');
$statement->execute();
$recepten = $statement->fetchAll();

// Een gevaarlijke tekst om te testen of e() zijn werk doet.
$gevaarlijkeTekst = '<script>alert("xss")</script>';

$paginaTitel = 'Test basisbestanden';
require __DIR__ . '/templates/header.php';
?>

<div class="container">

    <h1>Test basisbestanden</h1>

    <p class="muted">PHP-versie: <?= e(PHP_VERSION) ?></p>

    <h2>1. Database: <?= count($recepten) ?> recepten gevonden</h2>

    <ul>
        <?php foreach ($recepten as $recept): ?>
            <li>
                <?= e($recept['title']) ?>
                (<?= (int) $recept['prep_time'] ?> min)
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>2. Sessie</h2>

    <p>Sessie actief: <?= session_status() === PHP_SESSION_ACTIVE ? 'ja' : 'nee' ?></p>
    <p>Ingelogd: <?= isLoggedIn() ? 'ja, als ' . e(currentUsername()) : 'nee (inlogpagina komt in stap 7)' ?></p>
    <p>Admin: <?= isAdmin() ? 'ja' : 'nee' ?></p>

    <h2>3. Escapen met e()</h2>

    <p>Hieronder moet de tekst van het script zichtbaar zijn, en mag er geen pop-up komen:</p>
    <p><?= e($gevaarlijkeTekst) ?></p>

    <h2>4. Links met url()</h2>

    <p>url('login.php') geeft: <?= e(url('login.php')) ?></p>
    <p>url('admin/index.php') geeft: <?= e(url('admin/index.php')) ?></p>

    <h2>5. CSRF-token</h2>

    <p>De token is <?= strlen(csrf_token()) ?> tekens lang.</p>

    <?php if ($tokenGoedgekeurd): ?>
        <p><strong>Token goedgekeurd.</strong></p>
    <?php endif; ?>

    <form method="post" action="">
        <?= csrf_field() ?>
        <button class="btn" type="submit">Test de token</button>
    </form>

</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
