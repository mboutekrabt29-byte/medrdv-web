<?php
$pageTitle = "Connexion";
require __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <h3 class="fw-bold mb-4 text-center">Connexion</h3>

                <?php if (!empty($_GET['err'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_GET['err']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="../actions/login_action.php">

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Se connecter
                        </button>
                    </div>

                </form>

                <div class="text-center mt-3">
                    Pas encore inscrit ?
                    <a href="register.php">Créer un compte</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . "/../includes/footer.php";
?>