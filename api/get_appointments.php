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

require __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$patientId = (int)($_GET['patient_id'] ?? 0);

if ($patientId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'patient_id manquant'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT
a.id,
a.appt_at,
a.status,
u.first_name,
u.last_name,
d.specialty
FROM appointments a
INNER JOIN doctors d ON d.user_id = a.doctor_id
INNER JOIN users u ON u.id = a.doctor_id
WHERE a.patient_id = ?
ORDER BY a.appt_at DESC
");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
'success' => true,
'appointments' => $appointments
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