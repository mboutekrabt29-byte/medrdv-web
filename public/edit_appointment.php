<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

$apptId = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user']['id'];

if ($apptId <= 0) {
    header("Location: my_appointments.php?err=Rendez-vous invalide");
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, doctor_id, patient_id, slot_id, appt_at, status
    FROM appointments
    WHERE id = ? AND patient_id = ?
    LIMIT 1
");
$stmt->execute([$apptId, $userId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: my_appointments.php?err=Rendez-vous introuvable");
    exit;
}

if (in_array($appointment['status'], ['CANCELLED', 'COMPLETED'], true)) {
    header("Location: my_appointments.php?err=Modification impossible pour ce rendez-vous");
    exit;
}

$doctorId = (int)$appointment['doctor_id'];

$stmt = $pdo->prepare("
    SELECT id, doctor_id, slot_time, is_booked
    FROM availability_slots
    WHERE doctor_id = ?
      AND is_booked = 0
      AND slot_time > NOW()
    ORDER BY slot_time ASC
");
$stmt->execute([$doctorId]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jours = [
    'Sun' => 'Dimanche',
    'Mon' => 'Lundi',
    'Tue' => 'Mardi',
    'Wed' => 'Mercredi',
    'Thu' => 'Jeudi',
    'Fri' => 'Vendredi',
    'Sat' => 'Samedi',
];

$groupedSlots = [];
foreach ($slots as $slot) {
    $dateKey = date('Y-m-d', strtotime((string)$slot['slot_time']));
    $groupedSlots[$dateKey][] = $slot;
}

$pageTitle = "Modifier rendez-vous";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4">Modifier le rendez-vous</h3>

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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="fw-semibold mb-1">Rendez-vous actuel</div>
        <div class="text-muted">
            <?= date('d/m/Y H:i', strtotime((string)$appointment['appt_at'])) ?>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-4">Choisir un nouveau créneau</h4>

        <?php if (!$groupedSlots): ?>
            <div class="alert alert-info mb-0">
                Aucun créneau disponible pour le moment.
            </div>
        <?php else: ?>

            <?php foreach ($groupedSlots as $date => $dateSlots): ?>
                <?php
                $jourKey = date('D', strtotime($date));
                $jourFr = $jours[$jourKey] ?? $jourKey;
                ?>
                <div class="mb-4">
                    <div class="fw-semibold mb-3">
                        <?= htmlspecialchars(
                            $jourFr . ' ' . date('d/m/Y', strtotime($date)),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($dateSlots as $slot): ?>
                            <div class="col-md-4">
                                <form
                                    method="POST"
                                    action="../actions/update_appointment.php"
                                    onsubmit="return confirm('Confirmer la modification du rendez-vous ?');"
                                >
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="appointment_id" value="<?= $apptId ?>">
                                    <input type="hidden" name="slot_id" value="<?= (int)$slot['id'] ?>">

                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <?= date('H:i', strtotime((string)$slot['slot_time'])) ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>