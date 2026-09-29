# IT Noted untuk Windows

Aplikasi WPF/WebView2 membuka situs IT Noted dan menyimpan sesi browser pada profil pengguna Windows.

Jalankan `installer\build-installer.ps1` dari PowerShell dengan .NET 10 SDK. Skrip membuat aplikasi mandiri x64 dan paket setup EXE dengan IExpress bawaan Windows. Runtime WebView2 Evergreen Microsoft ikut disertakan dan dipasang otomatis jika belum tersedia.

Hasil setup ada di folder `dist`. Aplikasi dan setup belum ditandatangani sertifikat penerbit, sehingga Windows dapat menampilkan peringatan SmartScreen.
