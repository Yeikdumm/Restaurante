<?php
require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/helpers.php';
require __DIR__.'/../config/database.php';
$ok = [];
try {
  require_login();
  $ok[] = "login OK";
} catch (Throwable $e) {
  die("Falla login: ".$e->getMessage());
}
if (!file_exists(__DIR__.'/../app/Controllers/CashController.php')) die("Falta CashController.php");
if (!file_exists(__DIR__.'/../app/Views/cash/index.php')) die("Falta app/Views/cash/index.php");
echo "Vistas y controlador OK<br>";
try {
  $pdo = DB::conn();
  $pdo->query("SELECT 1 FROM cash_movements LIMIT 1");
  echo "Tabla cash_movements OK<br>";
} catch (Throwable $e) {
  echo "Problema con cash_movements: ".$e->getMessage();
}
