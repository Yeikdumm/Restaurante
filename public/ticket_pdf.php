<?php
// public/ticket_pdf.php  — versión compatible con tu stack (PDO + helpers)
// Genera PDF con FPDF si está instalado; si no, muestra HTML imprimible.

$DEBUG = (getenv('APP_DEBUG') === 'true');

if ($DEBUG) { ini_set('display_errors', '1'); error_reporting(E_ALL); }

require __DIR__.'/../bootstrap/autoload.php';
require __DIR__.'/../bootstrap/helpers.php';
require __DIR__.'/../config/database.php';
require_login();

use DB;

try {
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    if ($orderId <= 0) {
        http_response_code(400);
        exit('Pedido inválido.');
    }

    $pdo = DB::conn();

    // Empresa
    $cs = $pdo->query("SELECT * FROM company_settings LIMIT 1")->fetch() ?: [];
    $empresa = $cs['company_name'] ?? 'Mi Heladeria';
    $nit     = $cs['company_nit']  ?? 'NIT 0';

    // Pedido
    $st = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $st->execute([$orderId]);
    $order = $st->fetch();
    if (!$order) {
        http_response_code(404);
        exit('Pedido no existe.');
    }

    // Items
    $it = $pdo->prepare("
      SELECT oi.qty, oi.price_gross, oi.tax_rate, p.name
      FROM order_items oi
      JOIN products p ON p.id=oi.product_id
      WHERE oi.order_id=?
    ");
    $it->execute([$orderId]);
    $items = $it->fetchAll() ?: [];

    // Totales
    $gross = $net = $iva = 0.0;
    foreach ($items as $r) {
        $sub = (float)$r['qty'] * (float)$r['price_gross'];
        $gross += $sub;
        $n = ($r['tax_rate'] > 0) ? ($sub / (1 + (float)$r['tax_rate'])) : $sub;
        $i = $sub - $n;
        $net += $n; $iva += $i;
    }

    // Intento de PDF (solo si fpdf.php y fuentes existen)
    $fpdfPath = __DIR__ . '/../app/Libraries/fpdf.php';
    $fontDir  = __DIR__ . '/../app/Libraries/font';

    if (file_exists($fpdfPath) && is_dir($fontDir) && file_exists($fontDir.'/helveticab.php')) {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', rtrim($fontDir, '/').'/');
        }
        if (!class_exists('FPDF')) {
            require_once $fpdfPath;
        }

        // --- Generar PDF ticket 80mm ---
        $pdf = new \FPDF('P', 'mm', [80, 200]);
        $pdf->AddPage();
        $pdf->SetMargins(5, 5, 5);

        // Encabezado
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 6, utf8_decode($empresa), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, utf8_decode($nit), 0, 1, 'C');
        $pdf->Cell(0, 5, date('Y-m-d H:i'), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode('Pedido: #'.$order['code']), 0, 1, 'C');
        $pdf->Ln(1);
        $pdf->Cell(0, 0, '', 'T', 1);

        // Items
        $pdf->Ln(1);
        $pdf->SetFont('Helvetica', '', 10);
        foreach ($items as $r) {
            $nombre = utf8_decode($r['name']);
            $qty = (float)$r['qty'];
            $sub = $qty * (float)$r['price_gross'];
            $pdf->MultiCell(0, 5, "{$qty} x {$nombre}", 0, 'L');
            $pdf->Cell(0, 5, '$ '.number_format($sub, 0, ',', '.'), 0, 1, 'R');
        }

        // Totales
        $pdf->Ln(1);
        $pdf->Cell(0, 0, '', 'T', 1);
        $pdf->Ln(1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(25, 5, 'Neto', 0, 0, 'L');   $pdf->Cell(0, 5, '$ '.number_format($net, 0, ',', '.'), 0, 1, 'R');
        $pdf->Cell(25, 5, 'IVA', 0, 0, 'L');    $pdf->Cell(0, 5, '$ '.number_format($iva, 0, ',', '.'), 0, 1, 'R');
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(25, 6, 'TOTAL', 0, 0, 'L');  $pdf->Cell(0, 6, '$ '.number_format($gross, 0, ',', '.'), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, utf8_decode('¡Gracias por su compra!'), 0, 1, 'C');

        $nombre = 'ticket_'.$order['code'].'.pdf';
        $pdf->Output('I', $nombre);
        exit;
    }

} catch (Throwable $e) {
    if ($DEBUG) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Excepción en ticket_pdf.php\n\n".$e;
        exit;
    } else {
        http_response_code(500);
        exit('Error interno.');
    }
}

// ---------- Fallback HTML imprimible (sin FPDF) ----------
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
  <div class="center small">Nota: instala FPDF en <code>app/Libraries/fpdf.php</code> y fuentes en <code>app/Libraries/font</code> para descargar PDF automático.</div>
  <div class="center noprint" style="margin-top:10px">
    <button onclick="window.print()">Imprimir</button>
    <a href="cash.php"><button>Volver a caja</button></a>
  </div>
</div>
</body>
</html>
