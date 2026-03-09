<?php
/**
 * ECE In - API Upload de fichiers (avatar, image de fond)
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? '';

// Upload avatar
if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        $data = file_get_contents($file['tmp_name']);
        $mime = $file['type'];
        $chemin = 'api/media.php?type=avatar&id=' . $userId;

        $pdo->prepare("UPDATE utilisateurs SET photo = ?, photo_data = ?, photo_mime = ? WHERE id = ?")
            ->execute([$chemin, $data, $mime, $userId]);

        @mkdir(UPLOAD_PATH . 'avatars', 0755, true);
        @move_uploaded_file($file['tmp_name'], UPLOAD_PATH . 'avatars/avatar_' . $userId . '.jpg');

        echo json_encode(['succes' => true, 'chemin' => $chemin]);
        exit();
    }
    echo json_encode(['succes' => false, 'message' => 'Erreur lors du téléversement.']);
    exit();
}

// Upload image de fond
if ($action === 'upload_bg' && isset($_FILES['background'])) {
    $file = $_FILES['background'];
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        $data = file_get_contents($file['tmp_name']);
        $mime = $file['type'];
        $chemin = 'api/media.php?type=bg&id=' . $userId;

        $pdo->prepare("UPDATE utilisateurs SET image_fond = ?, bg_data = ?, bg_mime = ? WHERE id = ?")
            ->execute([$chemin, $data, $mime, $userId]);

        @mkdir(UPLOAD_PATH . 'backgrounds', 0755, true);
        @move_uploaded_file($file['tmp_name'], UPLOAD_PATH . 'backgrounds/bg_' . $userId . '.jpg');

        echo json_encode(['succes' => true, 'chemin' => $chemin]);
        exit();
    }
    echo json_encode(['succes' => false, 'message' => 'Erreur lors du téléversement.']);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
