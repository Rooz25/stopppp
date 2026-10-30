<?php
// Database connection with PDO.
// Every page that needs data starts with: require __DIR__ . '/includes/db.php';
// After that the variable $pdo is available.

require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            // Report mistakes as an exception instead of silently continuing.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Give every row back as an array with column names as keys.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Use real prepared statements: the database handles the values,
            // PHP does not glue them into the query. This is what stops SQL injection.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $fout) {
    // The visitor gets a short message. The technical details only go to the
    // Apache error log, because an error message can give away information
    // about the database that an attacker could use.
    error_log('Database connection failed: ' . $fout->getMessage());

    http_response_code(500);
    exit('De website kan de database nu niet bereiken. Probeer het later opnieuw.');
}
