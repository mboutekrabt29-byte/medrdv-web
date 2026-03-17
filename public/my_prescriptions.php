
<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Mes ordonnances";
require __DIR__ . "/../includes/header.php";

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
SELECT p.*, u.first_name, u.last_name
FROM prescriptions p
JOIN users u ON u.id = p.doctor_id
WHERE p.patient_id = ?
ORDER BY p.created_at DESC
");

$stmt->execute([$userId]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3 class="fw-bold mb-4">Mes ordonnances</h3>

<?php if (!$prescriptions): ?>

<div class="alert alert-info">
Aucune ordonnance disponible.
</div>

<?php else: ?>

<div class="card shadow-sm">
<div class="card-body">

<table class="table">

<thead>
<tr>
<th>Date</th>
<th>Médecin</th>
<th>Ordonnance</th>
<th>PDF</th>
</tr>
</thead>

<tbody>

<?php foreach ($prescriptions as $p): ?>

<tr>

<td>
<?= date("d/m/Y", strtotime($p['created_at'])) ?>
</td>

<td>
Dr. <?= htmlspecialchars($p['first_name']." ".$p['last_name']) ?>
</td>

<td>
<?= nl2br(htmlspecialchars($p['content'])) ?>
</td>

<td>
<a href="prescription_pdf.php?id=<?= $p['id'] ?>"
class="btn btn-sm btn-outline-primary">
Télécharger
</a>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

<?php endif; ?>

<?php require __DIR__ . "/../includes/footer.php"; ?>
