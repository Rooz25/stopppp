<?php
// Registratiepagina: een nieuw account aanmaken.

require __DIR__ . '/includes/db.php';   // geeft $pdo
require __DIR__ . '/includes/auth.php'; // sessie, e(), url(), csrf

// Wie al ingelogd is, hoeft geen account aan te maken.
if (isLoggedIn()) {
    header('Location: ' . url('recepten.php'));
    exit;
}

// Alle foutmeldingen samen, zodat de bezoeker ze in een keer ziet.
$fouten = [];

// De ingevulde waardes bewaren, behalve de wachtwoorden.
$gebruikersnaam = '';
$email          = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();

    $gebruikersnaam  = trim($_POST['username'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $wachtwoord      = $_POST['password'] ?? '';
    $wachtwoordExtra = $_POST['password_repeat'] ?? '';

    // ---------------------------------------------------------
    // 1. Controleren wat er is ingevuld
    // ---------------------------------------------------------

    // Gebruikersnaam: 3 tot 50 tekens, alleen letters, cijfers, - en _
    // De database staat maximaal 50 tekens toe.
    if ($gebruikersnaam === '') {
        $fouten[] = 'Vul een gebruikersnaam in.';
    } elseif (mb_strlen($gebruikersnaam) < 3 || mb_strlen($gebruikersnaam) > 50) {
        $fouten[] = 'Je gebruikersnaam moet tussen 3 en 50 tekens lang zijn.';
    } elseif (preg_match('/^[a-zA-Z0-9_-]+$/', $gebruikersnaam) !== 1) {
        $fouten[] = 'Gebruik in je gebruikersnaam alleen letters, cijfers, - en _';
    }

    // E-mailadres: PHP heeft hier een eigen controle voor.
    if ($email === '') {
        $fouten[] = 'Vul een e-mailadres in.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $fouten[] = 'Dit is geen geldig e-mailadres.';
    } elseif (mb_strlen($email) > 255) {
        $fouten[] = 'Dit e-mailadres is te lang.';
    }

    // Wachtwoord: minimaal 8 tekens en twee keer hetzelfde.
    if (mb_strlen($wachtwoord) < 8) {
        $fouten[] = 'Je wachtwoord moet minimaal 8 tekens lang zijn.';
    } elseif ($wachtwoord !== $wachtwoordExtra) {
        $fouten[] = 'De twee wachtwoorden zijn niet hetzelfde.';
    }

    // ---------------------------------------------------------
    // 2. Is de gebruikersnaam of het e-mailadres al bezet?
    // ---------------------------------------------------------
    // In de database staat een UNIQUE-regel op beide kolommen. Deze query is
    // er alleen om een nette melding te kunnen geven.

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
            // Kleine letters vergelijken, want de database let niet op
            // hoofdletters bij het vergelijken van tekst.
            if (mb_strtolower($bestaand['username']) === mb_strtolower($gebruikersnaam)) {
                $fouten[] = 'Deze gebruikersnaam is al bezet.';
            }

            if (mb_strtolower($bestaand['email']) === mb_strtolower($email)) {
                $fouten[] = 'Er bestaat al een account met dit e-mailadres.';
            }
        }
    }

    // ---------------------------------------------------------
    // 3. Opslaan
    // ---------------------------------------------------------

    if ($fouten === []) {

        // password_hash versleutelt het wachtwoord. Het echte wachtwoord
        // komt nooit in de database te staan, alleen deze hash.
        // PASSWORD_DEFAULT laat PHP zelf de beste methode kiezen.
        $hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

        try {
            // role en status vullen we niet in: de database zet die zelf
            // standaard op 'user' en 'active'.
            $opslaan = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash)
                 VALUES (:username, :email, :hash)'
            );
            $opslaan->execute([
                'username' => $gebruikersnaam,
                'email'    => $email,
                'hash'     => $hash,
            ]);

            // Het account is aangemaakt. We loggen de bezoeker meteen in,
            // zodat hij niet direct opnieuw zijn gegevens hoeft in te typen.
            //
            // De nieuwe rij halen we eerst op met het id dat de database net
            // heeft uitgedeeld (lastInsertId). Zo weten we zeker welke rol er
            // in de database staat, in plaats van die hier te herhalen.
            $nieuweGebruiker = $pdo->prepare(
                'SELECT id, username, role
                   FROM users
                  WHERE id = :id'
            );
            $nieuweGebruiker->execute(['id' => (int) $pdo->lastInsertId()]);

            // logInUser zet de gebruiker in de sessie en geeft de sessie
            // meteen een nieuw id (session_regenerate_id).
            logInUser($nieuweGebruiker->fetch());

            // Doorsturen naar het overzicht met een welkomstmelding.
            header('Location: ' . url('recepten.php?welkom=1'));
            exit;

        } catch (PDOException $fout) {

            // Vangnet: als twee mensen op precies hetzelfde moment dezelfde
            // naam kiezen, houdt de UNIQUE-regel in de database het alsnog
            // tegen. Foutcode 23000 betekent "deze waarde bestaat al".
            if ($fout->getCode() === '23000') {
                $fouten[] = 'Deze gebruikersnaam of dit e-mailadres is al bezet.';
            } else {
                // Andere fouten alleen in het logboek, niet op het scherm.
                error_log('Registreren mislukt: ' . $fout->getMessage());
                $fouten[] = 'Er ging iets mis bij het opslaan. Probeer het later opnieuw.';
            }
        }
    }
}

$paginaTitel = 'Account aanmaken';
require __DIR__ . '/templates/header.php';
?>

<div class="container">

    <h1>Account aanmaken</h1>
    <p>Maak een account om je eigen recepten te delen.</p>

    <?php // NOTE FOR ROOZ AND GIJS: there is no error style yet, so this uses .empty ?>
    <?php if ($fouten !== []): ?>
        <div class="empty">
            <strong>Let op:</strong>
            <ul>
                <?php foreach ($fouten as $foutmelding): ?>
                    <li><?= e($foutmelding) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="form" method="post" action="<?= e(url('registreren.php')) ?>">

        <?= csrf_field() ?>

        <label class="field">
            Gebruikersnaam
            <input type="text" name="username" required minlength="3" maxlength="50"
                   value="<?= e($gebruikersnaam) ?>">
        </label>

        <label class="field">
            E-mailadres
            <input type="email" name="email" required maxlength="255"
                   value="<?= e($email) ?>">
        </label>

        <label class="field">
            Wachtwoord (minimaal 8 tekens)
            <input type="password" name="password" required minlength="8">
        </label>

        <label class="field">
            Wachtwoord nog een keer
            <input type="password" name="password_repeat" required minlength="8">
        </label>

        <div class="actions">
            <button class="btn" type="submit">Account aanmaken</button>
            <a class="btn light" href="<?= e(url('login.php')) ?>">Ik heb al een account</a>
        </div>
    </form>

</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
