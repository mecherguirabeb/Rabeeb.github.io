<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers la page d'accueil
if (isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

// Initialisation des messages d'erreur
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les informations du formulaire
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Vérification des informations (vous pouvez remplacer cela par une base de données)
    if ($username === 'admin' && $password === '0000') {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'admin'; // Définir le rôle de l'utilisateur

        // Rediriger vers la page appropriée
        header('Location: visite.php');
        exit();
    } elseif ($username === 'admin' && $password === '1111') {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'agent'; // Définir le rôle de l'utilisateur

        // Rediriger vers la page des agents
        header('Location: agents.php');
        exit();
    } else {
        $error_message = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
}
?>

<!-- Formulaire de connexion -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SRTB</title>
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <div class="container" style="position: absolute;
    top: 20%;
    left: 30%;">
        <h1>Bienvenue à l'application SRTB</h1>
        <div class="form-container">
            <form method="POST" action="">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Se connecter</button>
            </form>
            <?php if ($error_message): ?>
                <div class="error"><?= $error_message ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
