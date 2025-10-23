<?php /* $products, $orders */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Pedidos</h3>
  <a class="btn btn-outline-secondary" href="kitchen.php">👩‍🍳 Ver cocina</a>
</div>

<?php if (!empty($_SESSION['ok'])): ?>
  <div class="alert alert-success"><?php echo $_SESSION['ok']; unset($_SESSION['ok']); ?></div>
<?php endif; if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h6 class="mb-3">Nuevo pedido</h6>
    <form id="order-form" method="post" action="orders.php?action=create" onsubmit="return submitOrder();">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="items_json" id="items_json">

     <div class="row g-2 mb-2">
  <div class="col-md-4">
    <label class="form-label">Tipo</label>
    <select class="form-select" name="type" id="type">
      <option value="table">Mesa</option>
      <option value="delivery">Domicilio</option>
    </select>
  </div>
  <div class="col-md-4" id="wrap-table">
    <label class="form-label">Mesa #</label>
    <input type="text" class="form-control" name="table_no">
  </div>
  <div class="col-md-8 d-none" id="wrap-customer">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Cliente</label>
        <input type="text" class="form-control" name="customer_name" placeholder="Nombre">
      </div>
      <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" class="form-control" name="customer_phone" placeholder="Tel">
      </div>
      <div class="col-md-4">
        <label class="form-label">Dirección</label>
        <input type="text" class="form-control" name="customer_address" placeholder="Dirección">
      </div>
    </div>
  </div>
</div>


      <div class="row g-2 align-items-end">
        <div class="col-md-8">
          <label class="form-label">Producto</label>
          <select class="form-select" id="prod">
            <option value="">Seleccione…</option>
            <?php foreach ($products as $p): ?>
              <option value="<?php echo $p['id']; ?>" data-price="<?php echo (float)$p['public_price_gross']; ?>">
                <?php echo htmlspecialchars($p['name']); ?> — $ <?php echo number_format($p['public_price_gross'],0,',','.'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Cantidad</label>
          <input type="number" class="form-control" id="qty" value="1" min="1" step="1">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" type="button" onclick="addItem()">Agregar</button>
        </div>
      </div>

      <table class="table table-sm mt-3" id="items">
        <thead><tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th><th style="width:60px;"></th></tr></thead>
        <tbody></tbody>
        <tfoot><tr><th colspan="3" class="text-end">Total:</th><th class="text-end" id="total">$ 0</th><th></th></tr></tfoot>
      </table>

      <div class="text-end">
        <button class="btn btn-primary">Enviar a cocina</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body table-responsive">
    <h6>Pedidos de hoy</h6>
    <table class="table table-sm">
      <thead><tr><th>#</th><th>Tipo</th><th>Detalle</th><th>Estado</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?php echo htmlspecialchars($o['code']); ?></td>
          <td><?php echo $o['type']==='table'?'Mesa':'Domicilio'; ?></td>
          <td>
            <?php echo $o['type']==='table' ? ('Mesa: '.htmlspecialchars($o['table_no'])) : (htmlspecialchars($o['customer_name']).' ('.htmlspecialchars($o['customer_phone']).')'); ?>
          </td>
          <td>
            <?php
              $badge = ['pending'=>'secondary','in_kitchen'=>'warning','ready'=>'success','paid'=>'primary'][$o['status']] ?? 'secondary';
              echo '<span class="badge bg-'.$badge.'">'.htmlspecialchars($o['status']).'</span>';
            ?>
          </td>
          <td><?php echo htmlspecialchars($o['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
        <tr><td colspan="5" class="text-muted text-center">Sin pedidos hoy</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
let cart = [];
function addItem(){
  const sel = document.getElementById('prod');
  const pid = sel.value;
  if(!pid) return;
  const name = sel.options[sel.selectedIndex].text.split(' — ')[0];
  const price = parseFloat(sel.options[sel.selectedIndex].dataset.price || '0');
  const qty = parseFloat(document.getElementById('qty').value || '1');
  if(qty<=0) return;

  cart.push({product_id: parseInt(pid), name, price, qty});
  render();
}
function removeItem(i){ cart.splice(i,1); render(); }
function render(){
  const tb = document.querySelector('#items tbody');
  tb.innerHTML = '';
  let total = 0;
  cart.forEach((it,i)=>{
    const sub = it.qty * it.price;
    total += sub;
    tb.innerHTML += `<tr>
      <td>${escapeHtml(it.name)}</td>
      <td class="text-end">${it.qty}</td>
      <td class="text-end">$ ${formatNum(it.price)}</td>
      <td class="text-end">$ ${formatNum(sub)}</td>
      <td><button class="btn btn-outline-danger btn-sm" onclick="removeItem(${i})">X</button></td>
    </tr>`;
  });
  document.getElementById('total').innerText = '$ ' + formatNum(total);
}
function submitOrder(){
  if(cart.length===0){ alert('Agrega productos'); return false; }
  document.getElementById('items_json').value = JSON.stringify(cart.map(x=>({product_id:x.product_id, qty:x.qty})));
  return true;
}
function formatNum(n){ return (n||0).toLocaleString('es-CO', {maximumFractionDigits:0}); }
function escapeHtml(s){ return s.replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

document.getElementById('type').addEventListener('change', function(){
  const isDel = this.value==='delivery';
  document.getElementById('wrap-customer').classList.toggle('d-none', !isDel);
  document.getElementById('wrap-table').classList.toggle('d-none', isDel);
});
</script>
