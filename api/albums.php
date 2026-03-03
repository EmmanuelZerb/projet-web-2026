<?php
/**
 * ECE In - API : Gestion des albums photos
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? '';

if ($action === 'creer_album') {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        redirect('../profil.php?onglet=albums');
    }

    $pdo->prepare("INSERT INTO albums (utilisateur_id, nom, description) VALUES (?, ?, ?)")
        ->execute([$userId, $nom, $description]);
}

redirect('../profil.php?onglet=albums');
