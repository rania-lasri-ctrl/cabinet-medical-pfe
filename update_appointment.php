<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    $allowed_statuses = ['Confirmé', 'En attente', 'Annulé', 'Terminé'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}

header("Location: index.php");
exit();
?>