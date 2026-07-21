<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$employeId = $data['employe_id'] ?? 0;
$type = $data['type'] ?? '';

if (!$employeId || !$type) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$typesValides = ['arrivee', 'pause', 'reprise', 'depart'];
if (!in_array($type, $typesValides)) {
    echo json_encode(['success' => false, 'message' => 'Type invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO pointages (employe_id, type, date, heure) VALUES (?, ?, CURDATE(), CURTIME())");
    $stmt->execute([$employeId, $type]);
    echo json_encode(['success' => true, 'message' => 'Pointage enregistré']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>