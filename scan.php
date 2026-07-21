<?php
// scan.php - Version Ultra WOW avec toutes les animations
session_start();
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pointage Pro - GaragePoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== BASE ===== */
        body {
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: var(--font);
            background-image:
                radial-gradient(ellipse at 30% 20%, rgba(108, 99, 255, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 80%, rgba(0, 212, 170, 0.08) 0%, transparent 50%);
        }

        .scan-wrapper {
            width: 100%;
            max-width: 520px;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        /* ===== CARD ===== */
        .scan-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .scan-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 30% 50%, rgba(108, 99, 255, 0.03), transparent 70%);
            pointer-events: none;
            animation: rotate 20s linear infinite;
        }

        /* ===== HEADER ===== */
        .scan-header {
            text-align: center;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .scan-header .logo-wrapper {
            display: inline-block;
            padding: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.1), rgba(0, 212, 170, 0.1));
            border: 1px solid var(--border-color);
            margin-bottom: 12px;
            animation: float 3s ease-in-out infinite;
        }

        .scan-header .logo-wrapper img {
            height: 52px;
            width: 52px;
            object-fit: contain;
            display: block;
        }

        .scan-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 30%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .scan-header p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 2px;
        }

        .scan-header .status-badge {
            display: inline-block;
            padding: 5px 18px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            background: rgba(0, 212, 170, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(0, 212, 170, 0.15);
            animation: pulseGlow 2s ease-in-out infinite;
        }

        .scan-header .status-badge i {
            font-size: 8px;
            vertical-align: middle;
            animation: pulse 1.5s infinite;
        }

        /* ===== PIN DISPLAY ===== */
        .pin-display {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 20px 24px;
            text-align: center;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }

        .pin-display:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 30px rgba(108, 99, 255, 0.08);
        }

        .pin-digits {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin: 8px 0;
        }

        .pin-digit {
            width: 60px;
            height: 72px;
            background: var(--bg-primary);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            border: 2px solid var(--border-color);
            transition: var(--transition);
            position: relative;
        }

        .pin-digit.filled {
            border-color: var(--secondary);
            color: var(--secondary);
            box-shadow: 0 0 30px rgba(0, 212, 170, 0.08);
        }

        .pin-digit.filled::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 20%;
            width: 60%;
            height: 2px;
            background: var(--secondary);
            border-radius: 2px;
            animation: scaleIn 0.3s ease;
        }

        .pin-digit.active {
            border-color: var(--primary);
            box-shadow: 0 0 30px rgba(108, 99, 255, 0.1);
            animation: pulseGlow 1.5s ease-in-out infinite;
        }

        .pin-status {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 10px;
            transition: var(--transition);
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .pin-status.success {
            color: var(--secondary);
            animation: fadeInUp 0.3s ease;
        }

        .pin-status.error {
            color: var(--accent);
            animation: fadeInUp 0.3s ease;
        }

        .pin-status .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: rotate 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        /* ===== NUMPAD ===== */
        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .numpad button {
            padding: 18px;
            font-size: 26px;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            background: var(--bg-input);
            color: white;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .numpad button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .numpad button:active::after {
            width: 200px;
            height: 200px;
        }

        .numpad button:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .numpad button:active {
            transform: scale(0.94);
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .numpad .btn-clear {
            background: rgba(255, 107, 107, 0.08);
            color: var(--accent);
            border-color: rgba(255, 107, 107, 0.08);
        }

        .numpad .btn-clear:hover {
            background: rgba(255, 107, 107, 0.15);
            border-color: rgba(255, 107, 107, 0.15);
        }

        .numpad .btn-clear:active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .numpad .btn-delete {
            background: rgba(255, 217, 61, 0.08);
            color: var(--warning);
            border-color: rgba(255, 217, 61, 0.08);
        }

        .numpad .btn-delete:hover {
            background: rgba(255, 217, 61, 0.15);
            border-color: rgba(255, 217, 61, 0.15);
        }

        .numpad .btn-delete:active {
            background: var(--warning);
            color: var(--bg-primary);
            border-color: var(--warning);
        }

        /* ===== ACTIONS ===== */
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .actions button {
            padding: 16px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .actions button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .actions button:active::after {
            width: 300px;
            height: 300px;
        }

        .actions button:active {
            transform: scale(0.95);
        }

        .actions button:disabled {
            opacity: 0.25;
            cursor: not-allowed;
            transform: none !important;
        }

        .actions button:disabled::after {
            display: none;
        }

        .actions .btn-arrivee {
            background: linear-gradient(135deg, var(--secondary), #00b894);
            box-shadow: 0 4px 20px rgba(0, 212, 170, 0.2);
        }
        .actions .btn-arrivee:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 170, 0.35);
        }

        .actions .btn-pause {
            background: linear-gradient(135deg, var(--warning), #f0b84a);
            color: var(--bg-primary);
            box-shadow: 0 4px 20px rgba(255, 217, 61, 0.2);
        }
        .actions .btn-pause:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 217, 61, 0.35);
        }

        .actions .btn-reprise {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.2);
        }
        .actions .btn-reprise:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.35);
        }

        .actions .btn-depart {
            background: linear-gradient(135deg, var(--accent), #e17055);
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.2);
        }
        .actions .btn-depart:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.35);
        }

        /* ===== USER INFO ===== */
        .user-info {
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.06), rgba(0, 212, 170, 0.04));
            border: 1px solid rgba(108, 99, 255, 0.12);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            margin-top: 18px;
            text-align: center;
            display: none;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.4s ease forwards;
        }

        .user-info.show {
            display: block;
        }

        .user-info .avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin: 0 auto 8px;
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.2);
            animation: scaleIn 0.4s var(--bounce);
        }

        .user-info .name {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .user-info .poste {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 2px;
        }

        .user-info .code {
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            margin-top: 4px;
            letter-spacing: 1px;
        }

        /* ===== FOOTER ===== */
        .scan-footer {
            text-align: center;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .scan-footer a {
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .scan-footer a:hover {
            color: var(--primary);
        }

        /* ===== CONFETTI ===== */
        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            animation: confettiFall 2s ease-out forwards;
        }

        @keyframes confettiFall {
            0% {
                opacity: 1;
                transform: translateY(-20px) rotate(0deg) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(100vh) rotate(720deg) scale(0.3);
            }
        }

        /* ===== SUCCESS OVERLAY ===== */
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            animation: fadeInUp 0.4s ease;
        }

        .success-overlay.show {
            display: flex;
        }

        .success-overlay .check {
            font-size: 100px;
            color: var(--secondary);
            animation: scaleIn 0.6s var(--bounce) forwards;
            filter: drop-shadow(0 0 40px rgba(0, 212, 170, 0.3));
        }

        .success-overlay .s-name {
            font-size: 40px;
            font-weight: 700;
            color: white;
            margin-top: 16px;
            animation: fadeInUp 0.5s ease 0.2s forwards;
            opacity: 0;
        }

        .success-overlay .s-action {
            font-size: 24px;
            color: var(--secondary);
            margin-top: 4px;
            animation: fadeInUp 0.5s ease 0.3s forwards;
            opacity: 0;
        }

        .success-overlay .s-time {
            font-size: 16px;
            color: var(--text-muted);
            margin-top: 8px;
            animation: fadeInUp 0.5s ease 0.4s forwards;
            opacity: 0;
        }

        .success-overlay .s-badge {
            margin-top: 16px;
            padding: 8px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            color: var(--text-secondary);
            font-size: 14px;
            animation: fadeInUp 0.5s ease 0.5s forwards;
            opacity: 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .scan-card {
                padding: 20px;
                border-radius: var(--radius-sm);
            }
            .pin-digit {
                width: 48px;
                height: 60px;
                font-size: 26px;
            }
            .pin-digits {
                gap: 10px;
            }
            .numpad button {
                padding: 14px;
                font-size: 22px;
            }
            .actions button {
                font-size: 13px;
                padding: 14px;
            }
            .success-overlay .s-name {
                font-size: 28px;
            }
            .success-overlay .check {
                font-size: 72px;
            }
            .scan-header h1 {
                font-size: 22px;
            }
            .scan-header .logo-wrapper img {
                height: 40px;
                width: 40px;
            }
            .user-info .name {
                font-size: 17px;
            }
            .user-info .avatar {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- SUCCESS OVERLAY -->
    <!-- ============================================ -->
    <div class="success-overlay" id="successOverlay">
        <div class="check"><i class="fas fa-check-circle"></i></div>
        <div class="s-name" id="sName">Jean Dupont</div>
        <div class="s-action" id="sAction">✅ Arrivée enregistrée</div>
        <div class="s-time" id="sTime">à 08:15</div>
        <div class="s-badge" id="sBadge">Mécanicien • PIN: 1234</div>
    </div>

    <!-- ============================================ -->
    <!-- CONFETTI -->
    <!-- ============================================ -->
    <div class="confetti-container" id="confettiContainer"></div>

    <!-- ============================================ -->
    <!-- MAIN -->
    <!-- ============================================ -->
    <div class="scan-wrapper">
        <div class="scan-card">

            <!-- HEADER -->
            <div class="scan-header">
                <div class="logo-wrapper">
                    <img src="assets/images/garagelogo.png" alt="GaragePoint">
                </div>
                <h1>GaragePoint</h1>
                <p>Pointage sécurisé par code PIN</p>
                <span class="status-badge">
                    <i class="fas fa-circle"></i> Prêt
                </span>
            </div>

            <!-- PIN DISPLAY -->
            <div class="pin-display">
                <div class="pin-digits">
                    <div class="pin-digit" id="pin1"></div>
                    <div class="pin-digit" id="pin2"></div>
                    <div class="pin-digit" id="pin3"></div>
                    <div class="pin-digit" id="pin4"></div>
                </div>
                <div class="pin-status" id="pinStatus">
                    <i class="fas fa-key" style="opacity:0.3;margin-right:6px;"></i>
                    Entrez 4 chiffres
                </div>
            </div>

            <!-- NUMPAD -->
            <div class="numpad">
                <button onclick="addDigit('1')">1</button>
                <button onclick="addDigit('2')">2</button>
                <button onclick="addDigit('3')">3</button>
                <button onclick="addDigit('4')">4</button>
                <button onclick="addDigit('5')">5</button>
                <button onclick="addDigit('6')">6</button>
                <button onclick="addDigit('7')">7</button>
                <button onclick="addDigit('8')">8</button>
                <button onclick="addDigit('9')">9</button>
                <button class="btn-clear" onclick="clearPin()">
                    <i class="fas fa-times"></i>
                </button>
                <button onclick="addDigit('0')">0</button>
                <button class="btn-delete" onclick="deleteDigit()">
                    <i class="fas fa-delete-left"></i>
                </button>
            </div>

            <!-- ACTIONS -->
            <div class="actions">
                <button class="btn-arrivee" id="btnArrivee" disabled onclick="pointer('arrivee')">
                    <i class="fas fa-sign-in-alt"></i> Arrivée
                </button>
                <button class="btn-pause" id="btnPause" disabled onclick="pointer('pause')">
                    <i class="fas fa-coffee"></i> Pause
                </button>
                <button class="btn-reprise" id="btnReprise" disabled onclick="pointer('reprise')">
                    <i class="fas fa-play"></i> Reprise
                </button>
                <button class="btn-depart" id="btnDepart" disabled onclick="pointer('depart')">
                    <i class="fas fa-sign-out-alt"></i> Départ
                </button>
            </div>

            <!-- USER INFO -->
            <div class="user-info" id="userInfo">
                <div class="avatar" id="userAvatar">JD</div>
                <div class="name" id="userName">Jean Dupont</div>
                <div class="poste" id="userPoste">Mécanicien</div>
                <div class="code" id="userCode">PIN: 1234</div>
            </div>

            <!-- FOOTER -->
            <div class="scan-footer">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>

        </div>
    </div>

    <!-- ============================================ -->
    <!-- SCRIPTS -->
    <!-- ============================================ -->
    <script>
        let pin = '';
        let currentUser = null;
        let isProcessing = false;

        // ===== SONS =====
        function playSound(type) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                if (type === 'success') {
                    // Son mélodieux à 3 notes
                    osc.frequency.value = 880;
                    osc.type = 'sine';
                    gain.gain.value = 0.2;
                    osc.start();
                    setTimeout(() => { osc.frequency.value = 1100; }, 100);
                    setTimeout(() => { osc.frequency.value = 1320; }, 200);
                    setTimeout(() => { osc.stop(); }, 350);
                } else if (type === 'error') {
                    osc.frequency.value = 440;
                    osc.type = 'sawtooth';
                    gain.gain.value = 0.12;
                    osc.start();
                    setTimeout(() => { osc.stop(); }, 300);
                } else if (type === 'click') {
                    osc.frequency.value = 660;
                    osc.type = 'sine';
                    gain.gain.value = 0.06;
                    osc.start();
                    setTimeout(() => { osc.stop(); }, 60);
                } else if (type === 'scan') {
                    osc.frequency.value = 770;
                    osc.type = 'sine';
                    gain.gain.value = 0.15;
                    osc.start();
                    setTimeout(() => { osc.frequency.value = 990; }, 80);
                    setTimeout(() => { osc.stop(); }, 200);
                }
            } catch(e) { /* Silencieux si le son ne fonctionne pas */ }
        }

        // ===== VIBRATION =====
        function vibrate() {
            if (navigator.vibrate) {
                navigator.vibrate(30);
            }
        }

        // ===== CONFETTI =====
        function launchConfetti() {
            const container = document.getElementById('confettiContainer');
            const colors = ['#6C63FF', '#00D4AA', '#FF6B6B', '#FFD93D', '#4ECDC4', '#FF6B9D', '#ff9ff3', '#54a0ff'];

            for (let i = 0; i < 60; i++) {
                const el = document.createElement('div');
                el.className = 'confetti';
                el.style.left = Math.random() * 100 + '%';
                el.style.background = colors[Math.floor(Math.random() * colors.length)];
                el.style.width = (Math.random() * 8 + 4) + 'px';
                el.style.height = (Math.random() * 8 + 4) + 'px';
                el.style.animationDuration = (Math.random() * 1.5 + 1.2) + 's';
                el.style.animationDelay = (Math.random() * 0.6) + 's';
                el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                el.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
                container.appendChild(el);

                setTimeout(() => el.remove(), 3000);
            }
        }

        // ===== SUCCESS OVERLAY =====
        function showSuccess(name, type, heure, poste, pinCode) {
            const labels = {
                'arrivee': '✅ Arrivée enregistrée',
                'pause': '☕ Pause enregistrée',
                'reprise': '▶️ Reprise enregistrée',
                'depart': '🚗 Départ enregistré'
            };

            const emojis = {
                'arrivee': '👋',
                'pause': '☕',
                'reprise': '▶️',
                'depart': '🚗'
            };

            document.getElementById('sName').textContent = name;
            document.getElementById('sAction').textContent = labels[type] || 'Pointage enregistré';
            document.getElementById('sTime').textContent = 'à ' + heure;
            document.getElementById('sBadge').textContent = (poste || 'Employé') + ' • PIN: ' + pinCode;

            document.getElementById('successOverlay').classList.add('show');
            playSound('success');
            vibrate();
            launchConfetti();

            setTimeout(() => {
                document.getElementById('successOverlay').classList.remove('show');
                clearPin();
                isProcessing = false;
            }, 2200);
        }

        // ===== PIN DISPLAY =====
        function updatePinDisplay() {
            for (let i = 0; i < 4; i++) {
                const digit = document.getElementById('pin' + (i + 1));
                if (i < pin.length) {
                    digit.textContent = pin[i];
                    digit.className = 'pin-digit filled';
                } else {
                    digit.textContent = '';
                    digit.className = 'pin-digit';
                    if (i === pin.length) digit.classList.add('active');
                }
            }

            const status = document.getElementById('pinStatus');
            if (pin.length === 0) {
                status.innerHTML = '<i class="fas fa-key" style="opacity:0.3;margin-right:6px;"></i>Entrez 4 chiffres';
                status.className = 'pin-status';
            } else if (pin.length < 4) {
                status.innerHTML = '🔒 ' + (4 - pin.length) + ' chiffre(s) restant(s)';
                status.className = 'pin-status';
            } else {
                status.innerHTML = '✅ Code complet';
                status.className = 'pin-status success';
            }
        }

        function addDigit(d) {
            if (pin.length >= 4) return;
            pin += d;
            playSound('click');
            updatePinDisplay();

            if (pin.length === 4) {
                setTimeout(checkPin, 300);
            }
        }

        function deleteDigit() {
            if (pin.length === 0) return;
            pin = pin.slice(0, -1);
            playSound('click');
            updatePinDisplay();
            document.getElementById('userInfo').classList.remove('show');
            enableActions(false);
            currentUser = null;
            document.getElementById('pinStatus').innerHTML = '<i class="fas fa-key" style="opacity:0.3;margin-right:6px;"></i>Entrez 4 chiffres';
            document.getElementById('pinStatus').className = 'pin-status';
        }

        function clearPin() {
            pin = '';
            updatePinDisplay();
            document.getElementById('userInfo').classList.remove('show');
            enableActions(false);
            currentUser = null;
            document.getElementById('pinStatus').innerHTML = '<i class="fas fa-key" style="opacity:0.3;margin-right:6px;"></i>Entrez 4 chiffres';
            document.getElementById('pinStatus').className = 'pin-status';
        }

        function enableActions(enabled) {
            document.getElementById('btnArrivee').disabled = !enabled;
            document.getElementById('btnPause').disabled = !enabled;
            document.getElementById('btnReprise').disabled = !enabled;
            document.getElementById('btnDepart').disabled = !enabled;
        }

        // ===== CHECK PIN =====
        function checkPin() {
            if (pin.length !== 4) return;

            const status = document.getElementById('pinStatus');
            status.innerHTML = '<span class="spinner"></span>Vérification...';
            status.className = 'pin-status';
            playSound('scan');

            fetch(`api/employe_by_pin.php?pin=${encodeURIComponent(pin)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        currentUser = data.employe;

                        // Avatar
                        const avatar = document.getElementById('userAvatar');
                        avatar.textContent = (data.employe.prenom[0] + data.employe.nom[0]).toUpperCase();

                        document.getElementById('userName').textContent = data.employe.prenom + ' ' + data.employe.nom;
                        document.getElementById('userPoste').textContent = data.employe.poste || 'Employé';
                        document.getElementById('userCode').textContent = 'PIN: ' + data.employe.pin_code;

                        document.getElementById('userInfo').classList.add('show');
                        enableActions(true);

                        status.innerHTML = '✅ ' + data.employe.prenom + ' reconnu(e)';
                        status.className = 'pin-status success';
                        playSound('success');
                        vibrate();
                    } else {
                        status.innerHTML = '❌ PIN incorrect';
                        status.className = 'pin-status error';
                        playSound('error');
                        vibrate();
                        setTimeout(() => clearPin(), 1500);
                    }
                })
                .catch(() => {
                    status.innerHTML = '❌ Erreur de connexion';
                    status.className = 'pin-status error';
                    playSound('error');
                    setTimeout(() => clearPin(), 1500);
                });
        }

        // ===== POINTER =====
        function pointer(type) {
            if (!currentUser || isProcessing) return;
            isProcessing = true;

            const status = document.getElementById('pinStatus');
            status.innerHTML = '<span class="spinner"></span>Enregistrement...';
            status.className = 'pin-status';

            fetch('api/pointage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employe_id: currentUser.id,
                    type: type
                })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    const heure = new Date().toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    showSuccess(
                        currentUser.prenom + ' ' + currentUser.nom,
                        type,
                        heure,
                        currentUser.poste,
                        currentUser.pin_code
                    );
                } else {
                    status.innerHTML = '❌ Erreur d\'enregistrement';
                    status.className = 'pin-status error';
                    playSound('error');
                    setTimeout(() => {
                        clearPin();
                        isProcessing = false;
                    }, 1500);
                }
            })
            .catch(() => {
                status.innerHTML = '❌ Erreur de connexion';
                status.className = 'pin-status error';
                playSound('error');
                setTimeout(() => {
                    clearPin();
                    isProcessing = false;
                }, 1500);
            });
        }

        // ===== KEYBOARD SUPPORT =====
        document.addEventListener('keydown', function(e) {
            if (e.key >= '0' && e.key <= '9') {
                addDigit(e.key);
            } else if (e.key === 'Backspace') {
                deleteDigit();
            } else if (e.key === 'Escape') {
                clearPin();
            } else if (e.key === 'Enter') {
                // Si on a un utilisateur reconnu, pointer automatiquement (Arrivée par défaut)
                if (currentUser && !isProcessing) {
                    // Déterminer le type automatiquement
                    fetch(`api/dernier_pointage.php?employe_id=${currentUser.id}`)
                        .then(r => r.json())
                        .then(data => {
                            let type = 'arrivee';
                            if (data.dernier) {
                                const d = data.dernier.type;
                                if (d === 'depart') type = 'arrivee';
                                else if (d === 'arrivee') type = 'pause';
                                else if (d === 'pause') type = 'reprise';
                                else if (d === 'reprise') type = 'depart';
                            }
                            pointer(type);
                        })
                        .catch(() => pointer('arrivee'));
                }
            }
        });

        // ===== INIT =====
        updatePinDisplay();
    </script>

</body>
</html>