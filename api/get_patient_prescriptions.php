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

$patientId = (int)($_GET['patient_id'] ?? 0);

if ($patientId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'patient_id manquant'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.content,
            p.pdf_file,
            p.created_at,
            u.first_name AS doctor_first_name,
            u.last_name AS doctor_last_name,
            d.specialty,
            a.appt_at
        FROM prescriptions p
        INNER JOIN users u ON u.id = p.doctor_id
        LEFT JOIN doctors d ON d.user_id = u.id
        LEFT JOIN appointments a ON a.id = p.appointment_id
        WHERE p.patient_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$patientId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $baseUrl = 'http://192.168.100.5/medrdv_web/uploads/prescriptions/';

    $prescriptions = array_map(function ($row) use ($baseUrl) {
        $doctorName = 'Dr ' . trim(
            ((string)($row['doctor_first_name'] ?? '')) . ' ' .
            ((string)($row['doctor_last_name'] ?? ''))
        );

        return [
            'id' => (int)$row['id'],
            'doctor_name' => $doctorName,
            'specialty' => $row['specialty'] ?? '',
            'content' => $row['content'] ?? '',
            'pdf_file' => $row['pdf_file'] ?? '',
            'pdf_url' => !empty($row['pdf_file']) ? $baseUrl . $row['pdf_file'] : null,
            'created_at' => $row['created_at'] ?? null,
            'appt_at' => $row['appt_at'] ?? null,
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'prescriptions' => $prescriptions
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