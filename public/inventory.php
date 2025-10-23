<?php
// Mínimo y robusto, con manejo de errores visible si APP_DEBUG=true
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Controllers/InventoryController.php';

use App\Controllers\InventoryController;

try {
    $ctl = new InventoryController();
    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'store') { $ctl->storeItem(); exit; }
        if ($action === 'add')   { $ctl->addStock(); exit; }
        if ($action === 'out')   { $ctl->removeOrAdjustStock(); exit; }
    }

    echo $ctl->index();
} catch (Throwable $e) {
    // Si APP_DEBUG=true en .env, muestra el error en pantalla
    if (env('APP_DEBUG', 'false') === 'true') {
        echo "<pre style='white-space:pre-wrap'>", htmlspecialchars((string)$e), "</pre>";
    } else {
        http_response_code(500);
        echo "Error interno.";
    }
}
