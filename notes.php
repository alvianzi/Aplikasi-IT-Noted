<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$method = $_SERVER['REQUEST_METHOD'];

function row_to_note(array $r): array {
    return [
        'id' => (int)$r['id'],
        'title' => $r['title'],
        'body' => $r['body'],
        'checklist' => $r['checklist_json'] ? json_decode($r['checklist_json'], true) : null,
        'color' => $r['color'],
        'textColor' => $r['text_color'] ?: null,
        'fontFamily' => $r['font_family'] ?: 'default',
        'tags' => $r['tags_json'] ? json_decode($r['tags_json'], true) : [],
        'images' => $r['images_json'] ? json_decode($r['images_json'], true) : [],
        'pinned' => (bool)$r['pinned'],
        'archived' => (bool)$r['archived'],
        'trashed' => (bool)$r['trashed'],
        'createdBy' => $r['created_by_name'] ?? null,
        'updatedBy' => $r['updated_by_name'] ?? null,
        'updatedAt' => $r['updated_at'],
    ];
}

// ---------- GET: ambil semua catatan (tim berbagi semuanya) ----------
if ($method === 'GET') {
    $sql = "SELECT n.*, cu.display_name AS created_by_name, uu.display_name AS updated_by_name
            FROM notes n
            JOIN users cu ON cu.id = n.created_by
            LEFT JOIN users uu ON uu.id = n.updated_by
            ORDER BY n.updated_at DESC";
    $rows = db()->query($sql)->fetchAll();
    json_response(['notes' => array_map('row_to_note', $rows)]);
}

// Semua method di bawah ini mengubah data → hanya admin, dan wajib CSRF token
ensure_can_edit($me);
require_csrf();

$VALID_FONTS = ['default', 'serif', 'mono', 'handwriting'];
function valid_hex_or_null($v) {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $v ?? '') ? $v : null;
}

// ---------- POST: buat catatan baru ----------
if ($method === 'POST') {
    $b = read_json_body();
    $stmt = db()->prepare(
        'INSERT INTO notes (title, body, checklist_json, color, text_color, font_family, tags_json, images_json, pinned, created_by, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        substr(trim($b['title'] ?? ''), 0, 255),
        $b['body'] ?? '',
        isset($b['checklist']) && is_array($b['checklist']) ? json_encode(array_values($b['checklist'])) : null,
        valid_hex_or_null($b['color'] ?? null) ?: '#F3E1AE',
        valid_hex_or_null($b['textColor'] ?? null),
        in_array($b['fontFamily'] ?? '', $VALID_FONTS, true) ? $b['fontFamily'] : 'default',
        isset($b['tags']) && is_array($b['tags']) ? json_encode(array_values($b['tags'])) : json_encode([]),
        isset($b['images']) && is_array($b['images']) ? json_encode(array_values(array_map('basename', $b['images']))) : json_encode([]),
        !empty($b['pinned']) ? 1 : 0,
        $me['id'], $me['id'],
    ]);
    json_response(['id' => (int)db()->lastInsertId()], 201);
}

// ---------- PUT: ubah catatan (isi, warna, pin, arsip, sampah, label) ----------
if ($method === 'PUT') {
    $b = read_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_response(['error' => 'id wajib diisi'], 400);

    $fields = []; $params = [];
    $map = [
        'title' => 'title', 'body' => 'body',
        'pinned' => 'pinned', 'archived' => 'archived', 'trashed' => 'trashed',
    ];
    foreach ($map as $key => $col) {
        if (array_key_exists($key, $b)) {
            $fields[] = "$col = ?";
            $params[] = in_array($key, ['pinned','archived','trashed']) ? (!empty($b[$key]) ? 1 : 0) : $b[$key];
        }
    }
    if (array_key_exists('color', $b)) {
        $fields[] = 'color = ?';
        $params[] = valid_hex_or_null($b['color']) ?: '#F3E1AE';
    }
    if (array_key_exists('textColor', $b)) {
        $fields[] = 'text_color = ?';
        $params[] = valid_hex_or_null($b['textColor']); // null = ikut warna default tema
    }
    if (array_key_exists('fontFamily', $b)) {
        $fields[] = 'font_family = ?';
        $params[] = in_array($b['fontFamily'], $VALID_FONTS, true) ? $b['fontFamily'] : 'default';
    }
    if (array_key_exists('checklist', $b)) {
        $fields[] = 'checklist_json = ?';
        $params[] = is_array($b['checklist']) ? json_encode(array_values($b['checklist'])) : null;
    }
    if (array_key_exists('tags', $b)) {
        $fields[] = 'tags_json = ?';
        $params[] = is_array($b['tags']) ? json_encode(array_values($b['tags'])) : json_encode([]);
    }
    if (array_key_exists('images', $b)) {
        $fields[] = 'images_json = ?';
        $params[] = is_array($b['images']) ? json_encode(array_values(array_map('basename', $b['images']))) : json_encode([]);
    }
    if (!$fields) json_response(['error' => 'Tidak ada perubahan'], 400);

    $fields[] = 'updated_by = ?'; $params[] = $me['id'];
    $params[] = $id;
    $sql = 'UPDATE notes SET ' . implode(', ', $fields) . ' WHERE id = ?';
    db()->prepare($sql)->execute($params);
    json_response(['ok' => true]);
}

// ---------- DELETE: hapus permanen (hanya dari Sampah) ----------
if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $id = (int)($q['id'] ?? 0);
    if (!$id) json_response(['error' => 'id wajib diisi'], 400);
    $stmt = db()->prepare('DELETE FROM notes WHERE id = ? AND trashed = 1');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

json_response(['error' => 'Metode tidak didukung'], 405);
