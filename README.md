# IT Noted — Catatan Tim RS Santa Familia (versi server, multi-user)

Aplikasi catatan bersama dengan login sendiri (username & password),
disimpan di database MySQL, bisa diakses banyak orang sekaligus.
Cocok dipasang di hosting PHP+MySQL yang sama dengan website rumah sakit.

## Yang dibutuhkan di hosting
- PHP 7.4 atau lebih baru (dengan ekstensi PDO MySQL — biasanya sudah aktif)
- Database MySQL/MariaDB
- Sebaiknya sudah HTTPS aktif (kalau situsnya sudah pakai SSL, ini otomatis aman)

## Langkah instalasi

1. **Buat database & import skema**
   - Buat database baru di cPanel/phpMyAdmin (atau pakai yang sudah ada).
   - Import `schema.sql` ke database itu (di phpMyAdmin: tab *Import*, pilih file `schema.sql`).

2. **Isi `config.php`**
   Buka `config.php`, ganti 4 baris ini sesuai data hosting kamu:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nama_database_kamu');
   define('DB_USER', 'user_database_kamu');
   define('DB_PASS', 'password_database_kamu');
   ```
   Kalau situsnya sudah HTTPS, biarkan `APP_USE_HTTPS` bernilai `true`.
   Kalau **belum** ada HTTPS, ganti jadi `false` sementara — tapi sebaiknya
   segera aktifkan HTTPS karena tanpanya, kata sandi bisa disadap saat login.

3. **Upload semua file** ke hosting (lewat FTP/File Manager), misalnya ke folder
   `catatan/` di dalam situs rumah sakit — jadi bisa diakses di
   `https://domainmu.com/catatan/`. Membuka alamat itu langsung (tanpa nama
   file) akan otomatis diarahkan `index.php` ke halaman login atau ke
   aplikasi, tergantung sudah login atau belum.

4. **Buat akun admin pertama**
   Buka `https://domainmu.com/catatan/create_admin.php` di browser, isi
   username, nama, dan kata sandi untuk akun admin pertamamu.

5. **⚠️ WAJIB: hapus `create_admin.php` dari server** setelah akun admin
   dibuat. File ini sengaja hanya bisa dipakai sekali (ditolak otomatis kalau
   sudah ada user), tapi lebih aman kalau langsung dihapus juga.

6. **Login** di `https://domainmu.com/catatan/login.html`, lalu buka
   **Kelola Pengguna** (muncul di sidebar untuk akun admin) untuk menambahkan
   akun rekan kerja lainnya.

## Struktur file
```
index.php           → pintu masuk otomatis (arahkan ke app.html jika sudah login, ke login.html jika belum)
config.php        → koneksi database (edit ini)
schema.sql         → struktur tabel database (import sekali di awal)
migration_add_images.sql → migrasi tambahan (hanya untuk yang sudah pernah pasang sebelum fitur gambar ada)
create_admin.php   → buat admin pertama (hapus setelah dipakai)
auth.php            → proses login/cek sesi
ping.php            → menjaga sesi tetap aktif selama user beneran berinteraksi
logout.php          → proses logout
notes.php           → API catatan (list/tambah/ubah/hapus)
migration_add_style.sql → migrasi tambahan (hanya untuk yang sudah pernah pasang sebelum warna teks & font ada)
users.php           → API kelola pengguna (khusus admin)
upload.php          → API upload gambar catatan
labels.php          → API daftar & buat label
login.html          → halaman login
app.html            → aplikasi catatan utama
admin.html          → halaman kelola pengguna (khusus admin)
assets/logo.png     → logo IT Noted (dipakai di aplikasi)
assets/favicon.png  → ikon tab browser / taskbar
assets/simpan-ui.js → kumpulan animasi/hiasan JS bersama (tema, toast, ripple, tilt, confetti, partikel)
assets/simpan-ui.css → gaya pendukung untuk simpan-ui.js
uploads/            → tempat gambar catatan tersimpan (dibuat otomatis, dilindungi .htaccess)
.htaccess           → memblokir akses langsung ke config.php & schema.sql
```

## Kalau sebelumnya sudah pernah pasang (update ke versi bergambar & label)
1. Upload/timpa semua file baru (terutama `app.html`, `notes.php`, `upload.php`,
   `labels.php`, dan folder `uploads/`).
2. Jalankan `migration_add_images.sql`, `migration_add_labels.sql`, **dan**
   `migration_add_style.sql` di phpMyAdmin (tab SQL, tempel isinya, klik Go,
   satu-satu) — ini menambahkan kolom/tabel yang dibutuhkan tanpa menghapus
   catatan yang sudah ada.
3. Pastikan folder `uploads/` bisa ditulis oleh server (biasanya otomatis di
   hosting/XAMPP; kalau muncul error "Gagal menyimpan gambar", cek permission
   folder ini — di Linux/cPanel set ke 755).

## Pratinjau catatan (klik kartu)
Klik kartu catatan mana pun untuk membukanya dalam tampilan besar ala Google Keep:
seluruh isi, semua gambar, checklist, label, dan siapa yang membuatnya. Gambar bisa
diklik lagi untuk diperbesar penuh. Tutup dengan tombol Tutup, tombol ✕, klik area
gelap di luar, atau tekan Esc.
- **Admin:** ada tombol **✎ Edit** di dalam pratinjau untuk masuk ke mode edit, dan
  checklist bisa dicentang langsung di situ. Klik kartu tidak lagi langsung membuka editor.
- **Anggota:** bisa membaca catatan selengkapnya (berguna untuk catatan panjang), tetapi
  tombol Edit tidak muncul dan checklist terkunci.

## Peran: Admin vs Anggota
- **Admin** — bisa menambah, mengubah, mengarsipkan, dan menghapus catatan; membuat
  label; mengunggah gambar; serta mengelola akun (tambah, nonaktifkan, ganti peran,
  reset kata sandi, **hapus anggota**) lewat halaman Kelola Pengguna.
- **Anggota** — hanya bisa **melihat** catatan (dan memperbesar gambar). Kotak tulis,
  tombol edit/hapus/pin, dan pengelolaan label tidak ditampilkan.

Batasan ini ditegakkan **di server** (bukan cuma disembunyikan di tampilan), jadi
tidak bisa dilewati lewat DevTools. Perubahan peran, penonaktifan, atau penghapusan
akun berlaku **seketika** — sesi yang sedang aktif langsung ikut terpengaruh.

**Saat anggota dihapus:** akunnya hilang permanen dan tidak bisa login lagi, tetapi
catatan yang pernah dibuatnya **tidak ikut terhapus**. Kepemilikannya dipindahkan ke
admin yang menghapus (label "oleh ..." pada catatan itu jadi nama admin tersebut).
Admin tidak bisa menghapus, menonaktifkan, atau mencabut hak admin akunnya sendiri —
supaya sistem tidak pernah kehilangan admin.

## Logout otomatis (keamanan)
Setiap pengguna otomatis keluar setelah **30 menit tidak ada interaksi** (tidak
ada gerak mouse, klik, ketikan, atau scroll). Dua lapis:
- **Di server** (`config.php`, konstanta `SESSION_TIMEOUT_SECONDS`): sesi
  ditolak dan dihapus kalau sudah lewat 30 menit sejak aktivitas terakhir —
  tidak bisa diakali dengan mengutak-atik JavaScript di browser.
- **Di browser** (`assets/simpan-ui.js`): 2 menit sebelum habis muncul
  peringatan, lalu user diarahkan ke halaman login dengan pesan penjelasan.
Sinkronisasi otomatis catatan di latar belakang (tiap ±7 detik) **tidak**
dihitung sebagai aktivitas, jadi tab yang ditinggal terbuka tetap logout.
Mau ubah durasinya? Ganti angka `30` di `SESSION_TIMEOUT_SECONDS` (config.php)
dan `minutes: 30` di `app.html` / `admin.html`.

## Versi & pembaruan tampilan
Ada angka versi kecil (mis. "IT Noted v1.2.0") di pojok sidebar aplikasi, bawah
halaman login, dan bawah halaman Kelola Pengguna — supaya gampang cek apakah
file yang aktif di server sudah yang terbaru setelah kamu update. Variabel
`APP_VERSION` ada di bagian atas skrip masing-masing file (`app.html`,
`login.html`, `admin.html`) — naikkan angkanya setiap kali kamu ganti isinya,
biar gampang dilacak.

## Hiasan & animasi (assets/simpan-ui.js)
Semua efek visual (ganti tema, notifikasi toast, efek ripple saat klik tombol,
tilt 3D halus saat hover kartu catatan, confetti kecil waktu catatan baru
disimpan, partikel bergerak di halaman login, angka sidebar yang "melompat")
sekarang ada di satu file `assets/simpan-ui.js` + `assets/simpan-ui.css` yang
dipakai bersama oleh `login.html`, `app.html`, dan `admin.html`. Kalau suatu
saat mau menambah/mengubah animasi, cukup edit file ini sekali dan otomatis
berlaku di semua halaman — tidak perlu ubah tiga file terpisah.

## Cara kerja singkat
- Semua catatan **dibagikan ke seluruh pengguna yang login** — kalau user A
  menambah catatan, user B akan melihatnya juga (otomatis dimuat ulang setiap
  ±7 detik, atau tekan tombol muat ulang di pojok kanan atas).
- Password disimpan terenkripsi satu arah (bcrypt via `password_hash` PHP) —
  bukan teks biasa, jadi aman meski database bocor.
- Percobaan login yang gagal 5 kali berturut-turut akan mengunci akun itu
  selama 15 menit (mencegah tebak-tebak password).
- Hanya admin yang bisa menambah/menonaktifkan akun — tidak ada pendaftaran
  bebas, jadi kamu yang mengontrol siapa saja yang boleh akses.
- Catatan bisa dilampiri gambar (seperti Google Keep) — format JPG/PNG/GIF/WEBP,
  maksimal 8MB per gambar. Setiap file diverifikasi benar-benar gambar (bukan
  cuma dicek dari ekstensinya) dan disimpan dengan nama acak di folder
  `uploads/`, yang dikunci agar tidak bisa dieksekusi sebagai skrip apa pun —
  supaya upload gambar tidak bisa disalahgunakan untuk menyisipkan kode
  berbahaya. Kalau upload gambar besar gagal terus, cek juga batas
  `upload_max_filesize`/`post_max_size` di `php.ini` hosting kamu (default
  banyak hosting cuma 2MB — naikkan ke minimal 8MB kalau perlu).
- Label sekarang dikelola langsung di dalam kotak catatan (ikon label di
  sebelah ikon gambar) — klik untuk pasang/lepas label yang sudah ada, atau
  buat baru dari situ juga. Form "Label baru" di sidebar tetap ada untuk
  membuat label duluan tanpa harus buka catatan, tapi label baru itu belum
  otomatis terpasang ke catatan mana pun sampai kamu memilihnya lewat ikon
  label saat menulis/mengedit.
- Setiap catatan bisa diatur **warna kotak bebas** (bukan cuma 6 pilihan —
  ada color picker kustom juga), **warna tulisan bebas**, dan **font**
  (Default/Serif/Monospace/Tulisan tangan) lewat ikon palet di kotak catatan
  (klik "Simpan"/"Perbarui" untuk mengunci pilihannya). Semua pilihan ini
  tersimpan per catatan dan terlihat sama oleh semua anggota tim.

## Batasan yang perlu diketahui
- Ini **bukan** enkripsi ujung-ke-ujung: siapa pun yang punya akses admin ke
  database (misalnya hosting provider dalam skenario ekstrem) secara teknis
  bisa membaca isi catatan. Untuk kebanyakan kebutuhan internal, ini standar
  keamanan yang wajar (sama seperti sistem informasi RS pada umumnya).
- Belum ada notifikasi/real-time push — pembaruan dari user lain muncul lewat
  polling otomatis tiap beberapa detik, bukan instan.
- Belum ada halaman "lupa kata sandi" otomatis — kalau lupa, minta admin
  reset lewat halaman **Kelola Pengguna**.
