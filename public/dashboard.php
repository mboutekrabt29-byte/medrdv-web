
<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Dashboard";
require __DIR__ . "/../includes/header.php";

$user = $_SESSION['user'];
?>

<!-- ===== LOGO CLINIQUE ===== -->

<nav class="navbar navbar-light bg-light px-3 mb-4">

<img src="../assets/images/logo.jpeg"
     alt="Clinique Inaya"
     style="height:100px;">

<span class="ms-3 fw-bold fs-5">
Clinique Inaya - Kouba
</span>

</nav>


<h3 class="fw-bold mb-4">
Bonjour <?= htmlspecialchars($user['first_name']) ?> 👋
</h3>

<div class="row g-3">

<?php if ($user['role'] === 'PATIENT'): ?>

<!-- ================= PATIENT DASHBOARD ================= -->

<div class="col-md-6">
<a href="doctors.php" class="card shadow-sm text-decoration-none">
<div class="card-body">
<h5 class="fw-bold">Rechercher un médecin</h5>
<p class="text-muted">Trouver un spécialiste</p>
</div>
</a>
</div>

<div class="col-md-6">
<a href="my_appointments.php" class="card shadow-sm text-decoration-none">
<div class="card-body">
<h5 class="fw-bold">Mes rendez-vous</h5>
<p class="text-muted">Voir mes consultations</p>
</div>
</a>
</div>

<div class="col-md-6">
<a href="my_prescriptions.php" class="card shadow-sm text-decoration-none">
<div class="card-body">
<h5 class="fw-bold">Mes ordonnances</h5>
<p class="text-muted">Voir mes prescriptions</p>
</div>
</a>
</div>

<?php else: ?>

<!-- ================= DOCTOR DASHBOARD ================= -->

<?php

// RDV aujourd'hui
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM appointments
WHERE doctor_id = ?
AND DATE(appt_at) = CURDATE()
");
$stmt->execute([$user['id']]);
$todayCount = $stmt->fetchColumn();


// RDV en attente
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM appointments
WHERE doctor_id = ?
AND status = 'PENDING'
");
$stmt->execute([$user['id']]);
$pendingCount = $stmt->fetchColumn();


// RDV confirmés
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM appointments
WHERE doctor_id = ?
AND status = 'CONFIRMED'
");
$stmt->execute([$user['id']]);
$confirmedCount = $stmt->fetchColumn();


// RDV terminés
$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM appointments
WHERE doctor_id = ?
AND status = 'COMPLETED'
");
$stmt->execute([$user['id']]);
$completedCount = $stmt->fetchColumn();


// Liste des RDV
$stmt = $pdo->prepare("
SELECT a.id, a.appt_at, a.status,
u.first_name, u.last_name
FROM appointments a
JOIN users u ON u.id = a.patient_id
WHERE a.doctor_id = ?
ORDER BY a.appt_at DESC
");
$stmt->execute([$user['id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ===== STATISTIQUES ===== -->

<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h4><?= $todayCount ?></h4>
<p class="text-muted mb-0">RDV aujourd'hui</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h4 style="color:#9b59b6"><?= $pendingCount ?></h4>
<p class="text-muted mb-0">En attente</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h4 style="color:#9b59b6"><?= $pendingCount ?></h4>
<p class="text-muted mb-0">Confirmés</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-center shadow-sm">
<div class="card-body">
<h4 style="color:#4b2a84"><?= $completedCount ?></h4>
<p class="text-muted mb-0">Terminés</p>
</div>
</div>
</div>

</div>


<!-- ===== TABLE DES RDV ===== -->

<div class="col-12">
<div class="card shadow-sm">
<div class="card-body">

<h5 class="fw-bold mb-3">Mes rendez-vous</h5>

<?php if (!$appointments): ?>

<div class="alert alert-info">
Aucun rendez-vous pour le moment.
</div>

<?php else: ?>

<div class="table-responsive">

<table class="table align-middle">

<thead>
<tr>
<th>Date</th>
<th>Patient</th>
<th>Statut</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($appointments as $appt): ?>

<tr>

<td>
<?= date("d/m/Y H:i", strtotime($appt['appt_at'])) ?>
</td>

<td>
<?= htmlspecialchars($appt['first_name']." ".$appt['last_name']) ?>
</td>

<td>

<?php if ($appt['status'] === 'PENDING'): ?>

<span class="badge bg-warning text-dark">
En attente
</span>

<?php elseif ($appt['status'] === 'CONFIRMED'): ?>

<span class="badge bg-success">
Confirmé
</span>

<?php elseif ($appt['status'] === 'CANCELLED'): ?>

<span class="badge bg-secondary">
Annulé
</span>

<?php else: ?>

<span class="badge bg-dark">
Terminé
</span>

<?php endif; ?>

</td>

<td>

<?php if ($appt['status'] === 'PENDING'): ?>

<form method="POST"
action="../actions/doctor_update_appointment.php"
class="d-inline">

<input type="hidden" name="id" value="<?= $appt['id'] ?>">
<input type="hidden" name="action" value="confirm">

<button class="btn btn-sm btn-outline-success">
Confirmer
</button>

</form>

<form method="POST"
action="../actions/doctor_update_appointment.php"
class="d-inline ms-2">

<input type="hidden" name="id" value="<?= $appt['id'] ?>">
<input type="hidden" name="action" value="cancel">

<button class="btn btn-sm btn-outline-danger">
Refuser
</button>

</form>

<?php elseif ($appt['status'] === 'CONFIRMED'): ?>

<form method="POST"
action="../actions/doctor_update_appointment.php"
class="d-inline">

<input type="hidden" name="id" value="<?= $appt['id'] ?>">
<input type="hidden" name="action" value="complete">

<button class="btn btn-sm btn-outline-primary">
Terminer
</button>

</form>

<a href="prescription_create.php?id=<?= $appt['id'] ?>"
class="btn btn-sm btn-outline-dark ms-2">
Ordonnance
</a>

<?php else: ?>

—

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php endif; ?>

</div>
</div>
</div>

<?php endif; ?>

</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>
