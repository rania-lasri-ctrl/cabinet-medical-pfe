<?php
// Inclusion du fichier de configuration et de connexion à la base de données
require_once 'config.php';

// Récupération de l'ID de l'ordonnance depuis l'URL (méthode GET)
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Ordonnance non trouvée.");
}

// Requête préparée pour récupérer les informations de l'ordonnance et le nom du patient associé
$stmt = $pdo->prepare("SELECT pr.*, p.full_name AS patient_name FROM prescriptions pr JOIN patients p ON pr.patient_id = p.id WHERE pr.id = ?");
$stmt->execute([$id]);
$ord = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérification si l'ordonnance existe bien dans la base de données
if (!$ord) {
    die("Ordonnance introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance - <?= htmlspecialchars($ord['patient_name']) ?></title>
    <style>
        /* Styles généraux pour l'affichage à l'écran */
        body {
            font-family: 'Arial', sans-serif;
            color: #000;
            background: #fff;
            padding: 40px;
            max-width: 800px;
            margin: auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; color: #555; font-weight: bold; }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        .prescription-box {
            border: 1px solid #ddd;
            padding: 20px;
            min-height: 250px;
            border-radius: 8px;
            margin-bottom: 40px;
        }
        .prescription-box h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        
        .footer {
            margin-top: 60px;
            text-align: right;
            padding-right: 40px;
        }
        
        /* Styles spécifiques appliqués uniquement lors de l'impression de la page */
        @media print {
            /* Masquer le bouton d'impression sur le document papier */
            .btn-print { display: none !important; }
            
            /* Supprimer les en-têtes et pieds de page automatiques du navigateur (URL, date, etc.) */
            @page {
                size: auto;
                margin: 15mm; 
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Bouton pour déclencher l'impression manuelle si besoin -->
    <button class="btn-print" onclick="window.print()" style="margin-bottom:20px; padding:10px 20px; background:#0284c7; color:#fff; border:none; border-radius:6px; cursor:pointer;">
        🖨️ Imprimer l'ordonnance
    </button>

    <!-- En-tête de l'ordonnance avec le nom du cabinet et du médecin -->
    <div class="header">
        <h1>Cabinet Médical</h1>
        <p><?= htmlspecialchars($ord['doctor_name']) ?></p>
    </div>

    <!-- Informations sur le patient, le diagnostic et la date -->
    <div class="info-section">
        <div>
            <p><strong>Patient :</strong> <?= htmlspecialchars($ord['patient_name']) ?></p>
            <p><strong>Diagnostic :</strong> <?= htmlspecialchars($ord['diagnosis'] ?: 'N/A') ?></p>
        </div>
        <div>
            <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($ord['created_at'])) ?></p>
        </div>
    </div>

    <!-- Contenu des médicaments prescrits -->
    <div class="prescription-box">
        <h3>Prescription / Médicaments :</h3>
        <p style="font-size: 16px; line-height: 1.8; white-space: pre-line;">
            <?= htmlspecialchars($ord['medicines']) ?>
        </p>
    </div>

    
    <div class="footer">
        <p>Signature et Cachet :</p>
    </div>

    <!-- Script JavaScript pour lancer la fenêtre d'impression automatiquement au chargement -->
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>