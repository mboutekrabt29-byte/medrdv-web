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

$input = json_decode(file_get_contents("php://input"), true);

$appointmentId = (int)($input['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'appointment_id manquant'
]);
exit;
}

try {
$stmt = $pdo->prepare("
UPDATE appointments
SET status = 'COMPLETED'
WHERE id = ?
");
$stmt->execute([$appointmentId]);

echo json_encode([
'success' => true,
'message' => 'Rendez-vous terminé avec succès'
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