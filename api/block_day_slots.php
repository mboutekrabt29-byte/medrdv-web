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

try {
$stmt = $pdo->prepare("
SELECT COUNT(*) AS total_free
FROM availability_slots
WHERE doctor_id = ?
AND DATE(slot_time) = ?
AND is_booked = 0
");
$stmt->execute([$doctorId, $date]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$freeCount = (int)($row['total_free'] ?? 0);

if ($freeCount === 0) {
echo json_encode([
'success' => true,
'message' => 'Aucun créneau libre à bloquer pour cette date'
]);
exit;
}

$stmt = $pdo->prepare("
DELETE FROM availability_slots
WHERE doctor_id = ?
AND DATE(slot_time) = ?
AND is_booked = 0
");
$stmt->execute([$doctorId, $date]);

echo json_encode([
'success' => true,
'message' => 'Journée bloquée avec succès'
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