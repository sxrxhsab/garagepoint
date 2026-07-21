<?php
// install_db.php - Installer les tables sur Render
require_once 'config/database.php';

echo "🔧 Installation des tables...<br><br>";

try {
    // ============================================
    // CRÉATION DES TABLES
    // ============================================

    echo "📦 Création de la table utilisateurs...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS utilisateurs (
            id SERIAL PRIMARY KEY,
            email VARCHAR(100) UNIQUE,
            username VARCHAR(50) UNIQUE NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            nom VARCHAR(50),
            prenom VARCHAR(50),
            role VARCHAR(20) DEFAULT 'admin',
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table utilisateurs créée<br>";

    echo "📦 Création de la table employes...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employes (
            id SERIAL PRIMARY KEY,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            poste VARCHAR(100),
            telephone VARCHAR(20),
            pin_code VARCHAR(4) NOT NULL,
            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table employes créée<br>";

    echo "📦 Création de la table pointages...<br>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pointages (
            id SERIAL PRIMARY KEY,
            employe_id INTEGER NOT NULL REFERENCES employes(id) ON DELETE CASCADE,
            type VARCHAR(20) NOT NULL,
            date DATE NOT NULL,
            heure TIME NOT NULL,
            statut VARCHAR(20) DEFAULT 'valide',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table pointages créée<br><br>";

    // ============================================
    // INSERTION DES DONNÉES
    // ============================================

    echo "📝 Insertion de l'administrateur...<br>";
    $pdo->exec("
        INSERT INTO utilisateurs (username, mot_de_passe, nom, prenom, role) 
        VALUES ('hichem.slimani', 'hkauto2026', 'Slimani', 'Hichem', 'admin')
        ON CONFLICT (username) DO NOTHING
    ");
    echo "✅ Admin ajouté<br>";

    echo "📝 Insertion des employés...<br>";
    $pdo->exec("
        INSERT INTO employes (nom, prenom, poste, telephone, pin_code) VALUES
        ('Dupont', 'Jean', 'Mécanicien', '0612345678', '1234'),
        ('Martin', 'Sophie', 'Secrétaire', '0698765432', '5678'),
        ('Bernard', 'Pierre', 'Carrossier', '0654321876', '9012'),
        ('Petit', 'Marie', 'Réceptionniste', '0678912345', '3456')
        ON CONFLICT DO NOTHING
    ");
    echo "✅ 4 employés ajoutés<br><br>";

    // ============================================
    // VÉRIFICATION
    // ============================================

    echo "🔍 Vérification...<br>";
    $stmt = $pdo->query("SELECT * FROM utilisateurs");
    $users = $stmt->fetchAll();
    echo "✅ " . count($users) . " utilisateur(s) trouvé(s)<br>";

    $stmt = $pdo->query("SELECT * FROM employes");
    $employes = $stmt->fetchAll();
    echo "✅ " . count($employes) . " employé(s) trouvé(s)<br><br>";

    echo "🎉 <strong>Installation terminée avec succès !</strong><br>";
    echo "🔑 Connecte-toi avec : hichem.slimani / hkauto2026<br>";
    echo "📱 <a href='/'>Accéder au pointage</a> | ";
    echo "<a href='/admin/login.php'>Accéder à l'admin</a>";

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>