<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start(); ?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar - Crafting Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="bg-light">
    <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
      <div class="card shadow-sm" style="max-width:420px; width:100%;">
        <div class="card-body">
          <h4 class="mb-3">Ingresar</h4>
          <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
          <?php endif; ?>
          <form method="post" action="login.php">
            <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
              <label class="form-label">Correo</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar</button>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>