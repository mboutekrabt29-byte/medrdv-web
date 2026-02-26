<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

$pageTitle = "Dashboard";
require __DIR__ . "/../includes/header.php";

$user = $_SESSION['user'];
?>

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

<?php else: ?>

    <!-- ================= DOCTOR DASHBOARD ================= -->

<?php
$stmt = $pdo->prepare("
    SELECT a.id, a.appt_at, a.status,
           u.first_name, u.last_name
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY a.appt_at DESC
");
$stmt->execute([$user['id']]);
$appointments = $stmt->fetchAll();
?>

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
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($appointments as $appt): ?>
                    <tr>

                        <td>
                            <?= date("d/m/Y H:i", strtotime($appt['appt_at'])) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($appt['first_name'] . " " . $appt['last_name']) ?>
                        </td>

                        <td>

                            <?php if ($appt['status'] === 'PENDING'): ?>
                                <span class="badge bg-warning text-dark">
                                    En attente
                                </span>

                                <form method="POST"
                                      action="../actions/doctor_update_appointment.php"
                                      class="d-inline ms-2">
                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$appt['id'] ?>">
                                    <input type="hidden"
                                           name="action"
                                           value="confirm">
                                    <button class="btn btn-sm btn-outline-success">
                                        Confirmer
                                    </button>
                                </form>

                                <form method="POST"
                                      action="../actions/doctor_update_appointment.php"
                                      class="d-inline ms-2">
                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$appt['id'] ?>">
                                    <input type="hidden"
                                           name="action"
                                           value="cancel">
                                    <button class="btn btn-sm btn-outline-danger">
                                        Refuser
                                    </button>
                                </form>

                            <?php elseif ($appt['status'] === 'CONFIRMED'): ?>

                                <span class="badge bg-success">
                                    Confirmé
                                </span>

                                <form method="POST"
                                      action="../actions/doctor_update_appointment.php"
                                      class="d-inline ms-2">
                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$appt['id'] ?>">
                                    <input type="hidden"
                                           name="action"
                                           value="complete">
                                    <button class="btn btn-sm btn-outline-primary">
                                        Terminer
                                    </button>
                                </form>

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