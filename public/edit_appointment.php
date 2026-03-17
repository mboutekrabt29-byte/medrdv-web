<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$apptId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT * FROM appointments
    WHERE id = ? AND patient_id = ?
");
$stmt->execute([$apptId, $userId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: my_appointments.php");
    exit;
}

$doctorId = $appointment['doctor_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM availability_slots
    WHERE doctor_id = ? AND is_booked = 0
    ORDER BY slot_time ASC
");
$stmt->execute([$doctorId]);
$slots = $stmt->fetchAll();

$pageTitle = "Modifier rendez-vous";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4">Modifier le rendez-vous</h3>

<div class="row g-3">

<?php foreach ($slots as $slot): ?>

<form method="POST" action="../actions/update_appointment.php" class="col-md-4">

<input type="hidden" name="appointment_id" value="<?= $apptId ?>">
<input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">

<button class="btn btn-outline-primary w-100">
<?= date("d/m/Y H:i", strtotime($slot['slot_time'])) ?>
</button>

</form>

<?php endforeach; ?>

</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>