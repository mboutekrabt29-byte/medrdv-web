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

use Dompdf\Dompdf;
use Dompdf\Options;
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
$doctorId = (int)($input['doctor_id'] ?? 0);
$patientId = (int)($input['patient_id'] ?? 0);
$content = trim((string)($input['content'] ?? ''));

if ($appointmentId <= 0 || $doctorId <= 0 || $patientId <= 0 || $content === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Données manquantes'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT id, pdf_file
FROM prescriptions
WHERE appointment_id = ?
ORDER BY id DESC
LIMIT 1
");
$stmt->execute([$appointmentId]);
$existingPrescription = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingPrescription) {
$pdfUrl = null;
if (!empty($existingPrescription['pdf_file'])) {
$pdfUrl = 'http://192.168.100.5/medrdv_web/uploads/prescriptions/' . $existingPrescription['pdf_file'];
}

echo json_encode([
'success' => true,
'already_exists' => true,
'message' => 'Une ordonnance existe déjà pour ce rendez-vous',
'prescription_id' => (int)$existingPrescription['id'],
'pdf_url' => $pdfUrl
]);
exit;
}

$stmt = $pdo->prepare("
SELECT
pu.first_name AS patient_first_name,
pu.last_name AS patient_last_name,
pu.email AS patient_email,
pu.phone AS patient_phone,
du.first_name AS doctor_first_name,
du.last_name AS doctor_last_name,
d.specialty AS doctor_specialty
FROM users pu
INNER JOIN users du ON du.id = ?
LEFT JOIN doctors d ON d.user_id = du.id
WHERE pu.id = ?
LIMIT 1
");
$stmt->execute([$doctorId, $patientId]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Patient ou médecin introuvable'
]);
exit;
}

$patientName = trim(($info['patient_first_name'] ?? '') . ' ' . ($info['patient_last_name'] ?? ''));
$doctorName = trim(($info['doctor_first_name'] ?? '') . ' ' . ($info['doctor_last_name'] ?? ''));
$doctorSpecialty = (string)($info['doctor_specialty'] ?? 'Médecin');
$patientEmail = (string)($info['patient_email'] ?? '');
$patientPhone = (string)($info['patient_phone'] ?? '');

$stmt = $pdo->prepare("
INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content)
VALUES (?, ?, ?, ?)
");
$stmt->execute([$appointmentId, $doctorId, $patientId, $content]);

$prescriptionId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("
UPDATE appointments
SET status = 'COMPLETED'
WHERE id = ?
");
$stmt->execute([$appointmentId]);

$safeContent = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
$today = date('d/m/Y');
$nowTime = date('H:i');

$logoPath = realpath(__DIR__ . '/../assets/images/inaya-logo.jpeg');
$logoDataUri = '';

if ($logoPath && file_exists($logoPath)) {
$logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
$logoBase64 = base64_encode(file_get_contents($logoPath));
$logoDataUri = 'data:image/' . $logoType . ';base64,' . $logoBase64;
}

$html = '
<html>
<head>
<meta charset="UTF-8">
<style>
body {
font-family: DejaVu Sans, sans-serif;
color: #1F2937;
margin: 0;
padding: 0;
background: #FFFFFF;
}
.page {
padding: 32px 34px 24px 34px;
}
.header {
background: #A56BCF;
color: #FFFFFF;
border-radius: 18px;
padding: 20px 22px;
margin-bottom: 24px;
}
.header-table {
width: 100%;
border-collapse: collapse;
}
.header-left {
width: 90px;
vertical-align: middle;
}
.header-right {
vertical-align: middle;
padding-left: 16px;
}
.logo {
width: 76px;
height: 76px;
object-fit: contain;
background: #FFFFFF;
border-radius: 12px;
padding: 4px;
}
.clinic-name {
font-size: 28px;
font-weight: bold;
margin-bottom: 6px;
}
.clinic-subtitle {
font-size: 13px;
opacity: 0.95;
}
.title {
text-align: center;
font-size: 22px;
font-weight: bold;
color: #A56BCF;
margin: 8px 0 20px 0;
}
.grid {
width: 100%;
border-collapse: separate;
border-spacing: 12px;
margin-bottom: 10px;
}
.box {
background: #F9FAFB;
border: 1px solid #E5E7EB;
border-radius: 14px;
padding: 14px 16px;
vertical-align: top;
}
.box-label {
font-size: 12px;
color: #6B7280;
margin-bottom: 6px;
}
.box-value {
font-size: 15px;
color: #111827;
font-weight: bold;
line-height: 1.5;
}
.prescription-box {
margin-top: 16px;
border: 2px solid #E9D5FF;
background: #FCFAFF;
border-radius: 16px;
padding: 18px 18px 22px 18px;
}
.prescription-title {
color: #7C3AED;
font-size: 18px;
font-weight: bold;
margin-bottom: 12px;
}
.prescription-content {
font-size: 15px;
line-height: 1.8;
color: #111827;
}
.signature-wrap {
margin-top: 34px;
text-align: right;
}
.signature-line {
display: inline-block;
width: 220px;
border-top: 1px solid #9CA3AF;
margin-top: 28px;
padding-top: 8px;
text-align: center;
font-size: 13px;
color: #374151;
}
.footer {
margin-top: 26px;
text-align: center;
font-size: 11px;
color: #6B7280;
border-top: 1px solid #E5E7EB;
padding-top: 12px;
}
.meta {
margin-top: 8px;
font-size: 12px;
color: #6B7280;
text-align: center;
}
</style>
</head>
<body>
<div class="page">
<div class="header">
<table class="header-table">
<tr>
<td class="header-left">'
. ($logoDataUri !== '' ? '<img src="' . $logoDataUri . '" class="logo">' : '') .
'</td>
<td class="header-right">
<div class="clinic-name">Inaya Care</div>
<div class="clinic-subtitle">Ordonnance médicale • Clinique Inaya - Kouba</div>
</td>
</tr>
</table>
</div>

<div class="title">Ordonnance</div>

<table class="grid">
<tr>
<td class="box" width="50%">
<div class="box-label">Médecin</div>
<div class="box-value">Dr ' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</div>
<div style="font-size:13px;color:#6B7280;margin-top:4px;">' . htmlspecialchars($doctorSpecialty, ENT_QUOTES, 'UTF-8') . '</div>
</td>
<td class="box" width="50%">
<div class="box-label">Patient</div>
<div class="box-value">' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . '</div>
<div style="font-size:13px;color:#6B7280;margin-top:4px;">' . htmlspecialchars($patientPhone, ENT_QUOTES, 'UTF-8') . '</div>
</td>
</tr>
<tr>
<td class="box" width="50%">
<div class="box-label">Date</div>
<div class="box-value">' . $today . '</div>
</td>
<td class="box" width="50%">
<div class="box-label">Heure</div>
<div class="box-value">' . $nowTime . '</div>
</td>
</tr>
</table>

<div class="prescription-box">
<div class="prescription-title">Prescription</div>
<div class="prescription-content">' . $safeContent . '</div>
</div>

<div class="signature-wrap">
<div class="signature-line">
Signature du médecin<br>
Dr ' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '
</div>
</div>

<div class="meta">
Référence ordonnance : #' . $prescriptionId . '
</div>

<div class="footer">
Inaya Care • Document généré automatiquement • Merci de suivre les indications du médecin
</div>
</div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfDir = __DIR__ . '/../uploads/prescriptions';
if (!is_dir($pdfDir)) {
mkdir($pdfDir, 0777, true);
}

$pdfFileName = 'prescription_' . $prescriptionId . '.pdf';
$pdfPath = $pdfDir . '/' . $pdfFileName;

file_put_contents($pdfPath, $dompdf->output());

$stmt = $pdo->prepare("
UPDATE prescriptions
SET pdf_file = ?
WHERE id = ?
");
$stmt->execute([$pdfFileName, $prescriptionId]);

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
$mail->Subject = 'Votre ordonnance médicale - Inaya Care';
$mail->Body = '
<h2>Bonjour ' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') . ',</h2>
<p>Votre ordonnance a été préparée par <strong>Dr ' . htmlspecialchars($doctorName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
<p>Le document PDF est joint à cet email.</p>
<p>Vous pouvez également le consulter depuis l’application.</p>';

$mail->addAttachment($pdfPath, $pdfFileName);
$mail->send();
} catch (Exception $e) {
// ne pas bloquer si le mail échoue
}
}

echo json_encode([
'success' => true,
'message' => 'Ordonnance créée avec succès',
'prescription_id' => $prescriptionId,
'pdf_file' => $pdfFileName
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