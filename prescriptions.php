<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$msg = "";
$error = "";

// Traitement de l'ajout d'une ordonnance
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $doctor_name = trim($_POST['doctor_name']);
    $diagnosis = trim($_POST['diagnosis']);
    $medicines = trim($_POST['medicines']);

    if (empty($patient_id) || empty($medicines)) {
        $error = "Veuillez sélectionner un patient et rédiger les médicaments.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_name, diagnosis, medicines) VALUES (?, ?, ?, ?)");
        $stmt->execute([$patient_id, $doctor_name, $diagnosis, $medicines]);
        $msg = "Ordonnance créée avec succès !";
    }
}

// Récupération de la liste des patients pour le formulaire
$patients = $pdo->query("SELECT id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Récupération de toutes les ordonnances avec les infos des patients
$prescriptions = $pdo->query("SELECT pr.*, p.full_name FROM prescriptions pr JOIN patients p ON pr.patient_id = p.id ORDER BY pr.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Ordonnances - Cabinet Médical</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Poppins', sans-serif; } 
        @media print {
            body * { visibility: hidden; }
            #printable-prescription, #printable-prescription * { visibility: visible; }
            #printable-prescription { position: absolute; left: 0; top: 0; width: 100%; background: white !important; color: black !important; border: none !important; box-shadow: none !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex selection:bg-sky-500 selection:text-white">
    
    <!-- Sidebar -->
    <aside class="w-72 bg-slate-800/80 backdrop-blur-md border-r border-slate-700/50 flex flex-col justify-between hidden md:flex sticky top-0 h-screen no-print">
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
                <a href="prescriptions.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-600/20 transition"><span>💊</span> <span>Ordonnances</span></a>
                <a href="invoices.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>💳</span> <span>Facturation</span></a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-700/50">
            <a href="logout.php" class="flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 text-sm font-semibold hover:bg-rose-500/20 transition"><span>🚪</span> <span>Déconnexion</span></a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-y-auto p-6 lg:p-10 space-y-8">
        
        <!-- Top Bar -->
        <div class="flex justify-between items-center bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl no-print">
            <div>
                <h1 class="text-2xl font-bold text-white">Gestion des Ordonnances</h1>
                <p class="text-xs text-slate-400 mt-1">Rédaction et impression des prescriptions médicales</p>
            </div>
            <div class="text-right hidden sm:block">
                <span class="text-xs text-slate-400">Connecté en tant que</span>
                <p class="text-sm font-bold text-sky-400"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if($msg): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg no-print">
                <span>✅</span> <span><?= $msg ?></span>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg no-print">
                <span>❌</span> <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulaire de rédaction d'ordonnance -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6 no-print">
            <h3 class="text-lg font-bold text-white">Rédiger une nouvelle ordonnance</h3>
            
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Nom du Médecin</label>
                    <input type="text" name="doctor_name" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" required class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Diagnostic</label>
                    <input type="text" name="diagnosis" placeholder="Ex: Grippe saisonnière, Hypertension..." class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Médicaments et Posologie</label>
                    <textarea name="medicines" rows="4" required placeholder="1. Paracétamol 1g - 3 fois par jour&#10;2. Vitamine C - 1 fois par jour" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition"></textarea>
                </div>
                <div>
                    <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white font-semibold px-8 py-3 rounded-xl transition shadow-lg shadow-sky-600/30 text-sm">
                        Enregistrer l'ordonnance
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des Ordonnances -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6 no-print">
            <h3 class="text-lg font-bold text-white">Historique des Ordonnances</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 font-semibold">Patient</th>
                            <th class="py-3 px-4 font-semibold">Médecin</th>
                            <th class="py-3 px-4 font-semibold">Diagnostic</th>
                            <th class="py-3 px-4 font-semibold">Médicaments</th>
                            <th class="py-3 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 text-sm">
                        <?php if(empty($prescriptions)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Aucune ordonnance trouvée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($prescriptions as $pr): ?>
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="py-4 px-4 font-semibold text-slate-200"><?= htmlspecialchars($pr['full_name']) ?></td>
                                <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($pr['doctor_name']) ?></td>
                                <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($pr['diagnosis'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-400 whitespace-pre-line"><?= htmlspecialchars($pr['medicines']) ?></td>
                                <td class="py-4 px-4 text-center">
                                    <a href="print_ordonnance.php?id=<?= $pr['id'] ?>" target="_blank" class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center space-x-2">
                                        <span>🖨️</span> <span>Imprimer</span>
                                    </a>
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