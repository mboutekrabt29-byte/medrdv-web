<?php
require __DIR__ . "/../config/db.php";
session_start();

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'PATIENT') {
    header("Location: ../public/login.php?err=Accès refusé");
    exit;
}

$patientId = $_SESSION['user']['id'];
$doctorId  = intval($_POST['doctor_id'] ?? 0);
$slotId    = intval($_POST['slot_id'] ?? 0);

if ($doctorId <= 0 || $slotId <= 0) {
    header("Location: ../public/doctors.php");
    exit;
}

// Récupérer le créneau
$stmt = $pdo->prepare("
    SELECT * FROM availability_slots
    WHERE id = ? AND doctor_id = ? AND is_booked = 0
");
$stmt->execute([$slotId, $doctorId]);
$slot = $stmt->fetch();

if (!$slot) {
    header("Location: ../public/doctor_slots.php?doctor_id=$doctorId&err=Créneau indisponible");
    exit;
}

// Créer rendez-vous
$stmt = $pdo->prepare("
    INSERT INTO appointments (patient_id, doctor_id, appt_at, status)
    VALUES (?, ?, ?, 'PENDING')
");
$stmt->execute([$patientId, $doctorId, $slot['slot_time']]);

// Marquer créneau comme réservé
$stmt = $pdo->prepare("
    UPDATE availability_slots
    SET is_booked = 1
    WHERE id = ?
");
$stmt->execute([$slotId]);

header("Location: ../public/my_appointments.php");
exit;