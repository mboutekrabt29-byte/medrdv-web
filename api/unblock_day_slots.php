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

$doctorId = (int)($input['doctor_id'] ?? 0);
$date = trim((string)($input['date'] ?? ''));

if ($doctorId <= 0 || $date === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'doctor_id ou date manquant'
]);
exit;
}

$defaultTimes = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];

try {
$addedCount = 0;

foreach ($defaultTimes as $time) {
$slotTime = $date . ' ' . $time . ':00';

$stmt = $pdo->prepare("
SELECT id
FROM availability_slots
WHERE doctor_id = ?
AND slot_time = ?
LIMIT 1
");
$stmt->execute([$doctorId, $slotTime]);

if (!$stmt->fetch()) {
$stmt = $pdo->prepare("
INSERT INTO availability_slots (doctor_id, slot_time, is_booked)
VALUES (?, ?, 0)
");
$stmt->execute([$doctorId, $slotTime]);
$addedCount++;
}
}

echo json_encode([
'success' => true,
'message' => $addedCount > 0
? 'Journée débloquée avec succès'
: 'Les créneaux standards existent déjà pour cette journée'
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