<?php
require_once 'auth.php';
require_once 'security.php';

setSecurityHeaders();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$ip      = clientIp();
$blocked = loginCheckRateLimit($ip);
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    if ($blocked) {
        $error = 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.';
    } else {
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['pass'] ?? '';
        if (doLogin($user, $pass)) {
            header('Location: index.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
        if (loginCheckRateLimit($ip)) {
            $error = 'Demasiados intentos fallidos. Espera 15 minutos.';
        }
    }
}
?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin · <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="images/logo-sp.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Page-specific: login */
    body {
      background-image: radial-gradient(ellipse at 50% 0%, rgba(220,38,38,.07) 0%, transparent 55%);
    }
    .card-login {
      background: #1e293b; border: 1px solid #334155; border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(0,0,0,.25), 0 0 0 1px rgba(51,65,85,.5);
    }
    .login-wrapper { animation: fadeInUp .4s ease-out both; }
    .pwd-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: #94a3b8; cursor: pointer;
      padding: 2px; line-height: 1; transition: color .15s;
    }
    .pwd-toggle:hover { color: #e2e8f0; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
  <div class="login-wrapper px-3" style="width:100%;max-width:400px">

    <div class="text-center mb-4">
      <img src="images/logo-sp.png" alt="Sportium" height="56" class="mb-3">
      <h5 class="fw-bold text-white mb-1" style="font-size:1.25rem">Buscador de Salones</h5>
      <p class="text-secondary mb-0" style="font-size:.85rem">Inicia sesión para continuar</p>
    </div>

    <div class="card-login p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger rounded-3 py-2 d-flex align-items-center gap-2 small mb-3" role="alert">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0" aria-hidden="true">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>>
          </svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($blocked && !$error): ?>
        <div class="alert alert-warning rounded-3 py-2 d-flex align-items-center gap-2 small mb-3" role="alert">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0" aria-hidden="true">
            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
          </svg>
          IP bloqueada temporalmente. Espera 15 minutos.
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off" id="loginForm">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label text-secondary small mb-1">Usuario</label>
          <input type="text" name="user" class="form-control bg-dark border-secondary text-white"
                 placeholder="Tu nombre de usuario"
                 autofocus autocomplete="username" required>
        </div>
        <div class="mb-4">
          <label class="form-label text-secondary small mb-1">Contraseña</label>
          <div class="position-relative">
            <input type="password" name="pass" id="passInput" class="form-control bg-dark border-secondary text-white pe-5"
                   autocomplete="current-password" required>
            <button type="button" class="pwd-toggle" id="pwdToggle" tabindex="-1" aria-label="Mostrar contraseña">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" id="loginBtn" class="btn btn-sportium w-100 fw-semibold py-2">
          Entrar
        </button>
      </form>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>

  </div>

<script>
document.getElementById('pwdToggle').addEventListener('click', function() {
  const inp = document.getElementById('passInput');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  this.style.color = inp.type === 'text' ? '#dc2626' : '';
});
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.innerHTML = '<span class="spinner-sm"></span> Entrando…';
  btn.disabled = true;
});
</script>
</body>
</html>
