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

$slotId = (int)($input['slot_id'] ?? 0);
$doctorId = (int)($input['doctor_id'] ?? 0);

if ($slotId <= 0 || $doctorId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données invalides'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT id, is_booked
FROM availability_slots
WHERE id = ?
AND doctor_id = ?
LIMIT 1
");
$stmt->execute([$slotId, $doctorId]);
$slot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slot) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Créneau introuvable'
]);
exit;
}

if ((int)$slot['is_booked'] === 1) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Impossible de bloquer un créneau déjà réservé'
]);
exit;
}

$stmt = $pdo->prepare("
DELETE FROM availability_slots
WHERE id = ?
AND doctor_id = ?
");
$stmt->execute([$slotId, $doctorId]);

echo json_encode([
'success' => true,
'message' => 'Créneau bloqué / supprimé avec succès'
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
