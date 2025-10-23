<?php
namespace App\Controllers;

use DB;

class ProductsController
{
    private function mustBeAdminProducts(): void {
        require_login();
        if (!can('products.manage')) {
            http_response_code(403);
            exit('No autorizado');
        }
    }

    public function index() {
        $this->mustBeAdminProducts();

        $editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

        $pdo = DB::conn();
        $ps = $pdo->query("SELECT * FROM products ORDER BY active DESC, name ASC")->fetchAll() ?: [];

        foreach ($ps as &$p) {
            $p['calc_cost'] = $this->calcProductCost((int)$p['id'], $pdo);
            $rate  = (float)$p['tax_rate'];
            $gross = (float)$p['public_price_gross'];
            $net   = ($rate >= 0 ? ($gross / (1.0 + $rate)) : $gross);
            $iva   = $gross - $net;
            $p['net']    = $net;
            $p['iva']    = $iva;
            $p['margin'] = $gross - $p['calc_cost'];
        }

        // Datos para el editor (si se pidió editar)
        $editProduct = null;
        $ingredients = [];
        $items = [];
        if ($editId > 0) {
            foreach ($ps as $pp) {
                if ((int)$pp['id'] === $editId) { $editProduct = $pp; break; }
            }
            if ($editProduct) {
                $st = $pdo->prepare("
                    SELECT pi.item_id, pi.qty_required, it.name, it.unit
                    FROM product_ingredients pi
                    JOIN inventory_items it ON it.id=pi.item_id
                    WHERE pi.product_id=?
                    ORDER BY it.name ASC
                ");
                $st->execute([$editId]);
                $ingredients = $st->fetchAll() ?: [];

                $items = $pdo->query("SELECT id, name, unit FROM inventory_items WHERE active=1 ORDER BY name ASC")->fetchAll() ?: [];
            }
        }

        ob_start();
        view('products/index', [
            'products'    => $ps,
            'editId'      => $editId,
            'editProduct' => $editProduct,
            'ingredients' => $ingredients,
            'items'       => $items
        ]);
        return ob_get_clean();
    }

    private function calcProductCost(int $productId, $pdo = null): float {
        $pdo = $pdo ?: DB::conn();
        $stmt = $pdo->prepare("
            SELECT i.avg_cost_per_unit, pi.qty_required
            FROM product_ingredients pi
            JOIN inventory_items i ON i.id = pi.item_id
            WHERE pi.product_id = ?
        ");
        $stmt->execute([$productId]);
        $sum = 0.0;
        foreach ($stmt->fetchAll() as $r) {
            $sum += (float)$r['avg_cost_per_unit'] * (float)$r['qty_required'];
        }
        return $sum;
    }

    public function storeProduct(): void {
        csrf_check();
        $this->mustBeAdminProducts();

        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim((string)($_POST['name'] ?? ''));
        $sku    = trim((string)($_POST['sku'] ?? ''));
        $gross  = (float)($_POST['public_price_gross'] ?? 0);
        $tax    = (float)($_POST['tax_rate'] ?? 0.19);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || $gross < 0) {
            $_SESSION['error'] = 'Nombre y precio público son obligatorios.';
            header('Location: products.php');
            exit;
        }

        if ($id > 0) {
            $sql = "UPDATE products SET name=?, sku=?, public_price_gross=?, tax_rate=?, active=? WHERE id=?";
            DB::conn()->prepare($sql)->execute([$name, $sku ?: null, $gross, $tax, $active, $id]);
            $_SESSION['ok'] = 'Producto actualizado.';
            header("Location: products.php?edit={$id}");
        } else {
            $sql = "INSERT INTO products (name, sku, public_price_gross, tax_rate, active) VALUES (?,?,?,?,1)";
            DB::conn()->prepare($sql)->execute([$name, $sku ?: null, $gross, $tax]);
            $_SESSION['ok'] = 'Producto creado.';
            header("Location: products.php");
        }
        exit;
    }

    public function deleteProduct(): void {
        csrf_check();
        $this->mustBeAdminProducts();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Producto inválido.';
            header('Location: products.php');
            exit;
        }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM product_ingredients WHERE product_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            $pdo->commit();
            $_SESSION['ok'] = 'Producto eliminado.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: products.php');
        exit;
    }

    public function addIngredient(): void {
        csrf_check();
        $this->mustBeAdminProducts();

        $product_id = (int)($_POST['product_id'] ?? 0);
        $item_id    = (int)($_POST['item_id'] ?? 0);
        $qty        = (float)($_POST['qty_required'] ?? 0);

        if ($product_id <= 0 || $item_id <= 0 || $qty <= 0) {
            $_SESSION['error'] = 'Datos inválidos para ingrediente.';
            header('Location: products.php');
            exit;
        }

        $pdo = DB::conn();
        $exists = $pdo->prepare("SELECT qty_required FROM product_ingredients WHERE product_id=? AND item_id=?");
        $exists->execute([$product_id, $item_id]);
        if ($row = $exists->fetch()) {
            $newQty = (float)$row['qty_required'] + $qty;
            $pdo->prepare("UPDATE product_ingredients SET qty_required=? WHERE product_id=? AND item_id=?")
                ->execute([$newQty, $product_id, $item_id]);
        } else {
            $pdo->prepare("INSERT INTO product_ingredients (product_id, item_id, qty_required) VALUES (?,?,?)")
                ->execute([$product_id, $item_id, $qty]);
        }

        $_SESSION['ok'] = 'Ingrediente agregado.';
        header("Location: products.php?edit={$product_id}");
        exit;
    }

    public function removeIngredient(): void {
        csrf_check();
        $this->mustBeAdminProducts();

        $product_id = (int)($_POST['product_id'] ?? 0);
        $item_id    = (int)($_POST['item_id'] ?? 0);

        if ($product_id <= 0 || $item_id <= 0) {
            $_SESSION['error'] = 'Datos inválidos para eliminar ingrediente.';
            header('Location: products.php');
            exit;
        }

        DB::conn()->prepare("DELETE FROM product_ingredients WHERE product_id=? AND item_id=?")
            ->execute([$product_id, $item_id]);

        $_SESSION['ok'] = 'Ingrediente eliminado.';
        header("Location: products.php?edit={$product_id}");
        exit;
    }
}
