<?php
session_start();

// Vérifier si l'utilisateur est connecté et a le bon rôle
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'agent') {
    header('Location: login.php');
    exit();
}


$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "accident_de_travail";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$message = '';

// Ajouter un nouvel agent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create") {
    $mat = filter_var($_POST["matricule"], FILTER_VALIDATE_INT);
    $nom_prenom = htmlspecialchars($_POST["np"]);
    $date_naissance = htmlspecialchars($_POST["date_naissance"]);
    $fonction = htmlspecialchars($_POST["fonction"]);
    $direction = htmlspecialchars($_POST["direction"]);
    $agence = htmlspecialchars($_POST["agence"]);
    $num_cin = filter_var($_POST["num_cin"], FILTER_VALIDATE_INT);
    $num_cnss = filter_var($_POST["num_cnss"], FILTER_VALIDATE_INT);

    // Vérifier si le numéro CIN contient exactement 8 chiffres
    if (strlen((string)$num_cin) != 8 || !ctype_digit((string)$num_cin)) {
        $message = "Erreur : Le numéro CIN doit contenir exactement 8 chiffres.";
    }
    // Vérifier si l'âge est > 18
    else {
        $birth_date = new DateTime($date_naissance);
        $current_date = new DateTime();
        $age = $current_date->diff($birth_date)->y; // Calculer la différence en années

        if ($age < 18) {
            $message = "Erreur : L'agent doit avoir plus de 18 ans.";
        } else {
            // Vérifier si le matricule existe déjà
            $check_sql = "SELECT * FROM agent WHERE mat = '$mat'";
            $result = $conn->query($check_sql);

            if ($result->num_rows > 0) {
                // Matricule déjà existant
                $message = "Erreur : Le matricule existe déjà.";
            } else {
                // Vérifier si num_cin ou num_cnss existe déjà
                $check_sql_cin_cnss = "SELECT * FROM agent WHERE num_cin = '$num_cin' OR num_cnss = '$num_cnss'";
                $result_cin_cnss = $conn->query($check_sql_cin_cnss);

                if ($result_cin_cnss->num_rows > 0) {
                    $message = "Erreur : Le numéro CIN ou CNSS existe déjà.";
                } else {
                    // Si aucun doublon, insérer le nouvel agent
                    $sql = "INSERT INTO agent (mat, `nom et prenom`, date_naissance, fonction, direction, agence, num_cin, num_cnss) 
                            VALUES ('$mat', '$nom_prenom', '$date_naissance', '$fonction', '$direction', '$agence', '$num_cin', '$num_cnss')";

                    if ($conn->query($sql) === TRUE) {
                        $message = "L'agent a été ajouté avec succès.";
                    } else {
                        $message = "Erreur lors de l'ajout : " . $conn->error;
                    }
                }
            }
        }
    }
}

// Supprimer un agent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
    $mat = filter_var($_POST["mat"], FILTER_VALIDATE_INT);

    $sql = "DELETE FROM agent WHERE mat = '$mat'";
    if ($conn->query($sql) === TRUE) {
        $message = "L'agent a été supprimé avec succès.";
    } else {
        $message = "Erreur lors de la suppression : " . $conn->error;
    }
}

// Mettre à jour un agent
// Mettre à jour un agent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update") {
    $mat = filter_var($_POST["mat"], FILTER_VALIDATE_INT);
    $field = htmlspecialchars($_POST["field"]);
    $value = htmlspecialchars($_POST["value"]);

    if (!empty($mat) && !empty($field) && !empty($value)) {
        $value = $conn->real_escape_string($value); // Sécurisation

        // Vérifier si le champ existe dans la table
        $allowedFields = ['nom et prenom', 'date_naissance', 'fonction', 'direction', 'agence', 'num_cin', 'num_cnss'];
        if (!in_array($field, $allowedFields)) {
            echo json_encode(["status" => "error", "message" => "Champ invalide"]);
            exit();
        }

        $sql = "UPDATE agent SET `$field` = ? WHERE mat = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $value, $mat);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Modification enregistrée."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour"]);
        }

        $stmt->close();
        exit(); 
    } else {
        echo json_encode(["status" => "error", "message" => "Données manquantes."]);
        exit();
    }
}



// Lire tous les agents
$sql = "SELECT * FROM agent ORDER BY mat ASC";
$result = $conn->query($sql);

$agents = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Agents</title>
    <link rel="stylesheet" href="agent.css">
</head>
<body>
    <h1>Gestion des Agents</h1>
    <ul>
        <li><a href="logout.php">Déconnexion</a></li>
    </ul>

    <!-- Message d'information -->
    <?php if (!empty($message)) : ?>
        <div class="message">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="matricule">Matricule :</label>
                <input type="number" id="matricule" name="matricule" required>
            </div>
            <div class="form-group">
                <label for="np">Nom et Prénom :</label>
                <input type="text" id="np" name="np" required>
            </div>
            <div class="form-group">
                <label for="date_naissance">Date de Naissance :</label>
                <input type="date" id="date_naissance" name="date_naissance" required>
            </div>
            <div class="form-group">
                <label for="fonction">Fonction :</label>
                <select id="fonction" name="fonction" required>
                    <option value="" disabled selected>Choisir la fonction</option>
                    <option value="chauffeur">Chauffeur</option>
                    <option value="receveur">Receveur</option>
                    <option value="technique">Technique</option>
                    <option value="securite">Sécurité et Nettoyage</option>
                    <option value="administratif">Administratif</option>
                </select>
            </div>
            <div class="form-group">
                <label for="direction">Direction :</label>
                <select id="direction" name="direction" required>
                    <option value="" disabled selected>Choisir la Direction</option>
                    <option value="Direction General">Direction Générale</option>
                    <option value="Direction administratif et financier">Direction administratif et financier</option>
                    <option value="Inspection interne">Inspection interne</option>
                    <option value="Exploitation">Exploitation</option>
                    <option value="Technique">Technique</option>
                </select>
            </div>
            <div class="form-group">
                <label for="agence">Agence :</label>
                <select id="agence" name="agence" required>
                    <option value="" disabled selected>Choisir l'Agence</option>
                    <option value="Bizerte">Bizerte</option>
                    <option value="Manzel bourguiba">Manzel bourguiba</option>
                    <option value="Mateur">Mateur</option>
                    <option value="Ras jbal">Ras jbal</option>
                    <option value="Sajnene">Sajnene</option>
                </select>
            </div>
            <div class="form-group">
                <label for="num_cin">Numéro CIN :</label>
                <input type="number" id="num_cin" name="num_cin" required>
            </div>
            <div class="form-group">
                <label for="num_cnss">Numéro CNSS :</label>
                <input type="number" id="num_cnss" name="num_cnss" required>
            </div>
            <button type="submit" class="submit-btn">Ajouter l'Agent</button>
        </form>
    </div>

    <!-- Liste des agents -->
    <h1>Liste des Agents</h1>
    <table id="agents-table">
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom et Prénom</th>
                <th>Date de Naissance</th>
                <th>Fonction</th>
                <th>Direction</th>
                <th>Agence</th>
                <th>Num CIN</th>
                <th>Num CNSS</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agents as $agent): ?>
                <tr>
                    <td><?= htmlspecialchars($agent['mat']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="nom et prenom"><?= htmlspecialchars($agent['nom et prenom']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="date_naissance"><?= htmlspecialchars($agent['date_naissance']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="fonction"><?= htmlspecialchars($agent['fonction']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="direction"><?= htmlspecialchars($agent['direction']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="agence"><?= htmlspecialchars($agent['agence']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="num_cin"><?= htmlspecialchars($agent['num_cin']); ?></td>
                    <td contenteditable="true" data-mat="<?= $agent['mat']; ?>" data-field="num_cnss"><?= htmlspecialchars($agent['num_cnss']); ?></td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirmDelete(this);">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="mat" value="<?= htmlspecialchars($agent['mat']); ?>">
                            <button type="submit" class="delete-btn">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Mettre à jour les champs en double-clic
        document.querySelectorAll('[contenteditable="true"]').forEach(cell => {
            cell.addEventListener('blur', function () {
                const mat = this.dataset.mat;
                const field = this.dataset.field;
                const value = this.textContent.trim();

                if (value !== '') {
                    fetch('agent.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'update', mat, field, value }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message); // Afficher un message de succès
                        } else {
                            alert(data.message); // Afficher un message d'erreur
                        }
                    })
                    .catch(error => {
                        console.error('Erreur fetch :', error);
                        alert('modification effectuer.');
                    });
                }
            });
        });

        // Confirmation de suppression
        function confirmDelete(form) {
            return confirm("Êtes-vous sûr de vouloir supprimer cet agent ?");
        }
    </script>
</body>
</html>