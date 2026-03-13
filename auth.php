<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

function startSession(): void
{
    configureSession();
}

function isLoggedIn(): bool
{
    startSession();
    return !empty($_SESSION['logged_in']);
}

function isAdmin(): bool
{
    startSession();
    return !empty($_SESSION['is_admin']);
}

function currentPerms(): string
{
    startSession();
    return $_SESSION['perms'] ?? 'both';
}

function canAccessAdmin(): bool
{
    if (!isLoggedIn()) return false;
    return currentPerms() !== 'viewer';
}

function canImportSalones(): bool
{
    if (!isLoggedIn()) return false;
    $p = currentPerms();
    return $p === 'all' || $p === 'salones' || $p === 'both';
}

function canImportPisignage(): bool
{
    if (!isLoggedIn()) return false;
    $p = currentPerms();
    return $p === 'all' || $p === 'pisignage' || $p === 'both';
}

function currentUsername(): string
{
    startSession();
    return $_SESSION['username'] ?? '';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: admin.php');
        exit;
    }
}

function doLogin(string $user, string $pass): bool
{
    $ip = clientIp();

    if (hash_equals(ADMIN_USER, $user) && password_verify($pass, ADMIN_PASS)) {
        startSession();
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin']  = true;
        $_SESSION['username']  = ADMIN_USER;
        $_SESSION['perms']     = 'all';
        $_SESSION['_last_regen'] = time();
        loginClearAttempts($ip);
        return true;
    }

    require_once __DIR__ . '/db.php';
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1");
    $stmt->execute([trim($user)]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password'])) {
        startSession();
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin']  = false;
        $_SESSION['username']  = $row['username'];
        $_SESSION['perms']     = $row['perms'];
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['_last_regen'] = time();
        loginClearAttempts($ip);
        return true;
    }

    loginRecordFailure($ip);
    return false;
}

function doLogout(): void
{
    startSession();
    session_destroy();
}
