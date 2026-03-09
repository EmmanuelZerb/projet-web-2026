<?php
/**
 * ECE In - Configuration de la base de données
 * Connexion MySQL via PDO
 * Supporte les variables d'environnement (Railway/cloud) avec fallback local
 */

function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

define('DB_HOST',    env('MYSQLHOST',     env('DB_HOST',    'localhost')));
define('DB_PORT',    env('MYSQLPORT',     env('DB_PORT',    '3306')));
define('DB_NAME',    env('MYSQLDATABASE', env('DB_NAME',    'ecein')));
define('DB_USER',    env('MYSQLUSER',     env('DB_USER',    'root')));
define('DB_PASS',    env('MYSQLPASSWORD', env('DB_PASS',    'root')));
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO de connexion à la base de données
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.');
        }
    }

    return $pdo;
}
