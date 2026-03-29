<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../vendor/autoload.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$channel = strtoupper(trim((string)($input['channel'] ?? '')));
$value = trim((string)($input['value'] ?? ''));

if (!in_array($channel, ['EMAIL', 'PHONE'], true) || $value === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit;
}

try {
    if ($channel === 'EMAIL') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Adresse email invalide'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([mb_strtolower($value)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cet email'
            ]);
            exit;
        }

        $email = $user['email'];
        $phone = null;
    } else {
        $cleanPhone = preg_replace('/[^\d+\s\-\(\)]/', '', $value) ?? '';

        if ($cleanPhone === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Numéro de téléphone invalide'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, phone FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$cleanPhone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Aucun compte trouvé avec ce numéro'
            ]);
            exit;
        }

        $email = null;
        $phone = $user['phone'];
    }

    $code = (string)random_int(100000, 999999);
    $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO password_resets (email, phone, reset_code, channel, expires_at, used)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$email, $phone, $code, $channel, $expiresAt]);

    if ($channel === 'EMAIL') {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'mboutekrabt29@gmail.com';
$mail->Password = 'oqceetxjsxiovxkw';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->CharSet = 'UTF-8';
            $mail->setFrom('mboutekrabt29@gmail.com', 'Inaya Care');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Code de reinitialisation - Inaya Care';
            $mail->Body = "
                <h2>Inaya Care</h2>
                <p>Bonjour,</p>
                <p>Votre code de réinitialisation est :</p>
                <h1 style='color:#A56BCF;'>$code</h1>
                <p>Saisissez ce code dans les 10 prochaines minutes pour vous connecter à votre compte Inaya Care .</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Impossible d’envoyer l’email : ' . $mail->ErrorInfo
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Code envoyé par email'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Code généré pour téléphone',
        'debug_code' => $code
    ]);
    exit;

} catch (Exception $e) {
     http_response_code(500);
     echo json_encode([
         'success' => false,
         'message' => $mail->ErrorInfo 
     ]);
     exit;

$mail ->SMTPDebug = 2;
}