<?php
require_once __DIR__ . '/functions.php';
requireAdmin();
$base = basePath();
$flash = getFlash();
$current = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Hazina Funding Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= $base ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark navbar-top px-3">
  <button class="btn btn-link text-white d-lg-none" type="button" id="sidebarToggle">
    <i class="bi bi-list fs-3"></i>
  </button>
  <a class="navbar-brand ms-2" href="<?= $base ?>index.php">
    <i class="bi bi-heart-fill text-danger"></i> Hazina Funding <span class="fw-light">Admin</span>
  </a>
  <div class="ms-auto dropdown">
    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
      <i class="bi bi-person-circle"></i> <?= e($_SESSION['admin_username'] ?? 'Admin') ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= $base ?>logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
    </ul>
  </div>
</nav>

<div class="d-flex">
  <aside class="sidebar" id="sidebar">
    <ul class="nav flex-column p-2">
      <li class="nav-item">
        <a class="nav-link <?= ($current === 'index.php' && $currentDir === 'admin') ? 'active' : '' ?>" href="<?= $base ?>index.php">
          <i class="bi bi-speedometer2"></i> Tableau de bord
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $currentDir === 'projects' ? 'active' : '' ?>" href="<?= $base ?>projects/index.php">
          <i class="bi bi-kanban"></i> Projets
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $currentDir === 'donations' ? 'active' : '' ?>" href="<?= $base ?>donations/index.php">
          <i class="bi bi-cash-coin"></i> Dons
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $currentDir === 'users' ? 'active' : '' ?>" href="<?= $base ?>users/index.php">
          <i class="bi bi-people"></i> Utilisateurs
        </a>
      </li>
    </ul>
  </aside>

  <main class="content flex-grow-1 p-3 p-md-4">
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
