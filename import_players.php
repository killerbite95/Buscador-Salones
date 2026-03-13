<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';
require_once 'helpers.php';

setSecurityHeaders();
requireLogin();
if (!canImportPisignage()) {
    header('Location: admin.php?error=' . urlencode('No tienes permiso para importar PiSignage.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}
csrfVerify();

$file = $_FILES['csv'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    header('Location: admin.php?error=' . urlencode('Error al subir el archivo de PiSignage.'));
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt'], true)) {
    header('Location: admin.php?error=' . urlencode('Solo se aceptan archivos .csv o .txt'));
    exit;
}

$raw  = file_get_contents($file['tmp_name']);
$text = ensureUtf8($raw);

[$headers, $rows] = parseCSV($text);

if (empty($rows)) {
    header('Location: admin.php?error=' . urlencode('El archivo PlayersListMongo no contiene filas.'));
    exit;
}

// Detectar columnas del CSV de PiSignage
$nameCol     = null;
$ipCol       = null;
$playlistCol = null;
$lastRepCol  = null;

foreach ($headers as $h) {
    $n = normStr($h);
    if ($n === 'name')             $nameCol     = $h;
    if ($n === 'myipaddress' || str_contains($n, 'ip'))       $ipCol       = $h;
    if ($n === 'currentplaylist' || str_contains($n, 'playlist')) $playlistCol = $h;
    if ($n === 'lastreported'   || str_contains($n, 'reported'))  $lastRepCol  = $h;
}

if (!$nameCol) {
    header('Location: admin.php?error=' . urlencode(
        'No se encontró la columna "name" en el CSV de PiSignage. ' .
        'Cabeceras: ' . implode(', ', array_slice($headers, 0, 6))
    ));
    exit;
}

$pdo = getDB();
$pdo->exec("BEGIN TRANSACTION");
$pdo->exec("DELETE FROM pisignage_players");

$stmt = $pdo->prepare("
    INSERT INTO pisignage_players (name, codigo, screen, ip_address, playlist, last_reported)
    VALUES (:name, :codigo, :screen, :ip, :playlist, :last_reported)
");

$imported = 0;
foreach ($rows as $row) {
    $name = trim($row[$nameCol] ?? '');
    if ($name === '') continue;

    $codigo = extractCodeFromPlayerName($name);
    if (!$codigo) continue;

    $screen   = extractScreenFromPlayerName($name);
    $ip       = trim($row[$ipCol]       ?? '');
    $playlist = trim($row[$playlistCol] ?? '');
    $lastRep  = trim($row[$lastRepCol]  ?? '');

    $stmt->execute([
        ':name'         => $name,
        ':codigo'       => $codigo,
        ':screen'       => $screen,
        ':ip'           => $ip,
        ':playlist'     => $playlist,
        ':last_reported'=> $lastRep,
    ]);
    $imported++;
}

$pdo->prepare("INSERT INTO pisignage_imports (filename, total_rows) VALUES (?, ?)")
    ->execute([$file['name'], $imported]);

$pdo->exec("COMMIT");

header('Location: admin.php?msg=' . urlencode(
    "✓ PiSignage importado: {$imported} players cargados desde «{$file['name']}»."
));
exit;
