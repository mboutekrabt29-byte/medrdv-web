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

require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$doctorId = (int)($_GET['doctor_id'] ?? 0);
$date = trim((string)($_GET['date'] ?? ''));

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
SELECT id, slot_time, is_booked
FROM availability_slots
WHERE doctor_id = ?
AND DATE(slot_time) = ?
ORDER BY slot_time ASC
");
$stmt->execute([$doctorId, $date]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
'success' => true,
'slots' => $slots
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