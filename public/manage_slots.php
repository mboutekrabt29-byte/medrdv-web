<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header("Location: dashboard.php");
    exit;
}

$doctorId = (int)$_SESSION['user']['id'];

/* =========================
   AJOUT CRENEAU
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slot_time'])) {

    $slotRaw = $_POST['slot_time'];

    if (!empty($slotRaw)) {

        // Convertir correctement le datetime-local
        $slotTime = str_replace('T', ' ', $slotRaw) . ':00';

        $insert = $pdo->prepare("
            INSERT INTO availability_slots (doctor_id, slot_time, is_booked)
            VALUES (?, ?, 0)
        ");

        $insert->execute([$doctorId, $slotTime]);
    }

    header("Location: manage_slots.php");
    exit;
}
/* =========================
   SUPPRESSION CRENEAU
========================= */
if (isset($_GET['delete'])) {

    $slotId = (int)$_GET['delete'];

    $delete = $pdo->prepare("
        DELETE FROM availability_slots
        WHERE id = ? AND doctor_id = ? AND is_booked = 0
    ");

    $delete->execute([$slotId, $doctorId]);

    header("Location: manage_slots.php");
    exit;
}

/* =========================
   LISTE CRENEAUX
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM availability_slots
    WHERE doctor_id = ?
    ORDER BY slot_time ASC
");
$stmt->execute([$doctorId]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mes créneaux";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4">Gérer mes créneaux</h3>

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <form method="POST" action="" class="row g-3">
            <div class="col-md-8">
                <input type="datetime-local"
                       name="slot_time"
                       class="form-control"
                       required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    Ajouter créneau
                </button>
            </div>
        </form>

    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="fw-bold mb-3">Mes créneaux enregistrés</h5>

        <?php if (!$slots): ?>
            <div class="alert alert-info">
                Aucun créneau ajouté.
            </div>
        <?php else: ?>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($slots as $slot): ?>
                    <tr>
                        <td>
                            <?= date("d/m/Y H:i", strtotime($slot['slot_time'])) ?>
                        </td>

                        <td>
                            <?php if ($slot['is_booked']): ?>
                                <span class="badge bg-danger">Réservé</span>
                            <?php else: ?>
                                <span class="badge bg-success">Disponible</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!$slot['is_booked']): ?>
                                <a href="manage_slots.php?delete=<?= $slot['id'] ?>"
                                   class="btn btn-sm btn-outline-danger">
                                   Supprimer
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>