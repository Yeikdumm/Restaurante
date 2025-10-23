<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;

// Basic front controller for /index.php?r=controller@action
session_start();
$path = $_GET['r'] ?? 'dashboard@index';

[$controller, $action] = explode('@', $path) + [null, null];
$controller = $controller ?: 'dashboard';
$action = $action ?: 'index';

$controller = ucfirst($controller) . 'Controller';
$controllerClass = "App\\Controllers\\$controller";

if (!class_exists($controllerClass)) {
    http_response_code(404);
    echo "Controller not found";
    exit;
}

$instance = new $controllerClass;
if (!method_exists($instance, $action)) {
    http_response_code(404);
    echo "Action not found";
    exit;
}
echo $instance->$action();