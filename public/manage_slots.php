<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: dashboard.php?err=Accès refusé");
    exit;
}

$doctorId = (int)$_SESSION['user']['id'];
$error = '';
$success = '';

function groupSlotsByDate(array $slots): array
{
    $grouped = [];

    foreach ($slots as $slot) {
        $dateKey = date('Y-m-d', strtotime((string)$slot['slot_time']));
        $grouped[$dateKey][] = $slot;
    }

    return $grouped;
}

/* =========================
   AJOUT / SUPPRESSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Ajout créneau
    if (isset($_POST['slot_time']) && empty($_POST['delete_id'])) {
        $slotRaw = trim((string)($_POST['slot_time'] ?? ''));

        if ($slotRaw === '') {
            $error = "Veuillez choisir une date et une heure.";
        } else {
            $slotTime = str_replace('T', ' ', $slotRaw) . ':00';
            $slotTimestamp = strtotime($slotTime);
            $now = time();

            if ($slotTimestamp === false) {
                $error = "Date invalide.";
            } elseif ($slotTimestamp <= $now) {
                $error = "Impossible d'ajouter un créneau passé.";
            } else {
                $check = $pdo->prepare("
                    SELECT id
                    FROM availability_slots
                    WHERE doctor_id = ? AND slot_time = ?
                    LIMIT 1
                ");
                $check->execute([$doctorId, $slotTime]);

                if ($check->fetch(PDO::FETCH_ASSOC)) {
                    $error = "Ce créneau existe déjà.";
                } else {
                    $insert = $pdo->prepare("
                        INSERT INTO availability_slots (doctor_id, slot_time, is_booked)
                        VALUES (?, ?, 0)
                    ");
                    $insert->execute([$doctorId, $slotTime]);

                    $success = "Créneau ajouté avec succès.";
                }
            }
        }
    }

    // Suppression créneau
    if (!empty($_POST['delete_id'])) {
        $slotId = (int)$_POST['delete_id'];

        if ($slotId <= 0) {
            $error = "Créneau invalide.";
        } else {
            $delete = $pdo->prepare("
                DELETE FROM availability_slots
                WHERE id = ?
                  AND doctor_id = ?
                  AND is_booked = 0
                  AND slot_time > NOW()
            ");
            $delete->execute([$slotId, $doctorId]);

            if ($delete->rowCount() > 0) {
                $success = "Créneau supprimé avec succès.";
            } else {
                $error = "Impossible de supprimer ce créneau (réservé ou déjà passé).";
            }
        }
    }
}

/* =========================
   LISTE CRENEAUX
========================= */
$stmt = $pdo->prepare("
    SELECT id, doctor_id, slot_time, is_booked
    FROM availability_slots
    WHERE doctor_id = ?
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

$groupedSlots = groupSlotsByDate($slots);

$pageTitle = "Mes créneaux";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4" style="color:#4b2a84;">Gérer mes créneaux</h3>

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

<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form method="POST" action="" class="row g-3 align-items-end" novalidate>
            <?= csrf_input() ?>

            <div class="col-md-8">
                <label for="slot_time" class="form-label fw-semibold">Ajouter un créneau</label>
                <input
                    type="datetime-local"
                    id="slot_time"
                    name="slot_time"
                    class="form-control"
                    min="<?= date('Y-m-d\TH:i') ?>"
                    required
                >
            </div>

            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    Ajouter créneau
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-4" style="color:#4b2a84;">Mes créneaux enregistrés</h4>

        <?php if (!$groupedSlots): ?>
            <div class="alert alert-info mb-0">
                Aucun créneau ajouté.
            </div>
        <?php else: ?>

            <?php foreach ($groupedSlots as $date => $dateSlots): ?>
                <?php
                $jourKey = date('D', strtotime($date));
                $jourFr = $jours[$jourKey] ?? $jourKey;
                $countSlots = count($dateSlots);
                ?>
                <div class="day-card mb-4">
                    <div class="day-title mb-3">
                        <?= htmlspecialchars(
                            $jourFr . ' ' . date('d/m/Y', strtotime($date)) . ' (' . $countSlots . ' créneau(x))',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($dateSlots as $slot): ?>
                            <div class="slot-manage-card">
                                <div class="fw-semibold mb-2">
                                    <?= date('H:i', strtotime((string)$slot['slot_time'])) ?>
                                </div>

                                <?php if ((int)$slot['is_booked'] === 1): ?>
                                    <span class="badge bg-danger mb-2">Réservé</span>
                                    <div class="text-muted small">Non supprimable</div>
                                <?php else: ?>
                                    <span class="badge bg-success mb-2">Disponible</span>
                                    <div>
                                        <form
                                            method="POST"
                                            action=""
                                            style="display:inline;"
                                            onsubmit="return confirm('Supprimer ce créneau ?');"
                                        >
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="delete_id" value="<?= (int)$slot['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>