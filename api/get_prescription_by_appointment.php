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

$appointmentId = (int)($_GET['appointment_id'] ?? 0);

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
SELECT id, appointment_id, doctor_id, patient_id, content, pdf_file, created_at
FROM prescriptions
WHERE appointment_id = ?
ORDER BY id DESC
LIMIT 1
");
$stmt->execute([$appointmentId]);
$prescription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prescription) {
echo json_encode([
'success' => true,
'exists' => false
]);
exit;
}

$pdfUrl = null;
if (!empty($prescription['pdf_file'])) {
$pdfUrl = 'http://192.168.100.5/medrdv_web/uploads/prescriptions/' . $prescription['pdf_file'];
}

echo json_encode([
'success' => true,
'exists' => true,
'prescription' => [
'id' => (int)$prescription['id'],
'appointment_id' => (int)$prescription['appointment_id'],
'doctor_id' => (int)$prescription['doctor_id'],
'patient_id' => (int)$prescription['patient_id'],
'content' => $prescription['content'],
'pdf_file' => $prescription['pdf_file'],
'pdf_url' => $pdfUrl,
'created_at' => $prescription['created_at'],
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