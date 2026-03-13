<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

setSecurityHeaders();
requireLogin();
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$total = (int) $pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$logs = $stmt->fetchAll();

$actionLabels = [
    'create' => ['Usuario creado',    'bg-success'],
    'edit'   => ['Usuario editado',   'bg-warning text-dark'],
    'delete' => ['Usuario eliminado', 'bg-danger'],
];
?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historial · <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="images/logo-sp.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php
$headerTitle    = 'Historial de Actividad';
$headerSubtitle = 'Registro de cambios en usuarios';
$headerHref     = 'admin.php';
$headerMaxWidth = '880px';

ob_start();
?>
  <a href="admin.php"
     class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
     style="font-size:.8rem"
     aria-label="Panel de administración">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
      </svg>
    <span class="d-none d-md-inline">Admin</span>
  </a>
<?php
$headerActions = ob_get_clean();
include __DIR__ . '/header.php';
?>

<main class="container py-4" style="max-width:880px">

  <div class="card-dark rounded-4 p-4 animate-in">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="text-secondary text-uppercase small mb-0" style="letter-spacing:.06em">
        Historial de actividad
        <span class="text-white fw-normal" style="letter-spacing:0">(<?= $total ?> registros)</span>
      </h6>
    </div>

    <?php if (empty($logs)): ?>
      <p class="text-secondary small fst-italic mb-0">No hay registros de actividad todavía.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-dark align-middle mb-0" style="font-size:.85rem">
          <thead>
            <tr class="text-secondary" style="font-size:.75rem">
              <th style="width:150px">Fecha</th>
              <th style="width:140px">Acción</th>
              <th>Usuario afectado</th>
              <th>Detalles</th>
              <th style="width:120px">Realizado por</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $i => $log):
              $label = $actionLabels[$log['action']] ?? [$log['action'], 'bg-secondary'];
            ?>
            <tr class="animate-in" style="animation-delay:<?= $i * 0.02 ?>s">
              <td class="text-secondary"><?= htmlspecialchars($log['created_at']) ?></td>
              <td><span class="badge <?= $label[1] ?>" style="font-size:.7rem"><?= $label[0] ?></span></td>
              <td class="fw-semibold"><?= htmlspecialchars($log['target_user']) ?></td>
              <td class="text-secondary"><?= htmlspecialchars($log['details']) ?: '—' ?></td>
              <td>
                <span class="d-inline-flex align-items-center gap-1">
                  <span class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                        style="width:20px;height:20px;background:#dc2626;font-size:.55rem;flex-shrink:0">
                    <?= strtoupper(substr($log['performed_by'], 0, 1)) ?>
                  </span>
                  <?= htmlspecialchars($log['performed_by']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($pages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">
          <?php for ($p = 1; $p <= $pages; $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>"
               style="background:<?= $p === $page ? '#dc2626' : '#0f172a' ?>;border-color:#334155;color:<?= $p === $page ? '#fff' : '#94a3b8' ?>">
              <?= $p ?>
            </a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</main>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
