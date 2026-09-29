<?php
require_once 'config.php';
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];

    try {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            $error_msg = "Cet email est déjà utilisé.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
            $stmt->execute([$name, $email, $password]);
            $user_id = $pdo->lastInsertId();

            $stmt_pat = $pdo->prepare("INSERT INTO patients (user_id, full_name, phone, gender, birth_date) VALUES (?, ?, ?, ?, ?)");
            $stmt_pat->execute([$user_id, $name, $phone, $gender, $birth_date]);

            $success_msg = "Inscription réussie avec succès !";
        }
    } catch (Exception $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-6">
    <div class="bg-white w-full max-w-lg p-8 rounded-3xl shadow-xl">
        <h2 class="text-2xl font-bold text-slate-800 mb-2 text-center">Inscription Patient</h2>
        <p class="text-sm text-slate-400 text-center mb-6">Créez votre compte pour gérer vos rendez-vous</p>

        <?php if($success_msg): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm font-semibold mb-4 text-center">
                <?= $success_msg ?> <br>
                <a href="login.php" class="text-sky-600 underline mt-1 inline-block font-bold">Se connecter maintenant</a>
            </div>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 p-4 rounded-xl text-sm font-semibold mb-4 text-center"><?= $error_msg ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nom complet</label>
                <input type="text" name="name" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Adresse Email</label>
                <input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Mot de passe</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-12 focus:outline-none focus:border-sky-500">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span id="eye-icon">👁️</span>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Genre</label>
                    <select name="gender" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Date de naissance</label>
                <input type="date" name="birth_date" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
            </div>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 rounded-xl transition">S'inscrire</button>
            <p class="text-center text-sm text-slate-500 mt-4">Déjà un compte ? <a href="login.php" class="text-sky-600 font-semibold">Se connecter</a></p>
        </form>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>