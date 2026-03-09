<?php
/**
 * ECE In - Configuration de la base de données
 * Connexion MySQL via PDO
 * Compatible Railway + local
 */

function _env(string $key, string $default = ''): string {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
}

$dbHost = 'localhost';
$dbPort = '3306';
$dbName = 'ecein';
$dbUser = 'root';
$dbPass = 'root';

$mysqlUrl = _env('MYSQL_URL', _env('MYSQL_PUBLIC_URL', _env('DATABASE_URL')));

if (!empty($mysqlUrl)) {
    $parsed = parse_url($mysqlUrl);
    $dbHost = $parsed['host'] ?? $dbHost;
    $dbPort = (string) ($parsed['port'] ?? $dbPort);
    $dbUser = $parsed['user'] ?? $dbUser;
    $dbPass = $parsed['pass'] ?? $dbPass;
    $dbName = ltrim($parsed['path'] ?? '/' . $dbName, '/');
} else {
    $dbHost = _env('MYSQLHOST', $dbHost);
    $dbPort = _env('MYSQLPORT', $dbPort);
    $dbName = _env('MYSQLDATABASE', $dbName);
    $dbUser = _env('MYSQLUSER', $dbUser);
    $dbPass = _env('MYSQLPASSWORD', $dbPass);
}

define('DB_HOST',    $dbHost);
define('DB_PORT',    $dbPort);
define('DB_NAME',    $dbName);
define('DB_USER',    $dbUser);
define('DB_PASS',    $dbPass);
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO de connexion à la base de données
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
            die('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur. [' . $e->getMessage() . ']');
        }
    }

    return $pdo;
}
