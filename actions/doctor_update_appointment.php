<?php
require __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: ../public/login.php");
    exit;
}

$doctorId = (int)$_SESSION['user']['id'];
$apptId   = (int)($_POST['id'] ?? 0);
$action   = $_POST['action'] ?? '';

if ($apptId <= 0) {
    header("Location: ../public/dashboard.php");
    exit;
}

// Récupérer le rendez-vous
$stmt = $pdo->prepare("
    SELECT * FROM appointments
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$apptId, $doctorId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: ../public/dashboard.php");
    exit;
}

// ================= ACTIONS =================

if ($action === 'confirm') {

    $pdo->prepare("
        UPDATE appointments
        SET status = 'CONFIRMED'
        WHERE id = ?
    ")->execute([$apptId]);

} elseif ($action === 'cancel') {

    // Annuler le rendez-vous
    $pdo->prepare("
        UPDATE appointments
        SET status = 'CANCELLED'
        WHERE id = ?
    ")->execute([$apptId]);

    // Libérer le créneau seulement si slot_id existe
    if (!empty($appointment['slot_id'])) {

        $pdo->prepare("
            UPDATE availability_slots
            SET is_booked = 0
            WHERE id = ?
        ")->execute([$appointment['slot_id']]);

    }

} elseif ($action === 'complete') {

    $pdo->prepare("
        UPDATE appointments
        SET status = 'COMPLETED'
        WHERE id = ?
    ")->execute([$apptId]);
}

header("Location: ../public/dashboard.php");
exit;