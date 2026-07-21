<?php
// auth_check.php - Vérification des rôles
function estAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function estEmploye() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'employe';
}

function estConnecte() {
    return isset($_SESSION['user_id']);
}
?>