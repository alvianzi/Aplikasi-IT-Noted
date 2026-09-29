<?php
require_once __DIR__ . '/config.php';
$me = require_login();
ensure_can_edit($me);
require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Metode tidak didukung'], 405);
}

if (empty($_FILES['image'])) {
    json_response(['error' => 'Tidak ada gambar yang dikirim'], 400);
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $msg = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        ? 'Ukuran gambar terlalu besar'
        : 'Gagal mengunggah gambar';
    json_response(['error' => $msg], 400);
}

$maxBytes = 8 * 1024 * 1024; // 8 MB
if ($file['size'] > $maxBytes) {
    json_response(['error' => 'Ukuran gambar maksimal 8MB'], 400);
}

// Verifikasi ini benar-benar gambar (bukan cuma cek nama/ekstensi — anti file berbahaya menyamar)
$info = @getimagesize($file['tmp_name']);
if ($info === false) {
    json_response(['error' => 'File yang diunggah bukan gambar yang valid'], 400);
}

$allowed = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_GIF  => 'gif',
    IMAGETYPE_WEBP => 'webp',
];
$type = $info[2];
if (!isset($allowed[$type])) {
    json_response(['error' => 'Format gambar harus JPG, PNG, GIF, atau WEBP'], 400);
}
$ext = $allowed[$type];

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    json_response(['error' => 'Gagal menyimpan gambar di server'], 500);
}

json_response(['filename' => $filename], 201);
