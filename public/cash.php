<?php
require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/helpers.php';
require __DIR__.'/../config/database.php';
require __DIR__.'/../app/Controllers/CashController.php';

use App\Controllers\CashController;

try {
    $ctl = new CashController();
    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD']==='POST' && $action==='charge') { $ctl->charge(); exit; }
    echo $ctl->index();
} catch (Throwable $e) {
    if (env('APP_DEBUG','false')==='true') {
        echo "<pre style='white-space:pre-wrap'>", htmlspecialchars((string)$e), "</pre>";
    } else {
        http_response_code(500); echo "Error interno.";
    }
}
