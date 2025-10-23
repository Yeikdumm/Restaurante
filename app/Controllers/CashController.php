<?php
namespace App\Controllers;

use DB;

class CashController
{
    private function mustBeLogged(): void { require_login(); }

    private function orderTotal(int $orderId, $pdo): array {
        // Retorna [total_gross, total_net, total_iva]
        $st = $pdo->prepare("
            SELECT qty, price_gross, tax_rate
            FROM order_items WHERE order_id = ?
        ");
        $st->execute([$orderId]);
        $gross = $net = $iva = 0.0;
        foreach ($st->fetchAll() as $r) {
            $qty = (float)$r['qty'];
            $pg  = (float)$r['price_gross'];
            $tr  = (float)$r['tax_rate'];
            $sub = $qty * $pg;
            $gross += $sub;
            $n = ($tr > 0 ? $sub / (1.0 + $tr) : $sub);
            $i = $sub - $n;
            $net += $n;  $iva += $i;
        }
        return [$gross, $net, $iva];
    }

    public function index() {
        $this->mustBeLogged();
        $pdo = DB::conn();

        // Pedidos listos para cobrar (del día, status=ready)
        $today = date('Y-m-d 00:00:00');
        $st = $pdo->prepare("SELECT * FROM orders WHERE created_at >= ? AND status='ready' ORDER BY id ASC");
        $st->execute([$today]);
        $pending = $st->fetchAll() ?: [];
        foreach ($pending as &$o) {
            [$g,$n,$i] = $this->orderTotal((int)$o['id'], $pdo);
            $o['_gross'] = $g; $o['_net']=$n; $o['_iva']=$i;
        }

        // Cobros de hoy (ya pagados)
        $paid = $pdo->prepare("
            SELECT o.id, o.code, o.type, o.table_no, o.customer_name, o.customer_phone, cm.amount, cm.payment_method, cm.created_at
            FROM orders o
            JOIN cash_movements cm ON cm.order_id=o.id
            WHERE o.created_at >= ? AND o.status='paid'
            ORDER BY cm.id DESC
        ");
        $paid->execute([$today]);
        $paidRows = $paid->fetchAll() ?: [];

        // Totales por método
        $tot = ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0,'total'=>0];
        foreach ($paidRows as $r) {
            $m = $r['payment_method'];
            $tot[$m] += (float)$r['amount'];
            $tot['total'] += (float)$r['amount'];
        }

        ob_start();
        view('cash/index', [
            'pending'=>$pending,
            'paidRows'=>$paidRows,
            'totals'=>$tot
        ]);
        return ob_get_clean();
    }

    public function charge() {
        csrf_check();
        $this->mustBeLogged();

        $order_id = (int)($_POST['order_id'] ?? 0);
        $method   = $_POST['payment_method'] ?? 'efectivo'; // efectivo|tarjeta|transferencia
        $paid     = (float)($_POST['amount_paid'] ?? 0);

        if ($order_id <= 0) { $_SESSION['error']='Pedido inválido'; header('Location: cash.php'); exit; }

        $pdo = DB::conn();
        $pdo->beginTransaction();
        try {
            // Total del pedido
            [$gross] = $this->orderTotal($order_id, $pdo);

            // Validar monto segun método
            if ($method === 'efectivo') {
                if ($paid < $gross) throw new \Exception('Pago insuficiente en efectivo.');
                $amountToSave = $gross; // se registra lo que entra a caja (no el recibido)
            } else {
                // tarjeta/transferencia
                $amountToSave = $gross;
                $paid = $gross; // para la UI de cambio
            }

            // Guardar movimiento
            $ins = $pdo->prepare("INSERT INTO cash_movements (order_id, amount, payment_method) VALUES (?,?,?)");
            $ins->execute([$order_id, $amountToSave, $method]);

            // Marcar pedido como pagado
            $pdo->prepare("UPDATE orders SET status='paid' WHERE id=?")->execute([$order_id]);

            $pdo->commit();

            $_SESSION['ok'] = 'Cobro registrado. Cambio: $ '.number_format(max(0,$paid-$gross),0,',','.');
            // Ir al ticket
            header('Location: ticket.php?order_id='.$order_id);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'No se pudo cobrar: '.$e->getMessage();
            header('Location: cash.php');
        }
        exit;
    }
}
