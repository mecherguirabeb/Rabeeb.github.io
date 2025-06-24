<?php
session_start();

// Vérifier si l'utilisateur est connecté et a le bon rôle
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Configuration des informations de la base de données
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "accident_de_travail";

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer l'année spécifiée via le formulaire ou utiliser l'année actuelle par défaut
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date("Y");

// Traitement du formulaire de visite
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $date = htmlspecialchars($_POST["date"]);
    $matricule = filter_var($_POST["matricule"], FILTER_VALIDATE_INT);
    $nom_prenom = htmlspecialchars($_POST["Nom"]);
    $motif = htmlspecialchars($_POST["motif"]);

    if (!$date || !$matricule || !$nom_prenom || !$motif) {
        $message = "Veuillez remplir tous les champs du formulaire.";
    } else {
        // Insérer les données dans la table `visite`
        $stmt = $conn->prepare("INSERT INTO visite (`date`, `matricule`, `nom et prenom`, `motif`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $date, $matricule, $nom_prenom, $motif);

        if ($stmt->execute()) {
            // Redirection après succès
            header("Location: visite.php?success=1");
            exit();
        } else {
            $message = "Erreur lors de l'enregistrement : " . $stmt->error;
        }
        $stmt->close();
    }
}

// Afficher un message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Les informations ont été enregistrées avec succès.";
}

// Récupérer les données mises à jour pour le tableau après l'ajout
$query = "
    SELECT 
        a.fonction AS Fonction,
        MONTH(v.date) AS Mois,
        COUNT(*) AS NombreVisites
    FROM 
        agent a
    JOIN 
        visite v ON a.mat = v.matricule
    WHERE 
        YEAR(v.date) = ?
    GROUP BY 
        a.fonction, MONTH(v.date)
    ORDER BY 
        a.fonction, Mois
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $annee);
$stmt->execute();
$result = $stmt->get_result();

// Préparer les données pour le tableau
$fonctions = [];
while ($row = $result->fetch_assoc()) {
    $fonction = $row['Fonction'];
    $mois = $row['Mois'];
    $nombreVisites = $row['NombreVisites'];

    // Initialiser les mois pour chaque fonction
    if (!isset($fonctions[$fonction])) {
        $fonctions[$fonction] = array_fill(1, 12, 0);
    }
    $fonctions[$fonction][$mois] = $nombreVisites;
}
$stmt->close();

// Récupérer les visites par année
$visites_par_annee = [];
$sql_annee = "SELECT YEAR(`date`) AS annee, COUNT(*) AS total FROM visite GROUP BY annee";
$result_annee = $conn->query($sql_annee);

if ($result_annee->num_rows > 0) {
    while ($row = $result_annee->fetch_assoc()) {
        $visites_par_annee[] = $row;
    }
}

// Récupérer les visites par motif
$motifs_par_annee = [];
$sql_motif_annee = "
    SELECT 
        YEAR(`date`) AS annee, 
        motif, 
        COUNT(*) AS total 
    FROM visite 
    GROUP BY annee, motif 
    ORDER BY annee, motif";
$result_motif_annee = $conn->query($sql_motif_annee);

if ($result_motif_annee->num_rows > 0) {
    while ($row = $result_motif_annee->fetch_assoc()) {
        $motifs_par_annee[] = $row;
    }
}

// Traitement de la recherche du matricule pour récupérer le nom
if (isset($_GET["matricule"])) {
    $matricule = intval($_GET["matricule"]); // Récupérer le matricule envoyé via JavaScript

    // Requête pour récupérer le nom et prénom depuis la table `agent`
    $stmt = $conn->prepare("SELECT `nom et prenom` FROM agent WHERE mat = ?");
    $stmt->bind_param("i", $matricule);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($nom_prenom);
        $stmt->fetch();
        // Retourner le nom et prénom en format JSON
        echo json_encode(["status" => "success", "nom_prenom" => $nom_prenom]);
    } else {
        // Aucun résultat trouvé
        echo json_encode(["status" => "error", "message" => "Matricule non trouvé"]);
    }
    $stmt->close();
    exit(); // Terminer l'exécution après avoir retourné la réponse JSON
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Formulaire</title>
    <link rel="stylesheet" href="visite.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="logo">
            <img src="img/logo.png" alt="Logo">
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Gestion Accidents de Travail</a></li>
            <li><a href="visite.php">Visite</a></li>
            <li><a href="logout.php">Se déconnecter</a></li>
        </ul>
    </div>

    <!-- Formulaire -->
    <div class="form-container">
        <!-- Message d'information -->
        <?php if (!empty($message)) : ?>
            <div class="message">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="date">Date :</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-group">
                <label for="matricule">Matricule :</label>
                <div style="display: flex; align-items: center;">
                    <input type="number" id="matricule" name="matricule" placeholder="Saisir le matricule" required>
                    <button type="button" id="search" style="margin-left: 10px; padding: 10px; cursor: pointer;">🔍</button>
                </div>
            </div>

            <div class="form-group">
                <label for="Nom">Nom et Prénom :</label>
                <input type="text" id="Nom" name="Nom" placeholder="Saisir le Nom et prénom" readonly required>
            </div>

            <div class="form-group">
                <label for="motif">Motif :</label>
                <select id="motif" name="motif" required>
                    <option value="" disabled selected>Choisir un motif</option>
                    <option value="Visite reprise">Visite reprise</option>
                    <option value="Visite d embauche">Visite d embauche</option>
                    <option value="Visite de reclassement">Visite de reclassement</option>
                    <option value="Visite périodique">Visite périodique</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">Envoyer</button>
        </form>
    </div>

    <!-- Graphiques -->
    <div style="width: 30%; float:left; margin-left:7%;">
        <h2>Visites par Année</h2><br>
        <canvas id="chart-visites-annee" style="margin-bottom:40%;"></canvas>
    </div>

    <div style="width: 50%;float:right;margin-right:6%;">
        <h2>Motifs par Année</h2><br>
        <canvas id="chart-motifs-annee" style="margin-bottom:40%;"></canvas>
    </div>

    <!-- Formulaire de recherche d'année -->
    <div class="search-container">
        <form method="GET" action="">
            <div class="form-group">
                <label for="annee">Année :</label>
                <input type="number" name="annee" id="annee" required value="<?= htmlspecialchars($annee); ?>">
            </div>
            <button type="submit" class="submit-btn">Rechercher</button>
        </form>
    </div>
    <style>
        .search-container {
  text-align: center;
 position: absolute;
 top:1080px;
 left: 500px;

}
    </style>

    <!-- Tableau des visites -->
    <table>
        <thead>
            <tr>
                <th>Fonction</th>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <th><?= date('F', mktime(0, 0, 0, $i, 1)) ?></th>
                <?php endfor; ?>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fonctions as $fonction => $visites): ?>
                <tr>
                    <td><?= htmlspecialchars($fonction) ?></td>
                    <?php
                    $total = 0;
                    foreach ($visites as $mois => $nombreVisites):
                        $total += $nombreVisites;
                    ?>
                        <td><?= $nombreVisites ?></td>
                    <?php endforeach; ?>
                    <td><?= $total ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br><br><br>

    <script>
        // Données des visites par année (transmises depuis PHP)
        const visitesParAnnee = <?= json_encode($visites_par_annee); ?>;
        const labelsAnnee = visitesParAnnee.map(item => item.annee);
        const dataAnnee = visitesParAnnee.map(item => item.total);

        // Graphique des visites par année
        const ctxVisitesAnnee = document.getElementById('chart-visites-annee').getContext('2d');
        new Chart(ctxVisitesAnnee, {
            type: 'pie',
            data: {
                labels: labelsAnnee,
                datasets: [{
                    data: dataAnnee,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Visites par Année' }
                }
            }
        });

        // Données des motifs par année (transmises depuis PHP)
        const motifsParAnnee = <?= json_encode($motifs_par_annee); ?>;
        const labelsMotifs = [...new Set(motifsParAnnee.map(item => item.motif))];
        const dataMotifs = labelsMotifs.map(motif => {
            return motifsParAnnee.filter(item => item.motif === motif).map(item => item.total);
        });

        // Graphique des motifs par année
        const ctxMotifsAnnee = document.getElementById('chart-motifs-annee').getContext('2d');
        new Chart(ctxMotifsAnnee, {
            type: 'bar',
            data: {
                labels: labelsMotifs,
                datasets: labelsAnnee.map((annee, index) => ({
                    label: `Année ${annee}`,
                    data: dataMotifs[index],
                    backgroundColor: `#${Math.floor(Math.random() * 16777215).toString(16)}`,
                }))
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Motifs par Année' }
                }
            }
        });

        // Recherche du matricule
        document.getElementById('search').addEventListener('click', function () {
            const matricule = document.getElementById('matricule').value;
            fetch(`visite.php?matricule=${matricule}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('Nom').value = data.nom_prenom;
                    } else {
                        alert(data.message);
                    }
                });
        });
    </script>
</body>
</html>