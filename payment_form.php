<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['session_id'])) {
    header("Location: patient_dashboard.php?error=payment_invalid");
    exit();
}

$session_id = $_GET['session_id'];

// ⚠️ ضعي هنا نفس Stripe TEST Secret Key
$stripe_secret_key = 'sk_test_YOUR_SECRET_KEY';

$ch = curl_init("https://api.stripe.com/v1/checkout/sessions/" . urlencode($session_id));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');

$response = curl_exec($ch);
curl_close($ch);

$session = json_decode($response, true);

// Vérification réelle du paiement
if (
    isset($session['payment_status']) &&
    $session['payment_status'] === 'paid'
) {
    $patient_id = $_SESSION['user_id'];

    $patient_stmt = $pdo->prepare(
        "SELECT id FROM patients WHERE user_id = ?"
    );
    $patient_stmt->execute([$patient_id]);
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {

        $appointment_date = $session['metadata']['appointment_date'] ?? '';
        $notes = $session['metadata']['notes'] ?? '';

        if (!empty($appointment_date)) {

            // Évite de créer deux fois le même rendez-vous
            $check = $pdo->prepare("
                SELECT id 
                FROM appointments 
                WHERE patient_id = ?
                AND appointment_date = ?
                AND payment_method = 'Carte Bancaire'
                AND payment_status = 'Payé'
            ");

            $check->execute([
                $patient['id'],
                $appointment_date
            ]);

            if (!$check->fetch()) {

                $stmt = $pdo->prepare("
                    INSERT INTO appointments
                    (
                        patient_id,
                        appointment_date,
                        notes,
                        payment_method,
                        payment_status,
                        status
                    )
                    VALUES (?, ?, ?, 'Carte Bancaire', 'Payé', 'Confirmé')
                ");

                $stmt->execute([
                    $patient['id'],
                    $appointment_date,
                    $notes
                ]);
            }
        }
    }

    header("Location: patient_dashboard.php?success=payment_done");
    exit();
}

// Paiement non effectué
header("Location: patient_dashboard.php?error=payment_failed");
exit();