<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$msg = "";
$error = "";

// Traitement de l'ajout d'un patient par le médecin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $password = password_hash('123456', PASSWORD_DEFAULT); // Mot de passe par défaut

    if (empty($full_name) || empty($email)) {
        $error = "Veuillez remplir les champs obligatoires.";
    } else {
        // Vérifier si l'email existe déjà
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $error = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Insérer dans la table users
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
            $stmtUser->execute([$full_name, $email, $password]);
            $user_id = $pdo->lastInsertId();

            // Insérer dans la table patients
            $stmtPatient = $pdo->prepare("INSERT INTO patients (user_id, full_name, phone, gender, birth_date) VALUES (?, ?, ?, ?, ?)");
            $stmtPatient->execute([$user_id, $full_name, $phone, $gender, $birth_date]);

            $msg = "Patient ajouté avec succès ! (Mot de passe par défaut : 123456)";
        }
    }
}

// Récupération de la liste des patients
$patients = $pdo->query("SELECT p.*, u.email FROM patients p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Patients - Cabinet Médical</title>
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
                <a href="patients.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-600/20 transition"><span>👥</span> <span>Patients</span></a>
                <a href="appointments.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>📅</span> <span>Rendez-vous</span></a>
                <a href="prescriptions.php" class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition"><span>💊</span> <span>Ordonnances</span></a>
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
        <div class="flex justify-between items-center bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl">
            <div>
                <h1 class="text-2xl font-bold text-white">Gestion des Patients</h1>
                <p class="text-xs text-slate-400 mt-1">Liste et enregistrement des dossiers patients</p>
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

        <!-- Formulaire d'ajout rapide d'un patient -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">
            <h3 class="text-lg font-bold text-white">Ajouter un nouveau patient</h3>
            
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Nom complet</label>
                    <input type="text" name="full_name" required placeholder="Ex: Mohammed Alami" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Email</label>
                    <input type="email" name="email" required placeholder="Ex: patient@email.com" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Téléphone</label>
                    <input type="text" name="phone" placeholder="Ex: 0612345678" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Genre</label>
                    <select name="gender" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">Date de naissance</label>
                    <input type="date" name="birth_date" class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold py-3 rounded-xl transition shadow-lg shadow-sky-600/30 text-sm">
                        Enregistrer le patient
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des Patients Table -->
        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">
            <h3 class="text-lg font-bold text-white">Dossiers Patients Enregistrés</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 font-semibold">Nom Complet</th>
                            <th class="py-3 px-4 font-semibold">Email</th>
                            <th class="py-3 px-4 font-semibold">Téléphone</th>
                            <th class="py-3 px-4 font-semibold">Genre</th>
                            <th class="py-3 px-4 font-semibold">Date de Naissance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40 text-sm">
                        <?php if(empty($patients)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">Aucun patient enregistré pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($patients as $p): ?>
                            <tr class="hover:bg-slate-700/20 transition">
                                <td class="py-4 px-4 font-semibold text-slate-200"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td class="py-4 px-4 text-slate-400"><?= htmlspecialchars($p['email']) ?></td>
                                <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($p['phone'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($p['gender'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-300"><?= htmlspecialchars($p['birth_date'] ?: '-') ?></td>
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