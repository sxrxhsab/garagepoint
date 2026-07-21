<?php
// admin/employes.php - Version Ultra WOW
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

$message = '';
$message_type = '';

// Ajouter
if (isset($_POST['ajouter'])) {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $poste = $_POST['poste'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $pin = $_POST['pin_code'] ?? '';

    if (!preg_match('/^[0-9]{4}$/', $pin)) {
        $message = "❌ Le PIN doit être 4 chiffres";
        $message_type = 'error';
    } elseif (empty($nom) || empty($prenom)) {
        $message = "❌ Nom et prénom obligatoires";
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("INSERT INTO employes (nom, prenom, poste, telephone, pin_code) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$nom, $prenom, $poste, $telephone, $pin])) {
            $message = "✅ Employé ajouté ! PIN: $pin";
            $message_type = 'success';
        } else {
            $message = "❌ Erreur";
            $message_type = 'error';
        }
    }
}

// Modifier
if (isset($_POST['modifier'])) {
    $id = $_POST['id'] ?? 0;
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $poste = $_POST['poste'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $pin = $_POST['pin_code'] ?? '';

    if (!preg_match('/^[0-9]{4}$/', $pin)) {
        $message = "❌ Le PIN doit être 4 chiffres";
        $message_type = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE employes SET nom=?, prenom=?, poste=?, telephone=?, pin_code=? WHERE id=?");
        if ($stmt->execute([$nom, $prenom, $poste, $telephone, $pin, $id])) {
            $message = "✅ Employé modifié !";
            $message_type = 'success';
        }
    }
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $stmt = $pdo->prepare("DELETE FROM employes WHERE id = ?");
    $stmt->execute([$_GET['supprimer']]);
    $message = "✅ Employé supprimé";
    $message_type = 'success';
}

$employes = $pdo->query("SELECT * FROM employes ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employés Pro - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { padding: 24px; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
            gap: 12px;
        }
        .form-grid .full {
            grid-column: span 5;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
            .form-grid .full {
                grid-column: span 2;
            }
        }
        @media (max-width: 480px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-grid .full {
                grid-column: span 1;
            }
        }
        .toast {
            padding: 16px 24px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInUp 0.3s ease forwards;
        }
        .toast-success {
            background: rgba(0, 212, 170, 0.12);
            border: 1px solid rgba(0, 212, 170, 0.2);
            color: var(--secondary);
        }
        .toast-error {
            background: rgba(255, 107, 107, 0.12);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: var(--accent);
        }
        .empty-employes {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-employes i {
            font-size: 64px;
            color: var(--text-muted);
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .modal-glass {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-hover);
        }
        .pin-badge {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(255, 217, 61, 0.08);
            color: var(--warning);
            padding: 2px 12px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="max-w-7xl mx-auto">

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h1 class="page-title animate-fadeUp">
                    <i class="fas fa-users text-blue-400 mr-3"></i>Employés
                </h1>
                <p class="page-subtitle animate-fadeUp animate-delay-1">
                    <i class="fas fa-user-check mr-2 text-muted"></i>
                    <?php echo count($employes); ?> employés enregistrés
                </p>
            </div>
            <!-- Dans l'en-tête -->
<div class="flex items-center gap-4">
    <img src="../assets/images/garagelogo.png" alt="GaragePoint" class="h-10">
    <div>
        <h1 class="text-2xl font-bold text-blue-600">👥 Gestion des employés</h1>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm">← Retour</a>
    </div>
</div>
            <div class="flex flex-wrap gap-3">
                <a href="dashboard.php" class="btn btn-glass animate-fadeRight animate-delay-1">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="../logout.php" class="btn btn-danger animate-fadeRight animate-delay-2">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- TOAST -->
        <?php if ($message): ?>
            <div class="toast <?php echo $message_type == 'success' ? 'toast-success' : 'toast-error'; ?> animate-fadeUp mb-6">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- AJOUTER -->
        <div class="glass-card p-6 mb-6 animate-fadeUp animate-delay-2">
            <h2 class="text-lg font-semibold mb-4">
                <i class="fas fa-user-plus text-green-400 mr-2"></i>Ajouter un employé
            </h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="ajouter" value="1">
                <div>
                    <label class="text-xs text-muted uppercase font-semibold tracking-wider">Nom *</label>
                    <input type="text" name="nom" required class="input-glass mt-1">
                </div>
                <div>
                    <label class="text-xs text-muted uppercase font-semibold tracking-wider">Prénom *</label>
                    <input type="text" name="prenom" required class="input-glass mt-1">
                </div>
                <div>
                    <label class="text-xs text-muted uppercase font-semibold tracking-wider">Poste</label>
                    <input type="text" name="poste" placeholder="Mécanicien" class="input-glass mt-1">
                </div>
                <div>
                    <label class="text-xs text-muted uppercase font-semibold tracking-wider">Téléphone</label>
                    <input type="text" name="telephone" placeholder="0612345678" class="input-glass mt-1">
                </div>
                <div>
                    <label class="text-xs text-muted uppercase font-semibold tracking-wider">PIN *</label>
                    <input type="text" name="pin_code" maxlength="4" pattern="[0-9]{4}" placeholder="1234" required class="input-glass mt-1">
                </div>
                <div class="full flex justify-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTE -->
        <div class="glass-card p-6 animate-fadeUp animate-delay-3">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-list text-primary mr-2"></i>
                    Liste des employés
                </h2>
                <span class="text-xs text-muted"><?php echo count($employes); ?> au total</span>
            </div>

            <?php if (count($employes) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="table-glass">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><i class="fas fa-user mr-1"></i>Nom</th>
                                <th><i class="fas fa-user mr-1"></i>Prénom</th>
                                <th><i class="fas fa-briefcase mr-1"></i>Poste</th>
                                <th><i class="fas fa-phone mr-1"></i>Tél</th>
                                <th><i class="fas fa-key mr-1"></i>PIN</th>
                                <th><i class="fas fa-cog mr-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employes as $e): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $e['id']; ?></td>
                                    <td class="font-medium text-white"><?php echo $e['nom']; ?></td>
                                    <td><?php echo $e['prenom']; ?></td>
                                    <td><?php echo $e['poste'] ?? '-'; ?></td>
                                    <td><?php echo $e['telephone'] ?? '-'; ?></td>
                                    <td><span class="pin-badge"><?php echo $e['pin_code']; ?></span></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button onclick="edit(<?php echo $e['id']; ?>, '<?php echo addslashes($e['nom']); ?>', '<?php echo addslashes($e['prenom']); ?>', '<?php echo addslashes($e['poste']); ?>', '<?php echo addslashes($e['telephone']); ?>', '<?php echo $e['pin_code']; ?>')" 
                                                    class="btn btn-warning" style="padding:6px 14px;font-size:13px;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?supprimer=<?php echo $e['id']; ?>" 
                                               onclick="return confirm('Supprimer <?php echo $e['prenom'] . ' ' . $e['nom']; ?> ?')" 
                                               class="btn btn-danger" style="padding:6px 14px;font-size:13px;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-employes">
                    <i class="fas fa-users"></i>
                    <h3 class="text-secondary">Aucun employé</h3>
                    <p class="text-muted text-sm">Ajoutez votre premier employé ci-dessus</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL -->
    <div id="editModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="modal-glass p-8 w-full max-w-md animate-scale">
            <h2 class="text-xl font-bold mb-4">
                <i class="fas fa-edit text-yellow-400 mr-2"></i>Modifier l'employé
            </h2>
            <form method="POST">
                <input type="hidden" name="modifier" value="1">
                <input type="hidden" name="id" id="edit-id">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-muted uppercase font-semibold">Nom *</label>
                        <input type="text" name="nom" id="edit-nom" required class="input-glass mt-1">
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase font-semibold">Prénom *</label>
                        <input type="text" name="prenom" id="edit-prenom" required class="input-glass mt-1">
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase font-semibold">Poste</label>
                        <input type="text" name="poste" id="edit-poste" class="input-glass mt-1">
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase font-semibold">Téléphone</label>
                        <input type="text" name="telephone" id="edit-telephone" class="input-glass mt-1">
                    </div>
                    <div>
                        <label class="text-xs text-muted uppercase font-semibold">PIN *</label>
                        <input type="text" name="pin_code" id="edit-pin" maxlength="4" pattern="[0-9]{4}" required class="input-glass mt-1">
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="flex-1 btn btn-warning">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 btn btn-secondary">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function edit(id, nom, prenom, poste, telephone, pin) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nom').value = nom;
            document.getElementById('edit-prenom').value = prenom;
            document.getElementById('edit-poste').value = poste || '';
            document.getElementById('edit-telephone').value = telephone || '';
            document.getElementById('edit-pin').value = pin;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer en cliquant à l'extérieur
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>