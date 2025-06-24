<?php
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
?>
