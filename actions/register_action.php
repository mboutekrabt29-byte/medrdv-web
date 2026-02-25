<?php
require __DIR__ . "/../config/db.php";
session_start();

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role  = $_POST['role'] ?? 'PATIENT';
$password = $_POST['password'] ?? '';

if ($first === '' || $last === '' || $email === '' || $phone === '' || $password === '') {
    header("Location: ../public/register.php?err=Champs manquants");
    exit;
}

if (!in_array($role, ['PATIENT','DOCTOR'])) {
    header("Location: ../public/register.php?err=Rôle invalide");
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (role, first_name, last_name, email, phone, password_hash)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$role, $first, $last, $email, $phone, $hash]);

    $userId = $pdo->lastInsertId();

    // Si médecin → créer entrée dans doctors
    if ($role === 'DOCTOR') {
        $pdo->prepare("
            INSERT INTO doctors (user_id, specialty)
            VALUES (?, 'Généraliste')
        ")->execute([$userId]);
    }

    $_SESSION['user'] = [
        'id' => $userId,
        'role' => $role,
        'first_name' => $first,
        'last_name' => $last,
        'email' => $email
    ];

    header("Location: ../public/dashboard.php");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/register.php?err=Email ou téléphone déjà utilisé");
    exit;
}