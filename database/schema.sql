-- =====================================================
-- ECE In - Réseau Social Professionnel ECE Paris
-- Schéma de la base de données
-- =====================================================

CREATE DATABASE IF NOT EXISTS ecein CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecein;

-- =====================================================
-- Table : utilisateurs
-- =====================================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role ENUM('auteur', 'administrateur') NOT NULL DEFAULT 'auteur',
    photo VARCHAR(255) DEFAULT 'assets/images/default_avatar.png',
    image_fond VARCHAR(255) DEFAULT 'assets/images/default_bg.jpg',
    titre VARCHAR(200) DEFAULT NULL,
    biographie TEXT DEFAULT NULL,
    localisation VARCHAR(200) DEFAULT NULL,
    site_web VARCHAR(255) DEFAULT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    date_naissance DATE DEFAULT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_pseudo (pseudo)
) ENGINE=InnoDB;

-- =====================================================
-- Table : formations
-- =====================================================
CREATE TABLE IF NOT EXISTS formations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    etablissement VARCHAR(200) NOT NULL,
    diplome VARCHAR(200) NOT NULL,
    domaine VARCHAR(200) DEFAULT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    en_cours TINYINT(1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    lieu VARCHAR(200) DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : projets
-- =====================================================
CREATE TABLE IF NOT EXISTS projets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    date_debut DATE DEFAULT NULL,
    date_fin DATE DEFAULT NULL,
    en_cours TINYINT(1) DEFAULT 0,
    lien VARCHAR(255) DEFAULT NULL,
    type VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : publications (posts)
-- =====================================================
CREATE TABLE IF NOT EXISTS publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    type ENUM('statut', 'photo', 'video', 'cv', 'evenement', 'partage') NOT NULL DEFAULT 'statut',
    contenu TEXT DEFAULT NULL,
    fichier VARCHAR(255) DEFAULT NULL,
    lieu VARCHAR(200) DEFAULT NULL,
    humeur VARCHAR(100) DEFAULT NULL,
    visibilite ENUM('public', 'amis', 'prive') NOT NULL DEFAULT 'public',
    publication_originale_id INT DEFAULT NULL,
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (publication_originale_id) REFERENCES publications(id) ON DELETE SET NULL,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_date (date_publication)
) ENGINE=InnoDB;

-- =====================================================
-- Table : reactions
-- =====================================================
CREATE TABLE IF NOT EXISTS reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    type ENUM('jaime', 'bravo', 'soutien', 'interessant') NOT NULL DEFAULT 'jaime',
    date_reaction DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (publication_id, utilisateur_id),
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table : commentaires
-- =====================================================
CREATE TABLE IF NOT EXISTS commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_publication (publication_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : connexions (amis)
-- =====================================================
CREATE TABLE IF NOT EXISTS connexions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demandeur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    statut ENUM('en_attente', 'accepte', 'refuse') NOT NULL DEFAULT 'en_attente',
    date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_reponse DATETIME DEFAULT NULL,
    UNIQUE KEY unique_connexion (demandeur_id, destinataire_id),
    FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_demandeur (demandeur_id),
    INDEX idx_destinataire (destinataire_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : conversations
-- =====================================================
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) DEFAULT NULL,
    est_groupe TINYINT(1) DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table : conversation_participants
-- =====================================================
CREATE TABLE IF NOT EXISTS conversation_participants (
    conversation_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversation_id, utilisateur_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table : messages
-- =====================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    expediteur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_expediteur (expediteur_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    expediteur_id INT DEFAULT NULL,
    type ENUM('demande_ami', 'ami_accepte', 'reaction', 'commentaire', 'partage', 'evenement', 'emploi', 'systeme') NOT NULL,
    message TEXT NOT NULL,
    lien VARCHAR(255) DEFAULT NULL,
    lue TINYINT(1) DEFAULT 0,
    date_notification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table : emplois
-- =====================================================
CREATE TABLE IF NOT EXISTS emplois (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publie_par INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    entreprise VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('cdi', 'cdd', 'stage', 'alternance', 'freelance', 'benevole') NOT NULL,
    lieu VARCHAR(200) DEFAULT NULL,
    salaire VARCHAR(100) DEFAULT NULL,
    date_debut DATE DEFAULT NULL,
    date_limite DATE DEFAULT NULL,
    lien_candidature VARCHAR(255) DEFAULT NULL,
    contact_email VARCHAR(100) DEFAULT NULL,
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (publie_par) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_date (date_publication)
) ENGINE=InnoDB;

-- =====================================================
-- Table : evenements
-- =====================================================
CREATE TABLE IF NOT EXISTS evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisateur_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    lieu VARCHAR(200) DEFAULT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    lien VARCHAR(255) DEFAULT NULL,
    est_officiel TINYINT(1) DEFAULT 0,
    date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_date (date_debut)
) ENGINE=InnoDB;

-- =====================================================
-- Table : albums
-- =====================================================
CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    couverture VARCHAR(255) DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table : album_photos
-- =====================================================
CREATE TABLE IF NOT EXISTS album_photos (
    album_id INT NOT NULL,
    publication_id INT NOT NULL,
    PRIMARY KEY (album_id, publication_id),
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Données de test (seed)
-- =====================================================

-- Administrateur par défaut (mot de passe : Admin@ECE2026)
INSERT INTO utilisateurs (pseudo, email, mot_de_passe, nom, prenom, role, titre, biographie) VALUES
('admin', 'admin@ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'ECE In', 'administrateur', 'Administrateur ECE In', 'Compte administrateur du réseau social ECE In.'),
('jdupont', 'jean.dupont@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont', 'Jean', 'auteur', 'Étudiant ING2 - Génie Informatique', 'Passionné de développement web et d''intelligence artificielle.'),
('mmartin', 'marie.martin@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin', 'Marie', 'auteur', 'Étudiante ING3 - Finance & Technologie', 'Étudiante en apprentissage chez BNP Paribas, passionnée de fintech.'),
('lpetit', 'lucas.petit@edu.ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petit', 'Lucas', 'auteur', 'Étudiant ING1 - Systèmes Embarqués', 'Fan de robotique et de systèmes embarqués.'),
('sleblanc', 'sophie.leblanc@ece.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leblanc', 'Sophie', 'auteur', 'Enseignante - Mathématiques Appliquées', 'Professeur à l''ECE Paris, chercheuse en optimisation.');

-- Connexions (amitiés)
INSERT INTO connexions (demandeur_id, destinataire_id, statut, date_reponse) VALUES
(2, 3, 'accepte', NOW()),
(2, 4, 'accepte', NOW()),
(3, 5, 'accepte', NOW()),
(4, 5, 'en_attente', NULL);

-- Formations
INSERT INTO formations (utilisateur_id, etablissement, diplome, domaine, date_debut, date_fin, en_cours, description) VALUES
(2, 'ECE Paris', 'Diplôme d''ingénieur', 'Informatique et Systèmes', '2022-09-01', NULL, 1, 'Formation d''ingénieur en 5 ans, spécialité Génie Informatique'),
(2, 'Lycée Louis le Grand', 'Baccalauréat Scientifique', 'Mathématiques', '2019-09-01', '2022-06-30', 0, 'Bac S mention Très Bien'),
(3, 'ECE Paris', 'Diplôme d''ingénieur', 'Finance & Technologie', '2021-09-01', NULL, 1, 'Formation en apprentissage chez BNP Paribas'),
(5, 'Université Paris-Saclay', 'Doctorat', 'Mathématiques Appliquées', '2015-09-01', '2019-06-30', 0, 'Thèse sur l''optimisation convexe');

-- Publications
INSERT INTO publications (utilisateur_id, type, contenu, visibilite) VALUES
(2, 'statut', 'Très content d''avoir terminé mon projet de machine learning ! L''algorithme atteint 95% de précision sur le jeu de données de test. 🎉', 'public'),
(3, 'statut', 'Première semaine d''alternance chez BNP Paribas terminée ! L''équipe est super et les projets sont vraiment stimulants. #FinTech #Alternance', 'public'),
(5, 'statut', 'Conférence sur l''optimisation convexe à l''École Polytechnique la semaine prochaine. Inscriptions ouvertes !', 'public'),
(2, 'statut', 'Qui serait intéressé par un groupe d''étude pour les examens de fin de semestre ?', 'amis');

-- Réactions
INSERT INTO reactions (publication_id, utilisateur_id, type) VALUES
(1, 3, 'bravo'),
(1, 5, 'interessant'),
(2, 2, 'jaime'),
(3, 2, 'interessant'),
(3, 3, 'interessant');

-- Commentaires
INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES
(1, 3, 'Félicitations Jean ! C''est un excellent résultat. Quel algorithme as-tu utilisé ?'),
(1, 5, 'Très bien ! Tu devrais penser à publier ces résultats.'),
(2, 2, 'Super Marie ! Tu vas adorer l''expérience en entreprise !');

-- Événements officiels
INSERT INTO evenements (organisateur_id, titre, description, lieu, date_debut, date_fin, est_officiel) VALUES
(1, 'Portes Ouvertes ECE Paris 2026', 'Venez découvrir l''ECE Paris ! Visite des locaux, rencontres avec les étudiants et les enseignants, présentation des formations.', 'ECE Paris - 37 Quai de Grenelle, 75015 Paris', '2026-03-14 10:00:00', '2026-03-14 17:00:00', 1),
(1, 'Forum Emploi ECE 2026', 'Forum annuel emploi et alternance. Plus de 50 entreprises partenaires présentes pour rencontrer les étudiants ECE.', 'ECE Paris - Grand Amphithéâtre', '2026-03-21 09:00:00', '2026-03-21 18:00:00', 1),
(5, 'Séminaire : Intelligence Artificielle et Éthique', 'Conférence sur les enjeux éthiques de l''IA animée par Dr. Sophie Leblanc.', 'ECE Paris - Salle de conférence A', '2026-03-10 14:00:00', '2026-03-10 17:00:00', 0);

-- Emplois
INSERT INTO emplois (publie_par, titre, entreprise, description, type, lieu, salaire, date_debut, date_limite, contact_email) VALUES
(1, 'Développeur Full-Stack (Stage)', 'Startup TechParis', 'Nous recherchons un stagiaire développeur full-stack pour rejoindre notre équipe. Stack : React, Node.js, MongoDB. Durée : 6 mois.', 'stage', 'Paris 10e', '1200€/mois', '2026-04-01', '2026-03-20', 'rh@techparis.fr'),
(1, 'Ingénieur Data Science (Alternance)', 'BNP Paribas', 'Rejoignez l''équipe Data Science de BNP Paribas en alternance. Python, R, ML requis.', 'alternance', 'Paris 9e', '1500€/mois', '2026-09-01', '2026-06-30', 'alternance@bnpparibas.com'),
(5, 'Enseignant Vacataire - Mathématiques', 'ECE Paris', 'L''ECE Paris recrute des enseignants vacataires en mathématiques pour l''année 2026-2027.', 'cdd', 'Paris 15e', 'Selon convention', '2026-09-01', '2026-05-31', 'rh@ece.fr');

-- Notifications
INSERT INTO notifications (utilisateur_id, expediteur_id, type, message, lien) VALUES
(2, 3, 'reaction', 'Marie Martin a réagi à votre publication.', 'index.php#post-1'),
(2, 5, 'reaction', 'Sophie Leblanc a réagi à votre publication.', 'index.php#post-1'),
(3, 2, 'commentaire', 'Jean Dupont a commenté votre publication.', 'index.php#post-2'),
(2, 3, 'ami_accepte', 'Marie Martin a accepté votre demande de connexion.', 'reseau.php');
