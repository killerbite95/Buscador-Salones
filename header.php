<?php
/**
 * Shared header/navbar partial.
 *
 * Expected variables before include:
 *   $headerTitle    — Main title (string)
 *   $headerSubtitle — Subtitle (string|HTML, rendered raw)
 *   $headerHref     — Link for logo click (string, default 'index.php')
 *   $headerMaxWidth — Container max-width (string, default '880px')
 *   $headerActions  — Extra HTML rendered before the user dropdown (string, optional)
 */

$headerHref     = $headerHref     ?? 'index.php';
$headerMaxWidth = $headerMaxWidth ?? '880px';
$headerActions  = $headerActions  ?? '';
?>
<header class="app-header">
  <div class="container d-flex align-items-center justify-content-between py-3" style="max-width:<?= $headerMaxWidth ?>">

    <a href="<?= htmlspecialchars($headerHref) ?>" class="d-flex align-items-center gap-3 text-decoration-none">
      <img src="images/logo-sp.png" alt="Logo Buscador" height="36">
      <div class="d-none d-sm-block lh-sm">
        <div class="fw-semibold text-white" style="font-size:.95rem"><?= htmlspecialchars($headerTitle) ?></div>
        <div class="text-secondary" style="font-size:.72rem"><?= $headerSubtitle ?></div>
      </div>
    </a>

    <div class="d-flex align-items-center gap-2">
      <?= $headerActions ?>

      <div class="dropdown">
        <button class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle"
                style="background:#0f172a;border:1px solid #334155;color:#e2e8f0;font-size:.8rem"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                style="width:24px;height:24px;background:#dc2626;font-size:.65rem;flex-shrink:0">
            <?= strtoupper(substr(currentUsername(), 0, 1)) ?>
          </span>
          <span class="d-none d-sm-inline"><?= htmlspecialchars(currentUsername()) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="background:#1e293b;border:1px solid #334155;min-width:160px">
          <li>
            <span class="dropdown-item-text text-secondary" style="font-size:.75rem">
              Conectado como<br>
              <strong class="text-white"><?= htmlspecialchars(currentUsername()) ?></strong>
            </span>
          </li>
          <li><hr class="dropdown-divider border-secondary"></li>
          <li><a class="dropdown-item" href="change_password.php" style="font-size:.85rem">Cambiar contraseña</a></li>
          <li><hr class="dropdown-divider border-secondary"></li>
          <li><a class="dropdown-item text-danger" href="logout.php" style="font-size:.85rem">Cerrar sesión</a></li>
        </ul>
      </div>
    </div>

  </div>
</header>
