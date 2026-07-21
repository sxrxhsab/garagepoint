<?php
// admin/pointages.php - Historique avec gestion des pointages
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// ============================================
// AJOUTER UN POINTAGE MANUELLEMENT
// ============================================
if (isset($_POST['ajouter_pointage'])) {
    $employe_id = $_POST['employe_id'] ?? 0;
    $type = $_POST['type'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $heure = $_POST['heure'] ?? date('H:i:s');
    
    if ($employe_id > 0 && !empty($type)) {
        $stmt = $pdo->prepare("INSERT INTO pointages (employe_id, type, date, heure) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$employe_id, $type, $date, $heure])) {
            $message = "✅ Pointage ajouté avec succès !";
            $message_type = 'success';
        } else {
            $message = "❌ Erreur lors de l'ajout";
            $message_type = 'error';
        }
    } else {
        $message = "❌ Veuillez sélectionner un employé et un type";
        $message_type = 'error';
    }
}

// ============================================
// SUPPRIMER UN POINTAGE
// ============================================
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'] ?? 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM pointages WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "✅ Pointage supprimé avec succès !";
            $message_type = 'success';
        } else {
            $message = "❌ Erreur lors de la suppression";
            $message_type = 'error';
        }
    }
}

// ============================================
// RÉCUPÉRER LES FILTRES
// ============================================
$date = $_GET['date'] ?? date('Y-m-d');
$employe_id = $_GET['employe'] ?? '';

// Construction de la requête avec filtres
$sql = "SELECT p.*, e.nom, e.prenom, e.poste 
        FROM pointages p 
        JOIN employes e ON p.employe_id = e.id 
        WHERE 1=1";
$params = [];

if (!empty($date)) {
    $sql .= " AND p.date = ?";
    $params[] = $date;
}

if (!empty($employe_id)) {
    $sql .= " AND p.employe_id = ?";
    $params[] = $employe_id;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pointages = $stmt->fetchAll();

// Récupérer la liste des employés pour le filtre
$employes = $pdo->query("SELECT id, nom, prenom FROM employes ORDER BY nom")->fetchAll();

// Statistiques
$total = count($pointages);
$arrivees = count(array_filter($pointages, fn($p) => $p['type'] === 'arrivee'));
$pauses = count(array_filter($pointages, fn($p) => $p['type'] === 'pause'));
$reprises = count(array_filter($pointages, fn($p) => $p['type'] === 'reprise'));
$departs = count(array_filter($pointages, fn($p) => $p['type'] === 'depart'));

// Types de pointage pour le formulaire
$types = [
    'arrivee' => 'Arrivée',
    'pause' => 'Pause',
    'reprise' => 'Reprise',
    'depart' => 'Départ'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { padding: 24px; background: var(--bg-primary); font-family: var(--font); }
        .page-header {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px 24px;
            backdrop-filter: blur(20px);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 30%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            backdrop-filter: blur(20px);
            margin-bottom: 24px;
        }
        .stat-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        .stat-mini:hover {
            border-color: rgba(108, 99, 255, 0.2);
            transform: translateY(-2px);
        }
        .stat-mini .value { font-size: 20px; font-weight: 700; }
        .stat-mini .label { color: var(--text-secondary); font-size: 12px; }
        .stat-mini .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-arrivee { background: rgba(0, 212, 170, 0.12); color: var(--secondary); }
        .badge-pause { background: rgba(255, 217, 61, 0.12); color: var(--warning); }
        .badge-reprise { background: rgba(108, 99, 255, 0.12); color: var(--primary); }
        .badge-depart { background: rgba(255, 107, 107, 0.12); color: var(--accent); }

        .table-glass {
            width: 100%;
            border-collapse: collapse;
        }
        .table-glass th {
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        .table-glass td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        .table-glass tr:hover td { background: rgba(255,255,255,0.02); }
        .table-glass .actions-cell {
            display: flex;
            gap: 6px;
        }
        .table-glass .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
        }
        .table-glass .btn-sm:hover { transform: scale(1.05); }
        .btn-sm-danger { background: rgba(255, 107, 107, 0.15); color: var(--accent); }
        .btn-sm-danger:hover { background: var(--accent); color: white; }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
        }
        .filter-group label {
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }
        .filter-group select, .filter-group input {
            min-width: 160px;
        }
        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: 42px;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108, 99, 255, 0.3);
        }
        .btn-secondary {
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 42px;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.15);
        }
        .btn-success {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--secondary), #00b894);
            color: var(--bg-primary);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: 42px;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 212, 170, 0.3);
        }
        .btn-danger {
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--accent), #e17055);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 42px;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 107, 107, 0.3);
        }
        .logo-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-header img {
            height: 36px;
            filter: drop-shadow(0 4px 20px rgba(108, 99, 255, 0.1));
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: var(--text-muted);
            opacity: 0.3;
            margin-bottom: 12px;
        }
        .empty-state h3 {
            color: var(--text-secondary);
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
        }
        .toast {
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .toast-success {
            background: rgba(0, 212, 170, 0.1);
            border: 1px solid rgba(0, 212, 170, 0.2);
            color: var(--secondary);
        }
        .toast-error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: var(--accent);
        }
        .form-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }
        .form-inline select, .form-inline input {
            min-width: 140px;
        }
        @media (max-width: 768px) {
            body { padding: 12px; }
            .page-title { font-size: 20px; }
            .stat-mini .value { font-size: 16px; }
            .filter-group select, .filter-group input { min-width: 120px; }
            .logo-header img { height: 28px; }
            .form-inline select, .form-inline input { min-width: 100px; }
        }
    </style>
</head>
<body>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="page-header">
        <div class="logo-header">
            <img src="../assets/images/garagelogo.png" alt="GaragePoint">
            <div>
                <h1 class="page-title">📜 Gestion des pointages</h1>
                <p class="text-muted text-sm">
                    <a href="dashboard.php" class="text-blue-400 hover:text-blue-300 transition">← Retour au dashboard</a>
                </p>
            </div>
        </div>
        <a href="../logout.php" class="btn-danger">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <!-- MESSAGES -->
    <?php if (isset($message)): ?>
        <div class="toast <?php echo $message_type == 'success' ? 'toast-success' : 'toast-error'; ?>">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- FORMULAIRE D'AJOUT MANUEL -->
    <!-- ============================================ -->
    <div class="glass-card">
        <h3 class="text-lg font-semibold mb-3">
            <i class="fas fa-plus-circle text-green-400 mr-2"></i>
            Ajouter un pointage manuellement
        </h3>
        <form method="POST" class="form-inline">
            <input type="hidden" name="ajouter_pointage" value="1">
            <div>
                <label class="text-xs text-muted uppercase font-semibold">Employé</label>
                <select name="employe_id" class="input-glass" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo $emp['prenom'] . ' ' . $emp['nom']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs text-muted uppercase font-semibold">Type</label>
                <select name="type" class="input-glass" required>
                    <option value="">Sélectionner</option>
                    <option value="arrivee">Arrivée</option>
                    <option value="pause">Pause</option>
                    <option value="reprise">Reprise</option>
                    <option value="depart">Départ</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-muted uppercase font-semibold">Date</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="input-glass">
            </div>
            <div>
                <label class="text-xs text-muted uppercase font-semibold">Heure</label>
                <input type="time" name="heure" value="<?php echo date('H:i'); ?>" class="input-glass">
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn-success">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- STATISTIQUES -->
    <!-- ============================================ -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="stat-mini">
            <div class="icon-circle" style="background:rgba(108,99,255,0.1);color:var(--primary);">
                <i class="fas fa-list"></i>
            </div>
            <div>
                <div class="value"><?php echo $total; ?></div>
                <div class="label">Total</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon-circle" style="background:rgba(0,212,170,0.1);color:var(--secondary);">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <div class="value" style="color:var(--secondary);"><?php echo $arrivees; ?></div>
                <div class="label">Arrivées</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon-circle" style="background:rgba(255,217,61,0.1);color:var(--warning);">
                <i class="fas fa-coffee"></i>
            </div>
            <div>
                <div class="value" style="color:var(--warning);"><?php echo $pauses; ?></div>
                <div class="label">Pauses</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon-circle" style="background:rgba(108,99,255,0.1);color:var(--primary);">
                <i class="fas fa-play"></i>
            </div>
            <div>
                <div class="value" style="color:var(--primary);"><?php echo $reprises; ?></div>
                <div class="label">Reprises</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon-circle" style="background:rgba(255,107,107,0.1);color:var(--accent);">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div>
                <div class="value" style="color:var(--accent);"><?php echo $departs; ?></div>
                <div class="label">Départs</div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- FILTRES -->
    <!-- ============================================ -->
    <div class="glass-card">
        <form method="GET" class="filter-group">
            <div>
                <label><i class="fas fa-calendar mr-1"></i>Date</label>
                <input type="date" name="date" value="<?php echo $date; ?>" class="input-glass">
            </div>
            <div>
                <label><i class="fas fa-user mr-1"></i>Employé</label>
                <select name="employe" class="input-glass">
                    <option value="">Tous les employés</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $employe_id == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo $emp['prenom'] . ' ' . $emp['nom']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
            </div>
            <div>
                <label>&nbsp;</label>
                <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn-secondary">
                    <i class="fas fa-undo"></i> Aujourd'hui
                </a>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- LISTE DES POINTAGES AVEC SUPPRESSION -->
    <!-- ============================================ -->
    <div class="glass-card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">
                <i class="fas fa-list-ul text-primary mr-2"></i>
                Pointages (<?php echo $total; ?>)
            </h2>
            <span class="text-xs text-muted">
                <i class="fas fa-sync-alt mr-1"></i>En temps réel
            </span>
        </div>

        <?php if ($total > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-glass">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Poste</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pointages as $p): ?>
                            <tr>
                                <td class="font-medium text-white"><?php echo $p['prenom'] . ' ' . $p['nom']; ?></td>
                                <td><?php echo $p['poste'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $p['type']; ?>">
                                        <i class="fas fa-<?php echo $p['type'] == 'arrivee' ? 'sign-in-alt' : ($p['type'] == 'pause' ? 'coffee' : ($p['type'] == 'reprise' ? 'play' : 'sign-out-alt')); ?>"></i>
                                        <?php echo ucfirst($p['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($p['date'])); ?></td>
                                <td class="font-mono"><?php echo date('H:i', strtotime($p['heure'])); ?></td>
                                <td class="text-center">
                                    <a href="?supprimer=<?php echo $p['id']; ?>&date=<?php echo $date; ?>&employe=<?php echo $employe_id; ?>" 
                                       onclick="return confirm('Supprimer ce pointage ?')"
                                       class="btn-sm btn-sm-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clock"></i>
                <h3>Aucun pointage trouvé</h3>
                <p>Aucun pointage pour les filtres sélectionnés.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>