<?php /* $orders con _items */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Cocina</h3>
  <a class="btn btn-outline-secondary" href="orders.php">🧾 Ver pedidos</a>
</div>

<div class="row g-3">
<?php foreach ($orders as $o): ?>
  <div class="col-md-4">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between">
          <h5 class="mb-1">#<?php echo htmlspecialchars($o['code']); ?></h5>
          <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($o['status']); ?></span>
        </div>
        <div class="text-muted mb-2">
          <?php if ($o['type']==='table'): ?>
            Mesa: <b><?php echo htmlspecialchars($o['table_no']); ?></b>
          <?php else: ?>
            Domicilio: <b><?php echo htmlspecialchars($o['customer_name']); ?></b> (<?php echo htmlspecialchars($o['customer_phone']); ?>)
          <?php endif; ?>
        </div>
        <ul class="mb-3">
          <?php foreach ($o['_items'] as $it): ?>
            <li><?php echo (float)$it['qty']; ?> × <?php echo htmlspecialchars($it['name']); ?></li>
          <?php endforeach; ?>
        </ul>
        <form method="post" action="kitchen.php?action=ready" class="mt-auto">
          <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
          <button class="btn btn-success w-100">Marcar listo</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php if (empty($orders)): ?>
  <div class="col-12"><div class="alert alert-info">No hay pedidos en cocina ahora.</div></div>
<?php endif; ?>
</div>
