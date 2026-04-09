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
u.id AS doctor_id,
u.first_name,
u.last_name,
u.email,
u.phone,
d.specialty,
d.city,
d.clinic_address
FROM users u
INNER JOIN doctors d ON d.user_id = u.id
WHERE u.id = ?
AND u.role = 'DOCTOR'
LIMIT 1
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Médecin introuvable'
]);
exit;
}

$initials = strtoupper(
mb_substr((string)$doctor['first_name'], 0, 1) .
mb_substr((string)$doctor['last_name'], 0, 1)
);

echo json_encode([
'success' => true,
'doctor' => [
'doctor_id' => (int)$doctor['doctor_id'],
'name' => 'Dr ' . trim($doctor['first_name'] . ' ' . $doctor['last_name']),
'first_name' => $doctor['first_name'],
'last_name' => $doctor['last_name'],
'email' => $doctor['email'],
'phone' => $doctor['phone'],
'specialty' => $doctor['specialty'],
'city' => $doctor['city'],
'clinic_address' => $doctor['clinic_address'],
'initials' => $initials,
'rating' => 4.8,
'availability' => 'Disponible 7j/7',
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