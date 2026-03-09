<?php
/**
 * ECE In - API AJAX : Gestion des publications
 * Endpoints : publier, supprimer, reagir, commenter, charger_commentaires
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

header('Content-Type: application/json');

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===================== PUBLIER UN POST =====================
if ($action === 'publier') {
    $type       = $_POST['type'] ?? 'statut';
    $contenu    = trim($_POST['contenu'] ?? '');
    $visibilite = $_POST['visibilite'] ?? 'public';
    $lieu       = trim($_POST['lieu'] ?? '');
    $humeur     = trim($_POST['humeur'] ?? '');
    $fichier    = null;

    // Valider le type
    $typesValides = ['statut', 'photo', 'video', 'cv', 'evenement'];
    if (!in_array($type, $typesValides)) {
        echo json_encode(['succes' => false, 'message' => 'Type de publication invalide.']);
        exit();
    }

    if (empty($contenu) && !isset($_FILES['fichier'])) {
        echo json_encode(['succes' => false, 'message' => 'Le contenu ne peut pas être vide.']);
        exit();
    }

    $fichierData = null;
    $fichierMime = null;

    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $file = $_FILES['fichier'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($file['type'], PHOTO_TYPES) && $file['size'] <= MAX_PHOTO_SIZE) {
            $dossier = 'photos';
        } elseif (in_array($file['type'], VIDEO_TYPES) && $file['size'] <= MAX_VIDEO_SIZE) {
            $dossier = 'videos';
        } elseif (in_array($file['type'], CV_TYPES) && $file['size'] <= MAX_CV_SIZE) {
            $dossier = 'cvs';
        } else {
            echo json_encode(['succes' => false, 'message' => 'Type ou taille de fichier non autorisé.']);
            exit();
        }

        $fichierData = file_get_contents($file['tmp_name']);
        $fichierMime = $file['type'];
        $fichier = 'db_stored_' . $dossier . '/' . uniqid() . '.' . $ext;

        @mkdir(UPLOAD_PATH . $dossier, 0755, true);
        @move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $dossier . '/' . basename($fichier));
    }

    $stmt = $pdo->prepare("
        INSERT INTO publications (utilisateur_id, type, contenu, fichier, fichier_data, fichier_mime, lieu, humeur, visibilite)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $type, $contenu, $fichier, $fichierData, $fichierMime, $lieu, $humeur, $visibilite]);
    $postId = $pdo->lastInsertId();

    // Récupérer le post créé avec les infos utilisateur
    $stmtGet = $pdo->prepare("
        SELECT p.id, p.utilisateur_id, p.type, p.contenu, p.fichier, p.fichier_mime,
               p.lieu, p.humeur, p.visibilite, p.publication_originale_id, p.date_publication,
               u.nom, u.prenom, u.photo AS avatar, u.pseudo, u.titre AS user_titre
        FROM publications p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        WHERE p.id = ?
    ");
    $stmtGet->execute([$postId]);
    $post = $stmtGet->fetch();

    echo json_encode([
        'succes'  => true,
        'post_id' => $postId,
        'post'    => $post,
        'message' => 'Publication créée avec succès !',
    ]);
    exit();
}

// ===================== SUPPRIMER UN POST =====================
if ($action === 'supprimer') {
    $postId = (int) ($_POST['post_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT utilisateur_id, fichier FROM publications WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['succes' => false, 'message' => 'Publication introuvable.']);
        exit();
    }

    // Vérifier propriétaire ou admin
    if ($post['utilisateur_id'] != $userId && !estAdmin()) {
        echo json_encode(['succes' => false, 'message' => 'Accès refusé.']);
        exit();
    }

    // Supprimer le fichier associé
    if ($post['fichier'] && file_exists(BASE_PATH . '/' . $post['fichier'])) {
        unlink(BASE_PATH . '/' . $post['fichier']);
    }

    $pdo->prepare("DELETE FROM publications WHERE id = ?")->execute([$postId]);

    echo json_encode(['succes' => true, 'message' => 'Publication supprimée.']);
    exit();
}

// ===================== RÉAGIR =====================
if ($action === 'reagir') {
    $postId     = (int) ($_POST['post_id'] ?? 0);
    $typeReaction = $_POST['type'] ?? 'jaime';

    $typesReactions = ['jaime', 'bravo', 'soutien', 'interessant'];
    if (!in_array($typeReaction, $typesReactions)) {
        $typeReaction = 'jaime';
    }

    // Vérifier si déjà réagi
    $stmtCheck = $pdo->prepare("SELECT id, type FROM reactions WHERE publication_id = ? AND utilisateur_id = ?");
    $stmtCheck->execute([$postId, $userId]);
    $existante = $stmtCheck->fetch();

    if ($existante) {
        if ($existante['type'] === $typeReaction) {
            // Supprimer la réaction (toggle off)
            $pdo->prepare("DELETE FROM reactions WHERE id = ?")->execute([$existante['id']]);
            $etat = 'retire';
        } else {
            // Changer la réaction
            $pdo->prepare("UPDATE reactions SET type = ? WHERE id = ?")
                ->execute([$typeReaction, $existante['id']]);
            $etat = 'modifie';
        }
    } else {
        // Nouvelle réaction
        $pdo->prepare("INSERT INTO reactions (publication_id, utilisateur_id, type) VALUES (?, ?, ?)")
            ->execute([$postId, $userId, $typeReaction]);
        $etat = 'ajoute';

        // Notifier le propriétaire (sauf si c'est soi-même)
        $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
        $stmtOwner->execute([$postId]);
        $ownerId = $stmtOwner->fetchColumn();
        if ($ownerId && $ownerId != $userId) {
            $user = getUtilisateurConnecte();
            creerNotification(
                $ownerId, $userId, 'reaction',
                $user['prenom'] . ' ' . $user['nom'] . ' a réagi à votre publication.',
                'index.php#post-' . $postId
            );
        }
    }

    // Compter les réactions
    $nbReactions = $pdo->prepare("SELECT COUNT(*) FROM reactions WHERE publication_id = ?");
    $nbReactions->execute([$postId]);
    $count = $nbReactions->fetchColumn();

    echo json_encode([
        'succes'      => true,
        'etat'        => $etat,
        'nb_reactions' => $count,
        'message'     => 'Réaction enregistrée.',
    ]);
    exit();
}

// ===================== COMMENTER =====================
if ($action === 'commenter') {
    $postId  = (int) ($_POST['post_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if (empty($contenu)) {
        echo json_encode(['succes' => false, 'message' => 'Le commentaire ne peut pas être vide.']);
        exit();
    }

    $pdo->prepare("INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES (?, ?, ?)")
        ->execute([$postId, $userId, $contenu]);
    $commId = $pdo->lastInsertId();

    // Notifier le propriétaire
    $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
    $stmtOwner->execute([$postId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId && $ownerId != $userId) {
        $user = getUtilisateurConnecte();
        creerNotification(
            $ownerId, $userId, 'commentaire',
            $user['prenom'] . ' ' . $user['nom'] . ' a commenté votre publication.',
            'index.php#post-' . $postId
        );
    }

    // Retourner le commentaire
    $user = getUtilisateurConnecte();
    echo json_encode([
        'succes' => true,
        'commentaire' => [
            'id'               => $commId,
            'contenu'          => $contenu,
            'prenom'           => $user['prenom'],
            'nom'              => $user['nom'],
            'avatar'           => $user['photo'],
            'date_commentaire' => date('Y-m-d H:i:s'),
        ],
    ]);
    exit();
}

// ===================== CHARGER COMMENTAIRES =====================
if ($action === 'charger_commentaires') {
    $postId = (int) ($_GET['post_id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom, u.photo AS avatar
        FROM commentaires c
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        WHERE c.publication_id = ?
        ORDER BY c.date_commentaire ASC
    ");
    $stmt->execute([$postId]);
    $commentaires = $stmt->fetchAll();

    echo json_encode(['succes' => true, 'commentaires' => $commentaires]);
    exit();
}

// ===================== PARTAGER =====================
if ($action === 'partager') {
    $postId  = (int) ($_POST['post_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    $stmt = $pdo->prepare("
        INSERT INTO publications (utilisateur_id, type, contenu, visibilite, publication_originale_id)
        VALUES (?, 'partage', ?, 'public', ?)
    ");
    $stmt->execute([$userId, $contenu, $postId]);

    // Notifier le propriétaire original
    $stmtOwner = $pdo->prepare("SELECT utilisateur_id FROM publications WHERE id = ?");
    $stmtOwner->execute([$postId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId && $ownerId != $userId) {
        $user = getUtilisateurConnecte();
        creerNotification(
            $ownerId, $userId, 'partage',
            $user['prenom'] . ' ' . $user['nom'] . ' a partagé votre publication.',
            'index.php'
        );
    }

    echo json_encode(['succes' => true, 'message' => 'Publication partagée !']);
    exit();
}

echo json_encode(['succes' => false, 'message' => 'Action non reconnue.']);
