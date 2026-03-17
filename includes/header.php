

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Clinique Inaya </title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link rel="stylesheet" href="/medrdv_web/assets/css/style.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
<div class="container">

<a class="navbar-brand d-flex align-items-center fw-bold"
   href="/medrdv_web/public/index.php">

<img src="/medrdv_web/assets/images/logo.jpeg"
     alt="Clinique Inaya"
     style="height:40px; margin-right:10px;">

<span>Clinique Inaya</span>

</a>

<div class="ms-auto">

<?php if (!empty($_SESSION['user'])): ?>

<a href="/medrdv_web/public/dashboard.php"
class="btn btn-light btn-sm me-2">
Dashboard
</a>

<a href="/medrdv_web/public/logout.php"
class="btn btn-outline-light btn-sm">
Déconnexion
</a>

<?php else: ?>

<a href="/medrdv_web/public/login.php"
class="btn btn-light btn-sm me-2">
Connexion
</a>

<a href="/medrdv_web/public/register.php"
class="btn btn-outline-light btn-sm">
Inscription
</a>

<?php endif; ?>

</div>

</div>
</nav>

<div class="container py-5">

