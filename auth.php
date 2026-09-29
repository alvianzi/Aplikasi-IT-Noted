<?php
require_once __DIR__ . '/config.php';
start_session();

$method = $_SERVER['REQUEST_METHOD'];

// GET → cek status login saat ini (dipakai app.html saat pertama dibuka)
if ($method === 'GET') {
    if (empty($_SESSION['user_id'])) {
        json_response(['loggedIn' => false]);
    }
    $now = time();
    $last = $_SESSION['last_activity'] ?? $now;
    $started = $_SESSION['session_started_at'] ?? $last;
    if ($now - $last >= SESSION_TIMEOUT_SECONDS || $now - $started >= SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_destroy();
        json_response(['loggedIn' => false, 'timeout' => true]);
    }
    if (!refresh_session_user()) {
        $_SESSION = [];
        session_destroy();
        json_response(['loggedIn' => false]);
    }
    $_SESSION['last_activity'] = $now; // membuka/refresh halaman dianggap aktivitas nyata
    json_response([
        'loggedIn' => true,
        'userId' => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'displayName' => $_SESSION['display_name'],
        'isAdmin' => (bool)$_SESSION['is_admin'],
        'csrfToken' => $_SESSION['csrf_token'],
    ]);
}

// POST → proses login
if ($method === 'POST') {
    $body = read_json_body();
    $username = trim($body['username'] ?? '');
    $password = (string)($body['password'] ?? '');

    if ($username === '' || $password === '') {
        json_response(['error' => 'Username dan kata sandi wajib diisi'], 400);
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Selalu proses waktu yang relatif sama agar tidak bocorkan username valid/tidak (timing attack)
    $dummyHash = '$2y$10$abcdefghijklmnopqrstuuVYNH1p9pFhK3z0m8s5jvOa1c1wq1r9C';

    if (!$user) {
        password_verify($password, $dummyHash);
        json_response(['error' => 'Username atau kata sandi salah'], 401);
    }

    if (!$user['is_active']) {
        json_response(['error' => 'Akun ini telah dinonaktifkan'], 403);
    }

    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
        json_response(['error' => "Terlalu banyak percobaan gagal. Coba lagi dalam $mins menit."], 429);
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_attempts'] + 1;
        $lockUntil = null;
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 15 * 60); // kunci 15 menit
        }
        $stmt = db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $stmt->execute([$attempts, $lockUntil, $user['id']]);
        json_response(['error' => 'Username atau kata sandi salah'], 401);
    }

    // Login berhasil
    $stmt = db()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['last_activity'] = time();
    $_SESSION['session_started_at'] = time();

    json_response([
        'loggedIn' => true,
        'userId' => (int)$user['id'],
        'username' => $user['username'],
        'displayName' => $user['display_name'],
        'isAdmin' => (bool)$user['is_admin'],
        'csrfToken' => $_SESSION['csrf_token'],
    ]);
}

json_response(['error' => 'Metode tidak didukung'], 405);
