<?php
// Endpoint ringan: dipanggil dari JS saat mendeteksi ada interaksi pengguna sungguhan
// (gerak mouse/klik/keyboard), supaya sesi tetap terjaga selama user memang aktif.
// require_login() di config.php yang menangani logika perpanjangan/kedaluwarsa sesi.
require_once __DIR__ . '/config.php';
require_login();
json_response(['ok' => true]);
