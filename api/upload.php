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
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nom  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . 'avatars/' . $nom;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $chemin = 'uploads/avatars/' . $nom;
            $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")->execute([$chemin, $userId]);
            echo json_encode(['succes' => true, 'chemin' => $chemin]);
            exit();
        }
    }
    echo json_encode(['succes' => false, 'message' => 'Erreur lors du téléversement.']);
    exit();
}

// Upload image de fond
if ($action === 'upload_bg' && isset($_FILES['background'])) {
    $file = $_FILES['background'];
    if ($file['error'] === 0 && in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nom  = 'bg_' . $userId . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . 'backgrounds/' . $nom;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $chemin = 'uploads/backgrounds/' . $nom;
            $pdo->prepare("UPDATE utilisateurs SET image_fond = ? WHERE id = ?")->execute([$chemin, $userId]);
            echo json_encode(['succes' => true, 'chemin' => $chemin]);
            exit();
        }
    }
    echo json_encode(['succes' => false, 'message' => 'Erreur lors du téléversement.']);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
