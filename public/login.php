<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
$controller = new App\Controllers\AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo $controller->login();
} else {
    echo $controller->showLogin();
}