<?php
// api/dernier_pointage.php
header('Content-Type: application/json');
require_once '../config/database.php';

$employeId = $_GET['employe_id'] ?? 0;

if (!$employeId) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pointages WHERE employe_id = ? AND date = CURDATE() ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$employeId]);
$dernier = $stmt->fetch();

echo json_encode(['dernier' => $dernier]);
?>