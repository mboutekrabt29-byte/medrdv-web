Change_password . Php :
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
$currentPassword = (string)($input['current_password'] ?? '');
$newPassword = (string)($input['new_password'] ?? '');

if ($userId <= 0 || $currentPassword === '' || $newPassword === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données invalides'
]);
exit;
}

if (strlen($newPassword) < 8) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Le mot de passe doit contenir au moins 8 caractères'
]);
exit;
}

$hasUpper = preg_match('/[A-Z]/', $newPassword);
$hasLower = preg_match('/[a-z]/', $newPassword);
$hasDigit = preg_match('/\d/', $newPassword);

if (!$hasUpper || !$hasLower || !$hasDigit) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Le nouveau mot de passe doit contenir une majuscule, une minuscule et un chiffre'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT password_hash
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

if (!password_verify($currentPassword, $user['password_hash'])) {
http_response_code(401);
echo json_encode([
'success' => false,
'message' => 'Mot de passe actuel incorrect'
]);
exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
UPDATE users
SET password_hash = ?
WHERE id = ?
");
$stmt->execute([$newHash, $userId]);

echo json_encode([
'success' => true,
'message' => 'Mot de passe mis à jour avec succès'
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
