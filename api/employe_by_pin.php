<?php
// api/employe_by_pin.php - Récupérer un employé par son code PIN
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$pin = $_GET['pin'] ?? '';

if (empty($pin) || strlen($pin) !== 4 || !is_numeric($pin)) {
    echo json_encode(['success' => false, 'message' => 'PIN invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM employes WHERE pin_code = ?");
    $stmt->execute([$pin]);
    $employe = $stmt->fetch();

    if ($employe) {
        echo json_encode(['success' => true, 'employe' => $employe]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>