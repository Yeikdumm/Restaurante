<?php
if (!isset($_SESSION)) session_start();
$current  = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user']['name'] ?? ($_SESSION['user_name'] ?? 'Usuario');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restaurante - Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    nav.navbar { background: #003366; }
    .navbar-brand, .nav-link, .navbar-text { color: #ffffff !important; }
    .nav-link.active { background: rgba(255,255,255,0.2); border-radius: 5px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">Restaurante</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo $current=='inventory.php'?'active':''; ?>" href="inventory.php">Inventario</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current=='products.php'?'active':''; ?>" href="products.php">Productos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current=='orders.php'?'active':''; ?>" href="orders.php">Pedidos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current=='kitchen.php'?'active':''; ?>" href="kitchen.php">Cocina</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current=='cash.php'?'active':''; ?>" href="cash.php">Caja</a>
        </li>
      </ul>

      <div class="navbar-text me-3">
        Usuario: <?php echo htmlspecialchars($userName); ?>
      </div>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
