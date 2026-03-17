<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

if ($_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: dashboard.php");
    exit;
}

$appointmentId = (int)($_GET['id'] ?? 0);

if ($appointmentId <= 0) {
    die("Rendez-vous invalide");
}

$pageTitle = "Créer ordonnance";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4">Créer une ordonnance</h3>

<div class="card shadow-sm">
<div class="card-body">

<form method="POST" action="../actions/save_prescription.php">

<input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">

<div class="mb-3">
<label class="form-label">Ordonnance</label>

<textarea name="content"
class="form-control"
rows="6"
required></textarea>
</div>

<button class="btn btn-primary">
Enregistrer ordonnance
</button>

</form>

</div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>