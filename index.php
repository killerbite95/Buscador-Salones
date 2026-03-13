<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

setSecurityHeaders();
requireLogin();

$pdo          = getDB();
$totalSalones = (int) $pdo->query("SELECT COUNT(*) FROM salones")->fetchColumn();
$lastImport   = $pdo->query("SELECT imported_at, filename FROM imports ORDER BY id DESC LIMIT 1")->fetch();
$lastPiImport = $pdo->query("SELECT imported_at FROM pisignage_imports ORDER BY id DESC LIMIT 1")->fetch();
$csrfToken    = csrfToken(); // para pasar al JS en meta tag
?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?></title>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <link rel="icon" type="image/png" href="images/logo-sp.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Page-specific: search & results */
    .field-card {
      background: #0f172a; border: 1px solid #334155; border-radius: .75rem;
      padding: .875rem 1rem; transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .field-card:hover { border-color: #475569; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
    .field-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .3rem; }
    .field-value { font-size: .875rem; font-weight: 600; word-break: break-all; }
    .field-group-title {
      font-size: .65rem; text-transform: uppercase; letter-spacing: .08em;
      color: #94a3b8; margin-top: .75rem; margin-bottom: .25rem; padding-left: .125rem;
    }
    #codigoInput { transition: border-color .2s, box-shadow .2s; }
    #codigoInput:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.15); }
    #suggestionsBox {
      position: absolute; z-index: 30; left: 0; right: 0; top: 100%; margin-top: .4rem;
      background: #0f172a; border: 1px solid #475569; border-radius: .75rem;
      max-height: 16rem; overflow-y: auto; box-shadow: 0 8px 24px rgba(0,0,0,.3);
    }
    .sugg-item { padding: .5rem .875rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: background .15s; }
    .sugg-item:hover, .sugg-item.active { background: #1e293b; }
    .btn-outline-secondary { transition: all .2s; }
    .btn-outline-secondary:hover { transform: translateY(-1px); }
    .table-dark { --bs-table-bg: transparent; }
    .badge { transition: all .2s; }
  </style>
</head>
<body>

<?php
$headerTitle    = 'Buscador de Salones';
$headerSubtitle = '<span style="line-height:1.4">Salones: ' . ($lastImport ? substr($lastImport['imported_at'], 0, 10) : '<span class="fst-italic">sin datos</span>') .
                  '<br>PiSignage: ' . ($lastPiImport ? substr($lastPiImport['imported_at'], 0, 10) : '<span class="fst-italic">sin datos</span>') . '</span>';
$headerHref     = 'index.php';
$headerMaxWidth = '1040px';

ob_start();
if (canAccessAdmin()):
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
<?php endif;
$headerActions = ob_get_clean();
include __DIR__ . '/header.php';
?>

<main class="container py-4" style="max-width:880px">

  <?php if ($totalSalones === 0): ?>
  <div class="alert rounded-4 d-flex align-items-start gap-2" style="background:#78350f22;border:1px solid #92400e;color:#fcd34d">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0 mt-1" aria-hidden="true">
      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
      <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
    </svg>
    <div>
      Sin datos cargados. <a href="login.php" class="fw-semibold" style="color:inherit">El administrador</a>
      debe importar el CSV primero desde el panel de admin.
    </div>
  </div>
  <?php endif; ?>

  <!-- Buscador -->
  <div class="card-dark p-4 mb-4">
    <div class="row g-2 align-items-start">
      <div class="col position-relative">
        <input id="codigoInput" type="search"
               placeholder="Código o nombre de sala…"
               autocomplete="off" autocorrect="off" autocapitalize="off"
               spellcheck="false"
               aria-label="Buscar sala por código o nombre"
               aria-autocomplete="list" aria-controls="suggestionsBox" aria-expanded="false"
               class="form-control form-control-lg bg-dark border-secondary text-white"
               style="border-radius:.75rem">
        <div id="suggestionsBox" class="d-none" role="listbox" aria-label="Sugerencias de salas"></div>
      </div>
      <div class="col-auto">
        <button id="searchBtn" class="btn btn-sportium btn-lg px-4 fw-semibold" style="border-radius:.75rem">
          Buscar
        </button>
      </div>
      <div class="col-auto">
        <button id="clearBtn" class="btn btn-secondary btn-lg px-3" style="border-radius:.75rem">
          Limpiar
        </button>
      </div>
    </div>

    <!-- Búsquedas recientes -->
    <div id="recentsWrap" class="d-none mt-3">
      <div class="text-secondary small text-uppercase mb-2" style="letter-spacing:.05em">
        Búsquedas recientes
      </div>
      <div id="recents" class="d-flex flex-wrap gap-2"></div>
    </div>
  </div>

  <!-- Resultado -->
  <section id="resultSection" class="d-none">
    <div class="card-dark overflow-hidden">
      <div class="px-4 py-3 border-bottom border-secondary" style="background:#0f172a">
        <h2 id="resultTitle" class="h5 fw-semibold mb-1">Sala</h2>
        <p id="resultSubtitle" class="text-secondary small mb-0">—</p>
      </div>
      <div class="p-4">
        <div class="row g-3" id="fieldsGrid"></div>
      </div>
      <!-- PiSignage players -->
      <div id="pisignageSection" class="d-none px-4 pb-4 overflow-auto" style="max-height:420px"></div>
    </div>
  </section>

  <!-- No encontrado -->
  <section id="notFoundSection" class="d-none">
    <div id="notFoundBox" class="rounded-4 p-3"
         style="background:#450a0a33;border:1px solid #991b1b;color:#fca5a5"></div>
  </section>

</main>

<?php include __DIR__ . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Field groups
function buildFieldGroups(hasPisignage) {
  const albosLabel = hasPisignage ? 'IP Albos / Multi · PiSignage ▼' : 'IP Albos / Multi';
  return [
    { title: 'Red', fields: [['router','Router'],['ip_ssbt','IP SSBT'],['ip_pos','IP POS'],['ip_albos', albosLabel]] },
    { title: 'Televisión', fields: [['pulgadas','Pulgadas TV'],['config_tv','Config. TV · Nº Pantallas']] },
    { title: 'Contenido', fields: [['sis','SIS'],['datos_sis','DATOS SIS'],['arc','ARC'],['datos_arc','DATOS ARC']] },
  ];
}

// Recientes
const RECENTS_KEY = 'salon_recents_v1';

function getRecents() {
  try { return JSON.parse(localStorage.getItem(RECENTS_KEY)) || []; } catch { return []; }
}
function pushRecent(code, name) {
  const list = getRecents();
  const i = list.findIndex(x => x.code === code);
  if (i >= 0) list.splice(i, 1);
  list.unshift({ code, name });
  localStorage.setItem(RECENTS_KEY, JSON.stringify(list.slice(0, 10)));
  renderRecents();
}
function renderRecents() {
  const list = getRecents();
  const wrap = document.getElementById('recentsWrap');
  const box  = document.getElementById('recents');
  box.innerHTML = '';
  if (!list.length) { wrap.classList.add('d-none'); return; }
  wrap.classList.remove('d-none');
  for (const it of list) {
    const label = it.name
      ? `${it.code} · ${it.name.replace(/^\(CERRAD[OA]\)\s*/i, '').substring(0, 42)}`
      : it.code;
    const btn = document.createElement('button');
    btn.type      = 'button';
    btn.className = 'btn btn-sm btn-outline-secondary rounded-pill';
    btn.textContent = label;
    btn.addEventListener('click', () => { setValue(it.code); doSearch(); });
    box.appendChild(btn);
  }
}

// Autocomplete
let suggestTimer = null;
let suggActiveIdx = -1;
const input    = document.getElementById('codigoInput');
const suggBox  = document.getElementById('suggestionsBox');

input.addEventListener('input', () => {
  clearTimeout(suggestTimer);
  const q = input.value.trim();
  if (!q) { hideSugg(); return; }
  suggestTimer = setTimeout(() => fetchSugg(q), 200);
});

function escHTML(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

async function fetchSugg(q) {
  try {
    const res  = await fetch('api.php?suggest=1&q=' + encodeURIComponent(q));
    const data = await res.json();
    if (!Array.isArray(data) || !data.length) { hideSugg(); return; }
    suggBox.innerHTML = '';
    suggActiveIdx = -1;
    data.forEach((r, i) => {
      const div = document.createElement('div');
      div.className = 'sugg-item';
      div.id = 'sugg-opt-' + i;
      div.setAttribute('role', 'option');
      const codeSpan = document.createElement('span');
      codeSpan.className = 'fw-semibold';
      codeSpan.textContent = r.codigo;
      const nameSpan = document.createElement('span');
      nameSpan.className = 'text-secondary small ms-3 text-truncate';
      nameSpan.style.maxWidth = '68%';
      nameSpan.textContent = r.nombre;
      div.appendChild(codeSpan);
      div.appendChild(nameSpan);
      div.addEventListener('click', () => { setValue(r.codigo); hideSugg(); doSearch(); });
      suggBox.appendChild(div);
    });
    suggBox.classList.remove('d-none');
    input.setAttribute('aria-expanded', 'true');
  } catch {}
}

function hideSugg() {
  suggBox.classList.add('d-none');
  suggActiveIdx = -1;
  input.setAttribute('aria-expanded', 'false');
  input.removeAttribute('aria-activedescendant');
}

function updateSuggActive() {
  const items = suggBox.querySelectorAll('.sugg-item');
  items.forEach((el, i) => {
    el.classList.toggle('active', i === suggActiveIdx);
  });
  if (suggActiveIdx >= 0 && items[suggActiveIdx]) {
    input.setAttribute('aria-activedescendant', items[suggActiveIdx].id);
    items[suggActiveIdx].scrollIntoView({ block: 'nearest' });
  } else {
    input.removeAttribute('aria-activedescendant');
  }
}

input.addEventListener('keydown', e => {
  const items = suggBox.querySelectorAll('.sugg-item');
  if (!suggBox.classList.contains('d-none') && items.length) {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      suggActiveIdx = (suggActiveIdx + 1) % items.length;
      updateSuggActive();
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      suggActiveIdx = suggActiveIdx <= 0 ? items.length - 1 : suggActiveIdx - 1;
      updateSuggActive();
      return;
    }
    if (e.key === 'Enter' && suggActiveIdx >= 0) {
      e.preventDefault();
      items[suggActiveIdx].click();
      return;
    }
  }
  if (e.key === 'Enter')  { e.preventDefault(); doSearch(); }
  if (e.key === 'Escape') hideSugg();
});
document.addEventListener('click', e => {
  if (!suggBox.contains(e.target) && e.target !== input) hideSugg();
});

// Búsqueda
document.getElementById('searchBtn').addEventListener('click', doSearch);
document.getElementById('clearBtn').addEventListener('click', () => {
  setValue('');
  hideSugg();
  document.getElementById('resultSection').classList.add('d-none');
  document.getElementById('notFoundSection').classList.add('d-none');
  input.focus();
});

async function doSearch() {
  hideSugg();
  const q = input.value.trim();
  if (!q) return;
  const btn = document.getElementById('searchBtn');
  const origHTML = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-sm"></span>';
  btn.disabled = true;
  try {
    const res  = await fetch('api.php?q=' + encodeURIComponent(q));
    const data = await res.json();
    if (data.error) return;
    if (data.not_found) { showNotFound(data.q, data.suggestions || []); return; }
    renderResult(data.salon, data.pisignage || []);
    pushRecent(data.salon.codigo, data.salon.nombre);
  } catch (e) { console.error(e); }
  finally { btn.innerHTML = origHTML; btn.disabled = false; }
}

// Render resultado
function looksLikeIP(s) {
  return /^\s*\d{1,3}(\.\d{1,3}){3}/.test((s || '').trim());
}

function renderResult(salon, pisignage) {
  document.getElementById('resultTitle').textContent    = salon.nombre || 'Sala';
  document.getElementById('resultSubtitle').textContent = 'Código: ' + salon.codigo;
  document.getElementById('notFoundSection').classList.add('d-none');

  const hasPi = pisignage && pisignage.length > 0;
  const FIELD_GROUPS = buildFieldGroups(hasPi);

  const grid = document.getElementById('fieldsGrid');
  grid.innerHTML = '';

  for (const group of FIELD_GROUPS) {
    const hdrCol = document.createElement('div');
    hdrCol.className = 'col-12';
    const hdrDiv = document.createElement('div');
    hdrDiv.className = 'field-group-title';
    hdrDiv.textContent = group.title;
    hdrCol.appendChild(hdrDiv);
    grid.appendChild(hdrCol);

    for (const [key, label] of group.fields) {
      const val = (salon[key] || '').trim();

      const col  = document.createElement('div');
      col.className = 'col-12 col-md-6';

      const card = document.createElement('div');
      card.className = 'field-card';
      if (key === 'ip_albos' && hasPi) {
        card.style.borderColor = '#dc2626';
        card.style.boxShadow = '0 0 0 1px rgba(220,38,38,.2)';
      }

      const lbl = document.createElement('div');
      lbl.className   = 'field-label';
      lbl.textContent = label;

      const row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-2';

      const valEl = document.createElement('div');
      valEl.className   = 'field-value';
      valEl.textContent = val || '—';

      row.appendChild(valEl);

      if (val && looksLikeIP(val) && navigator.clipboard) {
        const copyBtn = document.createElement('button');
        copyBtn.type      = 'button';
        copyBtn.className = 'btn btn-sm btn-outline-secondary ms-auto d-flex align-items-center gap-1';
        copyBtn.style.fontSize = '.72rem';
        copyBtn.setAttribute('aria-label', 'Copiar ' + val);
        copyBtn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
        </svg> Copiar`;
        copyBtn.addEventListener('click', async () => {
          await navigator.clipboard.writeText(val);
          copyBtn.textContent = '✓ Copiado';
          setTimeout(() => {
            copyBtn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
              <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg> Copiar`;
          }, 1600);
        });
        row.appendChild(copyBtn);
      }

      card.appendChild(lbl);
      card.appendChild(row);
      col.appendChild(card);
      grid.appendChild(col);
    }
  }

  const resultEl = document.getElementById('resultSection');
  resultEl.classList.remove('d-none');
  resultEl.classList.remove('animate-in');
  void resultEl.offsetWidth;
  resultEl.classList.add('animate-in');

  // PiSignage
  const piWrap = document.getElementById('pisignageSection');
  piWrap.innerHTML = '';
  if (pisignage && pisignage.length) {
    const header = document.createElement('div');
    header.className = 'mb-2 mt-1';
    const headerSpan = document.createElement('span');
    headerSpan.className = 'text-secondary small text-uppercase fw-semibold';
    headerSpan.style.letterSpacing = '.06em';
    headerSpan.textContent = 'PiSignage · ' + pisignage.length + ' pantalla' + (pisignage.length !== 1 ? 's' : '');
    header.appendChild(headerSpan);
    piWrap.appendChild(header);

    const table = document.createElement('table');
    table.className = 'table table-sm table-dark mb-0';
    table.style.fontSize = '.8rem';
    const thead = document.createElement('thead');
    const headRow = document.createElement('tr');
    ['Pantalla','IP','Playlist','Último reporte'].forEach(t => {
      const th = document.createElement('th');
      th.className = 'text-secondary fw-normal';
      th.textContent = t;
      headRow.appendChild(th);
    });
    thead.appendChild(headRow);
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);

    const now = Date.now();
    for (const p of pisignage) {
      const last    = p.last_reported ? new Date(p.last_reported) : null;
      const diffH   = last ? (now - last.getTime()) / 36e5 : null;
      const offline = diffH !== null && diffH > 24;

      const tr = document.createElement('tr');

      // Screen name
      const tdScreen = document.createElement('td');
      tdScreen.className = 'align-middle fw-semibold';
      tdScreen.textContent = p.screen || p.name;
      tr.appendChild(tdScreen);

      // IP
      const tdIp = document.createElement('td');
      tdIp.className = 'align-middle';
      if (p.ip_address) {
        const ipSpan = document.createElement('span');
        ipSpan.className = 'font-monospace';
        ipSpan.textContent = p.ip_address;
        tdIp.appendChild(ipSpan);
        if (navigator.clipboard) {
          const copyBtn = document.createElement('button');
          copyBtn.type = 'button';
          copyBtn.className = 'btn btn-sm btn-outline-secondary ms-1 py-0 px-1 copy-ip';
          copyBtn.style.fontSize = '.65rem';
          copyBtn.dataset.ip = p.ip_address;
          copyBtn.textContent = 'Copiar';
          copyBtn.setAttribute('aria-label', 'Copiar IP ' + p.ip_address);
          tdIp.appendChild(copyBtn);
        }
      } else {
        tdIp.textContent = '—';
      }
      tr.appendChild(tdIp);

      // Playlist
      const tdPlaylist = document.createElement('td');
      tdPlaylist.className = 'align-middle text-secondary';
      tdPlaylist.textContent = p.playlist || '—';
      tr.appendChild(tdPlaylist);

      // Last reported + badge
      const tdLast = document.createElement('td');
      tdLast.className = 'align-middle';
      const lastStr = last
        ? last.toLocaleString('es-ES', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})
        : '—';
      tdLast.textContent = lastStr;
      if (diffH !== null) {
        const badge = document.createElement('span');
        badge.className = offline ? 'badge bg-danger ms-1' : 'badge bg-success ms-1';
        badge.style.fontSize = '.65rem';
        badge.textContent = offline ? 'offline' : 'online';
        tdLast.appendChild(badge);
      }
      tr.appendChild(tdLast);

      tbody.appendChild(tr);
    }

    table.addEventListener('click', async e => {
      const btn = e.target.closest('.copy-ip');
      if (!btn) return;
      try {
        await navigator.clipboard.writeText(btn.dataset.ip.trim());
        btn.textContent = '✓';
        setTimeout(() => { btn.textContent = 'Copiar'; }, 1500);
      } catch {}
    });

    piWrap.appendChild(table);
    piWrap.classList.remove('d-none');
  } else {
    piWrap.classList.add('d-none');
  }

  input.focus();
}

function showNotFound(q, suggestions) {
  document.getElementById('resultSection').classList.add('d-none');
  const box = document.getElementById('notFoundBox');
  box.innerHTML = '';
  const strong = document.createElement('strong');
  strong.textContent = 'No se encontró "' + q + '".';
  box.appendChild(strong);
  if (suggestions.length) {
    const hint = document.createElement('div');
    hint.className = 'mt-2 mb-1';
    hint.textContent = '¿Quizá buscabas?';
    box.appendChild(hint);
    const ul = document.createElement('ul');
    ul.className = 'mb-0';
    for (const s of suggestions) {
      const li  = document.createElement('li');
      const btn = document.createElement('button');
      btn.type      = 'button';
      btn.className = 'btn btn-link p-0 text-decoration-none';
      btn.style.color = '#fca5a5';
      btn.textContent = s.codigo + ' · ' + s.nombre;
      btn.addEventListener('click', () => { setValue(s.codigo); doSearch(); });
      li.appendChild(btn);
      ul.appendChild(li);
    }
    box.appendChild(ul);
  }
  document.getElementById('notFoundSection').classList.remove('d-none');
}

function setValue(v) { input.value = v; }

renderRecents();
input.focus();
</script>
</body>
</html>
