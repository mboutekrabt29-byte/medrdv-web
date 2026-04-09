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

require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$email = trim((string)($input['email'] ?? ''));
$code = trim((string)($input['code'] ?? ''));

if ($email === '' || $code === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données manquantes'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT *
FROM doctor_verifications
WHERE email = ?
AND code = ?
AND is_verified = 0
AND expires_at >= NOW()
ORDER BY id DESC
LIMIT 1
");
$stmt->execute([$email, $code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Code invalide ou expiré'
]);
exit;
}

$pdo->beginTransaction();

$hash = password_hash($row['password_plain'], PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
INSERT INTO users (role, first_name, last_name, email, phone, password_hash)
VALUES ('DOCTOR', ?, ?, ?, ?, ?)
");
$stmt->execute([
$row['first_name'],
$row['last_name'],
$row['email'],
$row['phone'],
$hash
]);

$userId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("
INSERT INTO doctors (user_id, specialty, city, clinic_address)
VALUES (?, ?, 'Kouba', 'Clinique Inaya - Kouba')
");
$stmt->execute([$userId, $row['specialty']]);

$stmt = $pdo->prepare("
UPDATE doctor_verifications
SET is_verified = 1
WHERE id = ?
");
$stmt->execute([(int)$row['id']]);

$pdo->commit();

echo json_encode([
'success' => true,
'message' => 'Compte médecin créé avec succès'
]);
exit;

} catch (Throwable $e) {
if ($pdo->inTransaction()) {
$pdo->rollBack();
}

http_response_code(500);
echo json_encode([
'success' => false,
'message' => 'Erreur serveur'
]);
exit;
}