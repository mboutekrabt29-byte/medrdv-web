<?php
require __DIR__ . "/../config/db.php";
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: ../public/login.php");
    exit;
}

$appointmentId = (int)($_POST['appointment_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$doctorId = (int)$_SESSION['user']['id'];

if ($appointmentId <= 0 || $content === '') {
    header("Location: ../public/dashboard.php");
    exit;
}

// Récupérer le patient depuis le rendez-vous + vérifier que c'est bien le médecin connecté
$stmt = $pdo->prepare("
    SELECT patient_id
    FROM appointments
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$appointmentId, $doctorId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    die("Rendez-vous introuvable ou non autorisé.");
}

$patientId = (int)$appointment['patient_id'];

// Enregistrer l'ordonnance
$stmt = $pdo->prepare("
    INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, content)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$appointmentId, $doctorId, $patientId, $content]);

header("Location: ../public/dashboard.php");
exit;