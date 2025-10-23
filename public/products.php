<?php
require __DIR__ . '/../bootstrap/autoload.php';
require __DIR__ . '/../bootstrap/helpers.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Controllers/ProductsController.php';

use App\Controllers\ProductsController;

try {
    $ctl = new ProductsController();
    $action = $_GET['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if     ($action === 'store')     { $ctl->storeProduct(); exit; }
        elseif ($action === 'delete')    { $ctl->deleteProduct(); exit; }
        elseif ($action === 'adding')    { $ctl->addIngredient(); exit; }
        elseif ($action === 'removing')  { $ctl->removeIngredient(); exit; }
    }

    echo $ctl->index();
} catch (Throwable $e) {
    if (env('APP_DEBUG', 'false') === 'true') {
        echo "<pre style='white-space:pre-wrap'>", htmlspecialchars((string)$e), "</pre>";
    } else {
        http_response_code(500);
        echo "Error interno.";
    }
}
