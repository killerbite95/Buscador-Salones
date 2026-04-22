# Despliegue en XAMPP

Guía rápida para desplegar **Buscador de Salones** en XAMPP instalado en `C:\xampp`.

## Paso 1: Copiar archivos al htdocs

Copia la carpeta del proyecto a:
```
C:\xampp\htdocs\buscador-salones
```

O symlink (en PowerShell admin):
```powershell
New-Item -ItemType SymbolicLink -Path "C:\xampp\htdocs\buscador-salones" -Target "C:\Users\Killerbite\Desktop\N1-Buscador Salones"
```

## Paso 2: Generar hash bcrypt para la contraseña admin

Abre una terminal y usa el PHP de XAMPP:

```bash
C:\xampp\php\php.exe -r "echo password_hash('CONTRASEÑA-USUARIO', PASSWORD_DEFAULT);"
```

**Ejemplo:**
```bash
C:\xampp\php\php.exe -r "echo password_hash('sportium2026', PASSWORD_DEFAULT);"
```

La salida será algo como:
```
$2y$10$Sel0g5A/mbrGl28hIz5ESuXrQ./UTLDNnGNYB833sH1Nxnbj6ytN6
```

Copia este hash (será tu `ADMIN_PASS`).

## Paso 3: Configurar config.php

Navega a `C:\xampp\htdocs\buscador-salones\` y:

1. **Copia el archivo de plantilla:**
   ```bash
   copy config.example.php config.php
   ```

2. **Edita `config.php`** con tu editor favorito y reemplaza `ADMIN_PASS`:
   ```php
   define('DB_PATH',   __DIR__.'/data/salones.db');

   define('ADMIN_USER', 'admin');
   define('ADMIN_PASS', '$2y$10$Sel0g5A/mbrGl28hIz5ESuXrQ./UTLDNnGNYB833sH1Nxnbj6ytN6');

   define('APP_NAME', 'Buscador de Salones · Sportium');
   ```

   Reemplaza el valor entre comillas de `ADMIN_PASS` con el hash que generaste en el Paso 2.

3. **Guarda el archivo.**

## Paso 4: Crear la carpeta data con permisos

Asegúrate de que existe la carpeta `data`:
```bash
mkdir C:\xampp\htdocs\buscador-salones\data
```

XAMPP necesita permisos de escritura. Generalmente ya están correctos, pero si tienes problemas:
- Click derecho en la carpeta `data` → **Propiedades** → **Seguridad** → **Editar** → Asegúrate de que `SYSTEM`, `Administradores` y el usuario actual tengan permisos de lectura/escritura.

## Paso 5: Iniciar XAMPP y acceder

1. Abre **XAMPP Control Panel**
2. Inicia el servicio **Apache**
3. Abre el navegador y ve a:
   ```
   http://localhost/buscador-salones
   ```

4. Accede con:
   - Usuario: `admin`
   - Contraseña: la que usaste en el Paso 2 (sin hashear)

## Paso 6: Importar datos (primeros pasos)

Una vez dentro:

1. Accede a **Admin** (`/admin.php`)
2. Sube el CSV de salones (Insight/Jira export)
3. Sube el `PlayersListMongo.csv` (PiSignage)
4. Ya está listo para buscar

## Notas importantes

- **BD SQLite**: se crea automáticamente en `data/salones.db` la primera vez
- **Permisos**: asegúrate de que Apache tiene permisos de escritura en la carpeta `data/`
- **PHP 8.0+**: XAMPP debe tener PHP 8.0 o superior (generalmente sí)
- **pdo_sqlite**: debe estar habilitado en `php.ini` (está por defecto)

## Troubleshooting

**"No compatible SQLite"**
- Edita `C:\xampp\php\php.ini` y asegúrate de que `extension=pdo_sqlite` no está comentado

**"Permission denied" en data/**
- Dale permisos de escritura a la carpeta: click derecho → Propiedades → Seguridad → Permisos completos

**"Call to undefined function password_hash"**
- Requiere PHP 5.5+, que XAMPP tiene por defecto. Si aún así falla, actualiza XAMPP.

---

## Actualizar desde Git

Si quieres traer nuevos cambios del repositorio:

```bash
cd C:\xampp\htdocs\buscador-salones
git pull origin main
```

⚠️ **Nota:** no overwrite `config.php` (está en `.gitignore`).
