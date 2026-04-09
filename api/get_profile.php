<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
http_response_code(200);
exit;
}

require __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$userId = (int)($_GET['user_id'] ?? 0);

if ($userId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'user_id manquant'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT id, first_name, last_name, email, phone, role
FROM users
WHERE id = ?
LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Utilisateur introuvable'
]);
exit;
}

echo json_encode([
'success' => true,
'user' => [
'id' => (int)$user['id'],
'first_name' => $user['first_name'],
'last_name' => $user['last_name'],
'email' => $user['email'],
'phone' => $user['phone'],
'role' => $user['role']
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