<?php
// Inlogpagina.
// Je kunt inloggen met je e-mailadres of met je gebruikersnaam.
// Geblokkeerde accounts komen er niet in.

require __DIR__ . '/includes/db.php';   // geeft $pdo
require __DIR__ . '/includes/auth.php'; // sessie, e(), url(), csrf

// Wie al ingelogd is, hoeft hier niet te zijn.
if (isLoggedIn()) {
    header('Location: ' . url('recepten.php'));
    exit;
}

$fout = '';

// Wat de bezoeker had ingevuld, zodat het veld niet leeg is na een fout.
// Het wachtwoord vullen we nooit opnieuw in.
$ingevuldeLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Eerst de CSRF-token controleren. Bij een verkeerde token stopt de pagina.
    csrf_check();

    $login      = trim($_POST['login'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';

    $ingevuldeLogin = $login;

    if ($login === '' || $wachtwoord === '') {

        $fout = 'Vul je e-mailadres of gebruikersnaam en je wachtwoord in.';

    } else {

        // De gebruiker opzoeken op e-mailadres OF gebruikersnaam.
        // Let op: we gebruiken twee verschillende namen (:email en :username)
        // met dezelfde waarde. Bij echte prepared statements mag je dezelfde
        // parameternaam namelijk niet twee keer in een query gebruiken.
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

        // password_verify vergelijkt het ingetypte wachtwoord met de hash
        // uit de database. De hash zelf kun je niet terugrekenen.
        $wachtwoordKlopt = $gebruiker !== false
            && password_verify($wachtwoord, $gebruiker['password_hash']);

        if (!$wachtwoordKlopt) {

            // Bewust een algemene melding: we zeggen niet of het e-mailadres
            // bestaat. Anders kan iemand uitzoeken welke accounts er zijn.
            $fout = 'E-mailadres of wachtwoord is onjuist.';

        } elseif ($gebruiker['status'] === 'blocked') {

            // De statuscontrole komt NA het wachtwoord, zodat alleen iemand
            // die het wachtwoord echt weet te horen krijgt dat het account
            // geblokkeerd is.
            $fout = 'Dit account is geblokkeerd. Neem contact op met de beheerder.';

        } else {

            // Alles klopt: de gebruiker in de sessie zetten.
            logInUser($gebruiker);

            // Daarna doorsturen. Zo staat het formulier niet meer in de
            // browser en krijg je geen "formulier opnieuw versturen?" vraag.
            header('Location: ' . url('recepten.php'));
            exit;
        }
    }
}

$paginaTitel = 'Inloggen';
require __DIR__ . '/templates/header.php';
?>

<div class="container">

    <h1>Inloggen</h1>

    <?php if (isset($_GET['uitgelogd'])): ?>
        <p class="empty">Je bent uitgelogd.</p>
    <?php endif; ?>

    <?php // NOTE FOR ROOZ AND GIJS: there is no error style yet, so this uses .empty ?>
    <?php if ($fout !== ''): ?>
        <p class="empty"><strong><?= e($fout) ?></strong></p>
    <?php endif; ?>

    <form class="form" method="post" action="<?= e(url('login.php')) ?>">

        <?= csrf_field() ?>

        <label class="field">
            E-mailadres of gebruikersnaam
            <input type="text" name="login" required value="<?= e($ingevuldeLogin) ?>">
        </label>

        <label class="field">
            Wachtwoord
            <input type="password" name="password" required>
        </label>

        <div class="actions">
            <button class="btn" type="submit">Inloggen</button>
            <a class="btn light" href="<?= e(url('registreren.php')) ?>">Nog geen account?</a>
        </div>
    </form>

</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
