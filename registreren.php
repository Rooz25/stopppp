<?php
// Registration page: create a new account.

require __DIR__ . '/includes/db.php';   // gives $pdo
require __DIR__ . '/includes/auth.php'; // session, e(), url(), csrf

if (isLoggedIn()) {
    header('Location: ' . url('profiel.php'));
    exit;
}

$fouten = [];
$gebruikersnaam = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $gebruikersnaam  = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $wachtwoord      = $_POST['password'] ?? '';
    $wachtwoordExtra = $_POST['password_repeat'] ?? '';

    // Username: 3 to 50 characters, letters, digits, - and _ only.
    if ($gebruikersnaam === '') {
        $fouten[] = 'Vul een gebruikersnaam in.';
    } elseif (mb_strlen($gebruikersnaam) < 3 || mb_strlen($gebruikersnaam) > 50) {
        $fouten[] = 'Je gebruikersnaam moet tussen 3 en 50 tekens lang zijn.';
    } elseif (preg_match('/^[a-zA-Z0-9_-]+$/', $gebruikersnaam) !== 1) {
        $fouten[] = 'Gebruik in je gebruikersnaam alleen letters, cijfers, - en _';
    }

    // E-mail address.
    if ($email === '') {
        $fouten[] = 'Vul een e-mailadres in.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $fouten[] = 'Dit is geen geldig e-mailadres.';
    } elseif (mb_strlen($email) > 255) {
        $fouten[] = 'Dit e-mailadres is te lang.';
    }

    // Password: at least 8 characters and typed the same twice.
    if (mb_strlen($wachtwoord) < 8) {
        $fouten[] = 'Je wachtwoord moet minimaal 8 tekens lang zijn.';
    } elseif ($wachtwoord !== $wachtwoordExtra) {
        $fouten[] = 'De twee wachtwoorden zijn niet hetzelfde.';
    }

    // Is the username or e-mail already taken?
    if ($fouten === []) {
        $controle = $pdo->prepare(
            'SELECT username, email
               FROM users
              WHERE username = :username
                 OR email = :email'
        );
        $controle->execute([
            'username' => $gebruikersnaam,
            'email'    => $email,
        ]);

        foreach ($controle->fetchAll() as $bestaand) {
            if (mb_strtolower($bestaand['username']) === mb_strtolower($gebruikersnaam)) {
                $fouten[] = 'Deze gebruikersnaam is al bezet.';
            }

            if (mb_strtolower($bestaand['email']) === mb_strtolower($email)) {
                $fouten[] = 'Er bestaat al een account met dit e-mailadres.';
            }
        }
    }

    // Save the account.
    if ($fouten === []) {
        $hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

        try {
            $opslaan = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash)
                 VALUES (:username, :email, :hash)'
            );
            $opslaan->execute([
                'username' => $gebruikersnaam,
                'email'    => $email,
                'hash'     => $hash,
            ]);

            header('Location: ' . url('login.php?registreren=1'));
            exit;
        } catch (PDOException $fout) {
            if ($fout->getCode() === '23000') {
                $fouten[] = 'Deze gebruikersnaam of dit e-mailadres is al bezet.';
            } else {
                error_log('Registreren mislukt: ' . $fout->getMessage());
                $fouten[] = 'Er ging iets mis bij het opslaan. Probeer het later opnieuw.';
            }
        }
    }
}

// Four recipes for the right-hand panel.
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

$paginaTitel = 'Account aanmaken';
$navActive = 'profiel';
require __DIR__ . '/templates/header.php';
?>

<div class="container auth-page">
    <div class="auth-layout">
        <section class="auth-card">
            <p class="eyebrow">Doe mee</p>
            <h1>Account aanmaken</h1>
            <p class="auth-lead">Maak een account om je eigen recepten te delen en te bewaren.</p>

            <?php if ($fouten !== []): ?>
                <div class="notice notice-error">
                    <strong>Let op:</strong>
                    <ul>
                        <?php foreach ($fouten as $foutmelding): ?>
                            <li><?= e($foutmelding) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('registreren.php')) ?>">
                <?= csrf_field() ?>

                <label class="field">
                    Gebruikersnaam
                    <input type="text" name="username" required minlength="3" maxlength="50"
                           autocomplete="username" value="<?= e($gebruikersnaam) ?>">
                </label>

                <label class="field">
                    E-mailadres
                    <input type="email" name="email" required maxlength="255"
                           autocomplete="email" value="<?= e($email) ?>">
                </label>

                <label class="field">
                    Wachtwoord (minimaal 8 tekens)
                    <input type="password" name="password" required minlength="8"
                           autocomplete="new-password">
                </label>

                <label class="field">
                    Wachtwoord nog een keer
                    <input type="password" name="password_repeat" required minlength="8"
                           autocomplete="new-password">
                </label>

                <button class="btn btn-coral btn-block" type="submit">Account aanmaken</button>
            </form>

            <p class="auth-switch">
                Ik heb al een account.
                <a href="<?= e(url('login.php')) ?>">Inloggen</a>
            </p>
        </section>

        <section class="auth-showcase">
            <div class="showcase-head">
                <div>
                    <p class="eyebrow">Van de keuken</p>
                    <h2>Op het menu</h2>
                </div>
                <a class="btn btn-light btn-sm" href="<?= e(url('recepten.php')) ?>">Alles bekijken</a>
            </div>

            <?php if ($nieuwsteRecepten === []): ?>
                <div class="empty-state">
                    <h3>Nog geen recepten</h3>
                    <p>Wees de eerste die iets deelt.</p>
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
