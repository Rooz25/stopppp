<?php
// Login helpers and the session.
// Every page starts with:
//   require __DIR__ . '/includes/db.php';
//   require __DIR__ . '/includes/auth.php';
// This file loads functions.php by itself, so e() and csrf_token() also work.

require_once __DIR__ . '/functions.php';

// ---------------------------------------------------------
// Start the session
// ---------------------------------------------------------
// A session remembers who is logged in between pages. PHP stores a cookie
// with a session id in the browser.

if (session_status() === PHP_SESSION_NONE) {
    // Extra protection on that cookie. This must be set before session_start().
    session_set_cookie_params([
        // JavaScript cannot read the cookie, so a XSS bug cannot steal the session.
        'httponly' => true,

        // The browser does not send the cookie along with requests coming from
        // another website. That is extra help against CSRF.
        'samesite' => 'Lax',

        // Only send the cookie over https. Locally we use http, so there this
        // stays false, otherwise logging in would not work on XAMPP.
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);

    session_start();
}

// ---------------------------------------------------------
// Who is logged in?
// ---------------------------------------------------------

// Is anyone logged in at all?
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

// The id of the logged-in user, or null for a visitor.
// Used to check who owns a recipe.
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

// The username, for example to show in the menu.
function currentUsername(): string
{
    return $_SESSION['username'] ?? '';
}

// Is the logged-in user an administrator?
function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

// ---------------------------------------------------------
// Guarding pages
// ---------------------------------------------------------

// Visitors who are not logged in are sent to the login page.
// Put this at the top of pages like toevoegen.php and profiel.php.
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit; // stop right away, otherwise the rest of the page still runs
    }
}

// Only administrators may continue. Everyone else goes to the home page.
// Put this at the top of every page in the admin folder.
function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: ' . url('index.php'));
        exit;
    }
}

// ---------------------------------------------------------
// May this person edit or delete this recipe?
// ---------------------------------------------------------
// The rule: you may edit your own recipes, and an admin may edit everything.
// This is checked on the SERVER, not by hiding a button, because anyone can
// type a different id in the address bar.
function mayEditRecipe(int $recipeOwnerId): bool
{
    return isAdmin() || currentUserId() === $recipeOwnerId;
}
