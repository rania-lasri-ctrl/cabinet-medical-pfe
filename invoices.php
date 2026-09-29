<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$msg = "";
$error = "";

// Traitement de l'ajout d'une facture
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    if (empty($patient_id) || empty($amount)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO invoices (patient_id, amount, status) VALUES (?, ?, ?)");
        $stmt->execute([$patient_id, $amount, $status]);
        $msg = "Facture enregistrée avec succès !";
    }
}

// Récupération de la liste des patients pour le formulaire
$patients = $pdo->query("SELECT id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Récupération de toutes les factures avec les infos des patients
$invoices = $pdo->query("SELECT i.*, p.full_name FROM invoices i JOIN patients p ON i.patient_id = p.id ORDER BY i.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de la Facturation - Cabinet Médical</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex selection:bg-sky-500 selection:text-white">
    
    <!-- Sidebar -->
    <aside class="w-72 bg-slate-800/80 backdrop-blur-md border-r border-slate-700/50 flex flex-col justify-between hidden md:flex sticky top-0 h-screen">
        <div>
            <div class="p-6 border-b border-slate-700/50 flex items-center space-x-3">
                <div class="bg-gradient-to-tr from-sky-500 to-indigo-600 text-white p-2.5 rounded-2xl font-bold shadow-lg shadow-sky-500/20">🩺</div>
                <div>
                    <h2 class="text-base font-bold text-white tracking-wide">Cabinet Médical</h2>
                    <p class="text-xs text-slate-400">Panel Administrateur</p>
                </div>
            </div>
            <nav class="p-4 space-y-2">
                <a href="index.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>📊</span> <span>Tableau de bord</span></a>
                <a href="patients.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>👥</span> <span>Patients</span></a>
                <a href="appointments.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>📅</span> <span>Rendez-vous</span></a>
                <a href="prescriptions.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>💊</span> <span>Ordonnances</span></a>
                <a href="invoices.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-600/20 transition"><span>💳</span> <span>Facturation</span></a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-700/50">
            <a href="logout.php" class="flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 text-sm font-semibold hover:bg-rose-500/20 transition"><span>🚪</span> <span>Déconnexion</span></a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-y-auto p-6 lg:p-10 space-y-8">
        
        <!-- Top Bar -->
        <div class="flex justify-between items-center bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl">
            <div>
                <h1 class="text-2xl font-bold text-white">Gestion de la Facturation</h1>
                <p class="text-xs text-slate-400 mt-1">Suivi des honoraires, paiements et recettes du cabinet</p>
            </div>
            <div class="text-right hidden sm:block">
                <span class="text-xs text-slate-400">Connecté en tant que</span>
                <p class="text-sm font-bold text-sky-400"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if($msg): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg">
                <span>✅</span> <span><?= $msg ?></span>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg">
                <span>❌</span> <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout de facture -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">
            <h3 class="text-lg font-bold text-white">Émettre une nouvelle facture</h3>
            
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Sélectionner le patient</label>
                    <select name="patient_id" required class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                        <option value="">-- Choisir un patient --</option>
                        <?php foreach($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Montant (DH)</label>
                    <input type="number" step="0.01" name="amount" required placeholder="Ex: 200.00" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Statut du paiement</label>
                    <select name="status" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                        <option value="Payé">Payé</option>
                        <option value="Impayé">Impayé</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-semibold px-8 py-3 rounded-xl transition shadow-lg shadow-sky-600/30 text-sm">
                        Enregistrer la facture
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des Factures -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">
            <h3 class="text-lg font-bold text-white">Historique de la Facturation</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 font-semibold">ID Facture</th>
                            <th class="py-3 px-4 font-semibold">Patient</th>
                            <th class="py-3 px-4 font-semibold">Montant</th>
                            <th class="py-3 px-4 font-semibold">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 text-sm">
                        <?php if(empty($invoices)): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">Aucune facture enregistrée pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($invoices as $inv): ?>
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="py-4 px-4 font-medium text-slate-400">#<?= $inv['id'] ?></td>
                                <td class="py-4 px-4 font-semibold text-slate-200"><?= htmlspecialchars($inv['full_name']) ?></td>
                                <td class="py-4 px-4 font-bold text-emerald-400"><?= number_format($inv['amount'], 2) ?> DH</td>
                                <td class="py-4 px-4">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        <?= $inv['status'] == 'Payé' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                        <?= $inv['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>