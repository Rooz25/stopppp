<?php
// Uitloggen.
// Deze pagina heeft geen database nodig, alleen de sessie.

require __DIR__ . '/includes/auth.php';

// Uitloggen verandert iets aan je sessie, dus het mag alleen via een
// POST-formulier met een geldige CSRF-token. Het formulier staat in
// templates/header.php.
// Zou uitloggen via een gewone link kunnen, dan kan een andere website een
// plaatje met die link op hun pagina zetten en ben je zomaar uitgelogd.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('index.php'));
    exit;
}

csrf_check();

logOutUser();

// Doorsturen naar de inlogpagina met een melding.
header('Location: ' . url('login.php?uitgelogd=1'));
exit;
