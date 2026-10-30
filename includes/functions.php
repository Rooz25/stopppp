<?php
// Small helper functions used by every page.
// This file is loaded automatically by includes/auth.php.

// url() needs BASE_URL from config.php. We load that here, so a page that only
// includes auth.php (like logout.php, which needs no database) still works.
require_once __DIR__ . '/../config.php';

// ---------------------------------------------------------
// Printing text safely
// ---------------------------------------------------------

// Escape text before printing it in HTML. This protects against XSS:
// if someone saves a recipe title like <script>...</script>, the browser
// shows it as plain text instead of running it.
// Use this around EVERY piece of data that comes out of the database.
function e($tekst): string
{
    return htmlspecialchars((string) ($tekst ?? ''), ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------
// Links
// ---------------------------------------------------------

// Build a link that works both locally and on the server.
// Locally the site runs in a subfolder (/stopppp/), on Plesk in the root (/).
// BASE_URL is set in config.php, so each computer has its own value.
// Example: url('login.php') gives "/stopppp/login.php" locally.
function url(string $pad = ''): string
{
    return BASE_URL . ltrim($pad, '/');
}

// ---------------------------------------------------------
// Amounts
// ---------------------------------------------------------

// Print an ingredient amount the way a Dutch recipe writes it.
// The database column is DECIMAL(8,2), so it gives back "4.00" and "0.50".
// This turns that into "4" and "0,5". An empty amount gives an empty string,
// which means "naar smaak" in the recipe.
function formatAmount(?string $hoeveelheid): string
{
    if ($hoeveelheid === null || $hoeveelheid === '') {
        return '';
    }

    // First to a number with a comma, then remove trailing zeros
    // and a comma that is left over at the end.
    $tekst = number_format((float) $hoeveelheid, 2, ',', '');
    $tekst = rtrim($tekst, '0');
    $tekst = rtrim($tekst, ',');

    return $tekst;
}

// ---------------------------------------------------------
// Photos
// ---------------------------------------------------------

// Does the photo file really exist in the uploads folder?
// The database already holds paths for the test recipes, but those image files
// have not been uploaded yet. Without this check the cards would show broken
// image icons, because style.css gives every card image a fixed height.
// From step 11 on, real uploads will pass this check.
function photoExists(?string $pad): bool
{
    if ($pad === null || $pad === '') {
        return false;
    }

    return is_file(__DIR__ . '/../' . $pad);
}

// ---------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------
// CSRF means: someone else's website secretly sends a request with your
// login session, for example to delete your recipe. To prevent that, every
// form gets a secret code that only our own pages know.
// These functions need a running session, which includes/auth.php starts.

// Get the secret code for this visitor, or make one on the first call.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 random bytes, written as text. Not guessable.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Print the hidden field. Put a short echo of this function inside every
// POST form, so the secret code is sent along with the form.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Check the code when a form is sent in. Call this at the top of every page
// that handles a POST. If the code is wrong, the page stops immediately.
function csrf_check(): void
{
    $verzonden = $_POST['csrf_token'] ?? '';

    // hash_equals compares the two codes safely.
    if (!is_string($verzonden) || !hash_equals(csrf_token(), $verzonden)) {
        http_response_code(403);
        exit('Dit formulier is verlopen of ongeldig. Ga terug en probeer het opnieuw.');
    }
}
