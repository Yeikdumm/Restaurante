<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
echo (new App\Controllers\AuthController())->logout();