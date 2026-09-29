<?php
// ============================================================
// KONFIGURASI — isi sesuai data hosting/database kamu
// ============================================================
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_NAME', 'if0_43030647_schema');
define('DB_USER', 'if0_43030647');
define('DB_PASS', 'Nestha21321');

// Set true kalau situs ini sudah pakai HTTPS (WAJIB untuk keamanan cookie sesi)
define('APP_USE_HTTPS', true);

// ============================================================
// Jangan ubah bagian di bawah ini
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0'); // jangan tampilkan error mentah ke user di production

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => APP_USE_HTTPS,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

define('SESSION_TIMEOUT_SECONDS', 30 * 60); // 30 menit tidak aktif → otomatis logout

// Cek ulang ke database: akun yang dihapus/dinonaktifkan langsung kehilangan akses,
// dan perubahan peran (admin <-> anggota) berlaku seketika tanpa menunggu login ulang.
function refresh_session_user(): bool {
    $stmt = db()->prepare('SELECT display_name, is_admin, is_active FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u || !$u['is_active']) return false;
    $_SESSION['display_name'] = $u['display_name'];
    $_SESSION['is_admin'] = (bool)$u['is_admin'];
    return true;
}

// Anggota (non-admin) hanya boleh melihat. Panggil ini sebelum operasi tambah/ubah/hapus.
function ensure_can_edit(array $me): void {
    if (empty($me['is_admin'])) {
        json_response(['error' => 'Akun anggota hanya bisa melihat catatan. Hanya admin yang bisa menambah atau mengubah.'], 403);
    }
}

function require_login(): array {
    start_session();
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'Belum login'], 401);
    }

    $now = time();
    $last = $_SESSION['last_activity'] ?? $now;
    $started = $_SESSION['session_started_at'] ?? $last;
    if ($now - $last >= SESSION_TIMEOUT_SECONDS || $now - $started >= SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_destroy();
        json_response(['error' => 'Sesi berakhir setelah 30 menit. Silakan masuk kembali.', 'timeout' => true], 401);
    }

    if (!refresh_session_user()) {
        $_SESSION = [];
        session_destroy();
        json_response(['error' => 'Akun ini sudah tidak aktif atau telah dihapus.'], 401);
    }

    // Sinkronisasi pemantauan latar (loadNotes berkala) tidak dianggap "aktivitas" —
    // supaya tab yang dibiarkan terbuka tanpa disentuh tetap logout setelah 30 menit,
    // walau data di baliknya terus tersinkron sendiri.
    $isBackgroundSync = ($_SERVER['HTTP_X_BACKGROUND_SYNC'] ?? '') === '1';
    if (!$isBackgroundSync) {
        $_SESSION['last_activity'] = $now;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'display_name' => $_SESSION['display_name'],
        'is_admin' => $_SESSION['is_admin'],
    ];
}

function require_admin(): array {
    $u = require_login();
    if (empty($u['is_admin'])) {
        json_response(['error' => 'Hanya admin yang boleh mengakses ini'], 403);
    }
    return $u;
}

function require_csrf(): void {
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $header)) {
        json_response(['error' => 'Token tidak valid, silakan muat ulang halaman'], 403);
    }
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
