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

$identifier = trim((string)($input['identifier'] ?? ''));
$method = trim((string)($input['method'] ?? ''));

if ($identifier === '' || !in_array($method, ['email', 'phone'], true)) {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Email ou téléphone requis'
]);
exit;
}

try {
$stmt = $pdo->prepare("
SELECT id, first_name, last_name, email, phone
FROM users
WHERE email = ? OR phone = ?
LIMIT 1
");
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
http_response_code(404);
echo json_encode([
'success' => false,
'message' => 'Compte introuvable'
]);
exit;
}

$userId = (int)$user['id'];
$firstName = trim((string)($user['first_name'] ?? ''));
$email = trim((string)($user['email'] ?? ''));
$phone = trim((string)($user['phone'] ?? ''));
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmt->execute([$userId]);

if ($method === 'phone') {
if ($phone === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Aucun numéro de téléphone trouvé pour ce compte'
]);
exit;
}

$demoCode = (string)random_int(100000, 999999);

$stmt = $pdo->prepare("
INSERT INTO password_resets (user_id, code, expires_at)
VALUES (?, ?, ?)
");
$stmt->execute([$userId, $demoCode, $expiresAt]);

echo json_encode([
'success' => true,
'message' => 'Code généré pour téléphone',
'sent_by' => 'phone',
'demo_code' => $demoCode
]);
exit;
}

if ($email === '') {
http_response_code(400);
echo json_encode([
'success' => false,
'message' => 'Aucun email trouvé pour ce compte'
]);
exit;
}

$emailCode = (string)random_int(100000, 999999);

$stmt = $pdo->prepare("
INSERT INTO password_resets (user_id, code, expires_at)
VALUES (?, ?, ?)
");
$stmt->execute([$userId, $emailCode, $expiresAt]);

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = 'mboutekrabt29@gmail.com';
$mail->Password = 'oqceetxjsxiovxkw';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
$mail->CharSet = 'UTF-8';

$mail->setFrom('mboutekrabt29@gmail.com', 'Inaya Care');
$mail->addAddress($email, $firstName);

$mail->isHTML(true);
$mail->Subject = 'Code de réinitialisation - Inaya Care';
$mail->Body = "
<h2>Bonjour {$firstName},</h2>
<p>Votre code de réinitialisation est :</p>
<h1>{$emailCode}</h1>
<p>Ce code expire dans 10 minutes.</p>
";

$mail->send();

echo json_encode([
'success' => true,
'message' => 'Code envoyé par email',
'sent_by' => 'email'
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