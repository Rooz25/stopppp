<?php
// TIJDELIJKE TESTPAGINA - stap 1 uit PLAN.md
// Deze pagina controleert alleen of PHP de database kan bereiken.
// Hij wordt weer verwijderd zodra de echte pagina's klaar zijn (na stap 4).

require __DIR__ . '/config.php';

// We vangen fouten op, zodat je een leesbare melding krijgt
// in plaats van een witte pagina.
try {
    // Verbinden met de database via PDO.
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // fouten melden
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rijen als array
        ]
    );

    // Tellen hoeveel recepten er in de database staan.
    $aantal = (int) $pdo->query('SELECT COUNT(*) FROM recipes')->fetchColumn();

    // De titels ophalen om te zien dat het echte data is.
    $recepten = $pdo->query('SELECT title, prep_time FROM recipes ORDER BY id')->fetchAll();

} catch (PDOException $fout) {
    // Alleen op deze testpagina laten we de echte foutmelding zien,
    // omdat die helpt bij het instellen van XAMPP.
    $foutmelding = $fout->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Databasetest</title>
</head>
<body>
<h1>Databasetest</h1>

<p>PHP-versie: <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></p>

<?php if (isset($foutmelding)): ?>

    <h2>Verbinding mislukt</h2>
    <p><?= htmlspecialchars($foutmelding, ENT_QUOTES, 'UTF-8') ?></p>
    <p>Controleer of MySQL aan staat in XAMPP en of de database
       <strong><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></strong> bestaat.</p>

<?php else: ?>

    <h2><?= $aantal ?> recepten gevonden</h2>

    <ul>
        <?php foreach ($recepten as $recept): ?>
            <li>
                <?= htmlspecialchars($recept['title'], ENT_QUOTES, 'UTF-8') ?>
                (<?= (int) $recept['prep_time'] ?> min)
            </li>
        <?php endforeach; ?>
    </ul>

<?php endif; ?>
</body>
</html>
