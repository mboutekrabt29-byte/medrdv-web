<?php
declare(strict_types=1);

require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: ../public/login.php?err=Accès refusé");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/dashboard.php?err=Méthode non autorisée");
    exit;
}

verify_csrf();

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$content = trim((string)($_POST['content'] ?? ''));
$doctorId = (int)$_SESSION['user']['id'];

if ($appointmentId <= 0 || $content === '') {
    header("Location: ../public/dashboard.php?err=Données invalides");
    exit;
}

if (mb_strlen($content) < 5) {
    header("Location: ../public/prescription_create.php?id=$appointmentId&err=Contenu trop court");
    exit;
}

if (mb_strlen($content) > 5000) {
    header("Location: ../public/prescription_create.php?id=$appointmentId&err=Contenu trop long");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT patient_id, status
        FROM appointments
        WHERE id = ? AND doctor_id = ?
        LIMIT 1
    ");
    $stmt->execute([$appointmentId, $doctorId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        $pdo->rollBack();
        header("Location: ../public/dashboard.php?err=Rendez-vous introuvable ou non autorisé");
        exit;
    }

    if (!in_array($appointment['status'], ['CONFIRMED', 'COMPLETED'], true)) {
        $pdo->rollBack();
        header("Location: ../public/dashboard.php?err=Ordonnance non autorisée pour ce statut");
        exit;
    }

    // Option métier : une seule ordonnance par rendez-vous
    $stmt = $pdo->prepare("
        SELECT id
        FROM prescriptions
        WHERE appointment_id = ?
        LIMIT 1
    ");
    $stmt->execute([$appointmentId]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        header("Location: ../public/prescription_create.php?id=$appointmentId&err=Une ordonnance existe déjà pour ce rendez-vous");
        exit;
    }

    $patientId = (int)$appointment['patient_id'];

    $stmt = $pdo->prepare("
        INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$appointmentId, $doctorId, $patientId, $content]);

    $pdo->commit();

    header("Location: ../public/dashboard.php?ok=Ordonnance enregistrée avec succès");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: ../public/prescription_create.php?id=$appointmentId&err=Erreur lors de l'enregistrement");
    exit;
}