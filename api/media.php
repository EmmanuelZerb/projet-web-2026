<?php
/**
 * ECE In - Serveur de médias depuis la base de données
 * Sert les fichiers stockés en LONGBLOB (publications, avatars, backgrounds)
 * Usage : api/media.php?type=post&id=123
 *         api/media.php?type=avatar&id=5
 *         api/media.php?type=bg&id=5
 */
require_once __DIR__ . '/../config/database.php';

$type = $_GET['type'] ?? '';
$id   = (int) ($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['post', 'avatar', 'bg'])) {
    http_response_code(404);
    exit;
}

$pdo = getDB();

switch ($type) {
    case 'post':
        $stmt = $pdo->prepare("SELECT fichier_data, fichier_mime FROM publications WHERE id = ? AND fichier_data IS NOT NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $data = $row['fichier_data'] ?? null;
        $mime = $row['fichier_mime'] ?? 'application/octet-stream';
        break;

    case 'avatar':
        $stmt = $pdo->prepare("SELECT photo_data, photo_mime FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $data = $row['photo_data'] ?? null;
        $mime = $row['photo_mime'] ?? 'image/jpeg';
        break;

    case 'bg':
        $stmt = $pdo->prepare("SELECT bg_data, bg_mime FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $data = $row['bg_data'] ?? null;
        $mime = $row['bg_mime'] ?? 'image/jpeg';
        break;
}

if (empty($data)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($data));
header('Cache-Control: public, max-age=86400');
echo $data;
