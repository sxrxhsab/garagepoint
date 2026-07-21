<?php
// admin/recap.php - Version Ultra WOW
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

$date = $_GET['date'] ?? date('Y-m-d');

$recap = $pdo->prepare("
    SELECT 
        e.id, e.nom, e.prenom, e.poste,
        MIN(CASE WHEN p.type = 'arrivee' THEN p.heure END) as arrivee,
        MAX(CASE WHEN p.type = 'depart' THEN p.heure END) as depart,
        COUNT(CASE WHEN p.type = 'pause' THEN 1 END) as nb_pauses
    FROM employes e
    LEFT JOIN pointages p ON e.id = p.employe_id AND p.date = ?
    GROUP BY e.id
    ORDER BY e.nom
");
$recap->execute([$date]);
$recap = $recap->fetchAll();

$total = count($recap);
$presents = 0;
$enCours = 0;
$termines = 0;
$absents = 0;

foreach ($recap as $r) {
    if ($r['arrivee'] && $r['depart']) {
        $termines++;
        $presents++;
    } elseif ($r['arrivee'] && !$r['depart']) {
        $enCours++;
        $presents++;
    } elseif (!$r['arrivee']) {
        $absents++;
    }
}

// Calcul des heures travaillées
foreach ($recap as &$r) {
    if ($r['arrivee'] && $r['depart']) {
        $arr = new DateTime($r['arrivee']);
        $dep = new DateTime($r['depart']);
        $diff = $arr->diff($dep);
        $r['heures'] = $diff->format('%h:%i');
    } else {
        $r['heures'] = '-';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif Pro - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { padding: 24px; }
        .big-number {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 30%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 1.5s ease-in-out infinite;
        }
        .status-dot.green { background: var(--secondary); }
        .status-dot.yellow { background: var(--warning); }
        .status-dot.gray { background: var(--text-muted); }
        .status-dot.red { background: var(--accent); }
        .stat-big {
            text-align: center;
            padding: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            transition: var(--transition);
        }
        .stat-big:hover {
            border-color: rgba(108, 99, 255, 0.2);
            transform: translateY(-4px);
        }
        .stat-big .number {
            font-size: 36px;
            font-weight: 700;
        }
        .stat-big .label {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 4px;
        }
        .nav-date {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .nav-date .btn-date {
            padding: 8px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-size: 13px;
        }
        .nav-date .btn-date:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .nav-date .btn-date.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .statut-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        .statut-badge.present { background: rgba(0, 212, 170, 0.12); color: var(--secondary); }
        .statut-badge.en-cours { background: rgba(255, 217, 61, 0.12); color: var(--warning); }
        .statut-badge.termine { background: rgba(108, 99, 255, 0.12); color: var(--primary); }
        .statut-badge.absent { background: rgba(255, 107, 107, 0.12); color: var(--accent); }
        .empty-recap {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-recap i {
            font-size: 48px;
            color: var(--text-muted);
            opacity: 0.3;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="max-w-7xl mx-auto">

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h1 class="page-title animate-fadeUp">
                    <i class="fas fa-file-alt text-purple-400 mr-3"></i>Récapitulatif
                </h1>
                <p class="page-subtitle animate-fadeUp animate-delay-1">
                    <i class="fas fa-calendar-alt mr-2 text-muted"></i>
                    <?php echo date('l d F Y', strtotime($date)); ?>
                </p>
            </div>
            <!-- Dans l'en-tête -->
<div class="flex items-center gap-3">
    <img src="../assets/images/garagelogo.png" alt="GaragePoint" class="h-10">
    <div>
        <h1 class="text-2xl font-bold text-purple-600">Récapitulatif</h1>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm">← Retour</a>
    </div>
</div>
            <div class="flex flex-wrap gap-3 items-center">
                <div class="nav-date">
                    <a href="?date=<?php echo date('Y-m-d', strtotime($date . ' -1 day')); ?>" class="btn-date">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn-date <?php echo $date == date('Y-m-d') ? 'active' : ''; ?>">
                        Aujourd'hui
                    </a>
                    <a href="?date=<?php echo date('Y-m-d', strtotime($date . ' +1 day')); ?>" class="btn-date">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <a href="dashboard.php" class="btn btn-glass">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="../logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- BIG STATS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-big animate-fadeUp animate-delay-1">
                <div class="number" style="color:var(--primary);"><?php echo $presents; ?></div>
                <div class="label"><span class="status-dot green"></span>Présents</div>
            </div>
            <div class="stat-big animate-fadeUp animate-delay-2">
                <div class="number" style="color:var(--warning);"><?php echo $enCours; ?></div>
                <div class="label"><span class="status-dot yellow"></span>En cours</div>
            </div>
            <div class="stat-big animate-fadeUp animate-delay-3">
                <div class="number" style="color:var(--primary);"><?php echo $termines; ?></div>
                <div class="label"><span class="status-dot gray"></span>Terminés</div>
            </div>
            <div class="stat-big animate-fadeUp animate-delay-4">
                <div class="number" style="color:var(--accent);"><?php echo $absents; ?></div>
                <div class="label"><span class="status-dot red"></span>Absents</div>
            </div>
        </div>

        <!-- TABLEAU -->
        <div class="glass-card p-6 animate-fadeUp animate-delay-3">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-users text-primary mr-2"></i>
                    Détail par employé
                </h2>
                <span class="text-xs text-muted"><?php echo $total; ?> employés</span>
            </div>

            <?php if ($total > 0): ?>
                <div class="overflow-x-auto">
                    <table class="table-glass">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user mr-1"></i>Employé</th>
                                <th><i class="fas fa-briefcase mr-1"></i>Poste</th>
                                <th class="text-center"><i class="fas fa-sign-in-alt mr-1"></i>Arrivée</th>
                                <th class="text-center"><i class="fas fa-sign-out-alt mr-1"></i>Départ</th>
                                <th class="text-center"><i class="fas fa-clock mr-1"></i>Heures</th>
                                <th class="text-center"><i class="fas fa-coffee mr-1"></i>Pauses</th>
                                <th class="text-center"><i class="fas fa-info-circle mr-1"></i>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recap as $r): ?>
                                <tr>
                                    <td class="font-medium text-white"><?php echo $r['prenom'] . ' ' . $r['nom']; ?></td>
                                    <td><?php echo $r['poste'] ?? '-'; ?></td>
                                    <td class="text-center font-mono"><?php echo $r['arrivee'] ? date('H:i', strtotime($r['arrivee'])) : '-'; ?></td>
                                    <td class="text-center font-mono"><?php echo $r['depart'] ? date('H:i', strtotime($r['depart'])) : '-'; ?></td>
                                    <td class="text-center font-mono font-semibold text-white"><?php echo $r['heures']; ?></td>
                                    <td class="text-center"><?php echo $r['nb_pauses'] ?? 0; ?></td>
                                    <td class="text-center">
                                        <?php if ($r['arrivee'] && $r['depart']): ?>
                                            <span class="statut-badge termine"><i class="fas fa-check-circle"></i> Terminé</span>
                                        <?php elseif ($r['arrivee'] && !$r['depart']): ?>
                                            <span class="statut-badge en-cours"><i class="fas fa-circle" style="font-size:8px;animation:pulse 1.5s infinite;"></i> En cours</span>
                                        <?php else: ?>
                                            <span class="statut-badge absent"><i class="fas fa-times-circle"></i> Absent</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-recap">
                    <i class="fas fa-users"></i>
                    <p class="text-muted">Aucun employé enregistré</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>