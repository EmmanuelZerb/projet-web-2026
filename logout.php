<?php
/**
 * ECE In - Déconnexion
 */
require_once __DIR__ . '/config/config.php';

// Détruire la session
session_unset();
session_destroy();

// Rediriger vers la page de connexion
redirect('login.php');
