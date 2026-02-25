<?php
require __DIR__ . "/../config/db.php";
session_start();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: ../public/login.php?err=Champs manquants");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../public/login.php?err=Utilisateur introuvable");
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    header("Location: ../public/login.php?err=Mot de passe incorrect");
    exit;
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'role' => $user['role'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email']
];

header("Location: ../public/dashboard.php");
exit;