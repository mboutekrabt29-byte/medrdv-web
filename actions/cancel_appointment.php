<?php
require __DIR__ . "/../config/db.php";
session_start();

if (empty($_SESSION['user'])) {
    header("Location: ../public/login.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    UPDATE appointments
    SET status = 'CANCELLED'
    WHERE id = ? AND patient_id = ?
");
$stmt->execute([$id, $userId]);

header("Location: ../public/my_appointments.php");
exit;