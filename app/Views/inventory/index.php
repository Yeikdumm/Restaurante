<?php
// Vista principal del módulo de Inventario
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3>Inventario</h3>
  <div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalItem">➕ Nuevo insumo</button>
    <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalEntry">📦 Entrada</button>
    <button class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#modalOut">➖ Salida/Ajuste</button>
  </div>
</div>

<?php if (!empty($_SESSION['ok'])): ?>
  <div class="alert alert-success"><?php echo $_SESSION['ok']; unset($_SESSION['ok']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Insumo</th>
          <th>Unidad</th>
          <th class="text-end">Stock</th>
          <th class="text-end">Costo prom. (unidad)</th>
          <th class="text-end">Mínimo</th>
          <th>Estado</th>
          <th style="width:180px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr class="<?php echo $it['_low'] ? 'table-warning' : ''; ?>">
            <td><?php echo htmlspecialchars($it['name']); ?></td>
            <td><?php echo $it['unit'] === 'unit' ? 'unidad' : 'gramos'; ?></td>
            <td class="text-end"><?php echo (float)$it['stock_qty']; ?></td>
            <td class="text-end">$ <?php echo number_format($it['avg_cost_per_unit'], 2, ',', '.'); ?></td>
            <td class="text-end"><?php echo (float)$it['min_stock']; ?></td>
            <td>
              <?php if ($it['active']): ?>
                <span class="badge bg-success">Activo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalItem"
                data-id="<?php echo $it['id']; ?>"
                data-name="<?php echo htmlspecialchars($it['name']); ?>"
                data-unit="<?php echo $it['unit']; ?>"
                data-min="<?php echo (float)$it['min_stock']; ?>"
                data-active="<?php echo (int)$it['active']; ?>"
              >Editar</button>

              <button class="btn btn-outline-success btn-sm ms-1"
                data-bs-toggle="modal"
                data-bs-target="#modalEntry"
                data-itemid="<?php echo $it['id']; ?>"
                data-itemname="<?php echo htmlspecialchars($it['name']); ?>"
                data-unit="<?php echo $it['unit']; ?>"
              >Entrada</button>

              <button class="btn btn-outline-warning btn-sm ms-1"
                data-bs-toggle="modal"
                data-bs-target="#modalOut"
                data-itemid="<?php echo $it['id']; ?>"
                data-itemname="<?php echo htmlspecialchars($it['name']); ?>"
                data-unit="<?php echo $it['unit']; ?>"
              >Salida/Ajuste</button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" class="text-center text-muted">Sin insumos aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Crear/Editar Insumo -->
<div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="inventory.php?action=store">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="id" id="item-id">
      <div class="modal-header">
        <h5 class="modal-title" id="item-title">Nuevo insumo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" name="name" id="item-name" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Unidad</label>
          <select class="form-select" name="unit" id="item-unit">
            <option value="g">Gramos (g)</option>
            <option value="unit">Unidades</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Stock mínimo</label>
          <input type="number" step="0.001" class="form-control" name="min_stock" id="item-min" value="0">
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="active" id="item-active" checked>
          <label class="form-check-label" for="item-active">Activo</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Entrada de inventario -->
<div class="modal fade" id="modalEntry" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="inventory.php?action=add">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="item_id" id="entry-item-id">
      <div class="modal-header">
        <h5 class="modal-title">Registrar entrada</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Insumo</label>
          <input type="text" class="form-control" id="entry-item-name" disabled>
        </div>
        <div class="mb-2">
          <label class="form-label">Cantidad</label>
          <div class="input-group">
            <input type="number" step="0.001" min="0.001" class="form-control" name="qty_in" required>
            <span class="input-group-text" id="entry-unit">g</span>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Costo total ($)</label>
          <input type="number" step="0.01" min="0" class="form-control" name="cost_total" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Nota</label>
          <input type="text" class="form-control" name="note" placeholder="Compra, ajuste, etc.">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" type="submit">Registrar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Salida / Ajuste -->
<div class="modal fade" id="modalOut" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="inventory.php?action=out">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="item_id" id="out-item-id">
      <div class="modal-header">
        <h5 class="modal-title">Salida / Ajuste</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Insumo</label>
          <input type="text" class="form-control" id="out-item-name" disabled>
        </div>
        <div class="mb-2">
          <label class="form-label">Operación</label>
          <select class="form-select" name="mode" id="out-mode">
            <option value="out">Salida (merma/pérdida)</option>
            <option value="adjust">Ajuste</option>
          </select>
        </div>
        <div class="mb-2" id="adjust-sign-wrap" style="display:none;">
          <label class="form-label">Tipo de ajuste</label>
          <select class="form-select" name="sign" id="adjust-sign">
            <option value="minus">Disminuir</option>
            <option value="plus">Aumentar</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Cantidad</label>
          <div class="input-group">
            <input type="number" step="0.001" min="0.001" class="form-control" name="qty" required>
            <span class="input-group-text" id="out-unit">g</span>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Motivo / Nota</label>
          <input type="text" class="form-control" name="note" placeholder="Merma, conteo, ajuste físico...">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal Crear/Editar
  var modalItem = document.getElementById('modalItem');
  modalItem.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const id = btn?.getAttribute('data-id') || '';
    document.getElementById('item-id').value = id;
    document.getElementById('item-title').textContent = id ? 'Editar insumo' : 'Nuevo insumo';
    document.getElementById('item-name').value = btn?.getAttribute('data-name') || '';
    document.getElementById('item-unit').value = btn?.getAttribute('data-unit') || 'g';
    document.getElementById('item-min').value = btn?.getAttribute('data-min') || '0';
    document.getElementById('item-active').checked = (btn?.getAttribute('data-active') || '1') === '1';
  });

  // Modal Entrada
  var modalEntry = document.getElementById('modalEntry');
  modalEntry.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('entry-item-id').value = btn?.getAttribute('data-itemid') || '';
    document.getElementById('entry-item-name').value = btn?.getAttribute('data-itemname') || '';
    document.getElementById('entry-unit').innerText = (btn?.getAttribute('data-unit') === 'unit') ? 'unidad' : 'g';
  });

  // Modal Salida/Ajuste
  var modalOut = document.getElementById('modalOut');
  modalOut.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('out-item-id').value = btn?.getAttribute('data-itemid') || '';
    document.getElementById('out-item-name').value = btn?.getAttribute('data-itemname') || '';
    document.getElementById('out-unit').innerText = (btn?.getAttribute('data-unit') === 'unit') ? 'unidad' : 'g';
    document.getElementById('out-mode').value = 'out';
    document.getElementById('adjust-sign-wrap').style.display = 'none';
  });

  document.getElementById('out-mode').addEventListener('change', function() {
    document.getElementById('adjust-sign-wrap').style.display = (this.value === 'adjust') ? '' : 'none';
  });
});
</script>
