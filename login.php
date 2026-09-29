<?php
require_once 'config.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification sécurisée via password_verify uniquement
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'doctor' || $user['role'] == 'admin') {
            header("Location: index.php");
        } else {
            header("Location: patient_dashboard.php");
        }
        exit();
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Cabinet Médical</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-6">
    <div class="bg-white w-full max-w-md p-8 rounded-3xl shadow-xl">
        <h2 class="text-2xl font-bold text-slate-800 mb-2 text-center">Connexion</h2>
        <p class="text-sm text-slate-400 text-center mb-6">Plateforme de gestion de cabinet médical</p>

        <?php if($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 p-4 rounded-xl text-sm font-semibold mb-4 text-center"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-600 mb-1">Email</label>
                <input type="email" name="email" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-sky-500">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Mot de passe</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-12 focus:outline-none focus:border-sky-500">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span id="eye-icon">👁️</span>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 rounded-xl transition">Se connecter</button>
            <p class="text-center text-sm text-slate-500 mt-4">Pas de compte patient ? <a href="register.php" class="text-sky-600 font-semibold">S'inscrire</a></p>
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