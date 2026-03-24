<?php
declare(strict_types=1);

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

session_start();

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'PATIENT') {
    header("Location: ../public/login.php?err=Accès refusé");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/my_appointments.php?err=Méthode non autorisée");
    exit;
}

verify_csrf();

$apptId = (int)($_POST['appointment_id'] ?? 0);
$newSlotId = (int)($_POST['slot_id'] ?? 0);
$userId = (int)$_SESSION['user']['id'];

if ($apptId <= 0 || $newSlotId <= 0) {
    header("Location: ../public/my_appointments.php?err=Requête invalide");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, doctor_id, slot_id, status
        FROM appointments
        WHERE id = ? AND patient_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$apptId, $userId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        header("Location: ../public/my_appointments.php?err=Rendez-vous introuvable");
        exit;
    }

    if (in_array($appointment['status'], ['CANCELLED', 'COMPLETED'], true)) {
        $pdo->rollBack();
        header("Location: ../public/my_appointments.php?err=Modification impossible");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, doctor_id, slot_time, is_booked
        FROM availability_slots
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->execute([$newSlotId]);
    $newSlot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$newSlot ||
        (int)$newSlot['doctor_id'] !== (int)$appointment['doctor_id'] ||
        (int)$newSlot['is_booked'] === 1 ||
        strtotime((string)$newSlot['slot_time']) <= time()
    ) {
        $pdo->rollBack();
        header("Location: ../public/my_appointments.php?err=Nouveau créneau indisponible");
        exit;
    }

    if (!empty($appointment['slot_id'])) {
        $stmt = $pdo->prepare("
            UPDATE availability_slots
            SET is_booked = 0
            WHERE id = ?
        ");
        $stmt->execute([$appointment['slot_id']]);
    }

    $stmt = $pdo->prepare("
        UPDATE availability_slots
        SET is_booked = 1
        WHERE id = ?
    ");
    $stmt->execute([$newSlotId]);

    $stmt = $pdo->prepare("
        UPDATE appointments
        SET slot_id = ?, appt_at = ?, status = 'PENDING'
        WHERE id = ?
    ");
    $stmt->execute([$newSlotId, $newSlot['slot_time'], $apptId]);

    $pdo->commit();

    header("Location: ../public/my_appointments.php?ok=Rendez-vous modifié");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/my_appointments.php?err=Erreur lors de la modification");
    exit;
}