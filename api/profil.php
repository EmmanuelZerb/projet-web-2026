<?php
/**
 * ECE In - API AJAX : Actions sur le profil (suppression formations/projets)
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? '';

// Supprimer une formation
if ($action === 'supprimer_formation') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM formations WHERE id = ?");
    $stmt->execute([$id]);
    $f = $stmt->fetch();
    if (!$f || $f['utilisateur_id'] != $userId) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }
    $pdo->prepare("DELETE FROM formations WHERE id = ?")->execute([$id]);
    echo json_encode(['succes' => true]);
    exit();
}

// Supprimer un projet
if ($action === 'supprimer_projet') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT utilisateur_id FROM projets WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p || $p['utilisateur_id'] != $userId) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }
    $pdo->prepare("DELETE FROM projets WHERE id = ?")->execute([$id]);
    echo json_encode(['succes' => true]);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
