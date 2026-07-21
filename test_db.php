<?php
// test_db.php
require_once 'config/database.php';

echo "<h1>✅ Connexion à la base réussie !</h1>";
echo "<p>Base de données : $dbname</p>";

// Afficher les employés
$stmt = $pdo->query("SELECT * FROM employes");
$employes = $stmt->fetchAll();

if (count($employes) > 0) {
    echo "<h2>Employés :</h2>";
    echo "<ul>";
    foreach ($employes as $emp) {
        echo "<li>{$emp['prenom']} {$emp['nom']} - {$emp['poste']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Aucun employé trouvé. Ajoute-en dans phpMyAdmin.</p>";
}
?>