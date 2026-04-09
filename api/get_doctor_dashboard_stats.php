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

if ($doctorId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'doctor_id manquant'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT
SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
SUM(CASE WHEN status = 'CONFIRMED' THEN 1 ELSE 0 END) AS confirmed_count,
SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_count,
COUNT(*) AS total_count
FROM appointments
WHERE doctor_id = ?
");
$stmt->execute([$doctorId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
'success' => true,
'stats' => [
'pending_count' => (int)($stats['pending_count'] ?? 0),
'confirmed_count' => (int)($stats['confirmed_count'] ?? 0),
'cancelled_count' => (int)($stats['cancelled_count'] ?? 0),
'total_count' => (int)($stats['total_count'] ?? 0),
]
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