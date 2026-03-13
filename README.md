# Buscador de Salones · Sportium

Herramienta interna para buscar información de red, televisión y contenido de los salones de Sportium. Incluye integración con PiSignage para monitorizar pantallas.

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
define('ADMIN_PASS', 'tu_contraseña');
```

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
- Autocomplete sugiere resultados mientras escribes
- Los resultados muestran: red, televisión, contenido y pantallas PiSignage
- Las IPs tienen botón de copiar al portapapeles

### Usuarios

El admin puede crear usuarios desde el panel con 3 niveles de permisos:

| Permiso | Puede importar |
|---|---|
| `salones` | Solo CSV de salones |
| `pisignage` | Solo CSV de PiSignage |
| `both` | Ambos |

## Estructura

```
├── config.php          # Credenciales y configuración
├── config.example.php  # Plantilla de configuración
├── db.php              # Conexión SQLite y esquema
├── auth.php            # Sesiones y permisos
├── security.php        # CSRF, rate limiting, headers
├── helpers.php         # Parseo CSV y detección de columnas
├── index.php           # Buscador principal
├── admin.php           # Panel de administración
├── api.php             # API JSON (búsqueda + sugerencias)
├── login.php           # Login
├── logout.php          # Logout
├── import.php          # Importación CSV salones
├── import_players.php  # Importación CSV PiSignage
├── user_save.php       # CRUD de usuarios
├── data/
│   ├── .htaccess       # Bloquea acceso directo a la BD
│   └── salones.db      # Base de datos SQLite (auto-generada)
└── images/
    └── logo-sp.png     # Logo Sportium
```

## Seguridad

- Tokens CSRF en todos los formularios
- Rate limiting en login (10 intentos / 15 min por IP)
- Sesiones seguras (HttpOnly, SameSite=Strict, regeneración cada 20 min)
- Cabeceras HTTP de seguridad (CSP, X-Frame-Options, etc.)
- Todas las páginas requieren autenticación

## Licencia

Uso interno — Sportium.
