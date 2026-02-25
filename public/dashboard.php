<?php
require __DIR__ . "/../includes/auth_guard.php";

$pageTitle = "Dashboard";
require __DIR__ . "/../includes/header.php";

$user = $_SESSION['user'];
?>

<h3 class="fw-bold mb-4">
    Bonjour <?= htmlspecialchars($user['first_name']) ?> 👋
</h3>

<div class="row g-3">

<?php if ($user['role'] === 'PATIENT'): ?>

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

<?php else: ?>

    <div class="col-12">
        <div class="alert alert-info">
            Dashboard Médecin (on va le construire ensuite).
        </div>
    </div>

<?php endif; ?>

</div>

<?php
require __DIR__ . "/../includes/footer.php";
?>