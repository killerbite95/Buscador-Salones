<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA journal_mode = WAL");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS salones (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo    TEXT NOT NULL,
            nombre    TEXT NOT NULL,
            router    TEXT NOT NULL DEFAULT '',
            ip_ssbt   TEXT NOT NULL DEFAULT '',
            ip_pos    TEXT NOT NULL DEFAULT '',
            ip_albos  TEXT NOT NULL DEFAULT '',
            pulgadas  TEXT NOT NULL DEFAULT '',
            config_tv TEXT NOT NULL DEFAULT '',
            sis       TEXT NOT NULL DEFAULT '',
            datos_sis TEXT NOT NULL DEFAULT '',
            arc       TEXT NOT NULL DEFAULT '',
            datos_arc TEXT NOT NULL DEFAULT ''
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_codigo ON salones (codigo)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_nombre ON salones (nombre)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS imports (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            filename    TEXT    NOT NULL,
            total_rows  INTEGER NOT NULL,
            imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pisignage_players (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            codigo        TEXT NOT NULL,
            screen        TEXT NOT NULL DEFAULT '',
            ip_address    TEXT NOT NULL DEFAULT '',
            playlist      TEXT NOT NULL DEFAULT '',
            last_reported TEXT NOT NULL DEFAULT ''
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pi_codigo ON pisignage_players (codigo)");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pisignage_imports (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            filename    TEXT    NOT NULL,
            total_rows  INTEGER NOT NULL,
            imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT NOT NULL UNIQUE COLLATE NOCASE,
            password   TEXT NOT NULL,
            perms      TEXT NOT NULL DEFAULT 'both'
                           CHECK(perms IN ('viewer','salones','pisignage','both')),
            active     INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    
    $tableInfo = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
    if ($tableInfo && strpos($tableInfo, "'viewer'") === false) {
        $pdo->exec("ALTER TABLE users RENAME TO users_old");
        $pdo->exec("
            CREATE TABLE users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                username   TEXT NOT NULL UNIQUE COLLATE NOCASE,
                password   TEXT NOT NULL,
                perms      TEXT NOT NULL DEFAULT 'both'
                               CHECK(perms IN ('viewer','salones','pisignage','both')),
                active     INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("INSERT INTO users SELECT * FROM users_old");
        $pdo->exec("DROP TABLE users_old");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            action      TEXT NOT NULL,
            target_user TEXT NOT NULL,
            details     TEXT NOT NULL DEFAULT '',
            performed_by TEXT NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    return $pdo;
}
