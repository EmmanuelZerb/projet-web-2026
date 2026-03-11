<?php
/**
 * ECE In - Configuration générale du site
 *
 * Fichier de config central : toutes les pages de l'appli incluent ce fichier en premier.
 * On y définit les constantes du site, les chemins, et les fonctions utilitaires communes
 * (session, BDD, formatage, etc.). C'est le point d'entrée de la config.
 */

// --- Session et cookies sécurisés ---
// On configure les paramètres des cookies de session pour limiter les attaques :
// httponly empêche l'accès au cookie depuis du JS (XSS), samesite=Strict réduit les risques CSRF.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path'     => '/',
        'secure'   => false, // Mettre à true en production avec HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Inclusion de la configuration de base de données
require_once __DIR__ . '/database.php';

// Avatars et images par défaut
define('DEFAULT_AVATAR', 'assets/images/default_avatar.png');
define('DEFAULT_BG', 'assets/images/default_bg.jpg');

// Informations du site
define('SITE_NOM', 'ECE In');
define('SITE_SLOGAN', 'Le réseau social professionnel de la communauté ECE Paris');
define('SITE_EMAIL', 'contact@ecein.ece.fr');
define('SITE_TEL', '+33 1 44 39 06 00');
define('SITE_ADRESSE', '37 Quai de Grenelle, 75015 Paris');
define('SITE_URL', 'http://localhost/ecein');

// Chemin de base
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');

// --- Limites d'upload ---
// On limite la taille des fichiers pour éviter de saturer le serveur :
// 5 Mo pour les photos, 50 Mo pour les vidéos, 10 Mo pour les CV. Suffisant pour un usage classique.
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024);    // 5 Mo
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024);   // 50 Mo
define('MAX_CV_SIZE', 10 * 1024 * 1024);      // 10 Mo

// Types de fichiers autorisés
define('PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('CV_TYPES', ['application/pdf', 'application/xml', 'text/xml']);

/**
 * Redirige vers une page et arrête l'exécution
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function estConnecte(): bool {
    return isset($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool
 */
function estAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'administrateur';
}

/**
 * Redirige si l'utilisateur n'est pas connecté
 */
function requireConnexion(): void {
    if (!estConnecte()) {
        redirect('login.php');
    }
}

/**
 * Redirige si l'utilisateur n'est pas administrateur
 */
function requireAdmin(): void {
    requireConnexion();
    if (!estAdmin()) {
        redirect('index.php');
    }
}

/**
 * Échappe les données HTML pour prévenir les XSS
 * @param string $data
 * @return string
 */
function h(string $data): string {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Retourne un chemin photo valide, avec fallback sur l'avatar par défaut
 */
function photo(string|null $path): string {
    return (!empty($path) && $path !== 'NULL') ? $path : DEFAULT_AVATAR;
}

/**
 * Retourne un chemin image de fond valide
 */
function imageFond(string|null $path): string {
    return (!empty($path) && $path !== 'NULL') ? $path : DEFAULT_BG;
}

/**
 * Récupère les infos de l'utilisateur connecté
 * Utilise un pattern "static cache" : on ne fait la requête BDD qu'une fois par requête HTTP.
 * Si on appelle getUtilisateurConnecte() plusieurs fois dans une même page, on évite de spammer la BDD.
 *
 * @return array|null
 */
function getUtilisateurConnecte(): ?array {
    if (!estConnecte()) return null;

    static $utilisateur = null;
    if ($utilisateur === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $utilisateur = $stmt->fetch();
        if ($utilisateur) {
            if (empty($utilisateur['photo'])) {
                $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?")
                    ->execute([DEFAULT_AVATAR, $utilisateur['id']]);
                $utilisateur['photo'] = DEFAULT_AVATAR;
            }
            if (empty($utilisateur['image_fond'])) {
                $pdo->prepare("UPDATE utilisateurs SET image_fond = ? WHERE id = ?")
                    ->execute([DEFAULT_BG, $utilisateur['id']]);
                $utilisateur['image_fond'] = DEFAULT_BG;
            }
            $utilisateur['photo'] = photo($utilisateur['photo']);
            $utilisateur['image_fond'] = imageFond($utilisateur['image_fond']);
        }
    }
    return $utilisateur;
}

/**
 * Récupère le nombre de notifications non lues
 * @return int
 */
function getNbNotificationsNonLues(): int {
    if (!estConnecte()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Récupère le nombre de messages non lus
 * @return int
 */
function getNbMessagesNonLus(): int {
    if (!estConnecte()) return 0;
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT m.conversation_id)
        FROM messages m
        JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id
        WHERE cp.utilisateur_id = ? AND m.expediteur_id != ? AND m.lu = 0
    ");
    $stmt->execute([$_SESSION['utilisateur_id'], $_SESSION['utilisateur_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Vérifie si deux utilisateurs sont amis
 * La requête vérifie dans les 2 sens : soit A a demandé B, soit B a demandé A.
 * Car une connexion peut être initiée par l'un ou l'autre, on cherche dans les deux cas.
 *
 * @param int $userId1
 * @param int $userId2
 * @return string|null ('accepte', 'en_attente', null)
 */
function statutConnexion(int $userId1, int $userId2): ?string {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT statut FROM connexions
        WHERE (demandeur_id = ? AND destinataire_id = ?)
           OR (demandeur_id = ? AND destinataire_id = ?)
    ");
    $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    $res = $stmt->fetch();
    return $res ? $res['statut'] : null;
}

/**
 * Formate une date en français
 * @param string $date
 * @return string
 */
function formatDateFr(string $date): string {
    $mois = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Calcule le temps écoulé depuis une date
 * Retourne un format lisible style "il y a 3h" ou "5 min" au lieu d'afficher une date complète.
 * Plus parlant pour l'utilisateur qu'un "14/03/2026 10:42".
 *
 * @param string $date
 * @return string
 */
function tempsEcoule(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return formatDateFr($date);
}

/**
 * Crée une notification
 */
function creerNotification(int $userId, ?int $expediteurId, string $type, string $message, ?string $lien = null): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO notifications (utilisateur_id, expediteur_id, type, message, lien)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $expediteurId, $type, $message, $lien]);
}

/**
 * Détecte les jours spéciaux pour adapter le thème
 * Idée des thèmes saisonniers demandée dans le cahier des charges :
 * Saint-Valentin, Noël, Nouvel An, etc. pour personnaliser l'interface selon la date.
 *
 * @return array ['theme' => string, 'message' => string]
 */
function getJourSpecial(): array {
    $jour = (int) date('j');
    $mois = (int) date('n');

    $joursSpeciaux = [
        ['mois' => 2, 'jour' => 14, 'theme' => 'saint-valentin', 'message' => '💝 Bonne Saint-Valentin à tous !'],
        ['mois' => 5, 'jour_range' => [25, 31], 'semaine' => 0, 'theme' => 'fete-meres', 'message' => '🌸 Bonne Fête des Mères !'],
        ['mois' => 6, 'jour_range' => [15, 21], 'semaine' => 0, 'theme' => 'fete-peres', 'message' => '👔 Bonne Fête des Pères !'],
        ['mois' => 12, 'jour' => 25, 'theme' => 'noel', 'message' => '🎄 Joyeux Noël à tous !'],
        ['mois' => 12, 'jour' => 31, 'theme' => 'nouvel-an', 'message' => '🎆 Bonne Année 2027 !'],
        ['mois' => 1, 'jour' => 1, 'theme' => 'nouvel-an', 'message' => '🎉 Bonne Année !'],
    ];

    foreach ($joursSpeciaux as $special) {
        if (isset($special['jour']) && $special['mois'] === $mois && $special['jour'] === $jour) {
            return ['theme' => $special['theme'], 'message' => $special['message']];
        }
    }

    return ['theme' => '', 'message' => ''];
}
