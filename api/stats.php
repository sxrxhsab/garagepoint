<?php
// api/stats.php - Données pour les graphiques
header('Content-Type: application/json');
require_once '../config/database.php';

$period = $_GET['period'] ?? 'week'; // week, month, year

switch($period) {
    case 'week':
        $interval = 'INTERVAL 7 DAY';
        $format = '%a';
        $labels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        break;
    case 'month':
        $interval = 'INTERVAL 30 DAY';
        $format = '%d %b';
        $labels = null;
        break;
    default:
        $interval = 'INTERVAL 7 DAY';
        $format = '%a';
        $labels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
}

// Présences par jour
$sql = "
    SELECT 
        DATE(date) as jour,
        COUNT(DISTINCT employe_id) as presents
    FROM pointages
    WHERE date >= DATE_SUB(CURDATE(), $interval)
    AND type = 'arrivee'
    GROUP BY DATE(date)
    ORDER BY jour
";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll();

$response = [
    'labels' => $labels ?: array_column($data, 'jour'),
    'presents' => array_column($data, 'presents')
];

echo json_encode($response);
?>