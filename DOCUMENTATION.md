# Documentación Técnica

## Arquitectura

Aplicación PHP monolítica con SQLite. Sin frameworks, sin dependencias externas (solo Bootstrap 5 por CDN). Diseñada para funcionar con `php -S` en entorno local o detrás de Apache/Nginx.

```
Browser ──→ PHP Built-in Server / Apache
               │
               ├── index.php      (UI buscador)
               ├── api.php        (JSON endpoints)
               ├── admin.php      (panel admin)
               ├── login.php      (autenticación)
               │
               └── data/salones.db (SQLite)
```

---

## Base de Datos

SQLite con WAL journal mode. El esquema se crea automáticamente en `db.php` al primer acceso.

### Tablas

#### `salones`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INTEGER PK | Auto-incremental |
| `codigo` | TEXT | Código numérico extraído del nombre |
| `nombre` | TEXT | Nombre completo del salón (del CSV) |
| `router` | TEXT | Router asignado |
| `ip_ssbt` | TEXT | IP del SSBT |
| `ip_pos` | TEXT | IP del POS |
| `ip_albos` | TEXT | IP Albos/Multi |
| `pulgadas` | TEXT | Pulgadas de TV |
| `config_tv` | TEXT | Configuración TV / Nº pantallas |
| `sis` | TEXT | SIS |
| `datos_sis` | TEXT | Datos SIS |
| `arc` | TEXT | ARC |
| `datos_arc` | TEXT | Datos ARC |

Índices: `idx_codigo`, `idx_nombre`.

#### `pisignage_players`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INTEGER PK | Auto-incremental |
| `name` | TEXT | Nombre del player (ej: `ESGA80_T4025_D02`) |
| `codigo` | TEXT | Código de sala extraído del nombre |
| `screen` | TEXT | Identificador de pantalla (ej: `D02`) |
| `ip_address` | TEXT | IP del player |
| `playlist` | TEXT | Playlist activa |
| `last_reported` | TEXT | Última conexión reportada |

Índice: `idx_pi_codigo`.

#### `users`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INTEGER PK | Auto-incremental |
| `username` | TEXT UNIQUE | Nombre de usuario (case-insensitive) |
| `password` | TEXT | Hash bcrypt (`password_hash`) |
| `perms` | TEXT | `'viewer'`, `'salones'`, `'pisignage'` o `'both'` |
| `active` | INTEGER | 1 = activo, 0 = deshabilitado |
| `created_at` | DATETIME | Fecha de creación |

#### `imports` / `pisignage_imports`

Registro de cada importación: `filename`, `total_rows`, `imported_at`.

#### `audit_log`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INTEGER PK | Auto-incremental |
| `action` | TEXT | Tipo de acción (`create`, `edit`, `delete`) |
| `target_user` | TEXT | Usuario afectado por la acción |
| `details` | TEXT | Descripción de los cambios |
| `performed_by` | TEXT | Usuario que realizó la acción |
| `created_at` | DATETIME | Fecha/hora del evento |

#### `login_attempts`

Rate limiting: `ip`, `attempted_at`. Se purga automáticamente tras 15 minutos.

---

## Autenticación

### Dos fuentes de usuarios

1. **Admin de config** — Definido en `config.php` (`ADMIN_USER` / `ADMIN_PASS`). Tiene permisos totales (`'all'`). La contraseña se almacena como hash bcrypt generado con `password_hash()`. La verificación en `auth.php` usa `password_verify()`.

2. **Usuarios de BD** — Creados desde el panel. Contraseña hasheada con `password_hash()` / `password_verify()`. Permisos: `'viewer'` (solo búsqueda), `'salones'`, `'pisignage'` o `'both'`. Los usuarios `viewer` no tienen acceso al panel de administración.

### Sesión

- Cookie: `SPORTIUM_SID`
- `HttpOnly`, `SameSite=Strict`
- `Secure` si HTTPS está activo
- Regeneración de ID cada 20 minutos

### Rate Limiting

- 10 intentos fallidos por IP en ventana de 15 minutos
- Los intentos se guardan en `login_attempts` (SQLite)
- Se limpian automáticamente al expirar la ventana

---

## Importación de CSVs

### Salones (Insight/Jira)

**Archivo:** `import.php`

1. Se sube el CSV exportado de Insight/Jira (máx. 256 MB)
2. Se convierte a UTF-8 (detecta BOM e ISO-8859-1)
3. Se parsea con `fgetcsv` via stream en memoria
4. Se detectan las columnas automáticamente por nombre (fuzzy matching con normalización)
5. Se extraen los códigos de sala del campo `Nombre`:
   - Primero busca secuencia de 4-6 dígitos aislada
   - Fallback: concatena todos los dígitos del nombre
6. `DELETE` + `INSERT` en transacción (reemplazo completo)

### PiSignage (PlayersListMongo)

**Archivo:** `import_players.php`

1. Se sube el `PlayersListMongo.csv` (máx. 256 MB)
2. Se detectan columnas: `name`, `myIpAddress`, `currentPlaylist`, `lastReported`
3. Se extrae el código de sala del nombre del player:
   - `ESGA80_T4025_D02` → código `4025`, pantalla `D02`
   - Busca segmento que matchee `/^[A-Za-z]+(\d+)$/`
4. `DELETE` + `INSERT` en transacción

---

## API

**Archivo:** `api.php`

Requiere autenticación (devuelve 401 JSON si no hay sesión).

### `GET /api.php?q=<texto>&suggest=1`

Autocomplete. Devuelve array de `{codigo, nombre}` (máx. 10).

- Busca por prefijo de código (si hay dígitos en la consulta)
- Complementa con búsqueda por nombre (`LIKE %texto%`) si quedan huecos o el texto tiene ≥ 2 caracteres

### `GET /api.php?q=<texto>`

Búsqueda completa. Acepta código numérico o nombre de sala. Orden de búsqueda:

1. Código exacto → 2. Prefijo de código → 3. Nombre (`LIKE %texto%`)

Devuelve:

```json
{
  "salon": { "codigo": "4025", "nombre": "...", "router": "...", ... },
  "pisignage": [
    { "name": "ESGA80_T4025_D02", "screen": "D02", "ip_address": "...", ... }
  ]
}
```

Si no encuentra:

```json
{
  "not_found": true,
  "q": "4025",
  "suggestions": [ { "codigo": "4026", "nombre": "..." } ]
}
```

---

## Seguridad

### CSRF

- Token de 64 caracteres hex (`random_bytes(32)`)
- Almacenado en `$_SESSION['csrf_token']`
- Validado en cada POST con `hash_equals`
- **Rotación tras uso:** el token se destruye después de cada verificación exitosa, previniendo replay attacks
- Campo hidden generado con `csrfField()`

### Cabeceras HTTP

| Cabecera | Valor |
|---|---|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Cache-Control` | `no-store, no-cache, must-revalidate, private` |
| `X-XSS-Protection` | `1; mode=block` |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; ...` |

### Protección de datos

- `data/.htaccess` con `Deny from all`
- La BD SQLite no es accesible por URL
- Los CSVs no se almacenan en el servidor (se procesan en memoria)

---

## Frontend

### Dependencias

- Bootstrap 5.3.3 (CDN) — tema dark (`data-bs-theme="dark"`)
- Sin JavaScript frameworks

### Paleta de colores

| Uso | Color |
|---|---|
| Fondo | `#0f172a` |
| Cards | `#1e293b` |
| Bordes | `#334155` |
| Texto secundario | `#94a3b8` |
| Acento (Sportium) | `#dc2626` |

### Funcionalidades JS

- Autocomplete con debounce de 200ms
- Navegación por teclado en sugerencias (↑/↓ para navegar, Enter para seleccionar, Escape para cerrar)
- Búsqueda por código numérico o nombre de sala
- Búsquedas recientes en `localStorage`
- Copiar IPs al portapapeles
- Spinner de carga en botón de búsqueda
- Animación fadeInUp en resultados
- Indicador PiSignage en campo IP Albos (borde rojo + label dinámico)
- Badges online/offline en tabla PiSignage (umbral: 24h)
- Confirmación antes de importar con nombre de archivo y conteo de registros actuales
- Protección XSS: toda la UI se construye con DOM methods (`createElement` / `textContent`), sin `innerHTML` con datos de usuario

### Accesibilidad

- `role="listbox"` en contenedor de sugerencias, `role="option"` en cada ítem
- `aria-expanded`, `aria-activedescendant` en el input de búsqueda
- `aria-label` en botones que solo tienen icono (Admin, Buscador, Copiar IP)
- `aria-hidden="true"` en todos los SVGs decorativos
- Contraste WCAG AA: texto secundario `#94a3b8` sobre `#0f172a` (ratio 4.6:1)

### Arquitectura de componentes compartidos

- **`styles.css`** — CSS común: body, animaciones, `.card-dark`, `.btn-sportium`, dropdown, forms, scrollbar, spinner, header
- **`header.php`** — Navbar parametrizada vía variables PHP (`$headerTitle`, `$headerSubtitle`, `$headerHref`, `$headerMaxWidth`, `$headerActions`)
- **`footer.php`** — Footer con copyright
- **`helpers.php`** — Funciones compartidas: parseo CSV, detección de columnas, `auditLog()`
