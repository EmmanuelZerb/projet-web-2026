<?php
/**
 * ECE In - API AJAX : Gestion des connexions / amis
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? '';

// ===================== ENVOYER UNE DEMANDE =====================
if ($action === 'envoyer_demande') {
    $destinataireId = (int) ($_POST['destinataire_id'] ?? 0);

    if ($destinataireId === $userId) {
        echo json_encode(['succes' => false, 'message' => 'Vous ne pouvez pas vous connecter avec vous-même.']);
        exit();
    }

    // Vérifier que l'utilisateur existe
    $stmtUser = $pdo->prepare("SELECT id, prenom, nom FROM utilisateurs WHERE id = ? AND actif = 1");
    $stmtUser->execute([$destinataireId]);
    $destinataire = $stmtUser->fetch();

    if (!$destinataire) {
        echo json_encode(['succes' => false, 'message' => 'Utilisateur introuvable.']);
        exit();
    }

    // Vérifier si une connexion existe déjà
    $stmtCheck = $pdo->prepare("
        SELECT statut FROM connexions
        WHERE (demandeur_id = ? AND destinataire_id = ?)
           OR (demandeur_id = ? AND destinataire_id = ?)
    ");
    $stmtCheck->execute([$userId, $destinataireId, $destinataireId, $userId]);
    $existante = $stmtCheck->fetch();

    if ($existante) {
        $msg = match($existante['statut']) {
            'accepte'    => 'Vous êtes déjà connectés.',
            'en_attente' => 'Une invitation est déjà en attente.',
            'refuse'     => 'Cette invitation a été refusée.',
            default      => 'Connexion déjà existante.',
        };
        echo json_encode(['succes' => false, 'message' => $msg]);
        exit();
    }

    // Créer la demande
    $pdo->prepare("INSERT INTO connexions (demandeur_id, destinataire_id) VALUES (?, ?)")
        ->execute([$userId, $destinataireId]);

    // Notifier le destinataire
    $user = getUtilisateurConnecte();
    creerNotification(
        $destinataireId, $userId, 'demande_ami',
        $user['prenom'] . ' ' . $user['nom'] . ' souhaite se connecter avec vous.',
        'reseau.php#section-demandes'
    );

    echo json_encode([
        'succes'  => true,
        'message' => 'Invitation envoyée à ' . $destinataire['prenom'] . ' ' . $destinataire['nom'] . ' !',
    ]);
    exit();
}

// ===================== RÉPONDRE À UNE DEMANDE =====================
if ($action === 'repondre') {
    $connexionId = (int) ($_POST['connexion_id'] ?? 0);
    $reponse     = $_POST['reponse'] ?? ''; // 'accepter' ou 'refuser'

    $stmt = $pdo->prepare("
        SELECT * FROM connexions WHERE id = ? AND destinataire_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$connexionId, $userId]);
    $connexion = $stmt->fetch();

    if (!$connexion) {
        echo json_encode(['succes' => false, 'message' => 'Invitation introuvable.']);
        exit();
    }

    $statut = $reponse === 'accepter' ? 'accepte' : 'refuse';
    $pdo->prepare("UPDATE connexions SET statut = ?, date_reponse = NOW() WHERE id = ?")
        ->execute([$statut, $connexionId]);

    // Notifier le demandeur si accepté
    if ($statut === 'accepte') {
        $user = getUtilisateurConnecte();
        creerNotification(
            $connexion['demandeur_id'], $userId, 'ami_accepte',
            $user['prenom'] . ' ' . $user['nom'] . ' a accepté votre invitation.',
            'reseau.php'
        );
    }

    echo json_encode([
        'succes'  => true,
        'statut'  => $statut,
        'message' => $statut === 'accepte' ? 'Connexion acceptée !' : 'Invitation refusée.',
    ]);
    exit();
}

// ===================== ANNULER UNE DEMANDE =====================
if ($action === 'annuler') {
    $connexionId = (int) ($_POST['connexion_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id FROM connexions WHERE id = ? AND demandeur_id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$connexionId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['succes' => false, 'message' => 'Invitation introuvable.']);
        exit();
    }

    $pdo->prepare("DELETE FROM connexions WHERE id = ?")->execute([$connexionId]);
    echo json_encode(['succes' => true, 'message' => 'Invitation annulée.']);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
