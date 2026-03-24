<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

$pageTitle = "Mes rendez-vous";
require __DIR__ . "/../includes/header.php";

$userId = (int)$_SESSION['user']['id'];

// Récupérer les rendez-vous du patient
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

<?php if (!$appointments): ?>

    <div class="alert alert-info">
        Aucun rendez-vous pour le moment.
    </div>

<?php else: ?>

    <div class="card shadow-sm border-0">
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
                        <?php
                        $apptId = (int)$appt['id'];
                        $doctorName = htmlspecialchars(
                            (string)$appt['first_name'] . ' ' . (string)$appt['last_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        $status = (string)$appt['status'];
                        ?>

                        <tr>
                            <td>
                                <?= date("d/m/Y H:i", strtotime((string)$appt['appt_at'])) ?>
                            </td>

                            <td>
                                Dr. <?= $doctorName ?>
                            </td>

                            <td>
                                <?php if ($status === 'PENDING'): ?>
                                    <span class="badge bg-warning text-dark">En attente</span>
                                <?php elseif ($status === 'CONFIRMED'): ?>
                                    <span class="badge bg-success">Confirmé</span>
                                <?php elseif ($status === 'CANCELLED'): ?>
                                    <span class="badge bg-secondary">Annulé</span>
                                <?php elseif ($status === 'COMPLETED'): ?>
                                    <span class="badge bg-dark">Terminé</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($status === 'PENDING' || $status === 'CONFIRMED'): ?>

                                    <a
                                        href="edit_appointment.php?id=<?= $apptId ?>"
                                        class="btn btn-sm btn-outline-primary me-2"
                                    >
                                        Modifier
                                    </a>

                                    <form
                                        method="POST"
                                        action="../actions/cancel_appointment.php"
                                        class="d-inline"
                                        onsubmit="return confirm('Voulez-vous vraiment annuler ce rendez-vous ?');"
                                    >
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= $apptId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
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