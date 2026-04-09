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
$month = trim((string)($_GET['month'] ?? ''));

if ($doctorId <= 0 || $month === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'doctor_id ou month manquant'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT
DATE(appt_at) AS appt_date,
COUNT(*) AS total_count,
SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
SUM(CASE WHEN status = 'CONFIRMED' THEN 1 ELSE 0 END) AS confirmed_count
FROM appointments
WHERE doctor_id = ?
AND DATE_FORMAT(appt_at, '%Y-%m') = ?
GROUP BY DATE(appt_at)
ORDER BY appt_date ASC
");
$stmt->execute([$doctorId, $month]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
'success' => true,
'dates' => $rows
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