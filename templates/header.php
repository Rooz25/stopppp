<?php
// The top of every page: the <head> and the navigation bar.
// A page uses it like this:
//
//   require __DIR__ . '/includes/db.php';
//   require __DIR__ . '/includes/auth.php';
//   $paginaTitel = 'Recepten';
//   require __DIR__ . '/templates/header.php';
//
// $navActive is optional. Set it to 'home', 'recepten', 'toevoegen',
// 'profiel' or 'beheer' when the filename alone is not clear (for example in
// the admin folder).

$paginaTitel = $paginaTitel ?? 'Samen sneller en makkelijker koken';

if (!isset($navActive)) {
    $navActive = match (basename($_SERVER['SCRIPT_NAME'] ?? '')) {
        'recepten.php', 'recept.php' => 'recepten',
        'toevoegen.php'             => 'toevoegen',
        'profiel.php', 'login.php', 'registreren.php' => 'profiel',
        default                     => 'home',
    };
}

$letter = mb_strtoupper(mb_substr(currentUsername(), 0, 1));
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AA KOOK! - Samen sneller en makkelijker koken met wat je al in huis hebt.">
    <title><?= e($paginaTitel) ?> | AA KOOK!</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="nav-shell">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="AA KOOK! home">
            <span class="brand-mark">AA</span>
            <span class="brand-text">KOOK<span>!</span></span>
        </a>

        <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false">
            <span class="nav-toggle-bars" aria-hidden="true"></span>
            <span class="sr-only">Menu openen</span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="Hoofdmenu">
            <a class="nav-link <?= $navActive === 'home' ? 'is-active' : '' ?>"
               href="<?= e(url('index.php')) ?>">Home</a>
            <a class="nav-link <?= $navActive === 'recepten' ? 'is-active' : '' ?>"
               href="<?= e(url('recepten.php')) ?>">Recepten</a>
            <a class="nav-link <?= $navActive === 'toevoegen' ? 'is-active' : '' ?>"
               href="<?= e(url('toevoegen.php')) ?>">Toevoegen</a>
            <a class="nav-link <?= $navActive === 'profiel' ? 'is-active' : '' ?>"
               href="<?= e(url('profiel.php')) ?>">Profiel</a>

            <?php if (isAdmin()): ?>
                <a class="nav-link <?= $navActive === 'beheer' ? 'is-active' : '' ?>"
                   href="<?= e(url('admin/index.php')) ?>">Beheer</a>
            <?php endif; ?>

            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-user" href="<?= e(url('profiel.php')) ?>">
                        <span class="avatar avatar-sm" aria-hidden="true"><?= e($letter) ?></span>
                        <span><?= e(currentUsername()) ?></span>
                    </a>

                    <?php
                    // Logging out changes something, so it goes through a POST form
                    // with a CSRF token and not through a normal link.
                    ?>
                    <form class="logout-form" method="post" action="<?= e(url('logout.php')) ?>">
                        <?= csrf_field() ?>
                        <button class="nav-logout" type="submit">Uitloggen</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-indigo btn-sm" href="<?= e(url('login.php')) ?>">Inloggen</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main>
