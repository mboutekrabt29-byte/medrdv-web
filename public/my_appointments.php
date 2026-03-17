
<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Mes rendez-vous";
require __DIR__ . "/../includes/header.php";

$userId = $_SESSION['user']['id'];

// récupérer les rendez-vous du patient
$stmt = $pdo->prepare("
    SELECT a.id, a.appt_at, a.status,
           u.first_name, u.last_name
    FROM appointments a
    JOIN users u ON u.id = a.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.appt_at DESC
");
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3 class="fw-bold mb-4">Mes rendez-vous</h3>

<?php if (!$appointments): ?>

<div class="alert alert-info">
    Aucun rendez-vous pour le moment.
</div>

<?php else: ?>

<div class="card shadow-sm">
<div class="card-body">

<div class="table-responsive">
<table class="table align-middle">

<thead>
<tr>
<th>Date</th>
<th>Médecin</th>
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
Dr. <?= htmlspecialchars($appt['first_name']." ".$appt['last_name']) ?>
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

<?php if ($appt['status'] === 'PENDING' ): ?>

<!-- modifier -->
<a href="edit_appointment.php?id=<?= $appt['id'] ?>"
class="btn btn-sm btn-outline-primary me-2">
Modifier
</a>

<!-- annuler -->
<form method="POST"
action="../actions/cancel_appointment.php"
class="d-inline">

<input type="hidden"
name="id"
value="<?= $appt['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
Annuler
</button>

</form>

<?php else: ?>

—

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

</div>
</div>

<?php endif; ?>

<?php require __DIR__ . "/../includes/footer.php"; ?>
