<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = db()->query('SELECT name FROM labels ORDER BY name')->fetchAll();
    json_response(['labels' => array_column($rows, 'name')]);
}

ensure_can_edit($me);
require_csrf();

if ($method === 'POST') {
    $b = read_json_body();
    $name = trim($b['name'] ?? '');
    if ($name === '' || mb_strlen($name) > 30) {
        json_response(['error' => 'Nama label wajib diisi, maksimal 30 karakter'], 400);
    }
    try {
        db()->prepare('INSERT INTO labels (name) VALUES (?)')->execute([$name]);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e; // sudah ada = tidak masalah, anggap berhasil
    }
    json_response(['ok' => true, 'name' => $name], 201);
}

// ---------- DELETE: hapus label + lepas dari semua catatan yang memakainya ----------
if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
    $name = trim($q['name'] ?? '');
    if ($name === '') json_response(['error' => 'Nama label wajib diisi'], 400);

    db()->prepare('DELETE FROM labels WHERE name = ?')->execute([$name]);

    // Lepas label ini dari tags_json semua catatan yang memakainya
    $rows = db()->prepare("SELECT id, tags_json FROM notes WHERE tags_json LIKE ?");
    $rows->execute(['%' . $name . '%']); // saringan awal cepat, verifikasi asli lewat json_decode di bawah
    foreach ($rows->fetchAll() as $row) {
        $tags = json_decode($row['tags_json'], true);
        if (!is_array($tags) || !in_array($name, $tags, true)) continue;
        $tags = array_values(array_filter($tags, function($t) use ($name) { return $t !== $name; }));
        db()->prepare('UPDATE notes SET tags_json = ? WHERE id = ?')->execute([json_encode($tags), $row['id']]);
    }

    json_response(['ok' => true]);
}

json_response(['error' => 'Metode tidak didukung'], 405);
