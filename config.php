<?php
// Database login details. This file is different on every computer:
// on my laptop it points to XAMPP, on Plesk it points to the real database.
// This file is in .gitignore, so the password never lands on GitHub.
//
// If this file is missing, copy config.example.php to config.php and fill in
// your own details. Without it every page stops with a "Failed to open stream"
// error, because includes/functions.php needs BASE_URL from here.
//
// This file ends without a closing PHP tag on purpose. A space or empty line
// after such a tag would be sent to the browser, and then header() redirects
// (login, logout) stop working.
// Careful: never write those two characters in a // comment either, because PHP
// reads them as the real end of the code. Use a block comment if you must.

// --- Local XAMPP settings ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'ontkoking');
define('DB_USER', 'root');
define('DB_PASS', '');

// The folder the website runs in, with a slash at the start and the end.
// Locally that is /stopppp/ because of the junction in htdocs.
// On Plesk the site is in the root of the domain, so there it becomes /
define('BASE_URL', '/stopppp/');
