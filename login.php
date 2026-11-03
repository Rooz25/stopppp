<?php
// Login page. You can log in with your e-mail address or your username.
// Blocked accounts are refused.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url(), csrf

if (isLoggedIn()) {
    header('Location: ' . url('profiel.php'));
    exit;
}

$fout = '';
$ingevuldeLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $login      = trim($_POST['login'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';

    $ingevuldeLogin = $login;

    if ($login === '' || $wachtwoord === '') {
        $fout = 'Vul je e-mailadres of gebruikersnaam en je wachtwoord in.';
    } else {
        // Two different parameter names with the same value: a native prepared
        // statement may not use the same name twice.
        $statement = $pdo->prepare(
            'SELECT id, username, password_hash, role, status
               FROM users
              WHERE email = :email
                 OR username = :username
              LIMIT 1'
        );
        $statement->execute([
            'email'    => $login,
            'username' => $login,
        ]);

        $gebruiker = $statement->fetch();

        $wachtwoordKlopt = $gebruiker !== false
            && password_verify($wachtwoord, $gebruiker['password_hash']);

        if (!$wachtwoordKlopt) {
            // Deliberately general: do not reveal which accounts exist.
            $fout = 'E-mailadres of wachtwoord is onjuist.';
        } elseif ($gebruiker['status'] === 'blocked') {
            $fout = 'Dit account is geblokkeerd. Neem contact op met de beheerder.';
        } else {
            logInUser($gebruiker);

            header('Location: ' . url('profiel.php'));
            exit;
        }
    }
}

// The four newest recipes for the right-hand panel.
$nieuwsteStatement = $pdo->prepare(
    "SELECT r.id,
            r.user_id,
            r.title,
            r.description,
            r.prep_time,
            r.servings,
            u.username AS auteur,
            (SELECT rp.file_path
               FROM recipe_photos rp
              WHERE rp.recipe_id = r.id
              ORDER BY rp.sort_order, rp.id
              LIMIT 1) AS omslagfoto,
            (SELECT GROUP_CONCAT(c.name ORDER BY c.id SEPARATOR ', ')
               FROM recipe_categories rc
               JOIN categories c ON c.id = rc.category_id
              WHERE rc.recipe_id = r.id) AS categorieen
       FROM recipes r
       JOIN users u ON u.id = r.user_id
      ORDER BY r.created_at DESC, r.id DESC
      LIMIT 4"
);
$nieuwsteStatement->execute();
$nieuwsteRecepten = $nieuwsteStatement->fetchAll();

$paginaTitel = 'Inloggen';
$navActive = 'profiel';
require __DIR__ . '/templates/header.php';
?>

<div class="container auth-page">
    <div class="auth-layout">
        <section class="auth-card">
            <p class="eyebrow">Welkom terug</p>
            <h1>Inloggen</h1>
            <p class="auth-lead">Log in om recepten toe te voegen en te bewaren.</p>

            <?php if (isset($_GET['uitgelogd'])): ?>
                <div class="notice notice-success">Je bent uitgelogd.</div>
            <?php endif; ?>

            <?php if (isset($_GET['registreren'])): ?>
                <div class="notice notice-success">Je account is aangemaakt. Je kunt nu inloggen.</div>
            <?php endif; ?>

            <?php if ($fout !== ''): ?>
                <div class="notice notice-error"><?= e($fout) ?></div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('login.php')) ?>">
                <?= csrf_field() ?>

                <label class="field">
                    E-mailadres of gebruikersnaam
                    <input type="text" name="login" required autocomplete="username"
                           value="<?= e($ingevuldeLogin) ?>">
                </label>

                <label class="field">
                    Wachtwoord
                    <input type="password" name="password" required autocomplete="current-password">
                </label>

                <button class="btn btn-coral btn-block" type="submit">Inloggen</button>
            </form>

            <p class="auth-switch">
                Nog geen account?
                <a href="<?= e(url('registreren.php')) ?>">Registreren</a>
            </p>
        </section>

        <section class="auth-showcase">
            <div class="showcase-head">
                <div>
                    <p class="eyebrow">Van de keuken</p>
                    <h2>Mijn recepten</h2>
                </div>
                <a class="btn btn-coral btn-sm" href="<?= e(url('toevoegen.php')) ?>">+ nieuw recept</a>
            </div>

            <?php if ($nieuwsteRecepten === []): ?>
                <div class="empty-state">
                    <h3>Nog geen recepten</h3>
                    <p>Maak een account en deel het eerste recept.</p>
                </div>
            <?php else: ?>
                <div class="recipe-grid recipe-grid-compact">
                    <?php foreach ($nieuwsteRecepten as $recept): ?>
                        <?php require __DIR__ . '/templates/recipe-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
