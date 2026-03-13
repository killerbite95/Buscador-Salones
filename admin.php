<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

setSecurityHeaders();
requireLogin();
if (!canAccessAdmin()) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();
$totalSalones = (int) $pdo->query("SELECT COUNT(*) FROM salones")->fetchColumn();
$lastImport   = $pdo->query("SELECT * FROM imports ORDER BY id DESC LIMIT 1")->fetch();
$totalPlayers = (int) $pdo->query("SELECT COUNT(*) FROM pisignage_players")->fetchColumn();
$lastPiImport = $pdo->query("SELECT * FROM pisignage_imports ORDER BY id DESC LIMIT 1")->fetch();
$dbUsers      = isAdmin() ? $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll() : [];

$msg   = htmlspecialchars($_GET['msg']   ?? '');
$error = htmlspecialchars($_GET['error'] ?? '');

$permLabels = ['viewer' => 'Solo Lectura', 'salones' => 'Solo Salones', 'pisignage' => 'Solo PiSignage', 'both' => 'Ambos'];
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
    /* Page-specific: admin panel */
    .inner-card {
      background: #0f172a; border: 1px solid #334155; border-radius: .75rem;
      padding: 1rem 1.25rem; transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .inner-card:hover { border-color: #475569; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
    .dropzone {
      border: 2px dashed #475569; border-radius: .75rem; padding: 2.5rem 1rem;
      text-align: center; cursor: pointer; transition: border-color .2s, background .2s, box-shadow .2s;
    }
    .dropzone:hover { border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(71,85,105,.15); }
    .dropzone.drag { border-color: #dc2626; background: rgba(220,38,38,.06); box-shadow: 0 0 0 3px rgba(220,38,38,.15); }
    .stat-number { font-size: 2rem; font-weight: 700; line-height: 1.1; }
  </style>
</head>
<body>

<?php
$headerTitle    = 'Panel de Administración';
$headerSubtitle = 'Gestión de datos y usuarios';
$headerHref     = 'admin.php';
$headerMaxWidth = '820px';

ob_start();
?>
  <a href="index.php"
     class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
     style="font-size:.8rem"
     aria-label="Buscador de salas">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
    </svg>
    <span class="d-none d-md-inline">Buscador</span>
  </a>
<?php
$headerActions = ob_get_clean();
include __DIR__ . '/header.php';
?>

<main class="container py-4" style="max-width:820px">

  <?php if ($msg): ?>
    <div class="alert alert-success rounded-3"><?= $msg ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= $error ?></div>
  <?php endif; ?>

  <!-- Estado actual -->
  <div class="card-dark rounded-4 p-4 mb-4">
    <h6 class="text-secondary text-uppercase small mb-3" style="letter-spacing:.06em">Estado actual</h6>
    <div class="row g-3">
      <div class="col-sm-4">
        <div class="inner-card h-100">
          <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
              <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293z"/>
            </svg>
            Salones en BD
          </div>
          <div class="stat-number" style="color:#f1f5f9"><?= number_format($totalSalones) ?></div>
        </div>
      </div>
      <div class="col-sm-8">
        <div class="inner-card h-100">
          <div class="text-secondary small mb-1">Última importación · Salones</div>
          <?php if ($lastImport): ?>
            <div class="fw-semibold"><?= htmlspecialchars($lastImport['filename']) ?></div>
            <div class="text-secondary small mt-1">
              <?= number_format($lastImport['total_rows']) ?> salones
              · <?= $lastImport['imported_at'] ?>
            </div>
          <?php else: ?>
            <div class="text-secondary fst-italic">Sin importaciones previas.</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="inner-card h-100">
          <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
              <path d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4q0 1 .25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75Q6 13 6 12H2s-2 0-2-2zm1.398-.855a.76.76 0 0 0-.254.302A1.5 1.5 0 0 0 1 4.01V10c0 .325.078.502.145.602q.105.156.302.254a1.5 1.5 0 0 0 .538.143L2.01 11H14c.325 0 .502-.078.602-.145a.76.76 0 0 0 .254-.302 1.5 1.5 0 0 0 .143-.538L15 9.99V4c0-.325-.078-.502-.145-.602a.76.76 0 0 0-.302-.254A1.5 1.5 0 0 0 13.99 3H2c-.325 0-.502.078-.602.145"/>
            </svg>
            Players PiSignage en BD
          </div>
          <div class="stat-number" style="color:#f1f5f9"><?= number_format($totalPlayers) ?></div>
        </div>
      </div>
      <div class="col-sm-8">
        <div class="inner-card h-100">
          <div class="text-secondary small mb-1">Última importación · PiSignage</div>
          <?php if ($lastPiImport): ?>
            <div class="fw-semibold"><?= htmlspecialchars($lastPiImport['filename']) ?></div>
            <div class="text-secondary small mt-1">
              <?= number_format($lastPiImport['total_rows']) ?> players
              · <?= $lastPiImport['imported_at'] ?>
            </div>
          <?php else: ?>
            <div class="text-secondary fst-italic">Sin importaciones previas.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Subir CSV -->
  <?php if (canImportSalones()): ?>
  <div class="card-dark rounded-4 p-4">
    <h6 class="text-secondary text-uppercase small mb-1" style="letter-spacing:.06em">Cargar nuevo CSV</h6>
    <p class="text-secondary small mb-3">
      Al importar se reemplazarán <strong class="text-white">todos</strong> los datos anteriores por los del nuevo archivo.
      El CSV debe ser el export de Insight/Jira con la columna <code>Nombre</code>.
    </p>

    <form method="post" action="import.php" enctype="multipart/form-data" id="importForm">
      <?= csrfField() ?>
      <div id="dropzone" class="dropzone mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#94a3b8" viewBox="0 0 16 16" class="mb-2" aria-hidden="true">
          <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
          <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
        </svg>
        <p class="mb-1 text-secondary">Arrastra tu <strong class="text-white">salones.csv</strong> aquí o haz clic</p>
        <p class="small text-secondary mb-0">Se aceptan .csv y .txt</p>
        <input type="file" name="csv" id="csvFile" accept=".csv,.txt,text/csv,text/plain" class="d-none" required>
      </div>
      <div id="fileInfo" class="small text-secondary mb-3 d-none"></div>
      <button type="submit" class="btn btn-sportium px-4 fw-semibold" id="importBtn">
        Importar CSV
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Subir PlayersListMongo (PiSignage) -->
  <?php if (canImportPisignage()): ?>
  <div class="card-dark rounded-4 p-4 mt-4">
    <h6 class="text-secondary text-uppercase small mb-1" style="letter-spacing:.06em">Cargar PlayersListMongo (PiSignage)</h6>
    <p class="text-secondary small mb-3">
      Sube el <code>PlayersListMongo.csv</code> que recibes por correo. Se reemplazarán todos los players anteriores.
      Columnas esperadas: <code>name</code>, <code>myIpAddress</code>, <code>currentPlaylist</code>, <code>lastReported</code>.
    </p>
    <form method="post" action="import_players.php" enctype="multipart/form-data" id="importPiForm">
      <?= csrfField() ?>
      <div id="dropzonePi" class="dropzone mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#94a3b8" viewBox="0 0 16 16" class="mb-2" aria-hidden="true">
          <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
          <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
        </svg>
        <p class="mb-1 text-secondary">Arrastra tu <strong class="text-white">PlayersListMongo.csv</strong> aquí o haz clic</p>
        <p class="small text-secondary mb-0">Se aceptan .csv y .txt</p>
        <input type="file" name="csv" id="csvFilePi" accept=".csv,.txt,text/csv,text/plain" class="d-none" required>
      </div>
      <div id="fileInfoPi" class="small text-secondary mb-3 d-none"></div>
      <button type="submit" class="btn btn-sportium px-4 fw-semibold" id="importPiBtn">
        Importar PiSignage
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Usuarios -->
  <?php if (isAdmin()): ?>
  <div class="card-dark rounded-4 p-4 mt-4" id="usuarios">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="text-secondary text-uppercase small mb-0" style="letter-spacing:.06em">Usuarios</h6>
      <div class="d-flex gap-2">
        <a href="admin_history.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" style="font-size:.78rem">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.253.57-.583 1.098zM8.515 15h-.03a7 7 0 0 1-.887-.053l.124-.986a6 6 0 0 0 .762.04zm-1.928-.156q-.463-.105-.9-.264l.345-.936q.366.135.756.222zm-1.838-.63q-.393-.22-.754-.5l.602-.8q.3.227.636.417zm-1.487-1.18a7 7 0 0 1-.592-.716l.8-.6q.228.303.498.572zM2.07 11.217a7 7 0 0 1-.36-.756l.924-.383q.135.326.308.63zm-.615-1.668a7 7 0 0 1-.16-.827l.985-.17q.058.345.155.68zm-.223-1.798a7 7 0 0 1 .017-.851l.992.07q-.018.36.004.722zm.148-1.752a7 7 0 0 1 .163-.822l.969.238q-.1.397-.154.81zM1.82 4.32q.165-.395.38-.764l.86.505q-.178.307-.316.637zm.824-1.417q.261-.343.567-.652l.706.708a7 7 0 0 0-.47.54zm1.222-1.163q.097-.082.197-.16l.625.79a7 7 0 0 0-.394.322zM8 1a7 7 0 0 0-1.047.082l-.15-.983A8 8 0 0 1 8 0v1z"/>
            <path d="M8 3.5a.5.5 0 0 1 .5.5v4.125l2.182 1.272a.5.5 0 0 1-.504.866l-2.428-1.414A.5.5 0 0 1 7.5 8.5v-4a.5.5 0 0 1 .5-.5"/>
          </svg>
          Historial
        </a>
        <button class="btn btn-sm btn-sportium" data-bs-toggle="modal" data-bs-target="#modalUser"
                onclick="openCreate()">+ Nuevo usuario</button>
      </div>
    </div>

    <!-- Usuario admin (config, inmutable) -->
    <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-3 mb-2" style="background:#0f172a;border:1px solid #334155">
      <div class="d-flex align-items-center gap-3">
        <span class="fw-semibold"><?= htmlspecialchars(ADMIN_USER) ?></span>
        <span class="badge bg-warning text-dark" style="font-size:.65rem">Admin config</span>
        <span class="badge bg-primary" style="font-size:.65rem">Acceso total</span>
      </div>
      <span class="text-secondary small fst-italic">Gestionado en config.php</span>
    </div>

    <?php if (empty($dbUsers)): ?>
      <p class="text-secondary small fst-italic mt-3 mb-0">No hay otros usuarios creados.</p>
    <?php else: ?>
      <?php foreach ($dbUsers as $u): ?>
        <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-3 mb-2" style="background:#0f172a;border:1px solid #334155">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-semibold"><?= htmlspecialchars($u['username']) ?></span>
            <span class="badge <?= $u['active'] ? 'bg-success' : 'bg-secondary' ?>" style="font-size:.65rem">
              <?= $u['active'] ? 'Activo' : 'Inactivo' ?>
            </span>
            <span class="badge bg-secondary" style="font-size:.65rem">
              <?= htmlspecialchars($permLabels[$u['perms']] ?? $u['perms']) ?>
            </span>
            <span class="text-secondary small"><?= $u['created_at'] ?></span>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" title="Editar"
              onclick="openEdit(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= $u['perms'] ?>', <?= $u['active'] ?>)">
              Editar
            </button>
            <form method="post" action="user_save.php" onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars(addslashes($u['username'])) ?>?')">              <?= csrfField() ?>              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Modal crear / editar usuario -->
  <div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background:#1e293b;border:1px solid #334155">
        <form method="post" action="user_save.php" id="userForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="uAction" value="create">
          <input type="hidden" name="id"     id="uId"     value="0">
          <div class="modal-header border-secondary">
            <h5 class="modal-title" id="modalUserTitle">Nuevo usuario</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label text-secondary small">Usuario</label>
              <input type="text" name="username" id="uUsername" class="form-control bg-dark border-secondary text-white"
                     autocomplete="off" required>
            </div>
            <div class="mb-3">
              <label class="form-label text-secondary small" id="uPassLabel">Contrase&ntilde;a</label>
              <input type="password" name="password" id="uPassword" class="form-control bg-dark border-secondary text-white"
                     autocomplete="new-password">
              <div class="form-text text-secondary" id="uPassHint" style="display:none">
                Deja en blanco para mantener la contrase&ntilde;a actual.
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label text-secondary small">Permisos</label>
              <select name="perms" id="uPerms" class="form-select bg-dark border-secondary text-white">
                <option value="viewer">Solo Lectura</option>
                <option value="salones">Solo Salones</option>
                <option value="pisignage">Solo PiSignage</option>
                <option value="both" selected>Ambos</option>
              </select>
            </div>
            <div class="form-check" id="uActiveWrap" style="display:none">
              <input class="form-check-input" type="checkbox" name="active" id="uActive" value="1">
              <label class="form-check-label text-secondary small" for="uActive">Usuario activo</label>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sportium">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('csvFile');
const fileInfo  = document.getElementById('fileInfo');

if (dropzone) {
dropzone.addEventListener('click',     () => fileInput.click());
dropzone.addEventListener('dragover',  e  => { e.preventDefault(); dropzone.classList.add('drag'); });
dropzone.addEventListener('dragleave', ()  => dropzone.classList.remove('drag'));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('drag');
  if (e.dataTransfer.files[0]) {
    fileInput.files = e.dataTransfer.files;
    showInfo(e.dataTransfer.files[0]);
  }
});
fileInput.addEventListener('change', e => {
  if (e.target.files[0]) showInfo(e.target.files[0]);
});

function showInfo(file) {
  fileInfo.textContent = `Archivo seleccionado: ${file.name}  (${(file.size / 1024).toFixed(1)} KB)`;
  fileInfo.classList.remove('d-none');
}

document.getElementById('importForm').addEventListener('submit', function(e) {
  const file = fileInput.files[0];
  const totalActual = <?= $totalSalones ?>;
  const msg = file
    ? `¿Importar "${file.name}"?\n\nEsto reemplazará los ${totalActual.toLocaleString()} salones actuales por los datos del nuevo archivo.`
    : '¿Iniciar la importación? Se reemplazarán todos los datos actuales.';
  if (!confirm(msg)) { e.preventDefault(); return; }
  const btn = document.getElementById('importBtn');
  btn.textContent = 'Importando…';
  btn.disabled = true;
});
} // end if (dropzone)

// Dropzone PiSignage
const dropzonePi  = document.getElementById('dropzonePi');
const fileInputPi = document.getElementById('csvFilePi');
const fileInfoPi  = document.getElementById('fileInfoPi');

if (dropzonePi) {
dropzonePi.addEventListener('click',     () => fileInputPi.click());
dropzonePi.addEventListener('dragover',  e  => { e.preventDefault(); dropzonePi.classList.add('drag'); });
dropzonePi.addEventListener('dragleave', ()  => dropzonePi.classList.remove('drag'));
dropzonePi.addEventListener('drop', e => {
  e.preventDefault();
  dropzonePi.classList.remove('drag');
  if (e.dataTransfer.files[0]) {
    fileInputPi.files = e.dataTransfer.files;
    showInfoPi(e.dataTransfer.files[0]);
  }
});
fileInputPi.addEventListener('change', e => {
  if (e.target.files[0]) showInfoPi(e.target.files[0]);
});
function showInfoPi(file) {
  fileInfoPi.textContent = `Archivo seleccionado: ${file.name}  (${(file.size / 1024).toFixed(1)} KB)`;
  fileInfoPi.classList.remove('d-none');
}
document.getElementById('importPiForm').addEventListener('submit', function(e) {
  const file = fileInputPi.files[0];
  const totalActual = <?= $totalPlayers ?>;
  const msg = file
    ? `¿Importar "${file.name}"?\n\nEsto reemplazará los ${totalActual.toLocaleString()} players PiSignage actuales.`
    : '¿Iniciar la importación PiSignage? Se reemplazarán todos los players.';
  if (!confirm(msg)) { e.preventDefault(); return; }
  const btn = document.getElementById('importPiBtn');
  btn.textContent = 'Importando…';
  btn.disabled = true;
});
} // end if (dropzonePi)

// Modal usuarios
function openCreate() {
  document.getElementById('modalUserTitle').textContent = 'Nuevo usuario';
  document.getElementById('uAction').value   = 'create';
  document.getElementById('uId').value       = '0';
  document.getElementById('uUsername').value = '';
  document.getElementById('uUsername').readOnly = false;
  document.getElementById('uPassword').value = '';
  document.getElementById('uPassword').required = true;
  document.getElementById('uPassLabel').textContent = 'Contraseña';
  document.getElementById('uPassHint').style.display = 'none';
  document.getElementById('uPerms').value    = 'both';
  document.getElementById('uActiveWrap').style.display = 'none';
}
function openEdit(id, username, perms, active) {
  document.getElementById('modalUserTitle').textContent = 'Editar usuario';
  document.getElementById('uAction').value   = 'edit';
  document.getElementById('uId').value       = id;
  document.getElementById('uUsername').value = username;
  document.getElementById('uUsername').readOnly = false;
  document.getElementById('uPassword').value = '';
  document.getElementById('uPassword').required = false;
  document.getElementById('uPassLabel').textContent = 'Nueva contraseña';
  document.getElementById('uPassHint').style.display = '';
  document.getElementById('uPerms').value    = perms;
  document.getElementById('uActiveWrap').style.display = '';
  document.getElementById('uActive').checked = active == 1;
  new bootstrap.Modal(document.getElementById('modalUser')).show();
}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
