<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: /medrdv_web/public/login.php?err=Veuillez vous connecter");
    exit;
}

/**
 * Vérifie que l'utilisateur connecté a bien le rôle demandé.
 */
function require_role(string $role): void
{
    $currentRole = $_SESSION['user']['role'] ?? null;

    if ($currentRole !== $role) {
        header("Location: /medrdv_web/public/dashboard.php?err=Accès refusé");
        exit;
    }
}