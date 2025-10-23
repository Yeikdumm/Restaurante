<?php
// Variables disponibles:
// $products (todos), $editId, $editProduct (solo si ?edit=ID), $ingredients (del edit), $items (insumos activos)
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h3>Productos</h3>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduct">➕ Nuevo producto</button>
</div>

<?php if (!empty($_SESSION['ok'])): ?>
  <div class="alert alert-success"><?php echo $_SESSION['ok']; unset($_SESSION['ok']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<!-- Lista compacta -->
<div class="card shadow-sm mb-3">
  <div class="card-body table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>SKU</th>
          <th class="text-end">Precio (bruto)</th>
          <th class="text-end">Costo receta</th>
          <th class="text-end">Utilidad</th>
          <th>Estado</th>
          <th style="width:200px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['sku'] ?? '—'); ?></td>
            <td class="text-end">$ <?php echo number_format($p['public_price_gross'], 0, ',', '.'); ?></td>
            <td class="text-end">$ <?php echo number_format($p['calc_cost'], 0, ',', '.'); ?></td>
            <td class="text-end">
              <span class="badge <?php echo ($p['margin']>=0?'bg-success':'bg-danger'); ?>">
                $ <?php echo number_format($p['margin'], 0, ',', '.'); ?>
              </span>
            </td>
            <td><?php echo $p['active'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'; ?></td>
            <td>
              <a class="btn btn-outline-primary btn-sm" href="products.php?edit=<?php echo $p['id']; ?>">Editar</a>
              <form class="d-inline" method="post" action="products.php?action=delete" onsubmit="return confirm('¿Eliminar producto? Esta acción es permanente.');">
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <button class="btn btn-outline-danger btn-sm">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" class="text-center text-muted">Aún no hay productos</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Editor expandido SOLO cuando se elige Editar -->
<?php if ($editProduct): ?>
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between flex-wrap">
        <h5 class="mb-3">Editar: <?php echo htmlspecialchars($editProduct['name']); ?></h5>
        <a class="btn btn-outline-secondary btn-sm" href="products.php">Cerrar edición</a>
      </div>

      <div class="row g-3">
        <div class="col-lg-6">
          <h6>Ingredientes</h6>
          <form class="row gy-2 gx-2 align-items-end" method="post" action="products.php?action=adding">
            <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
            <div class="col-7">
              <label class="form-label">Insumo</label>
              <select class="form-select" name="item_id" required>
                <option value="">Seleccione…</option>
                <?php foreach ($items as $i): ?>
                  <option value="<?php echo $i['id']; ?>">
                    <?php echo htmlspecialchars($i['name']); ?> (<?php echo $i['unit']==='unit'?'unidad':'g'; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-3">
              <label class="form-label">Cantidad</label>
              <input type="number" step="0.001" min="0.001" name="qty_required" class="form-control" required>
            </div>
            <div class="col-2">
              <button class="btn btn-success w-100">Agregar</button>
            </div>
          </form>

          <table class="table table-sm mt-3">
            <thead><tr><th>Insumo</th><th class="text-end">Cantidad</th><th style="width:110px;"></th></tr></thead>
            <tbody>
              <?php foreach ($ingredients as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['name']); ?> (<?php echo $r['unit']==='unit'?'unidad':'g'; ?>)</td>
                  <td class="text-end"><?php echo (float)$r['qty_required']; ?></td>
                  <td>
                    <form method="post" action="products.php?action=removing" onsubmit="return confirm('¿Eliminar ingrediente?');">
                      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                      <input type="hidden" name="product_id" value="<?php echo $editProduct['id']; ?>">
                      <input type="hidden" name="item_id" value="<?php echo $r['item_id']; ?>">
                      <button class="btn btn-outline-danger btn-sm">Quitar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($ingredients)): ?>
                <tr><td colspan="3" class="text-muted text-center">Sin ingredientes aún</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="col-lg-6">
          <h6>Datos del producto</h6>
          <form class="row gy-2" method="post" action="products.php?action=store">
            <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">

            <div class="col-12">
              <label class="form-label">Nombre</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editProduct['name']); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">SKU</label>
              <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($editProduct['sku'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Precio público (bruto)</label>
              <input type="number" step="0.01" min="0" name="public_price_gross" class="form-control" value="<?php echo (float)$editProduct['public_price_gross']; ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">IVA (tasa)</label>
              <input type="number" step="0.001" min="0" max="1" name="tax_rate" class="form-control" value="<?php echo (float)$editProduct['tax_rate']; ?>" required>
              <small class="text-muted">Ej: 0.19</small>
            </div>
            <div class="col-12 form-check mt-2">
              <input type="checkbox" class="form-check-input" id="act<?php echo $editProduct['id']; ?>" name="active" <?php echo $editProduct['active'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="act<?php echo $editProduct['id']; ?>">Activo</label>
            </div>
            <div class="col-12">
              <button class="btn btn-primary">Guardar cambios</button>
              <a class="btn btn-outline-secondary" href="products.php">Cancelar</a>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
<?php endif; ?>

<!-- Modal Nuevo producto -->
<div class="modal fade" id="modalProduct" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="products.php?action=store">
      <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
      <div class="modal-header">
        <h5 class="modal-title">Nuevo producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">SKU (opcional)</label>
          <input type="text" name="sku" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Precio público (bruto)</label>
          <input type="number" step="0.01" min="0" name="public_price_gross" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">IVA (tasa)</label>
          <input type="number" step="0.001" min="0" max="1" name="tax_rate" value="0.19" class="form-control" required>
          <small class="text-muted">Ej: 0.19</small>
        </div>
        <div class="form-check mt-2">
          <input type="checkbox" class="form-check-input" id="pnew-active" name="active" checked>
          <label class="form-check-label" for="pnew-active">Activo</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>
