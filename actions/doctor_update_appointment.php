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

if ($apptId <= 0 || !in_array($action, ['confirm', 'cancel', 'complete'], true)) {
    header("Location: ../public/dashboard.php?err=Requête invalide");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM appointments
        WHERE id = ? AND doctor_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$apptId, $doctorId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        header("Location: ../public/dashboard.php?err=Rendez-vous introuvable");
        exit;
    }

    if ($action === 'confirm') {
        if ($appointment['status'] !== 'PENDING') {
            $pdo->rollBack();
            header("Location: ../public/dashboard.php?err=Action impossible");
            exit;
        }

        $pdo->prepare("
            UPDATE appointments
            SET status = 'CONFIRMED'
            WHERE id = ?
        ")->execute([$apptId]);
    }

    if ($action === 'cancel') {
        if (in_array($appointment['status'], ['CANCELLED', 'COMPLETED'], true)) {
            $pdo->rollBack();
            header("Location: ../public/dashboard.php?err=Action impossible");
            exit;
        }

        $pdo->prepare("
            UPDATE appointments
            SET status = 'CANCELLED'
            WHERE id = ?
        ")->execute([$apptId]);

        if (!empty($appointment['slot_id'])) {
            $pdo->prepare("
                UPDATE availability_slots
                SET is_booked = 0
                WHERE id = ?
            ")->execute([$appointment['slot_id']]);
        }
    }

    if ($action === 'complete') {
        if ($appointment['status'] !== 'CONFIRMED') {
            $pdo->rollBack();
            header("Location: ../public/dashboard.php?err=Action impossible");
            exit;
        }

        $pdo->prepare("
            UPDATE appointments
            SET status = 'COMPLETED'
            WHERE id = ?
        ")->execute([$apptId]);
    }

    $pdo->commit();

    header("Location: ../public/dashboard.php?ok=Statut mis à jour");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/dashboard.php?err=Erreur lors de la mise à jour");
    exit;
}