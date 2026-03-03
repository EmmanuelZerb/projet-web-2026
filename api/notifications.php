<?php
/**
 * ECE In - API AJAX : Notifications
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Marquer toutes comme lues
if ($action === 'marquer_lu_tout') {
    $pdo->prepare("UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?")
        ->execute([$userId]);
    echo json_encode(['succes' => true]);
    exit();
}

// Récupérer le nombre non lu (pour le badge)
if ($action === 'compter') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0");
    $stmt->execute([$userId]);
    echo json_encode(['succes' => true, 'count' => (int) $stmt->fetchColumn()]);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
