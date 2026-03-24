<?php
declare(strict_types=1);

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";
session_start();

date_default_timezone_set('Africa/Algiers');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/login.php?err=Méthode non autorisée");
    exit;
}

verify_csrf();

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: ../public/login.php?err=Veuillez remplir tous les champs");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../public/login.php?err=Adresse email invalide");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, role, first_name, last_name, email, password_hash
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        header("Location: ../public/login.php?err=Email ou mot de passe incorrect");
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'         => (int)$user['id'],
        'role'       => $user['role'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
    ];

    $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');

    header("Location: ../public/dashboard.php?ok=Connexion réussie");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/login.php?err=Erreur serveur, veuillez réessayer");
    exit;
}