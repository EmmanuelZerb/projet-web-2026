<?php
/**
 * ECE In - API AJAX : Messagerie (envoi et récupération de messages)
 * Envoi et réception de messages en temps réel.
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== ENVOYER UN MESSAGE =====================
if ($action === 'envoyer') {
    $convId  = (int) ($_POST['conversation_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if (empty($contenu)) {
        echo json_encode(['succes' => false, 'message' => 'Message vide.']);
        exit();
    }

    // On vérifie que l'utilisateur fait partie de la conversation avant d'insérer (sécurité)
    // Vérifier participation
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }

    // Insérer le message
    $pdo->prepare("INSERT INTO messages (conversation_id, expediteur_id, contenu) VALUES (?, ?, ?)")
        ->execute([$convId, $userId, $contenu]);
    $msgId = $pdo->lastInsertId();

    // Récupérer le message créé
    $user = getUtilisateurConnecte();

    echo json_encode([
        'succes'  => true,
        'message' => [
            'id'            => $msgId,
            'contenu'       => $contenu,
            'expediteur_id' => $userId,
            'prenom'        => $user['prenom'],
            'nom'           => $user['nom'],
            'avatar'        => $user['photo'],
            'date_envoi'    => date('Y-m-d H:i:s'),
        ],
    ]);
    exit();
}

// Le client envoie l'ID du dernier message reçu, on renvoie seulement les nouveaux (optimisation)
// ===================== RÉCUPÉRER LES NOUVEAUX MESSAGES (polling) =====================
if ($action === 'polling') {
    $convId    = (int) ($_GET['conversation_id'] ?? 0);
    $dernierMsgId = (int) ($_GET['dernier_id'] ?? 0);

    if (!$convId) {
        echo json_encode(['succes' => false, 'messages' => []]);
        exit();
    }

    // Vérifier participation
    $stmtCheck = $pdo->prepare("
        SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND utilisateur_id = ?
    ");
    $stmtCheck->execute([$convId, $userId]);
    if (!$stmtCheck->fetchColumn()) {
        echo json_encode(['succes' => false, 'messages' => []]);
        exit();
    }

    // Récupérer les nouveaux messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom, u.prenom, u.photo AS avatar
        FROM messages m
        JOIN utilisateurs u ON u.id = m.expediteur_id
        WHERE m.conversation_id = ? AND m.id > ?
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$convId, $dernierMsgId]);
    $messages = $stmt->fetchAll();

    // On marque automatiquement les messages comme lus quand on les poll
    // Marquer comme lus
    $pdo->prepare("UPDATE messages SET lu = 1 WHERE conversation_id = ? AND expediteur_id != ?")
        ->execute([$convId, $userId]);

    echo json_encode(['succes' => true, 'messages' => $messages]);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
