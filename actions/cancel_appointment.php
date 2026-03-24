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

$id = (int)($_POST['id'] ?? 0);
$userId = (int)$_SESSION['user']['id'];

if ($id <= 0) {
    header("Location: ../public/my_appointments.php?err=Requête invalide");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, slot_id, status
        FROM appointments
        WHERE id = ? AND patient_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id, $userId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        header("Location: ../public/my_appointments.php?err=Rendez-vous introuvable");
        exit;
    }

    if (in_array($appointment['status'], ['CANCELLED', 'COMPLETED'], true)) {
        $pdo->rollBack();
        header("Location: ../public/my_appointments.php?err=Action impossible");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE appointments
        SET status = 'CANCELLED'
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    if (!empty($appointment['slot_id'])) {
        $stmt = $pdo->prepare("
            UPDATE availability_slots
            SET is_booked = 0
            WHERE id = ?
        ");
        $stmt->execute([$appointment['slot_id']]);
    }

    $pdo->commit();

    header("Location: ../public/my_appointments.php?ok=Rendez-vous annulé");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/my_appointments.php?err=Erreur lors de l'annulation");
    exit;
}