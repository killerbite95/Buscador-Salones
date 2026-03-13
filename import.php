<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';
require_once 'helpers.php';

setSecurityHeaders();
requireLogin();
if (!canAccessAdmin() || !canImportSalones()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}
csrfVerify();

$file = $_FILES['csv'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera MAX_FILE_SIZE del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
    ];
    $msg = $codes[$file['error'] ?? -1] ?? 'Error desconocido al subir el archivo.';
    header('Location: admin.php?error=' . urlencode($msg));
    exit;
}

if ($file['size'] > 256 * 1024 * 1024) {
    header('Location: admin.php?error=' . urlencode('El archivo supera el límite de 256 MB.'));
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
    header('Location: admin.php?error=' . urlencode('El archivo no contiene filas de datos.'));
    exit;
}

['nameKey' => $nameKey, 'mapKeys' => $mapKeys] = detectColumns($headers);

if (!$nameKey) {
    header('Location: admin.php?error=' . urlencode(
        'No se detectó la columna "Nombre" en el CSV. ' .
        'Cabeceras encontradas: ' . implode(', ', array_slice($headers, 0, 8)) . '…'
    ));
    exit;
}

$pdo = getDB();
$pdo->exec("BEGIN TRANSACTION");
$pdo->exec("DELETE FROM salones");

$stmt = $pdo->prepare("
    INSERT INTO salones
        (codigo, nombre, router, ip_ssbt, ip_pos, ip_albos, pulgadas, config_tv, sis, datos_sis, arc, datos_arc)
    VALUES
        (:codigo, :nombre, :router, :ip_ssbt, :ip_pos, :ip_albos, :pulgadas, :config_tv, :sis, :datos_sis, :arc, :datos_arc)
");

$get = fn(string $k): string => !empty($mapKeys[$k]) ? trim($rows[0][$mapKeys[$k]] ?? '') : '';
$imported = 0;

foreach ($rows as $row) {
    $nombre = trim($row[$nameKey] ?? '');
    if ($nombre === '') continue;

    $codigo = extractCode($nombre);
    if (!$codigo) continue;

    $val = fn(string $k): string => !empty($mapKeys[$k]) ? trim($row[$mapKeys[$k]] ?? '') : '';

    $stmt->execute([
        ':codigo'    => $codigo,
        ':nombre'    => $nombre,
        ':router'    => $val('router'),
        ':ip_ssbt'   => $val('ip_ssbt'),
        ':ip_pos'    => $val('ip_pos'),
        ':ip_albos'  => $val('ip_albos'),
        ':pulgadas'  => $val('pulgadas'),
        ':config_tv' => $val('config_tv'),
        ':sis'       => $val('sis'),
        ':datos_sis' => $val('datos_sis'),
        ':arc'       => $val('arc'),
        ':datos_arc' => $val('datos_arc'),
    ]);
    $imported++;
}

$pdo->prepare("INSERT INTO imports (filename, total_rows) VALUES (?, ?)")
    ->execute([$file['name'], $imported]);

$pdo->exec("COMMIT");

header('Location: admin.php?msg=' . urlencode(
    "✓ Importación completada: {$imported} salones cargados desde «{$file['name']}»."
));
exit;
