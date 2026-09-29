<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || 
    ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

/* =========================
   STATISTIQUES
========================= */

$patients_count = $pdo->query("
    SELECT COUNT(*) FROM patients
")->fetchColumn();

$appointments_count = $pdo->query("
    SELECT COUNT(*) FROM appointments
")->fetchColumn();

$total_revenue = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM invoices
    WHERE status = 'Payé'
")->fetchColumn();


/* =========================
   DERNIERS RENDEZ-VOUS
========================= */

$stmt = $pdo->query("
    SELECT 
        a.*,
        p.full_name,
        p.phone,
        COALESCE(
            (
                SELECT i.amount
                FROM invoices i
                WHERE i.patient_id = p.id
                ORDER BY i.id DESC
                LIMIT 1
            ), 0
        ) AS invoice_amount,

        (
            SELECT i.status
            FROM invoices i
            WHERE i.patient_id = p.id
            ORDER BY i.id DESC
            LIMIT 1
        ) AS invoice_status

    FROM appointments a

    JOIN patients p 
        ON a.patient_id = p.id

    ORDER BY a.appointment_date DESC

    LIMIT 10
");

$recent_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>

    <meta charset="UTF-8">

    <title>Tableau de bord - Médecin</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>

</head>


<body class="bg-slate-900 text-slate-100 min-h-screen flex selection:bg-sky-500 selection:text-white">


<!-- =========================
     SIDEBAR
========================= -->

<aside class="w-72 bg-slate-800/80 backdrop-blur-md border-r border-slate-700/50 flex flex-col justify-between hidden md:flex sticky top-0 h-screen">

    <div>

        <div class="p-6 border-b border-slate-700/50 flex items-center space-x-3">

            <div class="bg-gradient-to-tr from-sky-500 to-indigo-600 text-white p-2.5 rounded-2xl font-bold shadow-lg shadow-sky-500/20">
                🩺
            </div>

            <div>

                <h2 class="text-base font-bold text-white tracking-wide">
                    Cabinet Médical
                </h2>

                <p class="text-xs text-slate-400">
                    Panel Administrateur
                </p>

            </div>

        </div>


        <nav class="p-4 space-y-2">

            <a href="index.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-600/20 transition">

                <span>📊</span>
                <span>Tableau de bord</span>

            </a>


            <a href="patients.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>👥</span>
                <span>Patients</span>

            </a>


            <a href="appointments.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>📅</span>
                <span>Rendez-vous</span>

            </a>


            <a href="prescriptions.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>💊</span>
                <span>Ordonnances</span>

            </a>


            <a href="invoices.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>💳</span>
                <span>Facturation</span>

            </a>

        </nav>

    </div>


    <div class="p-4 border-t border-slate-700/50">

        <a href="logout.php"
           class="flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 text-sm font-semibold hover:bg-rose-500/20 transition">

            <span>🚪</span>
            <span>Déconnexion</span>

        </a>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="flex-1 flex flex-col overflow-y-auto p-6 lg:p-10 space-y-8">


<!-- TOP BAR -->

<div class="flex justify-between items-center bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl">

    <div>

        <h1 class="text-2xl font-bold text-white">
            Tableau de bord principal
        </h1>

        <p class="text-xs text-slate-400 mt-1">
            Aperçu général de l'activité du cabinet
        </p>

    </div>


    <div class="text-right hidden sm:block">

        <span class="text-xs text-slate-400">
            Connecté en tant que
        </span>

        <p class="text-sm font-bold text-sky-400">
            <?= htmlspecialchars($_SESSION['user_name']) ?>
        </p>

    </div>

</div>



<!-- =========================
     STAT CARDS
========================= -->

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">


    <!-- PATIENTS -->

    <div class="bg-slate-800/80 border border-slate-700/50 p-5 rounded-3xl shadow-xl">

        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
            Total Patients
        </p>

        <h3 class="text-3xl font-bold text-white mt-2">
            <?= $patients_count ?>
        </h3>

        <div class="text-sky-400 text-xl mt-3">
            👥
        </div>

    </div>



    <!-- RENDEZ-VOUS -->

    <div class="bg-slate-800/80 border border-slate-700/50 p-5 rounded-3xl shadow-xl">

        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
            Rendez-vous
        </p>

        <h3 class="text-3xl font-bold text-white mt-2">
            <?= $appointments_count ?>
        </h3>

        <div class="text-indigo-400 text-xl mt-3">
            📅
        </div>

    </div>



    <!-- ENCAISSE -->

    <div class="bg-slate-800/80 border border-slate-700/50 p-5 rounded-3xl shadow-xl">

        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">
            Encaissé
        </p>

        <h3 class="text-2xl font-bold text-emerald-400 mt-2">
            <?= number_format($total_revenue, 2) ?> DH
        </h3>

        <div class="text-emerald-400 text-xl mt-3">
            💰
        </div>

    </div>

</div>



<!-- =========================
     TABLEAU PAIEMENTS
========================= -->

<div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">


    <div class="flex justify-between items-center">

        <div>

            <h3 class="text-lg font-bold text-white">
                Suivi des paiements
            </h3>

            <p class="text-xs text-slate-400 mt-1">
                Situation financière des derniers patients
            </p>

        </div>


        <a href="invoices.php"
           class="text-xs font-semibold text-sky-400 hover:underline">

            Voir toute la facturation →

        </a>

    </div>



    <div class="overflow-x-auto">

        <table class="w-full text-left border-collapse">


            <thead>

                <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">

                    <th class="py-3 px-4 font-semibold">
                        Patient
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Téléphone
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Rendez-vous
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Méthode
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Montant
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Paiement
                    </th>

                    <th class="py-3 px-4 font-semibold">
                        Statut RDV
                    </th>

                </tr>

            </thead>



            <tbody class="divide-y divide-slate-700/40 text-sm">


            <?php if (empty($recent_apps)): ?>

                <tr>

                    <td colspan="7"
                        class="py-8 text-center text-slate-500">

                        Aucun rendez-vous trouvé.

                    </td>

                </tr>


            <?php else: ?>


                <?php foreach ($recent_apps as $app): ?>


                <tr class="hover:bg-slate-700/20 transition">


                    <!-- PATIENT -->

                    <td class="py-4 px-4 font-semibold text-slate-200">

                        <?= htmlspecialchars($app['full_name']) ?>

                    </td>



                    <!-- PHONE -->

                    <td class="py-4 px-4 text-slate-400">

                        <?= htmlspecialchars($app['phone']) ?>

                    </td>



                    <!-- DATE -->

                    <td class="py-4 px-4 text-slate-300">

                        <?= htmlspecialchars($app['appointment_date']) ?>

                    </td>



                    <!-- PAYMENT METHOD -->

                    <td class="py-4 px-4 text-slate-300">

                        <?php

                        if (!empty($app['payment_method'])) {

                            if ($app['payment_method'] == 'Carte Bancaire') {
                                echo "💳 Carte bancaire";
                            }

                            elseif ($app['payment_method'] == 'Espèces') {
                                echo "💵 Espèces";
                            }

                            elseif ($app['payment_method'] == 'Assurance') {
                                echo "🏥 Assurance";
                            }

                            else {
                                echo htmlspecialchars($app['payment_method']);
                            }

                        } else {

                            echo '<span class="text-slate-500">Non renseigné</span>';

                        }

                        ?>

                    </td>



                    <!-- MONTANT -->

                    <td class="py-4 px-4 font-bold text-emerald-400">

                        <?= number_format($app['invoice_amount'], 2) ?> DH

                    </td>



                    <!-- PAYMENT STATUS -->

                    <td class="py-4 px-4">


                        <?php if ($app['invoice_status'] == 'Payé'): ?>


                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">

                                ✅ Payé

                            </span>


                        <?php elseif ($app['invoice_status'] == 'Impayé'): ?>


                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">

                                ⏳ Impayé

                            </span>


                        <?php else: ?>


                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-slate-500/10 text-slate-400 border border-slate-500/20">

                                Aucun paiement

                            </span>


                        <?php endif; ?>


                    </td>



                    <!-- RDV STATUS -->

                    <td class="py-4 px-4">


                        <span class="px-3 py-1 text-xs font-semibold rounded-full

                        <?php

                        if ($app['status'] == 'Confirmé') {

                            echo 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';

                        }

                        elseif ($app['status'] == 'Annulé') {

                            echo 'bg-rose-500/10 text-rose-400 border border-rose-500/20';

                        }

                        elseif ($app['status'] == 'Terminé') {

                            echo 'bg-blue-500/10 text-blue-400 border border-blue-500/20';

                        }

                        else {

                            echo 'bg-amber-500/10 text-amber-400 border border-amber-500/20';

                        }

                        ?>">

                            <?= htmlspecialchars($app['status']) ?>

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