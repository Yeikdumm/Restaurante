<?php
require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/helpers.php';
require __DIR__.'/../config/database.php';
require_login();

use DB;

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId<=0) { exit('Pedido inválido'); }
$pdo = DB::conn();

// Empresa (si tienes tabla company_settings, toma el primer registro)
$cs = $pdo->query("SELECT * FROM company_settings LIMIT 1")->fetch() ?: [];
$empresa = $cs['company_name'] ?? 'Mi Heladería';
$nit     = $cs['company_nit']  ?? 'NIT 0';
$logo    = $cs['logo_url']     ?? null;

// Pedido
$o = $pdo->prepare("SELECT * FROM orders WHERE id=?");
$o->execute([$orderId]);
$order = $o->fetch();
if (!$order) { exit('Pedido no existe'); }

// Items
$it = $pdo->prepare("
  SELECT oi.qty, oi.price_gross, oi.tax_rate, p.name
  FROM order_items oi
  JOIN products p ON p.id=oi.product_id
  WHERE oi.order_id=?
");
$it->execute([$orderId]);
$items = $it->fetchAll() ?: [];

$gross=$net=$iva=0.0;
foreach ($items as $r){
  $sub = (float)$r['qty'] * (float)$r['price_gross'];
  $gross += $sub;
  $n = ($r['tax_rate']>0) ? ($sub/(1+$r['tax_rate'])) : $sub;
  $i = $sub - $n;
  $net += $n; $iva += $i;
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ticket <?php echo htmlspecialchars($order['code']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{ font-family: ui-monospace, Menlo, monospace; }
  .ticket { width: 280px; margin:0 auto; }
  .center { text-align: center; }
  .right { text-align: right; }
  .small { font-size: 12px; }
  hr { border: 0; border-top: 1px dashed #999; }
  @media print { .noprint{ display:none } }
</style>
</head>
<body>
<div class="ticket">
  <?php if ($logo): ?>
    <div class="center"><img src="<?php echo htmlspecialchars($logo); ?>" style="max-width:160px"></div>
  <?php endif; ?>
  <div class="center">
    <div><b><?php echo htmlspecialchars($empresa); ?></b></div>
    <div class="small"><?php echo htmlspecialchars($nit); ?></div>
    <div class="small"><?php echo date('Y-m-d H:i'); ?></div>
    <div class="small">Pedido: #<?php echo htmlspecialchars($order['code']); ?></div>
  </div>
  <hr>
  <table style="width:100%; font-size:13px">
    <tbody>
      <?php foreach ($items as $r): ?>
      <tr>
        <td><?php echo (float)$r['qty']; ?> x <?php echo htmlspecialchars($r['name']); ?></td>
        <td class="right">$ <?php echo number_format($r['price_gross']*(float)$r['qty'],0,',','.'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <hr>
  <table style="width:100%; font-size:13px">
    <tr><td>Neto</td><td class="right">$ <?php echo number_format($net,0,',','.'); ?></td></tr>
    <tr><td>IVA</td><td class="right">$ <?php echo number_format($iva,0,',','.'); ?></td></tr>
    <tr><td><b>Total</b></td><td class="right"><b>$ <?php echo number_format($gross,0,',','.'); ?></b></td></tr>
  </table>
  <hr>
  <div class="center small">¡Gracias por su compra!</div>
  <div class="center noprint" style="margin-top:10px">
      <a href="ticket_pdf.php?order_id=<?php echo (int)$order['id']; ?>">
    <button>Descargar PDF</button>
  </a>
    <button onclick="window.print()">Imprimir</button>
    <a href="cash.php"><button>Volver a caja</button></a>
  </div>
</div>
</body>
</html>
