<?php
// The top of every page: the <head> and the navigation bar.
// A page uses it like this:
//
//   require __DIR__ . '/includes/db.php';
//   require __DIR__ . '/includes/auth.php';
//   $paginaTitel = 'Recepten';
//   require __DIR__ . '/templates/header.php';
//
// The HTML and the class names come from the index.php of Rooz and Gijs,
// so the styling in style.css keeps working. Only the links changed: they
// used to be #hash links for the JavaScript version, now they point to
// real pages.

// If a page forgets to set a title, we use this one.
$paginaTitel = $paginaTitel ?? 'Samen sneller en makkelijker koken';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($paginaTitel) ?> | AA KOOK!</title>
    <link rel="stylesheet" href="<?= e(url('style.css')) ?>">
</head>
<body>
<header class="navbar">
    <a class="logo" href="<?= e(url('index.php')) ?>">AA KOOK<span>!</span></a>

    <button id="menuButton" class="menu-button">&#9776;</button>

    <nav id="menu">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <a href="<?= e(url('recepten.php')) ?>">Recepten</a>

        <?php if (isLoggedIn()): ?>

            <a href="<?= e(url('toevoegen.php')) ?>">Toevoegen</a>
            <a href="<?= e(url('profiel.php')) ?>">Profiel</a>

            <?php if (isAdmin()): ?>
                <a href="<?= e(url('admin/index.php')) ?>">Beheer</a>
            <?php endif; ?>

            <?php
            // Logging out changes something, so it goes through a POST form with
            // a CSRF token and not through a normal link. A link could be opened
            // by another website to log you out without you knowing.
            // NOTE FOR ROOZ AND GIJS: this button has no styling yet.
            ?>
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit">Uitloggen (<?= e(currentUsername()) ?>)</button>
            </form>

        <?php else: ?>

            <a href="<?= e(url('login.php')) ?>">Inloggen</a>
            <a href="<?= e(url('registreren.php')) ?>">Registreren</a>

        <?php endif; ?>
    </nav>
</header>

<main>
