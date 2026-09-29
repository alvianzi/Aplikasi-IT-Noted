<?php
require_once __DIR__ . '/config.php';
$me = require_admin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = db()->query('SELECT id, username, display_name, is_admin, is_active, created_at FROM users ORDER BY id')->fetchAll();
    json_response(['users' => $rows]);
}

require_csrf();

// ---------- POST: tambah user baru ----------
if ($method === 'POST') {
    $b = read_json_body();
    $username = trim($b['username'] ?? '');
    $displayName = trim($b['displayName'] ?? '');
    $password = (string)($b['password'] ?? '');
    $isAdmin = !empty($b['isAdmin']) ? 1 : 0;

    if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
        json_response(['error' => 'Username 3-50 karakter, hanya huruf/angka/underscore/titik'], 400);
    }
    if ($displayName === '') json_response(['error' => 'Nama tampilan wajib diisi'], 400);
    if (strlen($password) < 8) json_response(['error' => 'Kata sandi minimal 8 karakter'], 400);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = db()->prepare('INSERT INTO users (username, password_hash, display_name, is_admin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $hash, $displayName, $isAdmin]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') json_response(['error' => 'Username sudah dipakai'], 409);
        throw $e;
    }
    json_response(['id' => (int)db()->lastInsertId()], 201);
}

// ---------- PUT: aktif/nonaktifkan user, atau ganti kata sandi ----------
if ($method === 'PUT') {
    $b = read_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_response(['error' => 'id wajib diisi'], 400);
    if ($id === (int)$me['id'] && array_key_exists('isActive', $b) && empty($b['isActive'])) {
        json_response(['error' => 'Tidak bisa menonaktifkan akun sendiri'], 400);
    }

    if ($id === (int)$me['id'] && array_key_exists('isAdmin', $b) && empty($b['isAdmin'])) {
        json_response(['error' => 'Tidak bisa mencabut hak admin akun sendiri'], 400);
    }

    $fields = []; $params = [];
    if (array_key_exists('isActive', $b)) { $fields[] = 'is_active = ?'; $params[] = !empty($b['isActive']) ? 1 : 0; }
    if (array_key_exists('isAdmin', $b)) { $fields[] = 'is_admin = ?'; $params[] = !empty($b['isAdmin']) ? 1 : 0; }
    if (!empty($b['newPassword'])) {
        if (strlen($b['newPassword']) < 8) json_response(['error' => 'Kata sandi minimal 8 karakter'], 400);
        $fields[] = 'password_hash = ?'; $params[] = password_hash($b['newPassword'], PASSWORD_DEFAULT);
        $fields[] = 'failed_attempts = 0'; $fields[] = 'locked_until = NULL';
    }
    if (!$fields) json_response(['error' => 'Tidak ada perubahan'], 400);

    $params[] = $id;
    db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    json_response(['ok' => true]);
}

// ---------- DELETE: hapus anggota ----------
if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if (!$id) json_response(['error' => 'id wajib diisi'], 400);
    if ($id === (int)$me['id']) json_response(['error' => 'Tidak bisa menghapus akun sendiri'], 400);

    $pdo = db();
    $exists = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) json_response(['error' => 'Pengguna tidak ditemukan'], 404);

    // Catatan milik akun ini TIDAK ikut dihapus: dipindahkan ke admin yang menghapus,
    // karena tabel notes mewajibkan pembuat (created_by) selalu ada.
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE notes SET created_by = ? WHERE created_by = ?')->execute([$me['id'], $id]);
        $pdo->prepare('UPDATE notes SET updated_by = NULL WHERE updated_by = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_response(['ok' => true]);
}

json_response(['error' => 'Metode tidak didukung'], 405);
