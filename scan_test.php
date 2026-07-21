<?php
// scan_test.php - Version debug
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Scan</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #1a1a2e; color: white; }
        .container { max-width: 600px; margin: 0 auto; text-align: center; }
        input, button { padding: 15px; font-size: 18px; margin: 10px; border-radius: 10px; border: none; }
        input { width: 200px; }
        button { background: #00ff88; color: #1a1a2e; font-weight: bold; cursor: pointer; }
        .result { background: #16213e; padding: 20px; border-radius: 10px; margin-top: 20px; text-align: left; }
        .success { color: #00ff88; }
        .error { color: #ff6b6b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Scan QR Code</h1>
        <p>Entre un code QR manuellement pour tester :</p>
        
        <form method="GET">
            <input type="text" name="qr" placeholder="EMP001" value="<?php echo $_GET['qr'] ?? ''; ?>">
            <button type="submit">Tester</button>
        </form>
        
        <?php if (isset($_GET['qr']) && !empty($_GET['qr'])): 
            $qr = $_GET['qr'];
            $stmt = $pdo->prepare("SELECT * FROM employes WHERE qr_token = ?");
            $stmt->execute([$qr]);
            $employe = $stmt->fetch();
        ?>
            <div class="result">
                <h3>Résultat pour "<?php echo htmlspecialchars($qr); ?>" :</h3>
                <?php if ($employe): ?>
                    <p class="success">✅ Employé trouvé !</p>
                    <pre>
ID : <?php echo $employe['id']; ?>
Nom : <?php echo $employe['nom']; ?>
Prénom : <?php echo $employe['prenom']; ?>
Poste : <?php echo $employe['poste']; ?>
QR Code : <?php echo $employe['qr_token']; ?>
                    </pre>
                    
                    <h4>Dernier pointage :</h4>
                    <?php
                    $stmt2 = $pdo->prepare("SELECT * FROM pointages WHERE employe_id = ? AND date = CURDATE() ORDER BY created_at DESC LIMIT 1");
                    $stmt2->execute([$employe['id']]);
                    $dernier = $stmt2->fetch();
                    ?>
                    <?php if ($dernier): ?>
                        <p>Type : <?php echo $dernier['type']; ?></p>
                        <p>Heure : <?php echo $dernier['heure']; ?></p>
                    <?php else: ?>
                        <p>Aucun pointage aujourd'hui</p>
                    <?php endif; ?>
                    
                    <h4>Pointage automatique :</h4>
                    <?php
                    // Déterminer le prochain type
                    if (!$dernier) {
                        $type = 'arrivee';
                    } else {
                        $types = ['arrivee' => 'pause', 'pause' => 'reprise', 'reprise' => 'depart', 'depart' => 'arrivee'];
                        $type = $types[$dernier['type']] ?? 'arrivee';
                    }
                    ?>
                    <p>👉 Prochain pointage : <strong><?php echo $type; ?></strong></p>
                    
                    <form method="POST" action="api/pointage.php">
                        <input type="hidden" name="employe_id" value="<?php echo $employe['id']; ?>">
                        <input type="hidden" name="type" value="<?php echo $type; ?>">
                        <button type="submit" style="background: #ffd700; color: #1a1a2e;">
                            ⚡ Pointer maintenant (<?php echo $type; ?>)
                        </button>
                    </form>
                    
                <?php else: ?>
                    <p class="error">❌ Employé non trouvé avec le QR Code "<?php echo htmlspecialchars($qr); ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <h3>Employés existants :</h3>
        <?php
        $stmt = $pdo->query("SELECT id, nom, prenom, qr_token FROM employes");
        $employes = $stmt->fetchAll();
        foreach ($employes as $e) {
            echo "- " . $e['prenom'] . ' ' . $e['nom'] . ' → ' . $e['qr_token'] . '<br>';
        }
        ?>
    </div>
</body>
</html>