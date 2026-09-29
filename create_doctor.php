<?php
// Inclusion du fichier de configuration de la base de données
require_once 'config.php';

// Informations du compte du médecin
$name = "Dr. Al Araqi";
$email = "doctor.alaraqi@cabinet.com";
$password_clair = "Araqi@2026"; // Mot de passe fort et sécurisé
$role = "doctor";

// Hachage sécurisé du mot de passe
$hashed_password = password_hash($password_clair, PASSWORD_DEFAULT);

// Vérification si l'e-mail existe déjà dans la base de données
$check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
    // Si le compte existe déjà, met à jour le mot de passe et le rôle
    $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE email = ?");
    $stmt->execute([$hashed_password, $role, $email]);
    echo "<h3 style='color: green; font-family: sans-serif; text-align: center; margin-top: 50px;'>✅ Mot de passe et rôle du médecin mis à jour avec succès !</h3>";
} else {
    // Si le compte n'existe pas, insère un nouveau compte en utilisant 'name' au lieu de 'full_name'
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed_password, $role]);
    echo "<h3 style='color: green; font-family: sans-serif; text-align: center; margin-top: 50px;'>✅ Compte du médecin créé avec succès !</h3>";
}

// Affichage des informations de connexion
echo "<p style='text-align: center; font-family: sans-serif;'>Email : <b>$email</b></p>";
echo "<p style='text-align: center; font-family: sans-serif;'>Mot de passe : <b>$password_clair</b></p>";
echo "<p style='text-align: center; font-family: sans-serif;'><a href='login.php'>Aller à la page de connexion</a></p>";
?>