<?php
// api/dernier_pointage.php - Récupérer le dernier pointage d'un employé
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$employeId = $_GET['employe_id'] ?? 0;

if (!$employeId) {
    echo json_encode(['success' => false, 'message' => 'ID employé manquant']);
    exit;
}

try {
    // Version PostgreSQL (CURRENT_DATE au lieu de CURDATE)
    $stmt = $pdo->prepare("
        SELECT * FROM pointages 
        WHERE employe_id = ? 
        AND date = CURRENT_DATE 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$employeId]);
    $dernier = $stmt->fetch();

    echo json_encode(['dernier' => $dernier]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>