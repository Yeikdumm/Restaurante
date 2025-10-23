<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';
require_login();
require __DIR__ . '/../app/Controllers/DashboardController.php';
echo (new App\Controllers\DashboardController())->index();