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
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$patientId = (int)($input['patient_id'] ?? 0);
$doctorId = (int)($input['doctor_id'] ?? 0);
$appointmentDate = trim((string)($input['appointment_date'] ?? ''));
$appointmentTime = trim((string)($input['appointment_time'] ?? ''));

if ($patientId <= 0 || $doctorId <= 0 || $appointmentDate === '' || $appointmentTime === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données manquantes'
]);
exit;
}

try {
$apptAt = $appointmentDate . ' ' . $appointmentTime . ':00';

$stmt = $pdo->prepare("
SELECT id
FROM availability_slots
WHERE doctor_id = ?
AND slot_time = ?
AND is_booked = 0
LIMIT 1
");
$stmt->execute([$doctorId, $apptAt]);
$slot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slot) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Créneau indisponible'
]);
exit;
}

$slotId = (int)$slot['id'];

$pdo->beginTransaction();

$stmt = $pdo->prepare("
SELECT id
FROM appointments
WHERE slot_id = ?
AND status IN ('PENDING', 'CONFIRMED')
LIMIT 1
");
$stmt->execute([$slotId]);

if ($stmt->fetch()) {
$pdo->rollBack();
http_response_code(409);
echo json_encode([
'success' => false,
'message' => 'Ce créneau est déjà réservé'
]);
exit;
}

$stmt = $pdo->prepare("
INSERT INTO appointments (patient_id, doctor_id, slot_id, appt_at, status)
VALUES (?, ?, ?, ?, 'PENDING')
");
$stmt->execute([$patientId, $doctorId, $slotId, $apptAt]);

$appointmentId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("
UPDATE availability_slots
SET is_booked = 1
WHERE id = ?
");
$stmt->execute([$slotId]);

$pdo->commit();

$stmt = $pdo->prepare("
SELECT
pu.first_name AS patient_first_name,
pu.last_name AS patient_last_name,
du.first_name AS doctor_first_name,
du.last_name AS doctor_last_name,
du.email AS doctor_email
FROM users pu
INNER JOIN users du ON du.id = ?
WHERE pu.id = ?
LIMIT 1
");
$stmt->execute([$doctorId, $patientId]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if ($info) {
$patientName = trim(($info['patient_first_name'] ?? '') . ' ' . ($info['patient_last_name'] ?? ''));
$doctorName = trim(($info['doctor_first_name'] ?? '') . ' ' . ($info['doctor_last_name'] ?? ''));
$doctorEmail = (string)($info['doctor_email'] ?? '');

$dateFormatted = date('d/m/Y à H:i', strtotime($apptAt));

if ($doctorEmail !== '') {
try {
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'mboutekrabt29@gmail.com';
$mail->Password = 'oqceetxjsxiovxkw';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
$mail->CharSet = 'UTF-8';

$mail->setFrom('mboutekrabt29@gmail.com', 'Inaya Care');
$mail->addAddress($doctorEmail, 'Dr ' . $doctorName);

$mail->isHTML(true);
$mail->Subject = 'Nouvelle demande de rendez-vous - Inaya Care';
$mail->Body = "
<h2>Bonjour Dr {$doctorName},</h2>
<p>Vous avez reçu une <strong>nouvelle demande de rendez-vous</strong>.</p>
<p><strong>Patient :</strong> {$patientName}</p>
<p><strong>Date :</strong> {$dateFormatted}</p>
<p>Connectez-vous à Inaya Care pour accepter ou refuser cette demande.</p>
";

$mail->send();
} catch (Exception $e) {
// ne pas bloquer si le mail échoue
}
}
}

echo json_encode([
'success' => true,
'message' => 'Rendez-vous enregistré avec succès',
'appointment_id' => $appointmentId
]);
exit;

} catch (Throwable $e) {
if ($pdo->inTransaction()) {
$pdo->rollBack();
}

http_response_code(500);
echo json_encode([
'success' => false,
'message' => $e->getMessage()
]);
exit;
}