<?php
// admin/login.php - Connexion admin
session_start();
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté en admin, rediriger
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $password == $user['mot_de_passe']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'admin';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "❌ Identifiants incorrects";
        }
    } else {
        $error = "❌ Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image:
                radial-gradient(ellipse at 10% 20%, rgba(108, 99, 255, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(0, 212, 170, 0.06) 0%, transparent 50%);
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(20px);
            text-align: center;
        }
        .login-card .logo {
            margin-bottom: 24px;
        }
        .login-card .logo img {
            height: 64px;
            margin-bottom: 8px;
            filter: drop-shadow(0 4px 20px rgba(108, 99, 255, 0.15));
        }
        .login-card .logo h1 {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        .login-card .logo p {
            color: var(--text-secondary);
            font-size: 13px;
            margin-top: 2px;
        }
        .input-group {
            margin-bottom: 16px;
            text-align: left;
        }
        .input-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .input-group label i {
            color: var(--primary);
            margin-right: 8px;
        }
        .input-glass {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            padding: 12px 16px;
            font-size: 14px;
            transition: var(--transition);
            width: 100%;
            outline: none;
        }
        .input-glass:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.15);
        }
        .input-glass::placeholder {
            color: var(--text-muted);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(108, 99, 255, 0.3);
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .error-message {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: var(--accent);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }
        .back-link {
            display: inline-block;
            margin-top: 16px;
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            transition: var(--transition);
        }
        .back-link:hover {
            color: var(--primary);
        }
        .admin-shield {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(108, 99, 255, 0.15);
            color: var(--primary);
            margin-bottom: 12px;
        }
        .return-pointage {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            transition: var(--transition);
            margin-top: 12px;
        }
        .return-pointage:hover {
            color: var(--primary);
        }
        @media (max-width: 480px) {
            .login-card { padding: 24px; }
            .login-card .logo img { height: 48px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">

        <div class="logo">
            <img src="../assets/images/garagelogo.png" alt="GaragePoint">
            <h1>Administration</h1>
            <p>Espace réservé au gérant</p>
        </div>

        <span class="admin-shield"><i class="fas fa-shield-alt mr-1"></i> Accès sécurisé</span>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label><i class="fas fa-user"></i>Nom d'utilisateur</label>
                <input type="text" name="username" class="input-glass" placeholder="Nom d'utilisateur" required>
            </div>
            <div class="input-group">
                <label><i class="fas fa-lock"></i>Mot de passe</label>
                <input type="password" name="password" class="input-glass" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
            </button>
        </form>

        <a href="../index.php" class="return-pointage">
            <i class="fas fa-arrow-left"></i> Retour au pointage
        </a>

    </div>
</div>

</body>
</html>