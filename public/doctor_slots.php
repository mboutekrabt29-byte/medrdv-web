<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Disponibilités";
require __DIR__ . "/../includes/header.php";

$doctorId = intval($_GET['doctor_id'] ?? 0);

if ($doctorId <= 0) {
    echo "<div class='alert alert-danger'>Médecin invalide</div>";
    require __DIR__ . "/../includes/footer.php";
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, d.specialty
    FROM users u
    JOIN doctors d ON u.id = d.user_id
    WHERE u.id = ?
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    echo "<div class='alert alert-danger'>Médecin introuvable</div>";
    require __DIR__ . "/../includes/footer.php";
    exit;
}

// Récupérer créneaux disponibles
$stmt = $pdo->prepare("
    SELECT * FROM availability_slots
    WHERE doctor_id = ?
    AND is_booked = 0
    AND slot_time >= NOW()
    ORDER BY slot_time ASC
");
$stmt->execute([$doctorId]);
$slots = $stmt->fetchAll();
?>

<h3 class="fw-bold mb-2">
    Dr. <?= htmlspecialchars($doctor['first_name'] . " " . $doctor['last_name']) ?>
</h3>

<p class="text-muted mb-4">
    <?= htmlspecialchars($doctor['specialty']) ?>
</p>

<h5 class="mb-3">Créneaux disponibles :</h5>

<?php if (!$slots): ?>
    <div class="alert alert-info">
        Aucun créneau disponible.
    </div>
<?php else: ?>

<div class="row g-3">
<?php foreach ($slots as $slot): ?>
    <div class="col-md-4">
        <form method="POST" action="../actions/book_appointment.php">
            <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
            <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
            <button class="btn btn-outline-primary w-100">
                <?= date("d/m/Y H:i", strtotime($slot['slot_time'])) ?>
            </button>
        </form>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . "/../includes/footer.php"; ?>