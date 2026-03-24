<?php
declare(strict_types=1);

require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../includes/csrf.php";

if (($_SESSION['user']['role'] ?? '') !== 'DOCTOR') {
    header("Location: dashboard.php?err=Accès refusé");
    exit;
}

$doctorId = (int)$_SESSION['user']['id'];
$appointmentId = (int)($_GET['id'] ?? 0);

if ($appointmentId <= 0) {
    header("Location: dashboard.php?err=Rendez-vous invalide");
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.id, a.status, a.appt_at,
           u.first_name, u.last_name
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.id = ? AND a.doctor_id = ?
    LIMIT 1
");
$stmt->execute([$appointmentId, $doctorId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: dashboard.php?err=Rendez-vous introuvable ou non autorisé");
    exit;
}

// Option métier : n'autoriser l'ordonnance que pour un RDV confirmé ou terminé
if (!in_array($appointment['status'], ['CONFIRMED', 'COMPLETED'], true)) {
    header("Location: dashboard.php?err=Ordonnance non autorisée pour ce statut");
    exit;
}

$patientName = htmlspecialchars(
    (string)$appointment['first_name'] . ' ' . (string)$appointment['last_name'],
    ENT_QUOTES,
    'UTF-8'
);

$pageTitle = "Créer ordonnance";
require __DIR__ . "/../includes/header.php";
?>

<h3 class="fw-bold mb-4">Créer une ordonnance</h3>

<?php if (!empty($_GET['err'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars((string)$_GET['err'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['ok'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars((string)$_GET['ok'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="mb-1">
            <span class="fw-semibold">Patient :</span> <?= $patientName ?>
        </div>
        <div class="text-muted">
            Rendez-vous du <?= date('d/m/Y H:i', strtotime((string)$appointment['appt_at'])) ?>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <form method="POST" action="../actions/save_prescription.php" novalidate>
            <?= csrf_input() ?>

            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">

            <div class="mb-3">
                <label for="content" class="form-label fw-semibold">Ordonnance</label>
                <textarea
                    id="content"
                    name="content"
                    class="form-control"
                    rows="8"
                    maxlength="5000"
                    required
                ></textarea>
                <div class="form-text">
                    Rédige le traitement, les consignes et la durée si nécessaire.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Enregistrer ordonnance
            </button>
        </form>

    </div>
</div>

<?php require __DIR__ . "/../includes/footer.php"; ?>