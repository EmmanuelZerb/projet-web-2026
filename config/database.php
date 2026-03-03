<?php
/**
 * ECE In - Configuration de la base de données
 * Connexion MySQL via PDO
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ecein');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO de connexion à la base de données
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, ne pas afficher le message d'erreur complet
            die('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.');
        }
    }

    return $pdo;
}
