<?php
require __DIR__ . "/../config/db.php";
session_start();

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: ../public/login.php?err=Accès refusé");
    exit;
}

$doctorId = (int)$_SESSION['user']['id'];
$apptId   = (int)($_POST['id'] ?? 0);
$action   = $_POST['action'] ?? '';

if ($apptId <= 0 || !in_array($action, ['confirm','cancel','complete'], true)) {
    header("Location: ../public/dashboard.php?err=Paramètres invalides");
    exit;
}

$map = [
  'confirm'  => 'CONFIRMED',
  'cancel'   => 'CANCELLED',
  'complete' => 'COMPLETED',
];

$newStatus = $map[$action];

$stmt = $pdo->prepare("
  UPDATE appointments
  SET status = ?
  WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$newStatus, $apptId, $doctorId]);

header("Location: ../public/dashboard.php");
exit;