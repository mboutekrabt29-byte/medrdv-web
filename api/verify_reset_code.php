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

$channel = strtoupper(trim((string)($input['channel'] ?? '')));
$value = trim((string)($input['value'] ?? ''));
$code = trim((string)($input['code'] ?? ''));

if (!in_array($channel, ['EMAIL', 'PHONE'], true) || $value === '' || $code === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit;
}

try {
    if ($channel === 'EMAIL') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM password_resets
            WHERE email = ?
              AND reset_code = ?
              AND channel = 'EMAIL'
              AND used = 0
              AND expires_at >= NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([mb_strtolower($value), $code]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id
            FROM password_resets
            WHERE phone = ?
              AND reset_code = ?
              AND channel = 'PHONE'
              AND used = 0
              AND expires_at >= NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$value, $code]);
    }

    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Code invalide ou expiré'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Code valide'
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