<?php
namespace App\Controllers;

use DB;

class OrdersController
{
    /* ----------------- Acceso: solo logueado (sin permisos granulares) ----------------- */
    private function mustBeLogged(): void {
        require_login(); // si no hay sesión, redirige a login
    }

    /* ----------------- LISTA / FORM NUEVO PEDIDO ----------------- */
    public function index() {
        $this->mustBeLogged();
        $pdo = DB::conn();

        // productos activos para selector (precio e IVA actuales)
        $products = $pdo->query("SELECT id, name, public_price_gross, tax_rate FROM products WHERE active=1 ORDER BY name ASC")->fetchAll() ?: [];

        // últimos pedidos del día (para listado inferior)
        $today = date('Y-m-d 00:00:00');
        $st = $pdo->prepare("SELECT * FROM orders WHERE created_at >= ? ORDER BY id DESC LIMIT 50");
        $st->execute([$today]);
        $orders = $st->fetchAll() ?: [];

        ob_start();
        view('orders/index', ['products'=>$products, 'orders'=>$orders]);
        return ob_get_clean();
    }

    /* ----------------- CREAR PEDIDO (descuenta inventario por receta) ----------------- */
    public function create() {
    csrf_check();
    $this->mustBeLogged();

    $type = ($_POST['type'] ?? 'table') === 'delivery' ? 'delivery' : 'table';
    $table_no = trim((string)($_POST['table_no'] ?? ''));
    $customer_name = trim((string)($_POST['customer_name'] ?? ''));
    $customer_phone = trim((string)($_POST['customer_phone'] ?? ''));
    $customer_address = trim((string)($_POST['customer_address'] ?? ''));
    $items = json_decode($_POST['items_json'] ?? '[]', true);

    if (empty($items)) {
        $_SESSION['error'] = 'Agrega al menos un producto.';
        header('Location: orders.php'); exit;
    }

    // ID de usuario actual (soporta ambos escenarios)
    $createdBy = function_exists('\\current_user_id')
        ? (int)\current_user_id()
        : (int)($_SESSION['user']['id'] ?? 0);

    $pdo = DB::conn();
    $pdo->beginTransaction();
    try {
        // crear pedido con código
        $code = 'P'.date('ymdHis');
        $ins = $pdo->prepare("
            INSERT INTO orders
                (code, type, table_no, customer_name, customer_phone, customer_address, status, created_by)
            VALUES
                (?,    ?,    ?,        ?,             ?,              ?,                'in_kitchen', ?)
        ");
        $ins->execute([
            $code,
            $type,
            ($type==='table' ? $table_no : null),
            ($type==='delivery' ? $customer_name : null),
            ($type==='delivery' ? $customer_phone : null),
            ($type==='delivery' ? $customer_address : null),
            $createdBy
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // insertar items y descontar inventario según receta
        $ip = $pdo->prepare("INSERT INTO order_items(order_id,product_id,qty,price_gross,tax_rate) VALUES (?,?,?,?,?)");
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = (float)($it['qty'] ?? 0);
            if ($pid<=0 || $qty<=0) continue;

            $pr = $pdo->prepare("SELECT public_price_gross, tax_rate FROM products WHERE id=?");
            $pr->execute([$pid]);
            $prow = $pr->fetch();
            if (!$prow) throw new \Exception('Producto no existe');

            $ip->execute([$orderId, $pid, $qty, (float)$prow['public_price_gross'], (float)$prow['tax_rate']]);

            // ingredientes
            $ings = $pdo->prepare("SELECT item_id, qty_required FROM product_ingredients WHERE product_id=?");
            $ings->execute([$pid]);
            foreach ($ings->fetchAll() as $ing) {
                $item_id = (int)$ing['item_id'];
                $need = (float)$ing['qty_required'] * $qty;

                $cur = $pdo->prepare("SELECT stock_qty, avg_cost_per_unit FROM inventory_items WHERE id=? FOR UPDATE");
                $cur->execute([$item_id]);
                $row = $cur->fetch();
                if (!$row) throw new \Exception('Insumo no existe');
                if ((float)$row['stock_qty'] < $need) throw new \Exception('Stock insuficiente para preparar el pedido.');

                $new_stock = (float)$row['stock_qty'] - $need;
                $pdo->prepare("UPDATE inventory_items SET stock_qty=? WHERE id=?")->execute([$new_stock, $item_id]);

                $cost_total = $need * (float)$row['avg_cost_per_unit'];
                $pdo->prepare("INSERT INTO inventory_movements(item_id, qty_change, cost_total, type, note) VALUES (?,?,?,?,?)")
                    ->execute([$item_id, -$need, $cost_total, 'out', 'Consumo por pedido '.$code]);
            }
        }

        $pdo->commit();
        $_SESSION['ok'] = 'Pedido enviado a cocina (#'.$code.').';
    } catch (\Throwable $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'No se pudo crear el pedido: '.$e->getMessage();
    }
    header('Location: orders.php'); exit;
}



    /* ----------------- VISTA COCINA ----------------- */
    public function kitchen() {
        $this->mustBeLogged();
        $pdo = DB::conn();
        $st = $pdo->query("SELECT * FROM orders WHERE status IN ('in_kitchen','pending') ORDER BY id ASC");
        $orders = $st->fetchAll() ?: [];

        // items de cada pedido
        $getItems = $pdo->prepare("
           SELECT oi.qty, p.name
           FROM order_items oi JOIN products p ON p.id=oi.product_id
           WHERE oi.order_id = ?
        ");
        foreach ($orders as &$o) {
            $getItems->execute([$o['id']]);
            $o['_items'] = $getItems->fetchAll() ?: [];
        }

        ob_start();
        view('kitchen/index', ['orders'=>$orders]);
        return ob_get_clean();
    }

    public function markReady() {
        csrf_check();
        $this->mustBeLogged();
        $id = (int)($_POST['order_id'] ?? 0);
        if ($id<=0) { header('Location: kitchen.php'); exit; }
        DB::conn()->prepare("UPDATE orders SET status='ready' WHERE id=?")->execute([$id]);
        header('Location: kitchen.php'); exit;
    }
}
