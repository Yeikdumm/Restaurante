<?php
require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/helpers.php';
require __DIR__.'/../config/database.php';
require __DIR__.'/../app/Controllers/OrdersController.php';

use App\Controllers\OrdersController;

try {
    $ctl = new OrdersController();
    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='ready') { $ctl->markReady(); exit; }
    echo $ctl->kitchen();
} catch (Throwable $e) {
    if (env('APP_DEBUG','false')==='true') {
        echo "<pre style='white-space:pre-wrap'>", htmlspecialchars((string)$e), "</pre>";
    } else {
        http_response_code(500); echo "Error interno.";
    }
}
