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

$search = trim((string)($_GET['search'] ?? ''));
$specialty = trim((string)($_GET['specialty'] ?? ''));
$region = trim((string)($_GET['region'] ?? ''));

try {
$sql = "
SELECT
d.user_id AS doctor_id,
d.specialty,
d.city,
d.commune,
d.clinic_address,
u.first_name,
u.last_name,
u.email,
u.phone
FROM doctors d
INNER JOIN users u ON u.id = d.user_id
WHERE u.role = 'DOCTOR'
";

$params = [];

if ($search !== '') {
$sql .= " AND (
u.first_name LIKE ?
OR u.last_name LIKE ?
OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
OR d.specialty LIKE ?
OR d.city LIKE ?
OR d.commune LIKE ?
)";
$like = '%' . $search . '%';
array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($specialty !== '' && $specialty !== 'Tous') {
$sql .= " AND d.specialty = ?";
$params[] = $specialty;
}

if ($region !== '' && $region !== 'Toutes') {
$sql .= " AND (d.city = ? OR d.commune = ?)";
$params[] = $region;
$params[] = $region;
}

$sql .= " ORDER BY u.first_name ASC, u.last_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$doctors = array_map(function ($row) {
$firstName = trim((string)($row['first_name'] ?? ''));
$lastName = trim((string)($row['last_name'] ?? ''));
$fullName = 'Dr ' . trim($firstName . ' ' . $lastName);

$initials = strtoupper(
substr($firstName, 0, 1) . substr($lastName, 0, 1)
);

return [
'doctor_id' => (int)$row['doctor_id'],
'name' => $fullName,
'first_name' => $firstName,
'last_name' => $lastName,
'specialty' => $row['specialty'] ?? '',
'email' => $row['email'] ?? '',
'phone' => $row['phone'] ?? '',
'city' => $row['city'] ?? '',
'commune' => $row['commune'] ?? '',
'clinic_address' => $row['clinic_address'] ?? '',
'initials' => $initials !== '' ? $initials : 'DR',
'rating' => 4.8,
'availability' => 'Disponible 7j/7',
'availableToday' => true,
];
}, $rows);

echo json_encode([
'success' => true,
'doctors' => $doctors
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