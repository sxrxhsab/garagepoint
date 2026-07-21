<?php
// admin/dashboard.php - Avec Hichem Slimani
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// ============================================
// STATISTIQUES
// ============================================

$totalEmployes = $pdo->query("SELECT COUNT(*) FROM employes")->fetchColumn();

$present = $pdo->query("
    SELECT COUNT(DISTINCT employe_id) FROM pointages 
    WHERE date = CURDATE() AND type = 'arrivee' 
    AND employe_id NOT IN (SELECT employe_id FROM pointages WHERE date = CURDATE() AND type = 'depart')
")->fetchColumn();

$enPause = $pdo->query("
    SELECT COUNT(DISTINCT employe_id) FROM pointages 
    WHERE date = CURDATE() AND type = 'pause' 
    AND employe_id NOT IN (SELECT employe_id FROM pointages WHERE date = CURDATE() AND type = 'reprise')
    AND employe_id NOT IN (SELECT employe_id FROM pointages WHERE date = CURDATE() AND type = 'depart')
")->fetchColumn();

$absents = $totalEmployes - $present;
$tauxPresence = $totalEmployes > 0 ? round(($present / $totalEmployes) * 100) : 0;

// Arrivées, départs, pauses
$arrivees = $pdo->query("SELECT COUNT(*) FROM pointages WHERE date = CURDATE() AND type = 'arrivee'")->fetchColumn();
$departs = $pdo->query("SELECT COUNT(*) FROM pointages WHERE date = CURDATE() AND type = 'depart'")->fetchColumn();
$pauses = $pdo->query("SELECT COUNT(*) FROM pointages WHERE date = CURDATE() AND type = 'pause'")->fetchColumn();
$retards = $pdo->query("SELECT COUNT(*) FROM pointages WHERE date = CURDATE() AND type = 'arrivee' AND heure > '08:30:00'")->fetchColumn();

// Heure de pointe
$heurePointe = $pdo->query("
    SELECT HOUR(heure) as h FROM pointages
    WHERE type = 'arrivee' AND date = CURDATE()
    GROUP BY HOUR(heure) ORDER BY COUNT(*) DESC LIMIT 1
")->fetch();

// Moyenne arrivée
$moyenneArrivee = $pdo->query("
    SELECT AVG(HOUR(heure) * 60 + MINUTE(heure)) as avg_minutes
    FROM pointages WHERE type = 'arrivee' AND date = CURDATE()
")->fetch();
$heureMoyenne = '';
if ($moyenneArrivee && $moyenneArrivee['avg_minutes']) {
    $avg = round($moyenneArrivee['avg_minutes']);
    $heureMoyenne = sprintf('%02d:%02d', floor($avg / 60), $avg % 60);
}

// Derniers pointages
$pointages = $pdo->query("
    SELECT p.*, e.nom, e.prenom, e.poste 
    FROM pointages p JOIN employes e ON p.employe_id = e.id 
    ORDER BY p.created_at DESC LIMIT 10
")->fetchAll();

// Top ponctuels
$topPonctuels = $pdo->query("
    SELECT e.nom, e.prenom, COUNT(*) as nb
    FROM pointages p JOIN employes e ON p.employe_id = e.id
    WHERE p.type = 'arrivee' AND p.heure < '08:30:00'
    GROUP BY e.id ORDER BY nb DESC LIMIT 5
")->fetchAll();

$plusPonctuel = $topPonctuels[0] ?? null;

// Employés présents
$employesPresent = $pdo->query("
    SELECT e.id, e.nom, e.prenom, e.poste,
           (SELECT heure FROM pointages WHERE employe_id = e.id AND type = 'arrivee' AND date = CURDATE() ORDER BY created_at DESC LIMIT 1) as arrivee,
           (SELECT heure FROM pointages WHERE employe_id = e.id AND type = 'pause' AND date = CURDATE() ORDER BY created_at DESC LIMIT 1) as pause
    FROM employes e
    WHERE e.id IN (
        SELECT employe_id FROM pointages 
        WHERE date = CURDATE() AND type = 'arrivee' 
        AND employe_id NOT IN (SELECT employe_id FROM pointages WHERE date = CURDATE() AND type = 'depart')
    )
    ORDER BY arrivee ASC
")->fetchAll();

// Premier et dernier arrivé
$premierArrive = $pdo->query("
    SELECT e.nom, e.prenom, p.heure
    FROM pointages p JOIN employes e ON p.employe_id = e.id
    WHERE p.type = 'arrivee' AND p.date = CURDATE()
    ORDER BY p.heure ASC LIMIT 1
")->fetch();

$dernierArrive = $pdo->query("
    SELECT e.nom, e.prenom, p.heure
    FROM pointages p JOIN employes e ON p.employe_id = e.id
    WHERE p.type = 'arrivee' AND p.date = CURDATE()
    ORDER BY p.heure DESC LIMIT 1
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GaragePoint</title>
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
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: var(--transition);
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(108, 99, 255, 0.2);
            box-shadow: var(--shadow-hover);
        }
        .stat-card .icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }
        .stat-card .label { color: var(--text-secondary); font-size: 13px; margin-top: 2px; }
        .stat-card .change {
            font-size: 12px;
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 50px;
            font-weight: 600;
        }
        .stat-card .change.up { background: rgba(0, 212, 170, 0.12); color: var(--secondary); }
        .stat-card .change.down { background: rgba(255, 107, 107, 0.12); color: var(--accent); }

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

        .mini-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        .mini-stat:hover {
            border-color: rgba(108, 99, 255, 0.2);
            transform: translateY(-2px);
        }
        .mini-stat .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .mini-stat .value { font-size: 18px; font-weight: 700; }
        .mini-stat .label { color: var(--text-secondary); font-size: 11px; }

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

        .avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s ease-in-out infinite;
        }
        .status-dot.green { background: var(--secondary); }
        .status-dot.yellow { background: var(--warning); }
        .status-dot.red { background: var(--accent); }

        .logo-header {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-header img {
            height: 44px;
            filter: drop-shadow(0 4px 20px rgba(108, 99, 255, 0.1));
        }
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: white;
        }

        @media (max-width: 768px) {
            body { padding: 12px; }
            .page-title { font-size: 22px; }
            .stat-card { padding: 16px; }
            .stat-card .number { font-size: 22px; }
            .mini-stat { padding: 10px 12px; }
            .logo-header img { height: 32px; }
        }
        @media (max-width: 480px) {
            .stat-card .number { font-size: 18px; }
            .stat-card .icon { width: 36px; height: 36px; font-size: 16px; }
        }
    </style>
</head>
<body>

<div class="max-w-7xl mx-auto">

    <!-- ===== HEADER AVEC LOGO ET NOM ADMIN ===== -->
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <div class="logo-header">
            <img src="../assets/images/garagelogo.png" alt="GaragePoint">
            <div>
                <h1 class="page-title">GaragePoint</h1>
                <p class="text-secondary text-sm flex items-center gap-2">
                    <span class="admin-avatar" style="width:28px;height:28px;font-size:12px;">HS</span>
                    <span class="font-semibold text-white">Hichem Slimani</span>
                    <span class="text-muted">·</span>
                    <span class="text-muted"><?php echo date('l d F Y', time()); ?></span>
                    <span class="text-muted">·</span>
                    <span class="text-xs text-muted" id="clock">00:00:00</span>
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="employes.php" class="btn btn-secondary">
                <i class="fas fa-users"></i> Employés
            </a>
            <a href="pointages.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> Historique
            </a>
            <a href="recap.php" class="btn btn-secondary">
                <i class="fas fa-file-alt"></i> Récap
            </a>
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <!-- ===== STATS PRINCIPALES ===== -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card glass-card">
            <div class="icon" style="background:rgba(108,99,255,0.1);color:var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="number"><?php echo $totalEmployes; ?></div>
            <div class="label">Total employés</div>
        </div>
        <div class="stat-card glass-card">
            <div class="icon" style="background:rgba(0,212,170,0.1);color:var(--secondary);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="number" style="color:var(--secondary);"><?php echo $present; ?></div>
            <div class="label">Présents aujourd'hui</div>
            <div class="change up"><i class="fas fa-arrow-up"></i> <?php echo $tauxPresence; ?>%</div>
        </div>
        <div class="stat-card glass-card">
            <div class="icon" style="background:rgba(255,217,61,0.1);color:var(--warning);">
                <i class="fas fa-coffee"></i>
            </div>
            <div class="number" style="color:var(--warning);"><?php echo $enPause; ?></div>
            <div class="label">En pause</div>
        </div>
        <div class="stat-card glass-card">
            <div class="icon" style="background:rgba(255,107,107,0.1);color:var(--accent);">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="number" style="color:var(--accent);"><?php echo $absents; ?></div>
            <div class="label">Absents aujourd'hui</div>
            <div class="change down"><i class="fas fa-arrow-down"></i> <?php echo $totalEmployes > 0 ? round(($absents / $totalEmployes) * 100) : 0; ?>%</div>
        </div>
    </div>

    <!-- ===== MINI STATS ===== -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(0,212,170,0.1);color:var(--secondary);">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <div class="value" style="color:var(--secondary);"><?php echo $arrivees; ?></div>
                <div class="label">Arrivées</div>
            </div>
        </div>
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(255,217,61,0.1);color:var(--warning);">
                <i class="fas fa-coffee"></i>
            </div>
            <div>
                <div class="value" style="color:var(--warning);"><?php echo $pauses; ?></div>
                <div class="label">Pauses</div>
            </div>
        </div>
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(108,99,255,0.1);color:var(--primary);">
                <i class="fas fa-play"></i>
            </div>
            <div>
                <div class="value" style="color:var(--primary);"><?php echo $departs; ?></div>
                <div class="label">Départs</div>
            </div>
        </div>
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(255,107,107,0.1);color:var(--accent);">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="value" style="color:var(--accent);"><?php echo $retards; ?></div>
                <div class="label">Retards</div>
            </div>
        </div>
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(78,205,196,0.1);color:var(--info);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="value" style="color:var(--info);"><?php echo $heurePointe['h'] ?? '-'; ?>h</div>
                <div class="label">Heure de pointe</div>
            </div>
        </div>
        <div class="mini-stat glass-card">
            <div class="icon-circle" style="background:rgba(255,107,107,0.1);color:var(--accent);">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="value" style="color:var(--accent);"><?php echo $heureMoyenne ?: '-'; ?></div>
                <div class="label">Moyenne arrivée</div>
            </div>
        </div>
    </div>

    <!-- ===== PRÉSENTS + TOP PONCTUELS ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Présents -->
        <div class="glass-card p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-users text-green-400 mr-2"></i>
                Présents en ce moment <span class="text-sm text-muted font-normal">(<?php echo $present; ?>)</span>
            </h3>
            <?php if (count($employesPresent) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-[300px] overflow-y-auto pr-2">
                    <?php foreach ($employesPresent as $emp): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-white/5 transition border border-border-color/30">
                            <div class="flex items-center gap-3">
                                <span class="avatar-sm">
                                    <?php echo strtoupper(substr($emp['prenom'], 0, 1) . substr($emp['nom'], 0, 1)); ?>
                                </span>
                                <div>
                                    <div class="font-medium text-sm text-white"><?php echo $emp['prenom'] . ' ' . $emp['nom']; ?></div>
                                    <div class="text-xs text-muted"><?php echo $emp['poste'] ?? 'Employé'; ?></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted">
                                    <?php echo $emp['arrivee'] ? date('H:i', strtotime($emp['arrivee'])) : '-'; ?>
                                </span>
                                <?php if ($emp['pause']): ?>
                                    <span class="badge badge-pause" style="font-size:10px;padding:2px 8px;">
                                        <i class="fas fa-coffee"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="status-dot green"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-muted">
                    <i class="fas fa-users text-4xl block mb-2 opacity-20"></i>
                    <p class="text-sm">Aucun employé présent</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top ponctuels -->
        <div class="glass-card p-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-trophy text-yellow-400 mr-2"></i>
                Top ponctualité
            </h3>

            <?php if ($plusPonctuel): ?>
                <div class="p-4 rounded-lg bg-yellow-500/5 border border-yellow-500/10 mb-4 text-center">
                    <div class="text-3xl mb-1">🏆</div>
                    <div class="font-bold text-white"><?php echo $plusPonctuel['prenom'] . ' ' . $plusPonctuel['nom']; ?></div>
                    <div class="text-xs text-muted"><?php echo $plusPonctuel['nb']; ?> arrivées avant 8h30</div>
                </div>
            <?php endif; ?>

            <?php if (count($topPonctuels) > 0): ?>
                <div class="space-y-2">
                    <?php foreach ($topPonctuels as $index => $t): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">
                                    <?php if ($index === 0): ?>🥇
                                    <?php elseif ($index === 1): ?>🥈
                                    <?php elseif ($index === 2): ?>🥉
                                    <?php else: ?><?php echo $index + 1; ?>.
                                    <?php endif; ?>
                                </span>
                                <span class="text-sm text-white"><?php echo $t['prenom'] . ' ' . $t['nom']; ?></span>
                            </div>
                            <span class="badge badge-arrivee" style="font-size:10px;"><?php echo $t['nb']; ?>x</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted text-sm">
                    <i class="fas fa-info-circle block mb-1"></i>
                    Aucune donnée de ponctualité
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-border-color">
                <div class="text-center p-2 rounded-lg bg-white/5">
                    <div class="text-xs text-muted">🏅 Premier arrivé</div>
                    <div class="font-bold text-green-400 text-sm">
                        <?php if ($premierArrive): ?>
                            <?php echo $premierArrive['prenom'] . ' ' . $premierArrive['nom']; ?>
                            <span class="text-xs text-muted font-normal block"><?php echo date('H:i', strtotime($premierArrive['heure'])); ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center p-2 rounded-lg bg-white/5">
                    <div class="text-xs text-muted">⏰ Dernier arrivé</div>
                    <div class="font-bold text-red-400 text-sm">
                        <?php if ($dernierArrive): ?>
                            <?php echo $dernierArrive['prenom'] . ' ' . $dernierArrive['nom']; ?>
                            <span class="text-xs text-muted font-normal block"><?php echo date('H:i', strtotime($dernierArrive['heure'])); ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== DERNIERS POINTAGES ===== -->
    <div class="glass-card p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-clock text-blue-400 mr-2"></i>
                Derniers pointages
            </h3>
            <a href="pointages.php" class="text-xs text-muted hover:text-primary transition">
                Voir tout <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <?php if (count($pointages) > 0): ?>
            <div class="overflow-x-auto">
                <table class="table-glass w-full">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Type</th>
                            <th>Heure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pointages as $p): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="avatar-sm">
                                            <?php echo strtoupper(substr($p['prenom'], 0, 1) . substr($p['nom'], 0, 1)); ?>
                                        </span>
                                        <span class="text-white text-sm"><?php echo $p['prenom'] . ' ' . $p['nom']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $p['type']; ?>">
                                        <i class="fas fa-<?php echo $p['type'] == 'arrivee' ? 'sign-in-alt' : ($p['type'] == 'pause' ? 'coffee' : ($p['type'] == 'reprise' ? 'play' : 'sign-out-alt')); ?>"></i>
                                        <?php echo ucfirst($p['type']); ?>
                                    </span>
                                </td>
                                <td class="font-mono text-sm"><?php echo date('H:i', strtotime($p['heure'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-muted">
                <i class="fas fa-clock text-4xl block mb-2 opacity-20"></i>
                <p class="text-sm">Aucun pointage aujourd'hui</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="text-center text-muted text-xs py-4 border-t border-border-color mt-4">
        <i class="fas fa-sync-alt mr-1"></i>
        Dernière mise à jour : <span id="lastUpdate"><?php echo date('H:i:s'); ?></span>
        · Auto-refresh toutes les 30 secondes
    </div>

</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = now.toLocaleTimeString('fr-FR');
        document.getElementById('lastUpdate').textContent = now.toLocaleTimeString('fr-FR');
    }
    setInterval(updateClock, 1000);
    updateClock();
    setTimeout(() => { location.reload(); }, 30000);
</script>

</body>
</html>