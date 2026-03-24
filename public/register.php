<?php
declare(strict_types=1);

session_start();

require __DIR__ . "/../includes/csrf.php";

$pageTitle = "Inscription";
require __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">

                <h3 class="fw-bold mb-4 text-center">Créer un compte</h3>

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

                <form method="POST" action="../actions/register_action.php" novalidate>
                    <?= csrf_input() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                class="form-control"
                                maxlength="50"
                                required
                                autofocus
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                class="form-control"
                                maxlength="50"
                                required
                            >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            class="form-control"
                            maxlength="20"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="PATIENT">Patient</option>
                            <option value="DOCTOR">Médecin</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            minlength="8"
                            required
                        >
                        <div class="form-text">
                            Minimum 8 caractères, avec au moins une majuscule, une minuscule et un chiffre.
                        </div>
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

<?php require __DIR__ . "/../includes/footer.php"; ?>