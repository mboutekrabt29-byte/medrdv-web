<?php
$pageTitle = "Inscription";
require __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h3 class="fw-bold mb-4 text-center">Créer un compte</h3>

                <?php if (!empty($_GET['err'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_GET['err']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="../actions/register_action.php">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-select" required>
                            <option value="PATIENT">Patient</option>
                            <option value="DOCTOR">Médecin</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Créer un compte
                        </button>
                    </div>

                </form>

                <div class="text-center mt-3">
                    Déjà inscrit ?
                    <a href="login.php">Connexion</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . "/../includes/footer.php";
?>