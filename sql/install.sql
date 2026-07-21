-- Supprimer l'ancienne base si elle existe
DROP DATABASE IF EXISTS garagepoint;
CREATE DATABASE garagepoint;
USE garagepoint;

-- Table utilisateurs
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    role VARCHAR(20) DEFAULT 'admin',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table employes
CREATE TABLE employes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    poste VARCHAR(100),
    telephone VARCHAR(20),
    pin_code VARCHAR(4) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table pointages
CREATE TABLE pointages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employe_id INT NOT NULL,
    type ENUM('arrivee', 'pause', 'reprise', 'depart') NOT NULL,
    date DATE NOT NULL,
    heure TIME NOT NULL,
    statut ENUM('valide', 'corrige', 'annule') DEFAULT 'valide',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE
);

-- Comptes
INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role) VALUES
('admin@garagepoint.com', 'admin123', 'Admin', 'Garage', 'admin'),
('employe@garagepoint.com', '1234', 'Test', 'Employe', 'employe');

-- Employés
INSERT INTO employes (nom, prenom, poste, telephone, pin_code) VALUES
('Dupont', 'Jean', 'Mécanicien', '0612345678', '1234'),
('Martin', 'Sophie', 'Secrétaire', '0698765432', '5678'),
('Bernard', 'Pierre', 'Carrossier', '0654321876', '9012'),
('Petit', 'Marie', 'Réceptionniste', '0678912345', '3456');

-- Pointages de test
INSERT INTO pointages (employe_id, type, date, heure) VALUES
(1, 'arrivee', CURDATE(), '08:15:00'),
(1, 'pause', CURDATE(), '12:00:00'),
(1, 'reprise', CURDATE(), '13:30:00'),
(1, 'depart', CURDATE(), '17:00:00'),
(2, 'arrivee', CURDATE(), '09:00:00'),
(3, 'arrivee', CURDATE(), '08:30:00'),
(4, 'arrivee', CURDATE(), '09:15:00');