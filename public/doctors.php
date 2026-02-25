<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Liste des médecins";
require __DIR__ . "/../includes/header.php";

$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, d.specialty, d.city
    FROM users u
    JOIN doctors d ON u.id = d.user_id
    WHERE u.role = 'DOCTOR'
");

$doctors = $stmt->fetchAll();
?>

<h3 class="fw-bold mb-4">Médecins disponibles</h3>

<div class="row g-3">

<?php if (!$doctors): ?>
    <div class="alert alert-info">
        Aucun médecin disponible pour le moment.
    </div>
<?php else: ?>

    <?php foreach ($doctors as $doc): ?>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold">
                        Dr. <?= htmlspecialchars($doc['first_name'] . " " . $doc['last_name']) ?>
                    </h5>

                    <p class="text-muted mb-2">
                        <?= htmlspecialchars($doc['specialty']) ?>
                        <?php if (!empty($doc['city'])): ?>
                            • <?= htmlspecialchars($doc['city']) ?>
                        <?php endif; ?>
                    </p>

                    <a href="doctor_slots.php?doctor_id=<?= $doc['id'] ?>"
                       class="btn btn-outline-primary btn-sm">
                        Voir disponibilités
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

</div>

<?php
require __DIR__ . "/../includes/footer.php";
?>