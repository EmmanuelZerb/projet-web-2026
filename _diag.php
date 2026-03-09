<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC RAILWAY ===\n\n";

echo "--- Variables d'environnement MySQL ---\n";
$vars = ['MYSQL_URL', 'MYSQL_PUBLIC_URL', 'MYSQLHOST', 'MYSQLPORT', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLDATABASE', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];

foreach ($vars as $v) {
    $val_env    = $_ENV[$v] ?? '(vide)';
    $val_server = $_SERVER[$v] ?? '(vide)';
    $val_getenv = getenv($v) ?: '(vide)';

    if ($v === 'MYSQLPASSWORD' || $v === 'DB_PASS' || $v === 'MYSQL_URL' || $v === 'MYSQL_PUBLIC_URL') {
        $val_env    = ($val_env !== '(vide)') ? substr($val_env, 0, 15) . '...' : '(vide)';
        $val_server = ($val_server !== '(vide)') ? substr($val_server, 0, 15) . '...' : '(vide)';
        $val_getenv = ($val_getenv !== '(vide)') ? substr($val_getenv, 0, 15) . '...' : '(vide)';
    }

    echo "$v:\n";
    echo "  \$_ENV    = $val_env\n";
    echo "  \$_SERVER = $val_server\n";
    echo "  getenv() = $val_getenv\n";
}

echo "\n--- Test connexion PDO ---\n";
require_once __DIR__ . '/config/database.php';

echo "DB_HOST = " . DB_HOST . "\n";
echo "DB_PORT = " . DB_PORT . "\n";
echo "DB_NAME = " . DB_NAME . "\n";
echo "DB_USER = " . DB_USER . "\n";
echo "DB_PASS = " . (strlen(DB_PASS) > 0 ? substr(DB_PASS, 0, 4) . '***' : '(vide)') . "\n";

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
echo "DSN = $dsn\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "CONNEXION OK!\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "ERREUR CONNEXION: " . $e->getMessage() . "\n";
}
