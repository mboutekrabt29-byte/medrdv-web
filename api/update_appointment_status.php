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

$appointmentId = (int)($input['appointment_id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));

if ($appointmentId <= 0 || !in_array($status, ['CONFIRMED', 'CANCELLED'], true)) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données invalides'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT a.id, a.slot_id, a.appt_at,
u.first_name, u.last_name, u.email
FROM appointments a
INNER JOIN users u ON u.id = a.patient_id
WHERE a.id = ?
LIMIT 1
");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Rendez-vous introuvable'
]);
exit;
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
UPDATE appointments
SET status = ?
WHERE id = ?
");
$stmt->execute([$status, $appointmentId]);

if ($status === 'CANCELLED' && !empty($appointment['slot_id'])) {
$stmt = $pdo->prepare("
UPDATE availability_slots
SET is_booked = 0
WHERE id = ?
");
$stmt->execute([(int)$appointment['slot_id']]);
}

$pdo->commit();

$patientName = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''));
$patientEmail = (string)($appointment['email'] ?? '');
$dateFormatted = date('d/m/Y à H:i', strtotime((string)$appointment['appt_at']));
$statusLabel = $status === 'CONFIRMED' ? 'confirmé' : 'refusé';

if ($patientEmail !== '') {
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
$mail->addAddress($patientEmail, $patientName);

$mail->isHTML(true);
$mail->Subject = 'Mise à jour de votre rendez-vous - Inaya Care';
$mail->Body = "
<h2>Bonjour {$patientName},</h2>
<p>Votre rendez-vous prévu le <strong>{$dateFormatted}</strong> a été <strong>{$statusLabel}</strong>.</p>
<p>Merci de consulter l’application Inaya Care pour plus de détails.</p>
";

$mail->send();
} catch (Exception $e) {
// ne pas bloquer si le mail échoue
}
}

echo json_encode([
'success' => true,
'message' => 'Statut mis à jour avec succès'
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