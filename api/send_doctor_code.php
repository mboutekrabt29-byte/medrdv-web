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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
http_response_code(405);
echo json_encode([
'success' => false,
'message' => 'Méthode non autorisée'
]);
exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$firstName = trim((string)($input['first_name'] ?? ''));
$lastName = trim((string)($input['last_name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$password = (string)($input['password'] ?? '');
$specialty = trim((string)($input['specialty'] ?? 'Généraliste'));

if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Veuillez remplir tous les champs'
]);
exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Adresse email invalide'
]);
exit;
}

try {
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
http_response_code(409);
echo json_encode([
'success' => false,
'message' => 'Cet email est déjà utilisé'
]);
exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
http_response_code(409);
echo json_encode([
'success' => false,
'message' => 'Ce téléphone est déjà utilisé'
]);
exit;
}

$code = (string)random_int(100000, 999999);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $pdo->prepare("
INSERT INTO doctor_verifications
(email, first_name, last_name, phone, password_plain, specialty, code, expires_at, is_verified)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
");
$stmt->execute([
$email,
$firstName,
$lastName,
$phone,
$password,
$specialty,
$code,
$expiresAt
]);

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
$mail->addAddress($email);

$mail->isHTML(true);
$mail->Subject = 'Code de vérification médecin - Inaya Care';
$mail->Body = "
<h2>Validation médecin</h2>
<p>Bonjour {$firstName},</p>
<p>Votre code secret est :</p>
<h1>{$code}</h1>
<p>Ce code expire dans 10 minutes.</p>
";

$mail->send();

echo json_encode([
'success' => true,
'message' => 'Code envoyé par email'
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