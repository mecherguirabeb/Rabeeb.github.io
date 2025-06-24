<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    header('Location: login.php');
    exit();
}
include 'includes/db_connect.php';


// Récupérer les informations selon le matricule recherché
$searchMatricule = '';
$personDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchMatricule'])) {
    $searchMatricule = filter_var($_POST['searchMatricule'], FILTER_SANITIZE_NUMBER_INT);

    // Utilisation de requêtes préparées pour éviter les injections SQL
    $stmt = $conn->prepare("SELECT 
                                mat, 
                                `nom et prenom` AS nom_et_prenom,
                                date_accident, 
                                SUM(nombre_dejour) AS total_jours,
                                causes
                            FROM accident
                            WHERE mat = ?
                            GROUP BY mat, date_accident, `nom et prenom`, causes
                            ORDER BY date_accident");
    $stmt->bind_param("i", $searchMatricule); // Bind the integer parameter
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $personDetails[] = $row;
        }
    }
}



// Retrieve the person's name based on matricule (if available)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($searchMatricule)) {
    $stmt_person = $conn->prepare("SELECT `nom et prenom` FROM accident WHERE mat = ? LIMIT 1");
    $stmt_person->bind_param("i", $searchMatricule);
    $stmt_person->execute();
    $result_person = $stmt_person->get_result();
    
    if ($result_person->num_rows > 0) {
        $person = $result_person->fetch_assoc();
        echo "<h3>Détails de la personne : " . htmlspecialchars($person['nom et prenom']) . "</h3>";
    }
}

// Get month name function
function getMonthName($monthNumber) {
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai',
        6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
        10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $months[$monthNumber] ?? 'Inconnu';
}


// Fonction pour répartir les jours entre les mois
function distributeDays($startDate, $days) {
    $distributedData = [];
    $currentDate = new DateTime($startDate);

    while ($days > 0) {
        // Obtenir le nombre de jours dans le mois courant
        $daysInMonth = (int)$currentDate->format('t');
        $currentDay = (int)$currentDate->format('j');
        $remainingDaysInMonth = $daysInMonth - $currentDay + 1;

        if ($days > $remainingDaysInMonth) {
            // Ajouter les jours restants pour le mois courant
            $distributedData[] = [
                'month' => (int)$currentDate->format('n'),
                'year' => (int)$currentDate->format('Y'),
                'days' => $remainingDaysInMonth
            ];
            $days -= $remainingDaysInMonth;
            $currentDate->modify('first day of next month'); // Passer au mois suivant
        } else {
            // Tous les jours restants tiennent dans le mois courant
            $distributedData[] = [
                'month' => (int)$currentDate->format('n'),
                'year' => (int)$currentDate->format('Y'),
                'days' => $days
            ];
            $days = 0;
        }
    }

    return $distributedData;
}

// Modification de la requête SQL pour récupérer et diviser les jours
if (isset($_GET['year'])) {
    $year = intval($_GET['year']);
    $sql = "
        SELECT date_accident, nombre_dejour 
        FROM accident 
        WHERE YEAR(date_accident) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $distributedResults = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['date_accident'];
        $days = intval($row['nombre_dejour']);

        // Répartir les jours pour chaque date
        $distributedData = distributeDays($date, $days);

        foreach ($distributedData as $data) {
            $key = $data['year'] . '-' . $data['month'];
            if (!isset($distributedResults[$key])) {
                $distributedResults[$key] = 0;
            }
            $distributedResults[$key] += $data['days'];
        }
    }

    // Convertir les résultats en format JSON
    $finalData = [];
    foreach ($distributedResults as $key => $totalDays) {
        [$year, $month] = explode('-', $key);
        $finalData[] = [
            'mois' => intval($month),
            'annee' => intval($year),
            'total_jours' => $totalDays
        ];
    }

    echo json_encode($finalData);
    exit;
}






$matricule = "";
$nom_prenom = "";
$fonction = "";
$date_accident = "";
$place_accident = "";
$nombre_dejour = "";
$causes = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Si le formulaire est soumis, on traite les données

    // Utilisation de trim pour éviter les espaces
    $matricule = isset($_POST['matricule']) ? trim($_POST['matricule']) : '';
    $nom_prenom = isset($_POST['nom_prenom']) ? trim($_POST['nom_prenom']) : '';
    $fonction = isset($_POST['fonction']) ? trim($_POST['fonction']) : '';
    $date_accident = isset($_POST['date_accident']) ? trim($_POST['date_accident']) : '';
    $place_accident = isset($_POST['place']) ? trim($_POST['place']) : '';
    $nombre_dejour = isset($_POST['nbj']) ? trim($_POST['nbj']) : '';
    $causes = isset($_POST['causes']) ? trim($_POST['causes']) : '';

    // Vérification des champs
    if (empty($matricule) || empty($nom_prenom) || empty($fonction) || empty($date_accident) || empty($place_accident) || empty($nombre_dejour) || empty($causes)) {
        $message = "";
    } else {
        // Étape 1: Insérer les données dans la table "accident"
        $stmt_insert = $conn->prepare("INSERT INTO accident (mat, `nom et prenom`, fonction, date_accident, place_accident, nombre_dejour, causes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssis", $matricule, $nom_prenom, $fonction, $date_accident, $place_accident, $nombre_dejour, $causes);

        if ($stmt_insert->execute()) {
            $message = "Enregistrement effectué avec succès.";
            
            // Redirection pour éviter la double soumission
            header("Location: ".$_SERVER['PHP_SELF']);
            exit(); // Terminer l'exécution pour éviter tout traitement supplémentaire
        } else {
            $message = "Erreur lors de l'enregistrement dans la base de données : " . $conn->error;
        }
        $stmt_insert->close();
    }
}

// Traitement de la recherche du matricule
if (isset($_GET['matricule'])) {
    $matricule = $_GET['matricule'];

    // Préparer la requête pour récupérer le nom et prénom et la fonction
    $stmt = $conn->prepare("SELECT `nom et prenom`, fonction FROM agent WHERE mat = ?");
    $stmt->bind_param("i", $matricule);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Si le matricule est trouvé, récupérer les données
        $stmt->bind_result($nom_prenom, $fonction);
        $stmt->fetch();
        // Retourner les données sous format JSON
        echo json_encode([
            "status" => "success",
            "nom_prenom" => $nom_prenom,
            "fonction" => $fonction
        ]);
    } else {
        // Si aucun résultat n'est trouvé
        echo json_encode(["status" => "error", "message" => "Matricule non trouvé"]);
    }
    $stmt->close();
    $conn->close();
    exit(); // Terminer l'exécution après avoir renvoyé la réponse JSON
}


// Récupérer les statistiques des causes
$sql = "SELECT causes AS cause, COUNT(*) AS count, 
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM accident) * 100), 2) AS percentage
        FROM accident
        GROUP BY causes";
$result = $conn->query($sql);

$stats = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
}



?>


<!-------------------------------------------------------HTML------------------------------------------------------------>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Accidents de Travail</title>
    <link rel="stylesheet" href="index.css">
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
            <li><a href="visite.php">visite</a></li>
            <li><a href="logout.php">Se déconnecter</a></li>
        </ul>

    
    </div>
    
    <!-- Formulaire -->
    <div class="form-container">
    <form method="POST" action="">
    <?php if (!empty($message)): ?>
    <div id="message" 
         class="message <?php echo (stripos($message, 'Erreur') !== false || stripos($message, 'error') !== false) ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>


        <div class="form-group">
            <label for="matricule">Matricule :</label>
            <input type="number" id="matricule" name="matricule" placeholder="Saisir le matricule" required>
        </div>

        <div class="form-group">
            <label for="nom_prenom">Nom et Prénom :</label>
            <input type="text" id="nom_prenom" name="nom_prenom" placeholder="Nom et Prénom" readonly>
        </div>

        <div class="form-group">
            <label for="fonction">Fonction :</label>
            <input type="text" id="fonction" name="fonction" placeholder="Fonction" readonly>
        </div>

        <button type="button" id="search">🔍</button>

        <div class="form-group">
            <label for="place">Place de l'accident :</label>
            <select id="place" name="place" required>
                <option value="" disabled selected>Choisir la place</option>
                <option value="atelier central">atelier central</option>
                <option value="voie publique">voie publique</option>
                <option value="la gare">la gare</option>
                <option value="atelier mateur">atelier mateur</option>
                <option value="atelier bizerte">atelier bizerte</option>
                <option value="atelier manzelbouguiba">atelier manzelbouguiba</option>
                <option value="atelier sajnene">atelier sajnene</option>
                <option value="atelier ras jbal">atelier ras jbal</option>
               
            </select>
        </div>

        <div class="form-group">
            <label for="date_accident">Date de l'accident :</label>
            <input type="date" id="date_accident" name="date_accident" required>
        </div>

        <div class="form-group">
            <label for="nbj">Nombre de jours :</label>
            <input type="number" id="nbj" name="nbj" placeholder="Saisir le nombre de jours" required>
        </div>

        <div class="form-group">
            <label for="causes">Les causes :</label>
            <select id="causes" name="causes" required>
                <option value="" disabled selected>Choisir une cause</option>
                <option value="Agression par violence">Agression par violence</option>
                <option value="Glissement et trébuchement">Glissement et trébuchement</option>
                <option value="Incident de parcours">Incident de parcours</option>
                <option value="Accident de la route">Accident de la route</option>
                <option value="Non-utilisation des moyens de prévention">Non-utilisation des moyens de prévention</option>
                <option value="Facteurs accidentels">Facteurs accidentels</option>
                <option value="Maintenance et équipements">Maintenance et équipements</option>
            </select>
        </div>

        <button type="submit" class="submit-btn">Envoyer</button>
    </form>
</div>


<div id="year-container">
    <h2>Liste des Années</h2>
    <ul id="year-list">
        <?php 
        $query = "SELECT YEAR(date_accident) AS annee, SUM(nombre_dejour) AS total_jours 
                  FROM accident 
                  GROUP BY YEAR(date_accident) 
                  ORDER BY YEAR(date_accident) DESC";

        $result_years = $conn->query($query);
        if ($result_years->num_rows > 0):
            while ($row = $result_years->fetch_assoc()):
        ?>
            <li class="year-item" data-year="<?= htmlspecialchars($row['annee']); ?>">
                <?= htmlspecialchars($row['annee']); ?> 
                <span class="total-days">(Total : <?= htmlspecialchars($row['total_jours'] ?? 0); ?> jours)</span>
            </li>
        <?php 
            endwhile;
        else: 
        ?>
            <li>Aucune donnée disponible.</li>
        <?php endif; ?>
    </ul>
</div>
<br> <br>
<div id="chart-container" style="display: none;">
    <h2 id="selected-year"></h2>
    <canvas id="year-chart" style="margin-right:50%;"></canvas>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const yearItems = document.querySelectorAll('.year-item');
        const chartContainer = document.getElementById('chart-container');
        const selectedYear = document.getElementById('selected-year');
        const ctx = document.getElementById('year-chart').getContext('2d');
        let chart;

        yearItems.forEach(item => {
            item.addEventListener('click', function () {
                const year = this.dataset.year;
                selectedYear.textContent = `Statistiques pour l'année ${year}`;

                // Charger les données pour les mois et les jours via AJAX
                fetch(`?year=${year}`)
    .then(response => response.json())
    .then(data => {
        const labels = data.map(item => getMonthName(item.mois));
        const values = data.map(item => item.total_jours);

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nombre de jours',
                    data: values,
                    backgroundColor: 'rgba(80, 255, 255, 0.5)',
                    borderColor: 'rgb(0, 136, 136)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        chartContainer.style.display = 'block';
    })
    .catch(error => {
        console.error('Erreur lors du chargement des données :', error);
    });

            });
        });

        function getMonthName(month) {
            const monthNames = [
                "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
                "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
            ];
            return monthNames[month - 1];
        }
    });
</script>

<br><br>
<!-- Zone de recherche -->
<form method="POST" action="" class="search">
    <input 
        type="number" 
        name="searchMatricule" 
        placeholder="Entrez un matricule" 
        value="<?= isset($searchMatricule) ? htmlspecialchars($searchMatricule) : ''; ?>" 
        required>
    <button type="submit">Rechercher</button>
</form>

<!-- Résultats -->
<?php 
    // Vérification si le formulaire a été soumis et si searchMatricule est défini
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchMatricule = isset($_POST['searchMatricule']) ? trim($_POST['searchMatricule']) : '';
        if (!empty($searchMatricule)) {
            // Effectuer la recherche et afficher les résultats
            if (!empty($personDetails)) : ?>
                <h2>Résultats pour le matricule : <?= htmlspecialchars($searchMatricule); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom et Prénom</th>
                            <th>Date de l'Accident</th>
                            <th>Nombre de Jours</th>
                            <th>Causes</th>
                            
                        </tr>
                    </thead>
                    <tbody>
    <?php 
    $totalJours = 0;
    foreach ($personDetails as $detail): 
        $totalJours += $detail['total_jours'];
    ?>
        <tr>
            <td><?= htmlspecialchars($detail['mat']); ?></td>
            <td><?= htmlspecialchars($detail['nom_et_prenom']); ?></td>
            <td><?= htmlspecialchars($detail['date_accident']); ?></td>
            <td><?= htmlspecialchars($detail['total_jours']); ?></td>
            <td><?= htmlspecialchars($detail['causes']); ?></td>
            

        </tr>
    <?php endforeach; ?>
</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total des Jours :</strong></td>
                            <td><strong><?= htmlspecialchars($totalJours); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p>Aucun résultat trouvé pour le matricule : <?= htmlspecialchars($searchMatricule); ?></p>
            <?php endif; ?>
        <?php } else { ?>
            <p class="message error">Veuillez remplir le champ du matricule.</p>
        <?php }
    }
?>
<br>
 <br>

<div class="stats-container">
    <!-- Tableau pour le nombre d'accidents par mois et année -->
     <div>
    <h2 style="text-align: center;">Nombre d'Accidents par Mois et Année</h2>
    <table>
        <thead>
            <tr>
                <th>Année</th>
                <th>Nom du Mois</th>
                <th>Nombre d'Accidents</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Requête pour récupérer le nombre d'accidents par mois et année
            $sql_accidents_per_month = "
                SELECT 
                    YEAR(date_accident) AS annee, 
                    MONTH(date_accident) AS mois, 
                    COUNT(*) AS nombre_accidents
                FROM accident
                GROUP BY annee, mois
                ORDER BY annee, mois
            ";
            $result_accidents_per_month = $conn->query($sql_accidents_per_month);
            if ($result_accidents_per_month->num_rows > 0) {
                while ($row = $result_accidents_per_month->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['annee']) . "</td>";
                    echo "<td>" . getMonthName($row['mois']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nombre_accidents']) . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
    </div>
    <br><br>
    <!-- Tableau pour le nombre de personnes par fonction et pourcentage -->
     <div>
    <h2 style="text-align: center;">Nombre de Personnes par Fonction et Pourcentage</h2>
    <table>
        <thead>
            <tr>
                <th>Fonction</th>
                <th>Nombre de Personnes</th>
                <th>Pourcentage</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Requête pour récupérer le nombre de personnes par fonction
            $sql_person_per_function = "
                SELECT fonction, COUNT(DISTINCT mat) AS nombre_personnes
                FROM accident
                GROUP BY fonction
            ";
            $result_person_per_function = $conn->query($sql_person_per_function);

            // Calcul du nombre total de personnes
            $total_personnes = 0;
            $personnes_per_function = [];
            if ($result_person_per_function->num_rows > 0) {
                while ($row = $result_person_per_function->fetch_assoc()) {
                    $fonction = $row['fonction'];
                    $nombre_personnes = $row['nombre_personnes'];
                    $personnes_per_function[$fonction] = $nombre_personnes;
                    $total_personnes += $nombre_personnes;
                }
            }

            // Affichage du nombre de personnes par fonction et calcul du pourcentage
            foreach ($personnes_per_function as $fonction => $nombre_personnes) {
                $pourcentage = $total_personnes > 0 ? ($nombre_personnes / $total_personnes) * 100 : 0;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($fonction) . "</td>";
                echo "<td>" . htmlspecialchars($nombre_personnes) . "</td>";
                echo "<td>" . number_format($pourcentage, 2) . "%</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
    <br><br>

<div>
<h2 style="text-align: center;">Statistiques des causes d'accidents</h2>
<table>
    <thead>
        <tr>
            <th>Cause</th>
            <th>Nombre</th>
            <th>Pourcentage</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($stats)) : ?>
            <?php foreach ($stats as $data) : ?>
                <tr>
                    <td><?= htmlspecialchars($data['cause'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($data['count'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($data['percentage'] ?? '0%') ?> %</td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="3">Aucune donnée disponible</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</div>

   <script src="index.js"></script> 
</body>
</html>
