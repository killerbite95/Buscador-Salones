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
  <style>
    body { background: #0f172a; }

    /* Animations */
    @keyframes fadeInUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
    @keyframes spin { to { transform:rotate(360deg); } }
    .animate-in { animation: fadeInUp .3s ease-out both; }

    /* Cards */
    .card-dark { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; transition: border-color .2s, box-shadow .2s; }
    .field-card {
      background: #0f172a; border: 1px solid #334155; border-radius: .75rem;
      padding: .875rem 1rem; transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .field-card:hover { border-color: #475569; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
    .field-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .3rem; }
    .field-value { font-size: .875rem; font-weight: 600; word-break: break-all; }

    /* Field group headers */
    .field-group-title {
      font-size: .65rem; text-transform: uppercase; letter-spacing: .08em;
      color: #64748b; margin-top: .75rem; margin-bottom: .25rem; padding-left: .125rem;
    }

    /* Buttons */
    .btn-sportium { background: #dc2626; border-color: #dc2626; color: #fff; transition: all .2s; }
    .btn-sportium:hover { background: #b91c1c; border-color: #b91c1c; color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220,38,38,.3); }
    .btn-sportium:active { transform: scale(.97); box-shadow: none; }

    /* Search input focus glow */
    #codigoInput { transition: border-color .2s, box-shadow .2s; }
    #codigoInput:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.15); }

    /* Suggestions dropdown */
    #suggestionsBox {
      position: absolute; z-index: 30; left: 0; right: 0; top: 100%; margin-top: .4rem;
      background: #0f172a; border: 1px solid #475569; border-radius: .75rem;
      max-height: 16rem; overflow-y: auto; box-shadow: 0 8px 24px rgba(0,0,0,.3);
    }
    .sugg-item { padding: .5rem .875rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: background .15s; }
    .sugg-item:hover { background: #1e293b; }

    /* Scrollbar */
    ::-webkit-scrollbar       { width: 6px; height: 6px; }
    ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }

    /* Dropdown menu */
    .dropdown-item { color: #cbd5e1; transition: background .15s, color .15s; }
    .dropdown-item:hover, .dropdown-item:focus { background: #0f172a; color: #f1f5f9; }
    .dropdown-item.text-danger:hover { background: #450a0a44; }
    .dropdown-toggle::after { vertical-align: middle; }

    /* Loading spinner */
    .spinner-sm {
      width: 1.1rem; height: 1.1rem;
      border: 2.5px solid rgba(255,255,255,.25); border-top-color: #fff;
      border-radius: 50%; animation: spin .5s linear infinite;
      display: inline-block; vertical-align: middle;
    }

    /* Recent search pills */
    .btn-outline-secondary { transition: all .2s; }
    .btn-outline-secondary:hover { transform: translateY(-1px); }

    /* PiSignage */
    .table-dark { --bs-table-bg: transparent; }
    .badge { transition: all .2s; }
  </style>
</head>
<body>

<header style="background:#1e293b;border-bottom:1px solid #334155">
  <div class="container d-flex align-items-center justify-content-between py-3" style="max-width:1040px">

    <!-- Logo + título -->
    <a href="index.php" class="d-flex align-items-center gap-3 text-decoration-none">
      <img src="images/logo-sp.png" alt="Sportium" height="36">
      <div class="d-none d-sm-block lh-sm">
        <div class="fw-semibold text-white" style="font-size:.95rem">Buscador de Salones</div>
        <div class="text-secondary" style="font-size:.72rem;line-height:1.4">
          Salones: <?= $lastImport ? substr($lastImport['imported_at'], 0, 10) : '<span class="fst-italic">sin datos</span>' ?><br>
          PiSignage: <?= $lastPiImport ? substr($lastPiImport['imported_at'], 0, 10) : '<span class="fst-italic">sin datos</span>' ?>
        </div>
      </div>
    </a>

    <!-- Controles derecha -->
    <div class="d-flex align-items-center gap-2">
      <?php if (canAccessAdmin()): ?>
        <a href="admin.php"
           class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
           style="font-size:.8rem">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
            <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
          </svg>
          <span class="d-none d-md-inline">Admin</span>
        </a>
      <?php endif; ?>

      <!-- Avatar / usuario -->
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
          <?php if (canAccessAdmin()): ?>
          <li>
            <a class="dropdown-item" href="admin.php" style="font-size:.85rem">
              Panel de administración
            </a>
          </li>
          <li><hr class="dropdown-divider border-secondary"></li>
          <?php endif; ?>
          <li>
            <a class="dropdown-item text-danger" href="logout.php" style="font-size:.85rem">
              Cerrar sesión
            </a>
          </li>
        </ul>
      </div>
    </div>

  </div>
</header>

<main class="container py-4" style="max-width:880px">

  <?php if ($totalSalones === 0): ?>
  <div class="alert rounded-4 d-flex align-items-start gap-2" style="background:#78350f22;border:1px solid #92400e;color:#fcd34d">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="flex-shrink-0 mt-1">
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
               placeholder="Introduce el código de sala…"
               autocomplete="off" autocorrect="off" autocapitalize="off"
               spellcheck="false" inputmode="numeric"
               class="form-control form-control-lg bg-dark border-secondary text-white"
               style="border-radius:.75rem">
        <div id="suggestionsBox" class="d-none"></div>
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

<footer class="container py-4 text-center" style="max-width:880px">
  <p class="text-secondary small mb-0">
    © <?= date('Y') ?> Sportium — Uso interno

  </p>
</footer>

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
const input    = document.getElementById('codigoInput');
const suggBox  = document.getElementById('suggestionsBox');

input.addEventListener('input', () => {
  clearTimeout(suggestTimer);
  const q = input.value.trim();
  if (!q) { hideSugg(); return; }
  suggestTimer = setTimeout(() => fetchSugg(q), 200);
});

async function fetchSugg(q) {
  try {
    const res  = await fetch('api.php?suggest=1&q=' + encodeURIComponent(q));
    const data = await res.json();
    if (!Array.isArray(data) || !data.length) { hideSugg(); return; }
    suggBox.innerHTML = '';
    for (const r of data) {
      const div = document.createElement('div');
      div.className = 'sugg-item';
      div.innerHTML = `
        <span class="fw-semibold">${r.codigo}</span>
        <span class="text-secondary small ms-3 text-truncate" style="max-width:68%">${r.nombre}</span>
      `;
      div.addEventListener('click', () => { setValue(r.codigo); hideSugg(); doSearch(); });
      suggBox.appendChild(div);
    }
    suggBox.classList.remove('d-none');
  } catch {}
}

function hideSugg() { suggBox.classList.add('d-none'); }

input.addEventListener('keydown', e => {
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
    hdrCol.innerHTML = `<div class="field-group-title">${group.title}</div>`;
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
        copyBtn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
        </svg> Copiar`;
        copyBtn.addEventListener('click', async () => {
          await navigator.clipboard.writeText(val);
          copyBtn.textContent = '✓ Copiado';
          setTimeout(() => {
            copyBtn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    header.innerHTML = `<span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing:.06em">
      PiSignage · ${pisignage.length} pantalla${pisignage.length !== 1 ? 's' : ''}
    </span>`;
    piWrap.appendChild(header);

    const table = document.createElement('table');
    table.className = 'table table-sm table-dark mb-0';
    table.style.fontSize = '.8rem';
    table.innerHTML = `
      <thead>
        <tr>
          <th class="text-secondary fw-normal">Pantalla</th>
          <th class="text-secondary fw-normal">IP</th>
          <th class="text-secondary fw-normal">Playlist</th>
          <th class="text-secondary fw-normal">Último reporte</th>
        </tr>
      </thead>
      <tbody></tbody>`;

    const now = Date.now();
    for (const p of pisignage) {
      const last    = p.last_reported ? new Date(p.last_reported) : null;
      const diffH   = last ? (now - last.getTime()) / 36e5 : null;
      const offline = diffH !== null && diffH > 24;
      const badge   = offline
        ? `<span class="badge bg-danger ms-1" style="font-size:.65rem">offline</span>`
        : (diffH !== null ? `<span class="badge bg-success ms-1" style="font-size:.65rem">online</span>` : '');

      const ipCell  = p.ip_address
        ? `<span class="font-monospace">${p.ip_address}</span>
           ${navigator.clipboard
             ? `<button type="button" class="btn btn-sm btn-outline-secondary ms-1 py-0 px-1 copy-ip" data-ip="${p.ip_address}" style="font-size:.65rem">Copiar</button>`
             : ''}`
        : '—';

      const lastStr = last
        ? last.toLocaleString('es-ES', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})
        : '—';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="align-middle fw-semibold">${p.screen || p.name}</td>
        <td class="align-middle">${ipCell}</td>
        <td class="align-middle text-secondary">${p.playlist || '—'}</td>
        <td class="align-middle">${lastStr}${badge}</td>`;
      table.querySelector('tbody').appendChild(tr);
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
  let html = `<strong>No se encontró el código "${q}".</strong>`;
  if (suggestions.length) {
    html += `<div class="mt-2 mb-1">¿Quizá buscabas?</div><ul class="mb-0">` +
      suggestions.map(s =>
        `<li>
          <button type="button" class="btn btn-link p-0 text-decoration-none"
                  style="color:#fca5a5"
                  onclick="setValue('${s.codigo}');doSearch()">
            ${s.codigo} · ${s.nombre}
          </button>
        </li>`
      ).join('') + '</ul>';
  }
  box.innerHTML = html;
  document.getElementById('notFoundSection').classList.remove('d-none');
}

function setValue(v) { input.value = v; }

renderRecents();
input.focus();
</script>
</body>
</html>
