<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

$pageTitle = "Disponibilités";
require __DIR__ . "/../includes/header.php";

$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;

if ($doctorId <= 0) {
    echo '<div class="alert alert-danger">Médecin invalide</div>';
    require __DIR__ . "/../includes/footer.php";
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, d.specialty
    FROM users u
    LEFT JOIN doctors d ON u.id = d.user_id
    WHERE u.id = ? AND u.role = 'DOCTOR'
");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo '<div class="alert alert-danger">Médecin introuvable</div>';
    require __DIR__ . "/../includes/footer.php";
    exit;
}

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

$doctorName = htmlspecialchars(
    (string)$doctor['first_name'] . ' ' . (string)$doctor['last_name'],
    ENT_QUOTES,
    'UTF-8'
);

$specialty = htmlspecialchars(
    (string)($doctor['specialty'] ?: 'Médecin'),
    ENT_QUOTES,
    'UTF-8'
);
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <h2 class="fw-bold mb-1" style="color:#4b2a84;">
            Dr. <?= $doctorName ?>
        </h2>
        <p class="mb-0 text-muted fs-5">
            <?= $specialty ?>
        </p>
    </div>
</div>

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

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h3 class="fw-bold mb-4" style="color:#4b2a84;">Créneaux disponibles</h3>

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
                <div class="day-card mb-4">
                    <div class="day-title mb-3">
                        <?= htmlspecialchars($jourFr . ' ' . date('d/m/Y', strtotime($date)), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($dateSlots as $slot): ?>
                            <form
                                method="POST"
                                action="../actions/book_appointment.php"
                                class="slot-form"
                                onsubmit="return confirm('Confirmer la réservation de ce créneau ?');"
                            >
                                <?= csrf_input() ?>
                                <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">
                                <input type="hidden" name="slot_id" value="<?= (int)$slot['id'] ?>">

                                <button type="submit" class="btn slot-time-btn">
                                    <?= date('H:i', strtotime((string)$slot['slot_time'])) ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>