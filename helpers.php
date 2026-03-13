<?php

function normStr(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $from = ['á','é','í','ó','ú','ü','ñ','à','è','ì','ò','ù','â','ê','î','ô','û',
             'Á','É','Í','Ó','Ú','Ü','Ñ','À','È','Ì','Ò','Ù'];
    $to   = ['a','e','i','o','u','u','n','a','e','i','o','u','a','e','i','o','u',
             'a','e','i','o','u','u','n','a','e','i','o','u'];
    $s = str_replace($from, $to, $s);
    $s = preg_replace('/[^a-z0-9\/]+/', ' ', $s);
    return trim($s);
}

function guessHeader(array $headers, array $candidates): ?string
{
    $normed = array_map(fn($h) => [normStr($h), $h], $headers);

    foreach ($candidates as $cand) {
        $n = normStr($cand);
        foreach ($normed as [$nn, $raw]) {
            if ($nn === $n) return $raw;
        }
    }

    foreach ($candidates as $cand) {
        $n = normStr($cand);
        foreach ($normed as [$nn, $raw]) {
            $tokens = preg_split('/\s+/', $nn, -1, PREG_SPLIT_NO_EMPTY);
            if (in_array($n, $tokens, true)) return $raw;
        }
    }

    foreach ($candidates as $cand) {
        $n = normStr($cand);
        $matches = [];
        foreach ($normed as [$nn, $raw]) {
            if (str_contains($nn, $n)) {
                $matches[] = [$nn, $raw];
            }
        }
        if ($matches) {
            usort($matches, fn($a, $b) => strlen($a[0]) <=> strlen($b[0]));
            return $matches[0][1];
        }
    }
    return null;
}

function wantedMap(): array
{
    return [
        'router'    => ['router'],
        'ip_ssbt'   => ['ip ssbt', 'ssbt', 'ipssbt', 'ssbt ip'],
        'ip_pos'    => ['ip pos', 'pos', 'ippos', 'pos ip'],
        'ip_albos'  => ['ip albos/multi', 'ip albos multi', 'ip albos', 'albos/multi', 'albos multi', 'albos', 'ip multi', 'multi'],
        'pulgadas'  => ['pulgadas tv', 'pulgadas', 'tamano tv'],
        'config_tv' => ['configuracion tv no de pantallas', 'configuracion tv', 'no de pantallas', 'config tv', 'pantallas'],
        'sis'       => ['sis'],
        'datos_sis' => ['datos sis', 'sis datos'],
        'arc'       => ['arc'],
        'datos_arc' => ['datos arc', 'arc datos'],
    ];
}

function detectColumns(array $headers): array
{
    $nameKey = guessHeader($headers, ['nombre', 'sala', 'salon', 'local', 'nombre sala', 'name', 'descripcion']);
    $mapKeys = [];
    foreach (wantedMap() as $k => $candidates) {
        $mapKeys[$k] = guessHeader($headers, $candidates);
    }
    return ['nameKey' => $nameKey, 'mapKeys' => $mapKeys];
}

/** ESGA80_T4025_D02 → "4025" */
function extractCodeFromPlayerName(string $name): ?string
{
    $parts = explode('_', $name);
    if (count($parts) < 2) return null;
    for ($i = 1; $i < count($parts) - 1; $i++) {
        if (preg_match('/^[A-Za-z]+(\d+)$/', $parts[$i], $m)) {
            return $m[1];
        }
    }
    return null;
}

/** ESGA80_T4025_D02 → "D02" */
function extractScreenFromPlayerName(string $name): string
{
    $parts = explode('_', $name);
    return end($parts) ?: '';
}

function extractCode(string $name): ?string
{
    if (preg_match('/(?:^|\D)(\d{4,6})(?!\d)/', $name, $m)) {
        return $m[1];
    }
    $all = preg_replace('/\D+/', '', $name);
    return $all !== '' ? $all : null;
}

function ensureUtf8(string $raw): string
{
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }
    if (mb_check_encoding($raw, 'UTF-8')) {
        return $raw;
    }
    return mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
}

function parseCSV(string $text): array
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $text);
    rewind($stream);

    $headers = null;
    $rows    = [];

    while (($cols = fgetcsv($stream)) !== false) {
        if ($headers === null) {
            $headers = $cols;
            continue;
        }
        $cols   = array_slice(array_pad($cols, count($headers), ''), 0, count($headers));
        $rows[] = array_combine($headers, $cols);
    }
    fclose($stream);

    return [$headers ?? [], $rows];
}

/**
 * Write an entry to the audit log.
 */
function auditLog(PDO $pdo, string $action, string $target, string $details): void
{
    $pdo->prepare("INSERT INTO audit_log (action, target_user, details, performed_by) VALUES (?, ?, ?, ?)")
        ->execute([$action, $target, $details, currentUsername()]);
}
