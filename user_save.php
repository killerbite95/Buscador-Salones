<?php
require_once 'auth.php';
require_once 'security.php';
require_once 'db.php';

setSecurityHeaders();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php#usuarios');
    exit;
}
csrfVerify();

$action   = $_POST['action']   ?? '';
$id       = (int)($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$perms    = $_POST['perms'] ?? 'both';
$active   = isset($_POST['active']) ? 1 : 0;

$validPerms = ['salones', 'pisignage', 'both'];
if (!in_array($perms, $validPerms, true)) {
    header('Location: admin.php?error=' . urlencode('Permiso no válido.') . '#usuarios');
    exit;
}

$pdo = getDB();


if ($action === 'create') {
    if ($username === '' || $password === '') {
        header('Location: admin.php?error=' . urlencode('Usuario y contraseña son obligatorios.') . '#usuarios');
        exit;
    }
    if (mb_strtolower($username) === mb_strtolower(ADMIN_USER)) {
        header('Location: admin.php?error=' . urlencode('Ese nombre de usuario está reservado.') . '#usuarios');
        exit;
    }
    $exists = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $exists->execute([$username]);
    if ($exists->fetch()) {
        header('Location: admin.php?error=' . urlencode("El usuario «{$username}» ya existe.") . '#usuarios');
        exit;
    }
    $pdo->prepare("INSERT INTO users (username, password, perms, active) VALUES (?, ?, ?, ?)")
        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $perms, 1]);
    header('Location: admin.php?msg=' . urlencode("✓ Usuario «{$username}» creado.") . '#usuarios');
    exit;
}


if ($action === 'edit') {
    if ($id <= 0) {
        header('Location: admin.php#usuarios'); exit;
    }
    $row = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $row->execute([$id]);
    $user = $row->fetch();
    if (!$user) {
        header('Location: admin.php?error=' . urlencode('Usuario no encontrado.') . '#usuarios'); exit;
    }
    if (mb_strtolower($username) === mb_strtolower(ADMIN_USER)) {
        header('Location: admin.php?error=' . urlencode('Ese nombre de usuario está reservado.') . '#usuarios'); exit;
    }
    $newHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $user['password'];
    $pdo->prepare("UPDATE users SET username=?, password=?, perms=?, active=? WHERE id=?")
        ->execute([$username ?: $user['username'], $newHash, $perms, $active, $id]);
    header('Location: admin.php?msg=' . urlencode("✓ Usuario actualizado.") . '#usuarios');
    exit;
}


if ($action === 'delete') {
    if ($id <= 0) {
        header('Location: admin.php#usuarios'); exit;
    }
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    header('Location: admin.php?msg=' . urlencode("✓ Usuario eliminado.") . '#usuarios');
    exit;
}

header('Location: admin.php#usuarios');
