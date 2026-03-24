<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Clinique Inaya';
$user = $_SESSION['user'] ?? null;
$isLoggedIn = !empty($user);
$userFirstName = $isLoggedIn
    ? htmlspecialchars((string)($user['first_name'] ?? ''), ENT_QUOTES, 'UTF-8')
    : '';
$userRole = $isLoggedIn ? (string)($user['role'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | Clinique Inaya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/medrdv_web/assets/css/style.css?v=2">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm">
    <div class="container">
        <a
            class="navbar-brand d-flex align-items-center fw-bold"
            href="/medrdv_web/public/index.php"
        >
            <img
                src="/medrdv_web/assets/images/logo.jpeg"
                alt="Clinique Inaya"
                style="height:40px; width:auto; object-fit:contain; margin-right:10px;"
            >
            <span class="text-white">Clinique Inaya - Kouba</span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Ouvrir la navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <div class="ms-auto d-flex flex-wrap align-items-center gap-2 mt-3 mt-lg-0">

                <?php if ($isLoggedIn): ?>
                    <span class="text-white small me-2">
                        Bonjour
                        <?= $userRole === 'DOCTOR' ? 'Dr ' : '' ?><?= $userFirstName ?>
                    </span>

                    <a
                        href="/medrdv_web/public/dashboard.php"
                        class="btn btn-primary btn-sm"
                    >
                        Dashboard
                    </a>

                    <?php if ($userRole === 'DOCTOR'): ?>
                        <a
                            href="/medrdv_web/public/manage_slots.php"
                            class="btn btn-outline-light btn-sm"
                        >
                            Mes créneaux
                        </a>
                    <?php else: ?>
                        <a
                            href="/medrdv_web/public/my_appointments.php"
                            class="btn btn-outline-light btn-sm"
                        >
                            Mes rendez-vous
                        </a>
                    <?php endif; ?>

                    <a
                        href="/medrdv_web/public/logout.php"
                        class="btn btn-danger btn-sm"
                    >
                        Déconnexion
                    </a>
                <?php else: ?>
                    <a
                        href="/medrdv_web/public/login.php"
                        class="btn btn-primary btn-sm"
                    >
                        Connexion
                    </a>

                    <a
                        href="/medrdv_web/public/register.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Inscription
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<div class="container py-5">