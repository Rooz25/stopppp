<?php
// EXAMPLE FILE - this one may be on GitHub because it holds no real password.
//
// How to use it:
//   1. Make a copy of this file in the same folder.
//   2. Name the copy config.php
//   3. Fill in your own details below.
//
// config.php is in .gitignore, so your password never ends up on GitHub.
//
// Local XAMPP: DB_USER is usually 'root' and DB_PASS is empty.
// On Plesk:    use the database name, user and password from your Plesk panel.
//              Note that the database there has a different name.
//
// This file ends without a closing PHP tag on purpose, and never write those
// two characters in a // comment: PHP reads them as the real end of the code.

define('DB_HOST', 'localhost');
define('DB_NAME', 'ontkoking');
define('DB_USER', 'jouw_gebruikersnaam');
define('DB_PASS', 'jouw_wachtwoord');

// The folder the website runs in, with a slash at the start and the end.
// In a subfolder of htdocs this becomes for example /stopppp/
// In the root of a domain (Plesk) it stays just /
define('BASE_URL', '/');
