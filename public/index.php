<?php
require __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body text-center p-5">
                <h1 class="fw-bold mb-3">Bienvenue sur MedRDV</h1>
                <p class="text-muted mb-4">
                    Prenez rendez-vous avec votre médecin en quelques clics.
                </p>

                <div class="d-grid gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        Connexion
                    </a>
                    <a href="register.php" class="btn btn-outline-primary btn-lg">
                        Inscription
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . "/../includes/footer.php";
?>