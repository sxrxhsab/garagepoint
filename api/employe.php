<?php
// admin/employes.php - Gestion des employés (version PIN uniquement)
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

// Variables pour les messages
$message = '';
$message_type = '';

// ============ AJOUTER UN EMPLOYÉ ============
if (isset($_POST['ajouter'])) {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $poste = $_POST['poste'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $pin_code = $_POST['pin_code'] ?? '';
    
    // Vérifier que le PIN est valide (4 chiffres)
    if (!preg_match('/^[0-9]{4}$/', $pin_code)) {
        $message = "❌ Le code PIN doit être 4 chiffres";
        $message_type = 'error';
    } elseif (empty($nom) || empty($prenom)) {
        $message = "❌ Le nom et le prénom sont obligatoires";
        $message_type = 'error';
    } else {
        // Vérifier que le PIN n'est pas déjà utilisé
        $stmt = $pdo->prepare("SELECT * FROM employes WHERE pin_code = ?");
        $stmt->execute([$pin_code]);
        if ($stmt->rowCount() > 0) {
            $message = "❌ Ce code PIN est déjà utilisé par un autre employé";
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO employes (nom, prenom, poste, telephone, pin_code) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nom, $prenom, $poste, $telephone, $pin_code])) {
                $message = "✅ Employé ajouté avec succès ! PIN : $pin_code";
                $message_type = 'success';
            } else {
                $message = "❌ Erreur lors de l'ajout";
                $message_type = 'error';
            }
        }
    }
}

// ============ MODIFIER UN EMPLOYÉ ============
if (isset($_POST['modifier'])) {
    $id = $_POST['id'] ?? 0;
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $poste = $_POST['poste'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $pin_code = $_POST['pin_code'] ?? '';
    
    if (!preg_match('/^[0-9]{4}$/', $pin_code)) {
        $message = "❌ Le code PIN doit être 4 chiffres";
        $message_type = 'error';
    } elseif (empty($nom) || empty($prenom) || $id <= 0) {
        $message = "❌ Le nom et le prénom sont obligatoires";
        $message_type = 'error';
    } else {
        // Vérifier que le PIN n'est pas utilisé par un autre employé
        $stmt = $pdo->prepare("SELECT * FROM employes WHERE pin_code = ? AND id != ?");
        $stmt->execute([$pin_code, $id]);
        if ($stmt->rowCount() > 0) {
            $message = "❌ Ce code PIN est déjà utilisé par un autre employé";
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE employes SET nom = ?, prenom = ?, poste = ?, telephone = ?, pin_code = ? WHERE id = ?");
            if ($stmt->execute([$nom, $prenom, $poste, $telephone, $pin_code, $id])) {
                $message = "✅ Employé modifié avec succès !";
                $message_type = 'success';
            } else {
                $message = "❌ Erreur lors de la modification";
                $message_type = 'error';
            }
        }
    }
}

// ============ SUPPRIMER UN EMPLOYÉ ============
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'] ?? 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM employes WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "✅ Employé supprimé avec succès !";
            $message_type = 'success';
        } else {
            $message = "❌ Erreur lors de la suppression";
            $message_type = 'error';
        }
    }
}

// ============ RÉCUPÉRER LA LISTE DES EMPLOYÉS ============
$stmt = $pdo->query("SELECT * FROM employes ORDER BY id DESC");
$employes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des employés - GaragePoint</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <!-- En-tête -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-users mr-2"></i>Gestion des employés
                    </h1>
                    <p class="text-gray-600">
                        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i>Retour au tableau de bord
                        </a>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>

            <!-- Message de confirmation -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-user-plus mr-2 text-green-600"></i>Ajouter un employé
                </h2>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input type="hidden" name="ajouter" value="1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input type="text" name="nom" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                        <input type="text" name="prenom" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                        <input type="text" name="poste" placeholder="Mécanicien" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" placeholder="0612345678" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code PIN (4 chiffres) *</label>
                        <input type="text" name="pin_code" maxlength="4" pattern="[0-9]{4}" 
                               placeholder="1234" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="md:col-span-5 flex justify-end">
                        <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-plus mr-2"></i>Ajouter
                        </button>
                    </div>
                </form>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>Le code PIN servira pour le pointage sur la tablette
                </p>
            </div>

            <!-- Liste des employés -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-list mr-2 text-blue-600"></i>Liste des employés (<?php echo count($employes); ?>)
                </h2>
                
                <?php if (count($employes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Nom</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Prénom</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Poste</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Téléphone</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">PIN</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($employes as $emp): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3"><?php echo $emp['id']; ?></td>
                                        <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($emp['nom']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($emp['prenom']); ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($emp['poste'] ?? '-'); ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($emp['telephone'] ?? '-'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-lg text-sm font-mono font-bold">
                                                <?php echo htmlspecialchars($emp['pin_code'] ?? '----'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex space-x-2">
                                                <!-- Bouton Modifier -->
                                                <button onclick="openEdit(<?php echo $emp['id']; ?>, '<?php echo addslashes($emp['nom']); ?>', '<?php echo addslashes($emp['prenom']); ?>', '<?php echo addslashes($emp['poste'] ?? ''); ?>', '<?php echo addslashes($emp['telephone'] ?? ''); ?>', '<?php echo addslashes($emp['pin_code'] ?? ''); ?>')" 
                                                        class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 transition text-sm">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Bouton Supprimer -->
                                                <a href="?supprimer=<?php echo $emp['id']; ?>" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')"
                                                   class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-sm">
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
                    <p class="text-gray-500 text-center py-8">
                        <i class="fas fa-users text-4xl block mb-2"></i>
                        Aucun employé enregistré
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-edit mr-2 text-yellow-600"></i>Modifier l'employé
            </h2>
            <form method="POST">
                <input type="hidden" name="modifier" value="1">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" name="nom" id="edit-nom" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                    <input type="text" name="prenom" id="edit-prenom" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                    <input type="text" name="poste" id="edit-poste" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="telephone" id="edit-telephone" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code PIN (4 chiffres) *</label>
                    <input type="text" name="pin_code" id="edit-pin" maxlength="4" pattern="[0-9]{4}" 
                           placeholder="1234" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-yellow-600 transition">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeEdit()" class="flex-1 bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, nom, prenom, poste, telephone, pin_code) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nom').value = nom;
            document.getElementById('edit-prenom').value = prenom;
            document.getElementById('edit-poste').value = poste;
            document.getElementById('edit-telephone').value = telephone;
            document.getElementById('edit-pin').value = pin_code;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEdit() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEdit();
            }
        });
    </script>
</body>
</html>