<?php
/**
 * ECE In - API AJAX : Gestion des appels audio/vidéo
 * La signalisation passe par le serveur, le média par WebRTC (P2P).
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// L'appelant crée un enregistrement en BDD, le receveur le détecte via polling
// ===================== INITIER UN APPEL =====================
if ($action === 'initier') {
    $convId     = (int) ($_POST['conversation_id'] ?? 0);
    $receveurId = (int) ($_POST['receveur_id'] ?? 0);
    $type       = in_array($_POST['type'] ?? '', ['audio', 'video']) ? $_POST['type'] : 'audio';

    if (!$convId || !$receveurId) {
        echo json_encode(['succes' => false, 'message' => 'Paramètres manquants.']);
        exit;
    }

    // Annuler les appels en sonnerie existants (des deux côtés)
    $pdo->prepare("UPDATE appels SET statut = 'manque', date_fin = NOW() WHERE appelant_id = ? AND statut = 'sonnerie'")
        ->execute([$userId]);
    $pdo->prepare("UPDATE appels SET statut = 'manque', date_fin = NOW() WHERE receveur_id = ? AND statut = 'sonnerie' AND date_debut < DATE_SUB(NOW(), INTERVAL 45 SECOND)")
        ->execute([$receveurId]);

    $stmt = $pdo->prepare("INSERT INTO appels (appelant_id, receveur_id, conversation_id, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $receveurId, $convId, $type]);

    echo json_encode(['succes' => true, 'appel_id' => (int) $pdo->lastInsertId()]);
    exit;
}

// Le receveur poll cette route toutes les secondes pour détecter un appel entrant
// ===================== VÉRIFIER LES APPELS ENTRANTS =====================
if ($action === 'verifier') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom, u.nom, u.photo
        FROM appels a
        JOIN utilisateurs u ON u.id = a.appelant_id
        WHERE a.receveur_id = ? AND a.statut = 'sonnerie'
          AND a.date_debut > DATE_SUB(NOW(), INTERVAL 35 SECOND)
        ORDER BY a.date_debut DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $appel = $stmt->fetch();

    echo json_encode(['succes' => true, 'appel' => $appel ?: null]);
    exit;
}

// ===================== STATUT D'UN APPEL =====================
if ($action === 'statut') {
    $appelId = (int) ($_GET['appel_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT statut FROM appels WHERE id = ? AND (appelant_id = ? OR receveur_id = ?)");
    $stmt->execute([$appelId, $userId, $userId]);
    $appel = $stmt->fetch();

    echo json_encode(['succes' => true, 'statut' => $appel ? $appel['statut'] : 'termine']);
    exit;
}

// Quand le receveur accepte, le statut passe à "en_cours" et l'appelant le détecte via son propre polling
// ===================== ACCEPTER =====================
if ($action === 'accepter') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'en_cours' WHERE id = ? AND receveur_id = ? AND statut = 'sonnerie'")
        ->execute([$appelId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

// ===================== REFUSER =====================
if ($action === 'refuser') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'refuse', date_fin = NOW() WHERE id = ? AND receveur_id = ? AND statut = 'sonnerie'")
        ->execute([$appelId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

// ===================== TERMINER =====================
if ($action === 'terminer') {
    $appelId = (int) ($_POST['appel_id'] ?? 0);
    $pdo->prepare("UPDATE appels SET statut = 'termine', date_fin = NOW() WHERE id = ? AND (appelant_id = ? OR receveur_id = ?) AND statut IN ('sonnerie', 'en_cours')")
        ->execute([$appelId, $userId, $userId]);

    echo json_encode(['succes' => true]);
    exit;
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
