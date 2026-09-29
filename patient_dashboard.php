<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$patient_stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
$patient_stmt->execute([$user_id]);
$patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

$msg = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Messages après paiement
|--------------------------------------------------------------------------
*/
if (isset($_GET['success']) && $_GET['success'] === 'payment_done') {
    $msg = "Paiement effectué avec succès ! Votre rendez-vous est confirmé.";
}

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'cancelled') {
        $error = "Le paiement a été annulé.";
    } elseif ($_GET['error'] === 'payment_failed') {
        $error = "Le paiement n'a pas été effectué.";
    } elseif ($_GET['error'] === 'payment_invalid') {
        $error = "Session de paiement invalide.";
    }
}

/*
|--------------------------------------------------------------------------
| Nouveau rendez-vous
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $patient) {

    $app_date = $_POST['appointment_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Espèces';

    if (empty($app_date)) {

        $error = "Veuillez choisir une date et une heure valides.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Paiement par Carte Bancaire - Stripe TEST
        |--------------------------------------------------------------------------
        */
        if ($payment_method === 'Carte Bancaire') {

            // 
            $stripe_secret_key = 'YOUR_STRIPE_TEST_SECRET_KEY';

            /*
            | URL après paiement réussi
            | Stripe remplacera automatiquement
            | {CHECKOUT_SESSION_ID} par le vrai ID de session.
            */
            $success_url =
                'http://localhost/cabinet_pfe/payment_success.php?session_id={CHECKOUT_SESSION_ID}';

            /*
            | URL si le patient annule le paiement
            */
            $cancel_url =
                'http://localhost/cabinet_pfe/patient_dashboard.php?error=cancelled';

            /*
            | Données envoyées à Stripe
            */
            $data = [
                'payment_method_types' => ['card'],

                'line_items' => [[
                    'price_data' => [
                        'currency' => 'mad',

                        'product_data' => [
                            'name' => 'Consultation Médicale',
                        ],

                        // 200 MAD = 20000 centimes
                        'unit_amount' => 20000,
                    ],

                    'quantity' => 1,
                ]],

                'mode' => 'payment',

                'success_url' => $success_url,

                'cancel_url' => $cancel_url,

                /*
                | Informations conservées dans Stripe
                | pour retrouver le rendez-vous après paiement.
                */
                'metadata' => [
                    'appointment_date' => $app_date,
                    'notes' => $notes,
                    'patient_id' => $patient['id']
                ]
            ];

            /*
            |--------------------------------------------------------------------------
            | Connexion à Stripe
            |--------------------------------------------------------------------------
            */

            $ch = curl_init(
                'https://api.stripe.com/v1/checkout/sessions'
            );

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt(
                $ch,
                CURLOPT_USERPWD,
                $stripe_secret_key . ':'
            );

            curl_setopt(
                $ch,
                CURLOPT_POST,
                true
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query($data)
            );

            $response = curl_exec($ch);

            $curl_error = curl_error($ch);

            curl_close($ch);

            /*
            |--------------------------------------------------------------------------
            | Vérification de la réponse Stripe
            |--------------------------------------------------------------------------
            */

            if ($curl_error) {

                $error = "Erreur de connexion avec Stripe : " . $curl_error;

            } else {

                $session = json_decode($response, true);

                if (isset($session['url'])) {

                    // Redirection vers la page de paiement Stripe
                    header("Location: " . $session['url']);
                    exit();

                } else {

                    $error = "Erreur Stripe. Vérifiez votre clé API TEST.";

                    // Pour le débogage pendant le développement
                    if (isset($session['error']['message'])) {
                        $error .= " " . $session['error']['message'];
                    }
                }
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | Paiement classique : Espèces / Assurance
            |--------------------------------------------------------------------------
            */

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
                VALUES (?, ?, ?, ?, 'Non payé', 'En attente')
            ");

            $stmt->execute([
                $patient['id'],
                $app_date,
                $notes,
                $payment_method
            ]);

            $msg = "Votre demande de rendez-vous a été envoyée avec succès !";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Récupération des rendez-vous
|--------------------------------------------------------------------------
*/

$appointments = [];
$total_apps = 0;
$confirmed_apps = 0;

if ($patient) {

    $apps_stmt = $pdo->prepare("
        SELECT *
        FROM appointments
        WHERE patient_id = ?
        ORDER BY appointment_date DESC
    ");

    $apps_stmt->execute([
        $patient['id']
    ]);

    $appointments = $apps_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointments as $a) {

        $total_apps++;

        if ($a['status'] == 'Confirmé') {
            $confirmed_apps++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>

    <meta charset="UTF-8">

    <title>Espace Patient - Cabinet Médical</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>

</head>

<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col selection:bg-sky-500 selection:text-white">

<!-- HEADER -->

<header class="bg-slate-800/60 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-50 px-6 lg:px-12 py-4 flex justify-between items-center">

    <div class="flex items-center space-x-3">

        <div class="bg-gradient-to-tr from-sky-500 to-indigo-600 text-white p-2.5 rounded-2xl font-bold shadow-lg shadow-sky-500/20">
            🩺
        </div>

        <div>

            <h1 class="text-base font-bold text-white tracking-wide">
                Espace Patient
            </h1>

            <p class="text-xs text-slate-400">
                Portail de santé personnel
            </p>

        </div>

    </div>

    <div class="flex items-center space-x-4">

        <span class="text-sm font-medium text-slate-300 hidden md:inline">

            Patient:

            <strong class="text-sky-400">
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Patient') ?>
            </strong>

        </span>

        <a
            href="logout.php"
            class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 px-4 py-2 rounded-xl text-sm font-semibold transition"
        >
            Déconnexion
        </a>

    </div>

</header>


<!-- MAIN -->

<main class="flex-1 max-w-6xl w-full mx-auto p-6 lg:p-10 space-y-8">


    <!-- SUCCESS MESSAGE -->

    <?php if ($msg): ?>

        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg">

            <span>✅</span>

            <span>
                <?= htmlspecialchars($msg) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($error): ?>

        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-2xl text-sm font-medium flex items-center space-x-3 shadow-lg">

            <span>❌</span>

            <span>
                <?= htmlspecialchars($error) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- STATISTICS -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">


        <div class="bg-slate-800/80 border border-slate-700/50 p-6 rounded-3xl shadow-xl">

            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                Total Rendez-vous
            </p>

            <h3 class="text-3xl font-bold text-white mt-2">
                <?= $total_apps ?>
            </h3>

        </div>


        <div class="bg-slate-800/80 border border-slate-700/50 p-6 rounded-3xl shadow-xl">

            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                Rendez-vous Confirmés
            </p>

            <h3 class="text-3xl font-bold text-emerald-400 mt-2">
                <?= $confirmed_apps ?>
            </h3>

        </div>


        <div class="bg-slate-800/80 border border-slate-700/50 p-6 rounded-3xl shadow-xl">

            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                Statut du Dossier
            </p>

            <h3 class="text-xl font-bold text-sky-400 mt-3 flex items-center space-x-2">

                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>

                <span>
                    Actif & Sécurisé
                </span>

            </h3>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">


        <!-- NEW APPOINTMENT -->

        <div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl h-fit space-y-6">

            <div>

                <h3 class="text-lg font-bold text-white">
                    Nouveau Rendez-vous
                </h3>

                <p class="text-xs text-slate-400 mt-1">
                    Prenez une consultation avec le médecin
                </p>

            </div>


            <form
                action=""
                method="POST"
                class="space-y-4"
            >


                <!-- DATE -->

                <div>

                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">

                        Date et heure

                    </label>

                    <input
                        type="datetime-local"
                        name="appointment_date"
                        required
                        class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition"
                    >

                </div>


                <!-- PAYMENT METHOD -->

                <div>

                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">

                        Mode de paiement

                    </label>

                    <select
                        name="payment_method"
                        required
                        class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition"
                    >

                        <option value="Espèces">
                            Espèces (au cabinet)
                        </option>

                        <option value="Carte Bancaire">
                            💳 Carte Bancaire (Paiement en ligne)
                        </option>

                        <option value="Assurance">
                            Assurance / Mutuelle
                        </option>

                    </select>

                </div>


                <!-- NOTES -->

                <div>

                    <label class="block text-xs font-semibold text-slate-300 mb-2 uppercase tracking-wider">

                        Motif / Symptômes

                    </label>

                    <textarea
                        name="notes"
                        rows="3"
                        placeholder="Ex: Consultation générale, fièvre..."
                        class="w-full bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition resize-none"
                    ></textarea>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="w-full bg-sky-600 hover:bg-sky-500 text-white font-semibold py-3 rounded-xl transition shadow-lg shadow-sky-600/30 text-sm"
                >

                    💳 Envoyer / Payer

                </button>

            </form>

        </div>


        <!-- APPOINTMENTS -->

        <div class="lg:col-span-2 bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">

            <div>

                <h3 class="text-lg font-bold text-white">
                    Historique de mes rendez-vous
                </h3>

                <p class="text-xs text-slate-400 mt-1">
                    Suivi de vos demandes en temps réel
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left border-collapse">

                    <thead>

                        <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">

                            <th class="py-3 px-4 font-semibold">
                                Date & Heure
                            </th>

                            <th class="py-3 px-4 font-semibold">
                                Statut
                            </th>

                            <th class="py-3 px-4 font-semibold">
                                Paiement
                            </th>

                            <th class="py-3 px-4 font-semibold">
                                Motif
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-700/40 text-sm">


                        <?php if (empty($appointments)): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="py-8 text-center text-slate-500"
                                >

                                    Aucun rendez-vous enregistré pour le moment.

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($appointments as $app): ?>

                                <tr class="hover:bg-slate-700/20 transition">


                                    <!-- DATE -->

                                    <td class="py-4 px-4 font-medium text-slate-200">

                                        <?= htmlspecialchars($app['appointment_date']) ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td class="py-4 px-4">

                                        <span
                                            class="px-3 py-1 text-xs font-semibold rounded-full

                                            <?= $app['status'] == 'Confirmé'

                                                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'

                                                : ($app['status'] == 'Annulé'

                                                    ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20'

                                                    : 'bg-amber-500/10 text-amber-400 border border-amber-500/20')

                                            ?>"
                                        >

                                            <?= htmlspecialchars($app['status']) ?>

                                        </span>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td class="py-4 px-4 text-slate-300 font-medium">

                                        <span class="text-xs bg-slate-700/50 px-2.5 py-1 rounded-lg border border-slate-600/50">

                                            <?= htmlspecialchars($app['payment_method'] ?? 'Espèces') ?>

                                            <?php if (
                                                isset($app['payment_status']) &&
                                                $app['payment_status'] == 'Payé'
                                            ): ?>

                                                <span class="text-emerald-400 ml-1">
                                                    (Payé)
                                                </span>

                                            <?php elseif (
                                                isset($app['payment_status']) &&
                                                $app['payment_status'] == 'Non payé'
                                            ): ?>

                                                <span class="text-amber-400 ml-1">
                                                    (Non payé)
                                                </span>

                                            <?php endif; ?>

                                        </span>

                                    </td>


                                    <!-- NOTES -->

                                    <td class="py-4 px-4 text-slate-400">

                                        <?= htmlspecialchars($app['notes'] ?: '-') ?>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

</body>
</html>