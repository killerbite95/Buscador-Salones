<?php

function setSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-XSS-Protection: 1; mode=block');
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
        "style-src  'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
        "font-src   'self' https://cdn.jsdelivr.net; " .
        "img-src    'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none';"
    );
}


function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken()) . '">';
}

function csrfVerify(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token     = $_POST['_csrf'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $token)) {
        http_response_code(403);
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $back = (str_starts_with($ref, 'http://localhost') || str_starts_with($ref, 'http://127.') ||
                 str_starts_with($ref, 'https://'))
            ? $ref : 'login.php';
        header('Location: ' . $back . '?error=' . urlencode('Solicitud no válida (CSRF). Inténtalo de nuevo.'));
        exit;
    }
}

function loginCheckRateLimit(string $ip): bool
{
    require_once __DIR__ . '/db.php';
    $pdo = getDB();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ip         TEXT    NOT NULL,
            attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts (ip)");

    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-15 minutes')");

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $count = (int) $stmt->fetchColumn();

    return $count >= 10;
}

function loginRecordFailure(string $ip): void
{
    require_once __DIR__ . '/db.php';
    $pdo = getDB();
    $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)")->execute([$ip]);
}

function loginClearAttempts(string $ip): void
{
    require_once __DIR__ . '/db.php';
    $pdo = getDB();
    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function configureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;

    session_name('SPORTIUM_SID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    if (!isset($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1200) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}
