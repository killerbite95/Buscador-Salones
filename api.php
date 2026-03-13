<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

header('Content-Type: application/json; charset=UTF-8');
setSecurityHeaders();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado.']);
    exit;
}

$pdo     = getDB();
$q       = trim($_GET['q'] ?? '');
$suggest = !empty($_GET['suggest']);

if ($q === '') {
    echo json_encode(['error' => 'Parámetro q requerido.']);
    exit;
}

$digits = preg_replace('/\D+/', '', $q);

if ($suggest) {
    $results = [];

    if ($digits !== '') {
        $stmt = $pdo->prepare("
            SELECT codigo, nombre FROM salones
            WHERE codigo LIKE :prefix
            ORDER BY LENGTH(codigo), codigo
            LIMIT 10
        ");
        $stmt->execute([':prefix' => $digits . '%']);
        $results = $stmt->fetchAll();
    }

    if (count($results) < 10 && mb_strlen($q) >= 2) {
        $limit = 10 - count($results);
        $stmt  = $pdo->prepare("
            SELECT codigo, nombre FROM salones
            WHERE LOWER(nombre) LIKE :q
            LIMIT :lim
        ");
        $stmt->bindValue(':q',   '%' . mb_strtolower($q, 'UTF-8') . '%');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $extra    = $stmt->fetchAll();
        $existing = array_column($results, 'codigo');
        foreach ($extra as $r) {
            if (!in_array($r['codigo'], $existing, true)) {
                $results[] = $r;
            }
        }
    }

    echo json_encode(array_values($results));
    exit;
}

// Búsqueda completa
$stmt = null;
$salon = null;

// Try exact code match first (digits only)
if ($digits !== '') {
    $stmt = $pdo->prepare("SELECT * FROM salones WHERE codigo = ? LIMIT 1");
    $stmt->execute([$digits]);
    $salon = $stmt->fetch();

    if (!$salon) {
        $stmt = $pdo->prepare("SELECT * FROM salones WHERE codigo LIKE ? ORDER BY LENGTH(codigo) LIMIT 1");
        $stmt->execute([$digits . '%']);
        $salon = $stmt->fetch();
    }
}

// If no match by code, try name search
if (!$salon && mb_strlen($q) >= 2) {
    $stmt = $pdo->prepare("SELECT * FROM salones WHERE LOWER(nombre) LIKE ? LIMIT 1");
    $stmt->execute(['%' . mb_strtolower($q, 'UTF-8') . '%']);
    $salon = $stmt->fetch();
}

if (!$salon) {
    $suggestions = [];
    if ($digits !== '') {
        $stmt = $pdo->prepare("
            SELECT codigo, nombre FROM salones
            WHERE codigo LIKE :a OR codigo LIKE :b
            ORDER BY LENGTH(codigo)
            LIMIT 8
        ");
        $stmt->execute([':a' => $digits . '%', ':b' => '%' . $digits]);
        $suggestions = $stmt->fetchAll();
    }
    if (count($suggestions) < 8 && mb_strlen($q) >= 2) {
        $limit = 8 - count($suggestions);
        $stmt  = $pdo->prepare("SELECT codigo, nombre FROM salones WHERE LOWER(nombre) LIKE ? LIMIT ?");
        $stmt->bindValue(1, '%' . mb_strtolower($q, 'UTF-8') . '%');
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $existing = array_column($suggestions, 'codigo');
        foreach ($stmt->fetchAll() as $r) {
            if (!in_array($r['codigo'], $existing, true)) {
                $suggestions[] = $r;
            }
        }
    }
    echo json_encode(['not_found' => true, 'q' => $q, 'suggestions' => $suggestions]);
    exit;
}

// Incluir players PiSignage si los hay para este código
$pi = $pdo->prepare("
    SELECT name, screen, ip_address, playlist, last_reported
    FROM pisignage_players
    WHERE codigo = ?
    ORDER BY screen
");
$pi->execute([$salon['codigo']]);
$players = $pi->fetchAll();

echo json_encode(['salon' => $salon, 'pisignage' => $players]);
