<?php
declare(strict_types=1);

session_start();

require __DIR__ . "/../includes/csrf.php";

$pageTitle = "Connexion";
require __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">

                <h3 class="fw-bold mb-4 text-center">Connexion</h3>

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

                <form method="POST" action="../actions/login_action.php" novalidate>
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            maxlength="120"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                        >
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

<?php require __DIR__ . "/../includes/footer.php"; ?>