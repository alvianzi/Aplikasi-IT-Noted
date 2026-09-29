<?php
// ============================================================
// SKRIP SEKALI-PAKAI — buat akun admin pertama.
// Cara pakai: buka https://domainmu.com/create_admin.php di browser
// SEKALI SAJA, lalu HAPUS FILE INI dari server (wajib, demi keamanan).
// ============================================================
require_once __DIR__ . '/config.php';

$existing = (int) db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
if ($existing > 0) {
    die('Sudah ada user terdaftar. Skrip ini hanya untuk setup awal. Hapus file ini dari server.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
        $error = 'Username 3-50 karakter, hanya huruf/angka/underscore/titik.';
    } elseif ($displayName === '') {
        $error = 'Nama tampilan wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (username, password_hash, display_name, is_admin) VALUES (?, ?, ?, 1)');
        $stmt->execute([$username, $hash, $displayName]);
        echo '<!DOCTYPE html><html lang="id"><meta charset="utf-8"><body style="font-family:sans-serif;max-width:480px;margin:60px auto;line-height:1.6">'
           . '<h2>✅ Akun admin dibuat</h2>'
           . '<p>Username: <b>' . htmlspecialchars($username) . '</b></p>'
           . '<p><b>Sekarang hapus file create_admin.php dari server ini juga — jangan ditunda.</b></p>'
           . '<p><a href="login.html">Lanjut ke halaman login →</a></p></body></html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Setup Admin — Simpan</title>
<style>body{font-family:system-ui,sans-serif;max-width:420px;margin:60px auto;padding:0 16px;line-height:1.5}
input{width:100%;padding:9px 10px;margin:6px 0 14px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
button{padding:10px 18px;border:none;background:#1F6F63;color:#fff;border-radius:6px;cursor:pointer;font-size:0.95rem}
.err{color:#B5482F;margin-bottom:10px}</style></head>
<body>
<h2>Buat Akun Admin Pertama</h2>
<p>Ini hanya bisa dijalankan sekali (selama tabel users masih kosong).</p>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
  <label>Username<input name="username" required></label>
  <label>Nama Tampilan<input name="display_name" required></label>
  <label>Kata Sandi (min. 8 karakter)<input type="password" name="password" required></label>
  <button type="submit">Buat Admin</button>
</form>
</body></html>
