<?php
require __DIR__ . "/../config/db.php";
session_start();

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'PATIENT') {
    header("Location: ../public/login.php?err=Accès refusé");
    exit;
}

$patientId = (int)$_SESSION['user']['id'];
$doctorId  = (int)($_POST['doctor_id'] ?? 0);
$slotId    = (int)($_POST['slot_id'] ?? 0);

if ($doctorId <= 0 || $slotId <= 0) {
    header("Location: ../public/doctors.php?err=Requête invalide");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, doctor_id, slot_time, is_booked
        FROM availability_slots
        WHERE id = ? AND doctor_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$slotId, $doctorId]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot) {
        $pdo->rollBack();
        header("Location: ../public/doctors.php?err=Créneau introuvable");
        exit;
    }

    if ((int)$slot['is_booked'] === 1) {
        $pdo->rollBack();
        header("Location: ../public/doctor_slots.php?doctor_id=$doctorId&err=Créneau déjà réservé");
        exit;
    }

    if (strtotime($slot['slot_time']) <= time()) {
        $pdo->rollBack();
        header("Location: ../public/doctor_slots.php?doctor_id=$doctorId&err=Créneau expiré");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, slot_id, appt_at, status)
        VALUES (?, ?, ?, ?, 'PENDING')
    ");
    $stmt->execute([$patientId, $doctorId, $slotId, $slot['slot_time']]);

    $stmt = $pdo->prepare("
        UPDATE availability_slots
        SET is_booked = 1
        WHERE id = ?
    ");
    $stmt->execute([$slotId]);

    $pdo->commit();

    header("Location: ../public/my_appointments.php?ok=Rendez-vous réservé");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/doctors.php?err=Erreur lors de la réservation");
    exit;
}