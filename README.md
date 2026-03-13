# Buscador de Salones · Sportium

Herramienta interna que centraliza la información de red y servicios de cada salón de Sportium: IPs (SSBT, POS, Albos/Multi, PiSignage), routers, configuración de TV, SIS, ARC y más. Todo en un solo lugar para poder dar un servicio rápido y eficaz sin tener que consultar múltiples fuentes.

La integración con PiSignage permite asociar las pantallas (players) de cada sala y consultar sus IPs, playlists y estado de conexión directamente desde el buscador.

## Requisitos

- PHP 8.0+
- Extensión `pdo_sqlite`

## Instalación

```bash
git clone <repo-url>
cd N1-Buscador-Salones
cp config.example.php config.php
```

Edita `config.php` con tus credenciales:

```php
define('ADMIN_USER', 'tu_usuario');
define('ADMIN_PASS', password_hash('tu_contraseña', PASSWORD_DEFAULT));
```

> **Nota:** `ADMIN_PASS` debe ser un hash bcrypt generado con `password_hash()`. Puedes generarlo con:
> ```bash
> php -r "echo password_hash('mi_password', PASSWORD_DEFAULT);"
> ```

Arranca el servidor:

```bash
php -S localhost:8080
```

Abre http://localhost:8080 y accede con tus credenciales.

## Uso

### Primer inicio

1. Entra en **Admin** (`/admin.php`)
2. Sube el CSV de Insight/Jira con los datos de salones
3. Sube el `PlayersListMongo.csv` con los datos de PiSignage
4. Ya puedes buscar en el buscador principal (`/index.php`)

### Búsqueda

- Escribe un código de sala o parte del nombre
- Autocomplete sugiere resultados mientras escribes (navega con ↑/↓, selecciona con Enter)
- Los resultados muestran: red, televisión, contenido y pantallas PiSignage
- Las IPs tienen botón de copiar al portapapeles

### Usuarios

El admin puede crear usuarios desde el panel con 4 niveles de permisos:

| Permiso | Acceso |
|---|---|
| `viewer` | Solo búsqueda (sin acceso al panel admin) |
| `salones` | Panel admin + importar CSV de salones |
| `pisignage` | Panel admin + importar CSV de PiSignage |
| `both` | Panel admin + importar ambos CSV |

## Estructura

```
├── config.php          # Credenciales y configuración
├── config.example.php  # Plantilla de configuración
├── db.php              # Conexión SQLite y esquema
├── auth.php            # Sesiones y permisos
├── security.php        # CSRF, rate limiting, headers
├── helpers.php         # Parseo CSV, detección de columnas, audit log
├── styles.css          # Estilos CSS compartidos
├── header.php          # Navbar compartida (partial)
├── footer.php          # Footer compartido (partial)
├── index.php           # Buscador principal
├── admin.php           # Panel de administración
├── admin_history.php   # Historial de auditoría (paginado)
├── api.php             # API JSON (búsqueda + sugerencias)
├── login.php           # Login
├── logout.php          # Logout
├── change_password.php # Cambio de contraseña (usuarios BD)
├── import.php          # Importación CSV salones
├── import_players.php  # Importación CSV PiSignage
├── user_save.php       # CRUD de usuarios
├── mejoras.md          # Roadmap de mejoras pendientes
├── data/
│   ├── .htaccess       # Bloquea acceso directo a la BD
│   └── salones.db      # Base de datos SQLite (auto-generada)
└── images/
    └── logo-sp.png     # Logo Sportium
```

## Seguridad

- Contraseña admin almacenada como hash bcrypt (nunca en texto plano)
- Tokens CSRF en todos los formularios (rotación tras cada uso)
- Rate limiting en login (10 intentos / 15 min por IP)
- Sesiones seguras (HttpOnly, SameSite=Strict, regeneración cada 20 min)
- Cabeceras HTTP de seguridad (CSP, X-Frame-Options, etc.)
- Límite de 256 MB en uploads
- Protección XSS: DOM construction en vez de innerHTML
- Todas las páginas requieren autenticación
- Historial de auditoría para todas las acciones sobre usuarios

## Accesibilidad

- Autocomplete ARIA-compliant (`role="listbox"`, `aria-expanded`, `aria-activedescendant`)
- `aria-label` en botones con solo icono
- `aria-hidden="true"` en SVGs decorativos
- Contraste WCAG AA en texto secundario (ratio ≥ 4.5:1)

## Documentación

Para detalles técnicos sobre la arquitectura, base de datos, API, seguridad y frontend, consulta [DOCUMENTATION.md](DOCUMENTATION.md).

## Licencia

Uso interno — Sportium.
