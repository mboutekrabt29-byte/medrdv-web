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

$input = json_decode(file_get_contents('php://input'), true);

$appointmentId = (int)($input['appointment_id'] ?? 0);
$patientId = (int)($input['patient_id'] ?? 0);

if ($appointmentId <= 0 || $patientId <= 0) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données invalides'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT
a.id,
a.slot_id,
a.status,
a.appt_at,
a.patient_id,
a.doctor_id,
pu.first_name AS patient_first_name,
pu.last_name AS patient_last_name,
pu.email AS patient_email,
du.first_name AS doctor_first_name,
du.last_name AS doctor_last_name,
du.email AS doctor_email
FROM appointments a
INNER JOIN users pu ON pu.id = a.patient_id
INNER JOIN users du ON du.id = a.doctor_id
WHERE a.id = ?
AND a.patient_id = ?
LIMIT 1
");
$stmt->execute([$appointmentId, $patientId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Rendez-vous introuvable'
]);
exit;
}

if ($appointment['status'] === 'CANCELLED') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Ce rendez-vous est déjà annulé'
]);
exit;
}

if ($appointment['status'] === 'COMPLETED') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Impossible d’annuler un rendez-vous terminé'
]);
exit;
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
UPDATE appointments
SET status = 'CANCELLED'
WHERE id = ?
");
$stmt->execute([$appointmentId]);

$stmt = $pdo->prepare("
UPDATE availability_slots
SET is_booked = 0
WHERE id = ?
");
$stmt->execute([(int)$appointment['slot_id']]);

$pdo->commit();

$patientName = trim(($appointment['patient_first_name'] ?? '') . ' ' . ($appointment['patient_last_name'] ?? ''));
$doctorName = trim(($appointment['doctor_first_name'] ?? '') . ' ' . ($appointment['doctor_last_name'] ?? ''));
$doctorEmail = $appointment['doctor_email'] ?? '';
$apptAt = $appointment['appt_at'] ?? '';

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
$mail->Subject = 'Annulation de rendez-vous - Inaya Care';
$mail->Body = "
<h2>Bonjour Dr {$doctorName},</h2>
<p>Le patient <strong>{$patientName}</strong> a annulé son rendez-vous.</p>
<p><strong>Date concernée :</strong> {$dateFormatted}</p>
<p>Le créneau a été remis à disposition.</p>
";

$mail->send();
} catch (Exception $e) {
// On ne bloque pas l'annulation si le mail échoue
}
}

echo json_encode([
'success' => true,
'message' => 'Rendez-vous annulé avec succès'
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