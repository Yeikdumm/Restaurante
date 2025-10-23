<?php
namespace App\Controllers;

use DB;

class InventoryController
{
    private function mustBeAdminInventory(): void {
        require_login();
        if (!can('inventory.manage')) {
            http_response_code(403);
            exit('No autorizado');
        }
    }

    public function index() {
        $this->mustBeAdminInventory();

        $stmt = DB::conn()->query("SELECT * FROM inventory_items ORDER BY active DESC, name ASC");
        $items = $stmt->fetchAll() ?: [];

        foreach ($items as &$it) {
            $min = (float)($it['min_stock'] ?? 0);
            $qty = (float)($it['stock_qty'] ?? 0);
            $it['_low'] = ($min > 0 && $qty <= $min);
        }

        ob_start();
        view('inventory/index', ['items' => $items]);
        return ob_get_clean();
    }

    // Crear/editar insumo
    public function storeItem(): void {
        csrf_check();
        $this->mustBeAdminInventory();

        $id        = (int)($_POST['id'] ?? 0);
        $name      = trim((string)($_POST['name'] ?? ''));
        $unitIn    = (string)($_POST['unit'] ?? 'g');
        $unit      = ($unitIn === 'unit') ? 'unit' : 'g';
        $min_stock = (float)($_POST['min_stock'] ?? 0);
        $active    = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['error'] = 'El nombre es obligatorio.';
            header('Location: inventory.php');
            exit;
        }

        if ($id > 0) {
            $sql = "UPDATE inventory_items SET name=?, unit=?, min_stock=?, active=? WHERE id=?";
            DB::conn()->prepare($sql)->execute([$name, $unit, $min_stock, $active, $id]);
            $_SESSION['ok'] = 'Insumo actualizado.';
        } else {
            $sql = "INSERT INTO inventory_items (name, unit, stock_qty, avg_cost_per_unit, min_stock, active)
                    VALUES (?, ?, 0, 0, ?, 1)";
            DB::conn()->prepare($sql)->execute([$name, $unit, $min_stock]);
            $_SESSION['ok'] = 'Insumo creado.';
        }

        header('Location: inventory.php');
        exit;
    }

    // Entrada de inventario (compra) — recalcula costo promedio ponderado
    public function addStock(): void {
        csrf_check();
        $this->mustBeAdminInventory();

        $item_id    = (int)($_POST['item_id'] ?? 0);
        $qty_in     = (float)($_POST['qty_in'] ?? 0);
        $cost_total = (float)($_POST['cost_total'] ?? 0);
        $note       = trim((string)($_POST['note'] ?? ''));

        if ($item_id <= 0 || $qty_in <= 0 || $cost_total < 0) {
            $_SESSION['error'] = 'Datos inválidos para la entrada.';
            header('Location: inventory.php');
            exit;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Bloquear fila actual
            $stmt = $pdo->prepare("SELECT stock_qty, avg_cost_per_unit FROM inventory_items WHERE id=? FOR UPDATE");
            $stmt->execute([$item_id]);
            $cur = $stmt->fetch();
            if (!$cur) { throw new \Exception('Insumo no encontrado'); }

            $stock = (float)$cur['stock_qty'];
            $avg   = (float)$cur['avg_cost_per_unit'];

            $new_stock = $stock + $qty_in;
            $new_avg   = $new_stock > 0 ? (($stock * $avg) + $cost_total) / $new_stock : 0;

            // Actualizar item
            $pdo->prepare("UPDATE inventory_items SET stock_qty=?, avg_cost_per_unit=? WHERE id=?")
                ->execute([$new_stock, $new_avg, $item_id]);

            // Movimiento
            $pdo->prepare("INSERT INTO inventory_movements (item_id, qty_change, cost_total, type, note)
                           VALUES (?, ?, ?, 'in', ?)")
                ->execute([$item_id, $qty_in, $cost_total, $note]);

            $pdo->commit();
            $_SESSION['ok'] = 'Entrada registrada. Stock y costo promedio actualizados.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: inventory.php');
        exit;
    }

    // Salida por merma o Ajuste (positivo/negativo)
    public function removeOrAdjustStock(): void {
        csrf_check();
        $this->mustBeAdminInventory();

        $item_id = (int)($_POST['item_id'] ?? 0);
        $mode    = (string)($_POST['mode'] ?? 'out'); // out|adjust
        $qty     = (float)($_POST['qty'] ?? 0);       // siempre positiva en el form
        $note    = trim((string)($_POST['note'] ?? ''));
        $sign    = (isset($_POST['sign']) && $_POST['sign'] === 'plus') ? 1 : -1;

        if ($item_id <= 0 || $qty <= 0 || !in_array($mode, ['out','adjust'], true)) {
            $_SESSION['error'] = 'Datos inválidos para salida/ajuste.';
            header('Location: inventory.php');
            exit;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT stock_qty, avg_cost_per_unit FROM inventory_items WHERE id=? FOR UPDATE");
            $stmt->execute([$item_id]);
            $cur = $stmt->fetch();
            if (!$cur) { throw new \Exception('Insumo no encontrado'); }

            $stock = (float)$cur['stock_qty'];
            $avg   = (float)$cur['avg_cost_per_unit'];

            if ($mode === 'out') {
                if ($qty > $stock) { throw new \Exception('No hay stock suficiente para la salida.'); }
                $new_stock  = $stock - $qty;
                $qty_change = -$qty;
                $cost_total = $qty * $avg; // costo contable de la merma
                $type       = 'out';
            } else {
                $delta = $sign * $qty;
                if (($stock + $delta) < 0) { throw new \Exception('El ajuste dejaría stock negativo.'); }
                $new_stock  = $stock + $delta;
                $qty_change = $delta;
                $cost_total = 0; // por defecto no afecta costo
                $type       = 'adjust';
            }

            $pdo->prepare("UPDATE inventory_items SET stock_qty=? WHERE id=?")
                ->execute([$new_stock, $item_id]);

            $pdo->prepare("INSERT INTO inventory_movements (item_id, qty_change, cost_total, type, note)
                           VALUES (?, ?, ?, ?, ?)")
                ->execute([$item_id, $qty_change, $cost_total, $type, $note]);

            $pdo->commit();
            $_SESSION['ok'] = ($mode === 'out') ? 'Salida registrada.' : 'Ajuste registrado.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: inventory.php');
        exit;
    }
}
