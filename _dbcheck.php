<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config/config.php';

$pdo = getDB();

$stmt = $pdo->prepare("
    UPDATE utilisateurs 
    SET photo = ? 
    WHERE photo IS NOT NULL 
      AND photo != '' 
      AND photo NOT LIKE 'assets/%'
      AND photo NOT IN (SELECT photo FROM (
          SELECT photo FROM utilisateurs WHERE photo LIKE 'uploads/%'
      ) AS sub)
");

$fixed = $pdo->prepare("
    UPDATE utilisateurs 
    SET photo = :default_photo
    WHERE photo LIKE 'uploads/%' 
      AND id IN (
          SELECT id FROM (SELECT id, photo FROM utilisateurs) AS sub
      )
");

$all = $pdo->query("SELECT id, photo FROM utilisateurs")->fetchAll();
$count = 0;
foreach ($all as $u) {
    if (!empty($u['photo']) && !file_exists(__DIR__ . '/' . $u['photo'])) {
        $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")
            ->execute([DEFAULT_AVATAR, $u['id']]);
        $count++;
        echo "Fixed user #{$u['id']}: {$u['photo']} -> " . DEFAULT_AVATAR . "\n";
    }
}

$allBg = $pdo->query("SELECT id, image_fond FROM utilisateurs")->fetchAll();
foreach ($allBg as $u) {
    if (!empty($u['image_fond']) && !file_exists(__DIR__ . '/' . $u['image_fond'])) {
        $pdo->prepare("UPDATE utilisateurs SET image_fond = ? WHERE id = ?")
            ->execute([DEFAULT_BG, $u['id']]);
        $count++;
        echo "Fixed user #{$u['id']} bg: {$u['image_fond']} -> " . DEFAULT_BG . "\n";
    }
}

echo "\nTotal fixed: $count\n";
echo "\n=== VERIFICATION ===\n";
$users = $pdo->query("SELECT id, pseudo, photo, image_fond FROM utilisateurs ORDER BY id")->fetchAll();
foreach ($users as $u) {
    $ok = file_exists(__DIR__ . '/' . $u['photo']) ? 'OK' : 'MANQUANT';
    echo "#{$u['id']} {$u['pseudo']}: photo={$u['photo']} [$ok]\n";
}
