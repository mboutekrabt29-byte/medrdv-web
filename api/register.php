<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$first = trim((string)($input['first_name'] ?? ''));
$last = trim((string)($input['last_name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$role = (string)($input['role'] ?? 'PATIENT');
$password = (string)($input['password'] ?? '');

// Vérification champs requis
if ($first === '' || $last === '' || $email === '' || $phone === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Veuillez remplir tous les champs'
    ]);
    exit;
}

// Validation rôle
if (!in_array($role, ['PATIENT', 'DOCTOR'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Rôle invalide'
    ]);
    exit;
}

// Validation prénom / nom
if (mb_strlen($first) < 2 || mb_strlen($first) > 50) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Prénom invalide'
    ]);
    exit;
}

if (mb_strlen($last) < 2 || mb_strlen($last) > 50) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nom invalide'
    ]);
    exit;
}

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Adresse email invalide'
    ]);
    exit;
}

$email = mb_strtolower($email);

// Nettoyage téléphone
$phone = preg_replace('/[^\d+\s\-\(\)]/', '', $phone) ?? '';

if ($phone === '' || mb_strlen($phone) < 8 || mb_strlen($phone) > 20) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Numéro de téléphone invalide'
    ]);
    exit;
}

// Validation mot de passe
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Le mot de passe doit contenir au moins 8 caractères'
    ]);
    exit;
}

$hasUpper = preg_match('/[A-Z]/', $password);
$hasLower = preg_match('/[a-z]/', $password);
$hasDigit = preg_match('/\d/', $password);

if (!$hasUpper || !$hasLower || !$hasDigit) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Mot de passe trop faible'
    ]);
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
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Cet email est déjà utilisé'
        ]);
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
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Ce téléphone est déjà utilisé'
        ]);
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

    echo json_encode([
        'success' => true,
        'message' => 'Compte créé avec succès',
        'requiresVerification' => $role === 'DOCTOR',
        'user' => [
            'id' => $userId,
            'role' => $role,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone
        ]
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}