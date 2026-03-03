<?php
/**
 * ECE In - Import CV depuis un fichier XML
 * Parse le XML et met à jour les données de l'utilisateur
 */
require_once __DIR__ . '/../config/config.php';
requireConnexion();

$pdo    = getDB();
$userId = $_SESSION['utilisateur_id'];

if (!isset($_FILES['cv_xml']) || $_FILES['cv_xml']['error'] !== 0) {
    redirect('../profil.php?onglet=cv&erreur=upload');
}

$file = $_FILES['cv_xml'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xml']) || $file['size'] > MAX_CV_SIZE) {
    redirect('../profil.php?onglet=cv&erreur=format');
}

try {
    $xml = simplexml_load_file($file['tmp_name']);
    if (!$xml) throw new Exception('Fichier XML invalide.');

    // Mettre à jour les infos personnelles
    $infos = $xml->informations_personnelles;
    if ($infos) {
        $updates = [];
        $params  = [];

        if (!empty((string)$infos->titre)) {
            $updates[] = 'titre = ?';
            $params[]  = (string)$infos->titre;
        }
        if (!empty((string)$infos->biographie)) {
            $updates[] = 'biographie = ?';
            $params[]  = (string)$infos->biographie;
        }
        if (!empty((string)$infos->localisation)) {
            $updates[] = 'localisation = ?';
            $params[]  = (string)$infos->localisation;
        }
        if (!empty((string)$infos->telephone)) {
            $updates[] = 'telephone = ?';
            $params[]  = (string)$infos->telephone;
        }
        if (!empty((string)$infos->site_web)) {
            $updates[] = 'site_web = ?';
            $params[]  = (string)$infos->site_web;
        }

        if (!empty($updates)) {
            $params[] = $userId;
            $pdo->prepare("UPDATE utilisateurs SET " . implode(', ', $updates) . " WHERE id = ?")
                ->execute($params);
        }
    }

    // Importer les formations
    if (isset($xml->formations->formation)) {
        foreach ($xml->formations->formation as $f) {
            $etablissement = (string)$f->etablissement;
            $diplome       = (string)$f->diplome;
            if (empty($etablissement) || empty($diplome)) continue;

            // Vérifier si la formation existe déjà
            $stmtCheck = $pdo->prepare("
                SELECT id FROM formations
                WHERE utilisateur_id = ? AND etablissement = ? AND diplome = ?
            ");
            $stmtCheck->execute([$userId, $etablissement, $diplome]);
            if ($stmtCheck->fetch()) continue; // Ne pas dupliquer

            $pdo->prepare("
                INSERT INTO formations (utilisateur_id, etablissement, diplome, domaine, date_debut, date_fin, en_cours, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $userId,
                $etablissement,
                $diplome,
                (string)($f->domaine ?? ''),
                !empty((string)$f->date_debut) ? (string)$f->date_debut : null,
                !empty((string)$f->date_fin)   ? (string)$f->date_fin   : null,
                (string)$f->en_cours === 'true' ? 1 : 0,
                (string)($f->description ?? ''),
            ]);
        }
    }

    // Importer les projets
    if (isset($xml->projets->projet)) {
        foreach ($xml->projets->projet as $p) {
            $titre = (string)$p->titre;
            if (empty($titre)) continue;

            $stmtCheck = $pdo->prepare("SELECT id FROM projets WHERE utilisateur_id = ? AND titre = ?");
            $stmtCheck->execute([$userId, $titre]);
            if ($stmtCheck->fetch()) continue;

            $pdo->prepare("
                INSERT INTO projets (utilisateur_id, titre, description, type, lien)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $userId,
                $titre,
                (string)($p->description ?? ''),
                (string)($p->type ?? ''),
                (string)($p->lien ?? ''),
            ]);
        }
    }

    redirect('../profil.php?onglet=cv&succes=import');

} catch (Exception $e) {
    redirect('../profil.php?onglet=cv&erreur=parse');
}
