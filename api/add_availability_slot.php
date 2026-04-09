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
$time = trim((string)($input['time'] ?? ''));

if ($doctorId <= 0 || $date === '' || $time === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données manquantes'
]);
exit;
}

if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Format heure invalide. Utilisez HH:MM'
]);
exit;
}

[$hour, $minute] = explode(':', $time);
$hour = (int)$hour;
$minute = (int)$minute;

if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Heure invalide'
]);
exit;
}

try {
$timeFormatted = sprintf('%02d:%02d', $hour, $minute);
$slotTime = $date . ' ' . $timeFormatted . ':00';

$stmt = $pdo->prepare("
SELECT id
FROM availability_slots
WHERE doctor_id = ?
AND slot_time = ?
LIMIT 1
");
$stmt->execute([$doctorId, $slotTime]);

if ($stmt->fetch()) {
http_response_code(409);
echo json_encode([
'success' => false,
'message' => 'Ce créneau existe déjà'
]);
exit;
}

$stmt = $pdo->prepare("
INSERT INTO availability_slots (doctor_id, slot_time, is_booked)
VALUES (?, ?, 0)
");
$stmt->execute([$doctorId, $slotTime]);

echo json_encode([
'success' => true,
'message' => 'Créneau ajouté avec succès'
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