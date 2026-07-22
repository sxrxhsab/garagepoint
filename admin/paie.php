<?php
// admin/paie.php - Rapport mensuel avec estimation des heures
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ============================================
// PARAMÈTRES
// ============================================
$mois = $_GET['mois'] ?? date('m');
$annee = $_GET['annee'] ?? date('Y');

// Heures de travail par jour
$HEURES_JOUR = 7;

// ============================================
// RÉCUPÉRER LES DONNÉES
// ============================================

// 1. Récupérer les jours travaillés, retards et infos de base
$sql = "
    SELECT 
        e.id,
        e.nom,
        e.prenom,
        e.poste,
        e.pin_code,
        COUNT(DISTINCT CASE WHEN p.type = 'arrivee' THEN p.date END) as jours_travailles,
        COUNT(CASE WHEN p.type = 'arrivee' AND p.heure > '08:30:00' THEN 1 END) as retards,
        (EXTRACT(DAY FROM (DATE_TRUNC('month', DATE '$annee-$mois-01') + INTERVAL '1 month' - INTERVAL '1 day'))) as jours_mois,
        MIN(CASE WHEN p.type = 'arrivee' THEN p.heure END) as premiere_arrivee
    FROM employes e
    LEFT JOIN pointages p ON e.id = p.employe_id 
        AND EXTRACT(MONTH FROM p.date) = ?
        AND EXTRACT(YEAR FROM p.date) = ?
    GROUP BY e.id, e.nom, e.prenom, e.poste, e.pin_code
    ORDER BY e.nom
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$mois, $annee]);
$rapport = $stmt->fetchAll();

// 2. Calculer les heures pour chaque employé
foreach ($rapport as &$r) {
    $totalHeures = 0;
    $estime = false;
    
    // Récupérer les pointages de l'employé pour le mois
    $sqlHeures = "
        SELECT 
            date,
            MIN(CASE WHEN type = 'arrivee' THEN heure END) as arrivee,
            MAX(CASE WHEN type = 'depart' THEN heure END) as depart,
            COUNT(CASE WHEN type = 'pause' THEN 1 END) as nb_pauses
        FROM pointages
        WHERE employe_id = ?
            AND EXTRACT(MONTH FROM date) = ?
            AND EXTRACT(YEAR FROM date) = ?
        GROUP BY date
    ";
    
    $stmtHeures = $pdo->prepare($sqlHeures);
    $stmtHeures->execute([$r['id'], $mois, $annee]);
    $jours = $stmtHeures->fetchAll();
    
    foreach ($jours as $jour) {
        if ($jour['arrivee']) {
            if ($jour['depart']) {
                // ✅ Départ enregistré : calcul exact
                $arr = new DateTime($jour['arrivee']);
                $dep = new DateTime($jour['depart']);
                $diff = $arr->diff($dep);
                $heuresJour = $diff->h + ($diff->i / 60);
                $totalHeures += $heuresJour;
            } else {
                // ⚠️ Pas de départ : estimation
                $estime = true;
                $heureArrivee = (int)date('H', strtotime($jour['arrivee']));
                
                // Estimation selon l'heure d'arrivée
                if ($heureArrivee <= 8) {
                    $heuresJour = 7; // 7h si arrivée avant 8h
                } elseif ($heureArrivee <= 9) {
                    $heuresJour = 6; // 6h si arrivée entre 8h et 9h
                } elseif ($heureArrivee <= 10) {
                    $heuresJour = 5; // 5h si arrivée entre 9h et 10h
                } else {
                    $heuresJour = 4; // 4h si arrivée après 10h
                }
                $totalHeures += $heuresJour;
            }
        }
    }
    
    $r['heures_travaillees'] = $totalHeures;
    $r['estime'] = $estime;
}

// ============================================
// STATISTIQUES GLOBALES
// ============================================
$totalEmployes = count($rapport);
$totalJours = 0;
$totalHeures = 0;
$totalRetards = 0;
$totalAbsences = 0;
$totalHeuresSupp = 0;
$totalEmployesHeuresSupp = 0;

foreach ($rapport as $r) {
    $totalJours += $r['jours_travailles'];
    $totalHeures += $r['heures_travailles'] ?? 0;
    $totalRetards += $r['retards'];
    $totalAbsences += ($r['jours_mois'] - $r['jours_travailles']);
    
    // Heures supplémentaires
    $heures = $r['heures_travailles'] ?? 0;
    $jours = $r['jours_travailles'];
    if ($jours > 0) {
        $moyenne = $heures / $jours;
        if ($moyenne > $HEURES_JOUR) {
            $totalHeuresSupp += ($moyenne - $HEURES_JOUR) * $jours;
            $totalEmployesHeuresSupp++;
        }
    }
}

// Mois en français
$nomsMois = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
    '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
    '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$nomMois = $nomsMois[$mois] ?? $mois;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport mensuel - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: var(--bg-primary);
            font-family: var(--font);
            padding: 24px;
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 10% 20%, rgba(108, 99, 255, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(0, 212, 170, 0.04) 0%, transparent 50%);
        }
        .page-title {
            font-size: 28px;
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
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        .glass-card:nth-child(1) { animation-delay: 0.05s; }
        .glass-card:nth-child(2) { animation-delay: 0.10s; }
        .glass-card:nth-child(3) { animation-delay: 0.15s; }
        .glass-card:nth-child(4) { animation-delay: 0.20s; }
        .stat-card {
            padding: 20px 24px;
            border-radius: var(--radius);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            text-align: center;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(108, 99, 255, 0.2);
            box-shadow: var(--shadow-hover);
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
        }
        .stat-card .label {
            color: var(--text-secondary);
            font-size: 13px;
            margin-top: 4px;
        }
        .stat-card .icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }
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
        .table-glass tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .table-glass .number-cell {
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }
        .badge-success { background: rgba(0, 212, 170, 0.12); color: var(--secondary); padding: 2px 12px; border-radius: 50px; font-size: 12px; }
        .badge-warning { background: rgba(255, 217, 61, 0.12); color: var(--warning); padding: 2px 12px; border-radius: 50px; font-size: 12px; }
        .badge-danger { background: rgba(255, 107, 107, 0.12); color: var(--accent); padding: 2px 12px; border-radius: 50px; font-size: 12px; }
        .badge-info { background: rgba(108, 99, 255, 0.12); color: var(--primary); padding: 2px 12px; border-radius: 50px; font-size: 12px; }
        .badge-estime { background: rgba(255, 217, 61, 0.08); color: #b7950b; padding: 2px 12px; border-radius: 50px; font-size: 11px; font-style: italic; }

        .logo-header { display: flex; align-items: center; gap: 14px; }
        .logo-header img { height: 44px; filter: drop-shadow(0 4px 20px rgba(108, 99, 255, 0.1)); }
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
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.15);
        }
        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(108, 99, 255, 0.3);
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
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 107, 107, 0.3);
        }
        .btn-export {
            padding: 10px 20px;
            background: linear-gradient(135deg, #00b894, #00a07e);
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
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 184, 148, 0.3);
        }
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
        .filter-group select {
            min-width: 140px;
        }
        .input-glass {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            padding: 10px 14px;
            font-size: 14px;
            transition: var(--transition);
            width: 100%;
            outline: none;
        }
        .input-glass:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
        }
        @media (max-width: 768px) {
            body { padding: 12px; }
            .page-title { font-size: 22px; }
            .stat-card .number { font-size: 24px; }
            .logo-header img { height: 32px; }
        }
        @media print {
            body { background: white !important; color: black !important; padding: 10px !important; }
            .glass-card { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; backdrop-filter: none !important; page-break-inside: avoid; }
            .page-title { color: black !important; background: none !important; -webkit-text-fill-color: black !important; }
            .stat-card { background: #f5f5f5 !important; border: 1px solid #ddd !important; box-shadow: none !important; }
            .stat-card .number { color: black !important; }
            .stat-card .label { color: #555 !important; }
            .table-glass th { color: black !important; border-bottom: 2px solid #333 !important; }
            .table-glass td { color: black !important; border-bottom: 1px solid #ddd !important; }
            .table-glass tr:hover td { background: white !important; }
            .badge-success, .badge-warning, .badge-danger, .badge-info, .badge-estime { color: black !important; background: #eee !important; border: 1px solid #ccc !important; }
            .btn-primary, .btn-secondary, .btn-danger, .btn-export { display: none !important; }
            .logo-header img { filter: none !important; }
            .text-muted { color: #666 !important; }
            .text-secondary { color: #333 !important; }
            .number-cell { font-weight: 700 !important; color: black !important; }
            a { color: black !important; text-decoration: none !important; }
            .filter-group { display: none !important; }
            .page-header { background: white !important; border-bottom: 2px solid #333 !important; }
            .admin-avatar { background: #333 !important; color: white !important; }
            .stat-card, .glass-card { page-break-inside: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            .status-dot { display: none !important; }
            .table-glass th i { display: none !important; }
        }
    </style>
</head>
<body>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <div class="logo-header">
            <img src="../assets/images/garagelogo.png" alt="GaragePoint">
            <div>
                <h1 class="page-title">📊 Rapport mensuel</h1>
                <p class="text-muted text-sm">
                    <a href="dashboard.php" class="text-blue-400 hover:text-blue-300 transition">← Retour au dashboard</a>
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="../logout.php" class="btn-danger">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <!-- FILTRES -->
    <div class="glass-card">
        <form method="GET" class="filter-group">
            <div>
                <label><i class="fas fa-calendar-alt mr-1"></i>Mois</label>
                <select name="mois" class="input-glass">
                    <?php foreach ($nomsMois as $key => $nom): ?>
                        <option value="<?php echo $key; ?>" <?php echo $mois == $key ? 'selected' : ''; ?>>
                            <?php echo $nom; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><i class="fas fa-calendar mr-1"></i>Année</label>
                <select name="annee" class="input-glass">
                    <?php for ($a = date('Y'); $a >= date('Y') - 3; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $annee == $a ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i>Voir
                </button>
            </div>
            <div>
                <label>&nbsp;</label>
                <button onclick="window.print()" class="btn-export">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
            </div>
            <div>
                <label>&nbsp;</label>
                <a href="paie.php?mois=<?php echo date('m'); ?>&annee=<?php echo date('Y'); ?>" class="btn-secondary">
                    <i class="fas fa-undo"></i> Mois en cours
                </a>
            </div>
        </form>
    </div>

    <!-- STATS GLOBALES -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card glass-card">
            <span class="icon">👥</span>
            <div class="number" style="color:var(--primary);"><?php echo $totalEmployes; ?></div>
            <div class="label">Total employés</div>
        </div>
        <div class="stat-card glass-card">
            <span class="icon">📅</span>
            <div class="number" style="color:var(--secondary);"><?php echo $totalEmployes > 0 ? round($totalJours / $totalEmployes, 1) : 0; ?></div>
            <div class="label">Moyenne jours/mois</div>
        </div>
        <div class="stat-card glass-card">
            <span class="icon">⏱️</span>
            <div class="number" style="color:var(--warning);"><?php echo $totalEmployes > 0 ? round($totalHeures / $totalEmployes, 1) : 0; ?>h</div>
            <div class="label">Moyenne heures/mois</div>
        </div>
        <div class="stat-card glass-card">
            <span class="icon">⚠️</span>
            <div class="number" style="color:var(--accent);"><?php echo $totalRetards; ?></div>
            <div class="label">Total retards</div>
        </div>
    </div>

    <!-- TABLEAU RAPPORT -->
    <div class="glass-card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">
                <i class="fas fa-list-ul text-primary mr-2"></i>
                Détail par employé - <?php echo $nomMois . ' ' . $annee; ?>
            </h2>
            <span class="text-xs text-muted"><?php echo $totalEmployes; ?> employés</span>
        </div>

        <?php if (count($rapport) > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-glass" id="rapportTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user mr-1"></i>Employé</th>
                            <th><i class="fas fa-briefcase mr-1"></i>Poste</th>
                            <th class="text-center"><i class="fas fa-calendar-day mr-1"></i>Jours</th>
                            <th class="text-center"><i class="fas fa-clock mr-1"></i>Heures</th>
                            <th class="text-center"><i class="fas fa-clock mr-1"></i>Moy./jour</th>
                            <th class="text-center"><i class="fas fa-exclamation-triangle mr-1"></i>Retards</th>
                            <th class="text-center"><i class="fas fa-user-slash mr-1"></i>Absences</th>
                            <th class="text-center"><i class="fas fa-info-circle mr-1"></i>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rapport as $r): 
                            $heures = round($r['heures_travaillees'] ?? 0, 1);
                            $moyenneJ = $r['jours_travailles'] > 0 ? round($heures / $r['jours_travailles'], 1) : 0;
                            $absences = $r['jours_mois'] - $r['jours_travailles'];
                            $tauxPresence = $r['jours_mois'] > 0 ? round(($r['jours_travailles'] / $r['jours_mois']) * 100) : 0;
                            $estime = $r['estime'] ?? false;
                        ?>
                            <tr>
                                <td class="font-medium text-white">
                                    <?php echo $r['prenom'] . ' ' . $r['nom']; ?>
                                    <?php if ($estime): ?>
                                        <span class="badge-estime">estimé</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $r['poste'] ?? '-'; ?></td>
                                <td class="text-center number-cell"><?php echo $r['jours_travailles']; ?> / <?php echo $r['jours_mois']; ?></td>
                                <td class="text-center number-cell"><?php echo $heures; ?>h</td>
                                <td class="text-center number-cell"><?php echo $moyenneJ; ?>h</td>
                                <td class="text-center">
                                    <?php if ($r['retards'] > 0): ?>
                                        <span class="badge-danger"><?php echo $r['retards']; ?></span>
                                    <?php else: ?>
                                        <span class="badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($absences > 0): ?>
                                        <span class="badge-danger"><?php echo $absences; ?></span>
                                    <?php else: ?>
                                        <span class="badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($tauxPresence >= 80): ?>
                                        <span class="badge-success"><i class="fas fa-check-circle"></i> Excellent</span>
                                    <?php elseif ($tauxPresence >= 50): ?>
                                        <span class="badge-warning"><i class="fas fa-clock"></i> Moyen</span>
                                    <?php else: ?>
                                        <span class="badge-danger"><i class="fas fa-exclamation-circle"></i> Critique</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-muted">
                <i class="fas fa-users text-4xl block mb-2 opacity-20"></i>
                <p>Aucune donnée pour cette période</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- RÉSUMÉ DES HEURES SUPÉRIEURES -->
    <div class="glass-card">
        <h3 class="text-lg font-semibold mb-4">
            <i class="fas fa-plus-circle text-green-400 mr-2"></i>
            Synthèse des heures supplémentaires
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <div class="number" style="color:var(--warning);"><?php echo round($totalHeuresSupp, 1); ?>h</div>
                <div class="label">Total heures supplémentaires</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color:var(--primary);"><?php echo $totalEmployesHeuresSupp; ?></div>
                <div class="label">Employés concernés</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color:var(--secondary);"><?php echo $totalEmployesHeuresSupp > 0 ? round($totalHeuresSupp / $totalEmployesHeuresSupp, 1) : 0; ?>h</div>
                <div class="label">Moyenne par employé</div>
            </div>
        </div>
        <p class="text-xs text-muted mt-4">
            <i class="fas fa-info-circle mr-1"></i>
            Les heures sont estimées selon l'heure d'arrivée : 7h (avant 8h), 6h (8h-9h), 5h (9h-10h), 4h (après 10h).
            <span class="badge-estime">estimé</span> indique une valeur approximative (aucun départ enregistré).
        </p>
    </div>

</div>

</body>
</html>