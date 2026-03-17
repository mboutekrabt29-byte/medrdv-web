<?php
require __DIR__ . "/../config/db.php";
session_start();

$apptId = (int)($_POST['appointment_id'] ?? 0);
$slotId = (int)($_POST['slot_id'] ?? 0);
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT * FROM appointments
    WHERE id = ? AND patient_id = ?
");
$stmt->execute([$apptId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: ../public/my_appointments.php");
    exit;
}

// libérer ancien créneau
$pdo->prepare("
UPDATE availability_slots
SET is_booked = 0
WHERE id = ?
")->execute([$appointment['slot_id']]);

// récupérer nouveau créneau
$stmt = $pdo->prepare("
SELECT * FROM availability_slots
WHERE id = ? AND is_booked = 0
");
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot) {
    header("Location: ../public/my_appointments.php");
    exit;
}

// réserver nouveau créneau
$pdo->prepare("
UPDATE availability_slots
SET is_booked = 1
WHERE id = ?
")->execute([$slotId]);

// mettre à jour rendez-vous
$pdo->prepare("
UPDATE appointments
SET slot_id = ?, appt_at = ?, status = 'PENDING'
WHERE id = ?
")->execute([$slotId, $slot['slot_time'], $apptId]);

header("Location: ../public/my_appointments.php");
exit;