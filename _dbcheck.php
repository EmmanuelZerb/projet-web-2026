<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/config.php';

echo "=== DB CHECK ===\n\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DEFAULT_AVATAR: " . DEFAULT_AVATAR . "\n\n";

try {
    $pdo = getDB();
    echo "CONNEXION OK\n\n";

    $stmt = $pdo->query("SELECT id, pseudo, prenom, nom, photo, image_fond FROM utilisateurs ORDER BY id");
    $users = $stmt->fetchAll();
    echo "=== UTILISATEURS (" . count($users) . ") ===\n";
    foreach ($users as $u) {
        $photoStatus = '(NULL)';
        if ($u['photo'] !== null) {
            $photoStatus = $u['photo'];
            if (file_exists(__DIR__ . '/' . $u['photo'])) {
                $photoStatus .= ' [FICHIER OK]';
            } else {
                $photoStatus .= ' [FICHIER MANQUANT]';
            }
        }
        echo "#{$u['id']} {$u['pseudo']} ({$u['prenom']} {$u['nom']})\n";
        echo "  photo: $photoStatus\n";
        echo "  fond:  " . ($u['image_fond'] ?? '(NULL)') . "\n";
    }

    echo "\n=== TABLES ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
