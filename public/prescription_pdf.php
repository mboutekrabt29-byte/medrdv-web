<?php
require __DIR__ . "/../includes/auth_guard.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../lib/fpdf/fpdf.php";

$user = $_SESSION['user'];
$prescId = (int)($_GET['id'] ?? 0);

if ($prescId <= 0) {
    die("Ordonnance invalide");
}

// Sécurité :
// - si PATIENT → il ne peut télécharger que ses ordonnances
// - si DOCTOR  → il ne peut télécharger que celles qu'il a créées
if ($user['role'] === 'PATIENT') {
    $stmt = $pdo->prepare("
        SELECT p.*,
               d.first_name AS doctor_first, d.last_name AS doctor_last,
               pt.first_name AS patient_first, pt.last_name AS patient_last
        FROM prescriptions p
        JOIN users d  ON d.id  = p.doctor_id
        JOIN users pt ON pt.id = p.patient_id
        WHERE p.id = ? AND p.patient_id = ?
    ");
    $stmt->execute([$prescId, (int)$user['id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*,
               d.first_name AS doctor_first, d.last_name AS doctor_last,
               pt.first_name AS patient_first, pt.last_name AS patient_last
        FROM prescriptions p
        JOIN users d  ON d.id  = p.doctor_id
        JOIN users pt ON pt.id = p.patient_id
        WHERE p.id = ? AND p.doctor_id = ?
    ");
    $stmt->execute([$prescId, (int)$user['id']]);
}

$prescription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prescription) {
    die("Ordonnance introuvable ou accès refusé.");
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetTitle("Ordonnance");

$pdf->SetFont("Arial", "B", 16);
$pdf->Cell(0, 10, "Ordonnance Medicale", 0, 1, "C");

$pdf->Ln(6);

$pdf->SetFont("Arial", "", 12);
$pdf->Cell(0, 8, "Medecin : Dr. " . $prescription['doctor_first'] . " " . $prescription['doctor_last'], 0, 1);
$pdf->Cell(0, 8, "Patient : " . $prescription['patient_first'] . " " . $prescription['patient_last'], 0, 1);
$pdf->Cell(0, 8, "Date : " . date("d/m/Y", strtotime($prescription['created_at'])), 0, 1);

$pdf->Ln(8);

$pdf->SetFont("Arial", "B", 12);
$pdf->Cell(0, 8, "Prescription :", 0, 1);

$pdf->SetFont("Arial", "", 12);
$pdf->MultiCell(0, 8, $prescription['content']);

$pdf->Ln(12);
$pdf->Cell(0, 8, "Signature du medecin", 0, 1, "R");

// Nom de fichier propre
$filename = "ordonnance_" . $prescId . ".pdf";

// Force l'affichage/téléchargement
$pdf->Output("I", $filename);
exit;