<?php
declare(strict_types=1);

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

session_start();

date_default_timezone_set('Africa/Algiers');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/register.php?err=Méthode non autorisée");
    exit;
}

verify_csrf();

$first = trim((string)($_POST['first_name'] ?? ''));
$last = trim((string)($_POST['last_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$role = (string)($_POST['role'] ?? 'PATIENT');
$password = (string)($_POST['password'] ?? '');

// Vérification champs requis
if ($first === '' || $last === '' || $email === '' || $phone === '' || $password === '') {
    header("Location: ../public/register.php?err=Veuillez remplir tous les champs");
    exit;
}

// Validation rôle
if (!in_array($role, ['PATIENT', 'DOCTOR'], true)) {
    header("Location: ../public/register.php?err=Rôle invalide");
    exit;
}

// Validation prénom / nom
if (mb_strlen($first) < 2 || mb_strlen($first) > 50) {
    header("Location: ../public/register.php?err=Prénom invalide");
    exit;
}

if (mb_strlen($last) < 2 || mb_strlen($last) > 50) {
    header("Location: ../public/register.php?err=Nom invalide");
    exit;
}

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../public/register.php?err=Adresse email invalide");
    exit;
}

$email = mb_strtolower($email);

// Nettoyage téléphone
$phone = preg_replace('/[^\d+\s\-\(\)]/', '', $phone) ?? '';

if ($phone === '' || mb_strlen($phone) < 8 || mb_strlen($phone) > 20) {
    header("Location: ../public/register.php?err=Numéro de téléphone invalide");
    exit;
}

// Validation mot de passe
if (strlen($password) < 8) {
    header("Location: ../public/register.php?err=Le mot de passe doit contenir au moins 8 caractères");
    exit;
}

$hasUpper = preg_match('/[A-Z]/', $password);
$hasLower = preg_match('/[a-z]/', $password);
$hasDigit = preg_match('/\d/', $password);

if (!$hasUpper || !$hasLower || !$hasDigit) {
    header("Location: ../public/register.php?err=Mot de passe trop faible");
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    // Vérifier email unique
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        header("Location: ../public/register.php?err=Cet email est déjà utilisé");
        exit;
    }

    // Vérifier téléphone unique
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE phone = ?
        LIMIT 1
    ");
    $stmt->execute([$phone]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        header("Location: ../public/register.php?err=Ce téléphone est déjà utilisé");
        exit;
    }

    // Création utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO users (role, first_name, last_name, email, phone, password_hash)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$role, $first, $last, $email, $phone, $hash]);

    $userId = (int)$pdo->lastInsertId();

    // Si médecin => créer entrée dans doctors
    if ($role === 'DOCTOR') {
        $stmt = $pdo->prepare("
            INSERT INTO doctors (user_id, specialty)
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, 'Généraliste']);
    }

    $pdo->commit();

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $userId,
        'role' => $role,
        'first_name' => $first,
        'last_name' => $last,
        'email' => $email,
    ];

    $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');

    header("Location: ../public/dashboard.php?ok=Compte créé avec succès");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/register.php?err=Erreur serveur, veuillez réessayer");
    exit;
}