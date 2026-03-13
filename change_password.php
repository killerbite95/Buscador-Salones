<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

setSecurityHeaders();
requireLogin();

$msg   = '';
$error = '';

if (!isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();

    $current = trim($_POST['current'] ?? '');
    $new     = trim($_POST['new'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($new !== $confirm) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif (strlen($new) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password'])) {
            $error = 'La contraseña actual no es correcta.';
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $msg = 'Contraseña actualizada correctamente.';
        }
    }
}
?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cambiar contraseña · <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="images/logo-sp.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container py-5" style="max-width:480px">

  <div class="text-center mb-4 animate-in">
    <a href="index.php">
      <img src="images/logo-sp.png" alt="Sportium" height="40">
    </a>
  </div>

  <div class="card card-dark rounded-4 p-4 animate-in" style="animation-delay:.05s">
    <h5 class="text-white mb-1">Cambiar contraseña</h5>
    <p class="text-secondary small mb-4"><?= htmlspecialchars(currentUsername()) ?></p>

    <?php if (isAdmin()): ?>
      <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.85rem">
        <strong>Admin principal</strong> — La contraseña se gestiona directamente en <code>config.php</code>.
      </div>
      <a href="index.php" class="btn btn-outline-secondary">Volver</a>
    <?php else: ?>

    <?php if ($msg): ?>
      <div class="alert alert-success py-2 px-3" style="font-size:.85rem"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 px-3" style="font-size:.85rem"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= csrfField() ?>
      <div class="mb-3">
        <label for="current" class="form-label text-secondary small">Contraseña actual</label>
        <input type="password" name="current" id="current" class="form-control bg-dark border-secondary text-white" required>
      </div>
      <div class="mb-3">
        <label for="new" class="form-label text-secondary small">Nueva contraseña</label>
        <input type="password" name="new" id="new" class="form-control bg-dark border-secondary text-white" minlength="6" required>
      </div>
      <div class="mb-3">
        <label for="confirm" class="form-label text-secondary small">Confirmar nueva contraseña</label>
        <input type="password" name="confirm" id="confirm" class="form-control bg-dark border-secondary text-white" minlength="6" required>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sportium flex-fill">Cambiar contraseña</button>
        <a href="index.php" class="btn btn-outline-secondary">Volver</a>
      </div>
    </form>

    <?php endif; ?>
  </div>

</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
