<?php
header('Content-Type: text/plain; charset=utf-8');
echo "DIAG OK - PAGE ATTEINTE\n\n";

echo "PHP version: " . phpversion() . "\n";
echo "Extensions PDO: " . (extension_loaded('pdo_mysql') ? 'OUI' : 'NON') . "\n\n";

$vars = ['MYSQL_URL','MYSQL_PUBLIC_URL','MYSQLHOST','MYSQLPORT','MYSQLUSER','MYSQLPASSWORD','MYSQLDATABASE','DATABASE_URL','PORT'];
foreach ($vars as $v) {
    $val = getenv($v);
    if ($val === false) $val = '(NON DEFINIE)';
    elseif (strpos($v, 'PASS') !== false || strpos($v, 'URL') !== false)
        $val = substr($val, 0, 20) . '...';
    echo "$v = $val\n";
}

echo "\n--- Toutes les env avec MYSQL ---\n";
foreach (getenv() as $k => $v) {
    if (stripos($k, 'mysql') !== false || stripos($k, 'database') !== false || stripos($k, 'db_') !== false) {
        if (stripos($k, 'pass') !== false || stripos($k, 'url') !== false)
            $v = substr($v, 0, 20) . '...';
        echo "$k = $v\n";
    }
}

echo "\n--- Test connexion ---\n";
$url = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL') ?: '';
if (empty($url)) {
    echo "Aucune URL de connexion trouvee.\n";
    $h = getenv('MYSQLHOST') ?: 'introuvable';
    $p = getenv('MYSQLPORT') ?: 'introuvable';
    echo "MYSQLHOST=$h MYSQLPORT=$p\n";
} else {
    echo "URL trouvee (debut): " . substr($url, 0, 30) . "...\n";
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '?';
    $port = $parsed['port'] ?? 3306;
    $user = $parsed['user'] ?? '?';
    $pass = $parsed['pass'] ?? '';
    $db   = ltrim($parsed['path'] ?? '/test', '/');
    echo "Host=$host Port=$port User=$user DB=$db\n";
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        echo "CONNEXION REUSSIE!\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    } catch (PDOException $e) {
        echo "ERREUR: " . $e->getMessage() . "\n";
    }
}
