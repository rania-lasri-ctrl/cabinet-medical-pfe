<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || 
    ($_SESSION['role'] != 'doctor' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Récupération des rendez-vous + informations patient + facturation
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT 
        a.*,
        p.full_name,
        p.phone,

        /* Dernière facture du patient */
        (
            SELECT i.amount
            FROM invoices i
            WHERE i.patient_id = p.id
            ORDER BY i.id DESC
            LIMIT 1
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
");

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>

    <meta charset="UTF-8">

    <title>Gestion des Rendez-vous - Cabinet Médical</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>

</head>


<body class="bg-slate-900 text-slate-100 min-h-screen flex selection:bg-sky-500 selection:text-white">


<!-- =========================================================
     SIDEBAR
========================================================= -->

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


            <!-- Dashboard -->

            <a href="index.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>📊</span>

                <span>Tableau de bord</span>

            </a>


            <!-- Patients -->

            <a href="patients.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>👥</span>

                <span>Patients</span>

            </a>


            <!-- Rendez-vous -->

            <a href="appointments.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl bg-sky-600 text-white font-semibold shadow-lg shadow-sky-600/20 transition">

                <span>📅</span>

                <span>Rendez-vous</span>

            </a>


            <!-- Ordonnances -->

            <a href="prescriptions.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>💊</span>

                <span>Ordonnances</span>

            </a>


            <!-- Facturation -->

            <a href="invoices.php"
               class="flex items-center space-x-3 py-3 px-4 rounded-2xl hover:bg-slate-700/40 text-slate-300 transition">

                <span>💳</span>

                <span>Facturation</span>

            </a>

        </nav>

    </div>


    <!-- Logout -->

    <div class="p-4 border-t border-slate-700/50">

        <a href="logout.php"
           class="flex items-center space-x-2 py-2.5 px-4 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 text-sm font-semibold hover:bg-rose-500/20 transition">

            <span>🚪</span>

            <span>Déconnexion</span>

        </a>

    </div>

</aside>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="flex-1 flex flex-col overflow-y-auto p-6 lg:p-10 space-y-8">


<!-- =========================================================
     TOP BAR
========================================================= -->

<div class="flex justify-between items-center bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-6 rounded-3xl shadow-xl">

    <div>

        <h1 class="text-2xl font-bold text-white">
            Gestion des Rendez-vous
        </h1>

        <p class="text-xs text-slate-400 mt-1">
            Suivi, validation et gestion de l'agenda du cabinet
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



<!-- =========================================================
     TABLEAU DES RENDEZ-VOUS
========================================================= -->

<div class="bg-slate-800/80 border border-slate-700/50 p-6 lg:p-8 rounded-3xl shadow-xl space-y-6">


    <div class="flex justify-between items-center">

        <div>

            <h3 class="text-lg font-bold text-white">
                Tous les rendez-vous enregistrés
            </h3>

            <p class="text-xs text-slate-400 mt-1">
                Rendez-vous, paiement et situation financière des patients
            </p>

        </div>

    </div>



    <div class="overflow-x-auto">

        <table class="w-full text-left border-collapse">


            <!-- HEADER -->

            <thead>

                <tr class="border-b border-slate-700/60 text-slate-400 text-xs uppercase tracking-wider">


                    <th class="py-3 px-4 font-semibold">
                        Patient
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Téléphone
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Date & Heure
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Motif / Notes
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Paiement
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Montant
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Statut paiement
                    </th>


                    <th class="py-3 px-4 font-semibold">
                        Statut RDV
                    </th>


                </tr>

            </thead>



            <!-- BODY -->

            <tbody class="divide-y divide-slate-700/40 text-sm">


            <?php if (empty($appointments)): ?>


                <tr>

                    <td colspan="8"
                        class="py-8 text-center text-slate-500">

                        Aucun rendez-vous trouvé pour le moment.

                    </td>

                </tr>


            <?php else: ?>


                <?php foreach ($appointments as $app): ?>


                <tr class="hover:bg-slate-700/20 transition">


                    <!-- PATIENT -->

                    <td class="py-4 px-4 font-semibold text-slate-200">

                        <?= htmlspecialchars($app['full_name']) ?>

                    </td>



                    <!-- TELEPHONE -->

                    <td class="py-4 px-4 text-slate-400">

                        <?= htmlspecialchars($app['phone'] ?: '-') ?>

                    </td>



                    <!-- DATE -->

                    <td class="py-4 px-4 text-slate-300 whitespace-nowrap">

                        <?= htmlspecialchars($app['appointment_date']) ?>

                    </td>



                    <!-- NOTES -->

                    <td class="py-4 px-4 text-slate-400 max-w-xs">

                        <?= htmlspecialchars($app['notes'] ?: '-') ?>

                    </td>



                    <!-- PAYMENT METHOD -->

                    <td class="py-4 px-4">

                        <?php

                        $paymentMethod = $app['payment_method'] ?? '';

                        if ($paymentMethod === 'Carte Bancaire'):

                        ?>

                            <span class="inline-flex items-center gap-1 text-sky-400">

                                💳 Carte bancaire

                            </span>


                        <?php elseif ($paymentMethod === 'Espèces'): ?>


                            <span class="inline-flex items-center gap-1 text-emerald-400">

                                💵 Espèces

                            </span>


                        <?php elseif ($paymentMethod === 'Assurance' || $paymentMethod === 'Assurance / Mutuelle'): ?>


                            <span class="inline-flex items-center gap-1 text-indigo-400">

                                🏥 Assurance

                            </span>


                        <?php else: ?>


                            <span class="text-slate-500">

                                Non renseigné

                            </span>


                        <?php endif; ?>

                    </td>



                    <!-- AMOUNT -->

                    <td class="py-4 px-4 font-bold text-emerald-400 whitespace-nowrap">

                        <?php if ($app['invoice_amount'] !== null): ?>

                            <?= number_format($app['invoice_amount'], 2) ?> DH

                        <?php else: ?>

                            <span class="text-slate-500 font-normal">
                                -
                            </span>

                        <?php endif; ?>

                    </td>



                    <!-- PAYMENT STATUS -->

                    <td class="py-4 px-4">


                        <?php

                        /*
                         * On considère le paiement comme payé
                         * si appointment.payment_status = Payé
                         * OU invoice.status = Payé
                         */

                        $isPaid =
                            (($app['payment_status'] ?? '') === 'Payé')
                            ||
                            (($app['invoice_status'] ?? '') === 'Payé');

                        ?>


                        <?php if ($isPaid): ?>


                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">

                                ✅ Payé

                            </span>


                        <?php else: ?>


                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">

                                ⏳ Non payé

                            </span>


                        <?php endif; ?>


                    </td>



                    <!-- APPOINTMENT STATUS -->

                    <td class="py-4 px-4">


                        <?php

                        $statusClass = 'bg-amber-500/10 text-amber-400 border border-amber-500/20';

                        if ($app['status'] === 'Confirmé') {

                            $statusClass = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';

                        } elseif ($app['status'] === 'Annulé') {

                            $statusClass = 'bg-rose-500/10 text-rose-400 border border-rose-500/20';

                        } elseif ($app['status'] === 'Terminé') {

                            $statusClass = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';

                        }

                        ?>


                        <div class="flex items-center gap-2">


                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">

                                <?= htmlspecialchars($app['status']) ?>

                            </span>


                            <!-- ACTIONS -->

                            <div class="flex space-x-1 text-xs">


                                <a href="update_appointment.php?id=<?= $app['id'] ?>&status=Confirmé"
                                   class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 px-2.5 py-1 rounded-lg font-bold transition"
                                   title="Confirmer">

                                    ✔

                                </a>


                                <a href="update_appointment.php?id=<?= $app['id'] ?>&status=Annulé"
                                   class="bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 px-2.5 py-1 rounded-lg font-bold transition"
                                   title="Annuler">

                                    ✖

                                </a>

                            </div>

                        </div>

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