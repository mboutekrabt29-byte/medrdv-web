<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

$pageTitle = "Dashboard";
require __DIR__ . "/../includes/header.php";

$user = $_SESSION['user'];
$firstName = htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8');
$isPatient = (($user['role'] ?? '') === 'PATIENT');
?>

<h3 class="fw-bold mb-4">
    Bonjour <?= $isPatient ? '' : 'Dr ' ?><?= $firstName ?>
</h3>

<?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars((string)$_GET['ok'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars((string)$_GET['err'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="row g-3">

<?php if ($isPatient): ?>

    <div class="col-md-6">
        <a href="doctors.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <h5 class="fw-bold">Rechercher un médecin</h5>
                <p class="text-muted mb-0">Trouver un spécialiste</p>
            </div>
        </a>
    </div>

    <div class="col-md-6">
        <a href="my_appointments.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <h5 class="fw-bold">Mes rendez-vous</h5>
                <p class="text-muted mb-0">Voir mes consultations</p>
            </div>
        </a>
    </div>

    <div class="col-md-6">
        <a href="my_prescriptions.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <h5 class="fw-bold">Mes ordonnances</h5>
                <p class="text-muted mb-0">Voir mes prescriptions</p>
            </div>
        </a>
    </div>

<?php else: ?>

    <?php
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM appointments
        WHERE doctor_id = ?
          AND DATE(appt_at) = CURDATE()
    ");
    $stmt->execute([(int)$user['id']]);
    $todayCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM appointments
        WHERE doctor_id = ?
          AND status = 'PENDING'
    ");
    $stmt->execute([(int)$user['id']]);
    $pendingCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM appointments
        WHERE doctor_id = ?
          AND status = 'CONFIRMED'
    ");
    $stmt->execute([(int)$user['id']]);
    $confirmedCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM appointments
        WHERE doctor_id = ?
          AND status = 'COMPLETED'
    ");
    $stmt->execute([(int)$user['id']]);
    $completedCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT a.id, a.appt_at, a.status, u.first_name, u.last_name
        FROM appointments a
        JOIN users u ON u.id = a.patient_id
        WHERE a.doctor_id = ?
        ORDER BY a.appt_at DESC
    ");
    $stmt->execute([(int)$user['id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h4><?= $todayCount ?></h4>
                    <p class="text-muted mb-0">RDV aujourd'hui</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h4 style="color:#9b59b6"><?= $pendingCount ?></h4>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h4 style="color:#6f42c1"><?= $confirmedCount ?></h4>
                    <p class="text-muted mb-0">Confirmés</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <h4 style="color:#4b2a84"><?= $completedCount ?></h4>
                    <p class="text-muted mb-0">Terminés</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Mes rendez-vous</h5>

                <?php if (!$appointments): ?>
                    <div class="alert alert-info mb-0">
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
                                <?php
                                $apptId = (int)$appt['id'];
                                $patientName = htmlspecialchars(
                                    (string)$appt['first_name'] . ' ' . (string)$appt['last_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                                <tr>
                                    <td>
                                        <?= date("d/m/Y H:i", strtotime((string)$appt['appt_at'])) ?>
                                    </td>

                                    <td><?= $patientName ?></td>

                                    <td>
                                        <?php if ($appt['status'] === 'PENDING'): ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php elseif ($appt['status'] === 'CONFIRMED'): ?>
                                            <span class="badge bg-success">Confirmé</span>
                                        <?php elseif ($appt['status'] === 'CANCELLED'): ?>
                                            <span class="badge bg-secondary">Annulé</span>
                                        <?php elseif ($appt['status'] === 'COMPLETED'): ?>
                                            <span class="badge bg-dark">Terminé</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars((string)$appt['status'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($appt['status'] === 'PENDING'): ?>

                                            <form
                                                method="POST"
                                                action="../actions/doctor_update_appointment.php"
                                                class="d-inline"
                                            >
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="id" value="<?= $apptId ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    Confirmer
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="../actions/doctor_update_appointment.php"
                                                class="d-inline ms-2"
                                                onsubmit="return confirm('Refuser ce rendez-vous ?');"
                                            >
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="id" value="<?= $apptId ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Refuser
                                                </button>
                                            </form>

                                        <?php elseif ($appt['status'] === 'CONFIRMED'): ?>

                                            <form
                                                method="POST"
                                                action="../actions/doctor_update_appointment.php"
                                                class="d-inline"
                                                onsubmit="return confirm('Marquer ce rendez-vous comme terminé ?');"
                                            >
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="id" value="<?= $apptId ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Terminer
                                                </button>
                                            </form>

                                            <a
                                                href="prescription_create.php?id=<?= $apptId ?>"
                                                class="btn btn-sm btn-outline-dark ms-2"
                                            >
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