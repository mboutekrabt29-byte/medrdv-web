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

$userId = (int)($input['user_id'] ?? 0);
$firstName = trim((string)($input['first_name'] ?? ''));
$lastName = trim((string)($input['last_name'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));

if ($userId <= 0 || $firstName === '' || $lastName === '' || $phone === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données invalides'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT id
FROM users
WHERE phone = ?
AND id <> ?
LIMIT 1
");
$stmt->execute([$phone, $userId]);

if ($stmt->fetch()) {
http_response_code(409);
echo json_encode([
'success' => false,
'message' => 'Ce numéro de téléphone est déjà utilisé'
]);
exit;
}

$stmt = $pdo->prepare("
UPDATE users
SET first_name = ?, last_name = ?, phone = ?
WHERE id = ?
");
$stmt->execute([$firstName, $lastName, $phone, $userId]);

echo json_encode([
'success' => true,
'message' => 'Profil mis à jour avec succès'
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