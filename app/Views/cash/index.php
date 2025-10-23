<?php /* Variables esperadas: $pending, $paidRows, $totals */ ?>

<?php if (!empty($_SESSION['ok'])): ?>
  <div class="alert alert-success"><?php echo $_SESSION['ok']; unset($_SESSION['ok']); ?></div>
<?php endif; if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Caja</h3>
  <a class="btn btn-outline-secondary" href="orders.php">Pedidos</a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6>Pedidos listos para cobrar</h6>
        <table class="table table-sm align-middle">
          <thead><tr><th>#</th><th>Tipo</th><th>Detalle</th><th class="text-end">Total</th><th style="width:110px;"></th></tr></thead>
          <tbody>
          <?php foreach ($pending as $o): ?>
            <tr>
              <td><?php echo htmlspecialchars($o['code']); ?></td>
              <td><?php echo $o['type']==='table'?'Mesa':'Domicilio'; ?></td>
              <td>
                <?php if ($o['type']==='table'): ?>
                  Mesa: <b><?php echo htmlspecialchars($o['table_no']); ?></b>
                <?php else: ?>
                  <b><?php echo htmlspecialchars($o['customer_name']); ?></b>
                  (<?php echo htmlspecialchars($o['customer_phone']); ?>)
                <?php endif; ?>
              </td>
              <td class="text-end">$ <?php echo number_format($o['_gross'],0,',','.'); ?></td>
              <td>
                <button class="btn btn-primary btn-sm" onclick="openCharge(<?php echo (int)$o['id']; ?>, <?php echo (float)$o['_gross']; ?>, '<?php echo htmlspecialchars($o['code']); ?>')">Cobrar</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($pending)): ?>
            <tr><td colspan="5" class="text-muted text-center">No hay pedidos listos para cobrar</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6>Pagos de hoy</h6>
        <table class="table table-sm">
          <thead><tr><th>#</th><th>Método</th><th class="text-end">Monto</th><th>Hora</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($paidRows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['code']); ?></td>
              <td><?php echo htmlspecialchars(ucfirst($r['payment_method'])); ?></td>
              <td class="text-end">$ <?php echo number_format($r['amount'],0,',','.'); ?></td>
              <td><?php echo htmlspecialchars(substr($r['created_at'],11,5)); ?></td>
                <td>
    <a class="btn btn-sm btn-outline-secondary" href="ticket.php?order_id=<?php echo (int)$r['id']; ?>" target="_blank">Ticket</a>
    <a class="btn btn-sm btn-outline-primary" href="ticket_pdf.php?order_id=<?php echo (int)$r['id']; ?>" target="_blank">PDF</a>
  </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($paidRows)): ?>
            <tr><td colspan="4" class="text-muted text-center">Aún no hay pagos hoy</td></tr>
          <?php endif; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="2" class="text-end">Efectivo</th><th class="text-end">$ <?php echo number_format($totals['efectivo'] ?? 0,0,',','.'); ?></th><th></th></tr>
            <tr><th colspan="2" class="text-end">Tarjeta</th><th class="text-end">$ <?php echo number_format($totals['tarjeta'] ?? 0,0,',','.'); ?></th><th></th></tr>
            <tr><th colspan="2" class="text-end">Transferencia</th><th class="text-end">$ <?php echo number_format($totals['transferencia'] ?? 0,0,',','.'); ?></th><th></th></tr>
            <tr><th colspan="2" class="text-end">TOTAL</th><th class="text-end">$ <?php echo number_format($totals['total'] ?? 0,0,',','.'); ?></th><th></th></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal de cobro -->
<div class="modal fade" id="modalCharge" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="cash.php?action=charge" onsubmit="return validateCharge();">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="order_id" id="order_id">
      <div class="modal-header">
        <h5 class="modal-title">Cobrar pedido <span id="code"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Total a pagar</label>
          <input type="text" class="form-control" id="total_show" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">Método de pago</label>
          <select class="form-select" name="payment_method" id="method" onchange="toggleAmount()">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
          </select>
        </div>
        <div class="mb-2" id="wrap-amount">
          <label class="form-label">Recibido (efectivo)</label>
          <input type="number" step="0.01" min="0" class="form-control" name="amount_paid" id="amount_paid" oninput="updateChange()">
        </div>
        <div class="alert alert-secondary py-2"><b>Cambio:</b> <span id="change">$ 0</span></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">Confirmar cobro</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentTotal = 0;
function openCharge(orderId, total, code){
  currentTotal = parseFloat(total||0);
  document.getElementById('order_id').value = orderId;
  document.getElementById('total_show').value = '$ ' + currentTotal.toLocaleString('es-CO');
  document.getElementById('code').innerText = '#'+code;
  document.getElementById('method').value = 'efectivo';
  document.getElementById('amount_paid').value = '';
  document.getElementById('change').innerText = '$ 0';
  document.getElementById('wrap-amount').classList.remove('d-none');
  const modal = new bootstrap.Modal(document.getElementById('modalCharge'));
  modal.show();
}
function toggleAmount(){
  const m = document.getElementById('method').value;
  const wrap = document.getElementById('wrap-amount');
  if(m === 'efectivo'){ wrap.classList.remove('d-none'); }
  else { wrap.classList.add('d-none'); document.getElementById('change').innerText = '$ 0'; }
}
function updateChange(){
  const val = parseFloat(document.getElementById('amount_paid').value||0);
  const ch = Math.max(0, val - currentTotal);
  document.getElementById('change').innerText = '$ ' + ch.toLocaleString('es-CO');
}
function validateCharge(){
  const m = document.getElementById('method').value;
  if(m==='efectivo'){
    const val = parseFloat(document.getElementById('amount_paid').value||0);
    if(val < currentTotal){ alert('El efectivo recibido es menor al total.'); return false; }
  }
  return true;
}
</script>
