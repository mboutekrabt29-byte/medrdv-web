<?php

$host = "localhost";
$dbname = "medrdv";
$user = "root";
$password = ""; // XAMPP par défaut

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur connexion DB : " . $e->getMessage());
}