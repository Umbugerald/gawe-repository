=======================================================================================================================
                                        KETUA : YASYFI RAHARJA (41825010016)
                                  ANGGOTA : UMBU GERALD DEBRYAN KAMURI (41825010012)
=======================================================================================================================

=======================================================================================================================
                                          STACK TEKNOLOGI YANG DIGUNAKAN:

FRONTEND (ANTARMUKA/KLIEN): "HTML5, CSS3 (FLEXBOX, MEDIA QUERIES), DAN VANILLA JAVASCRIPT (MANIPULASI DOM LANGSUNG)."

BACKEND (LOGIKA SERVER): "PHP NATIVE (SESSION MANAGEMENT, JSON/REST API HANDLING, VALIDASI & UPLOAD FILE)."

DATABASE (PENYIMPANAN): "MYSQL / MARIADB (TERHUBUNG VIA EKSTENSI MYSQLI)."

KEAMANAN SISTEM: "PREPARED STATEMENTS (ANTI-SQLI), PASSWORD HASHING, SOFT DELETION, DAN SANITASI XSS."

INFRASTRUKTUR & JARINGAN: "CLOUDFLARE TUNNEL (REVERSE PROXY, PENYEMBUNYIAN IP SERVER, DAN HTTPS/SSL OTOMATIS)."
=======================================================================================================================

Panduan Pengguna (User Manual)
1. Registrasi Akun Baru
Isi data diri: Nama Lengkap, Username, Password, dan No. Telp.

Unggah Foto Profil.

Pilih Role:

Pelanggan: Setelah daftar, Anda bisa langsung menggunakan aplikasi.

Pekerja: Anda wajib mengunggah Foto KTP dan menunggu verifikasi dari Admin sebelum bisa bekerja.

2.  Role: Pekerja (Worker)
Berikut langkah-langkah untuk mengambil dan menyelesaikan pekerjaan:

Login ke akun pekerja.

Atur Wilayah Kerja (Provinsi, Kota/Kabupaten, Kecamatan).

Klik Cari Pekerjaan untuk melihat daftar permintaan di wilayah Anda.

Klik Terima pada kartu pekerjaan yang tersedia.

Pekerjaan akan masuk ke Tab Diterima.

Navigasi & Komunikasi:

Klik Tombol Hijau WhatsApp untuk menghubungi pelanggan.

Klik Tombol Gmaps untuk menuju lokasi pelanggan.

Saat tiba, klik Sampai di Tujuan.

Klik Mulai Kerjakan saat mulai bekerja.

Jika selesai, Upload Foto Bukti dan klik Selesaikan.

Untuk keluar, klik Foto Profil lalu pilih Log Out.

3. Role: Pelanggan (Customer)
Berikut langkah-langkah untuk memesan jasa:

Login ke akun pelanggan.

Isi keluhan Anda pada kolom "Apa keluhan anda hari ini?" dan tekan Enter.

Atur Alamat: Pilih alamat yang ada atau klik Tambah Alamat Baru (Isi nama penerima, alamat lengkap, dan titik koordinat).

Isi Detail Keluhan lebih lanjut dan Upload Foto kendala.

Masukkan Harga Tawaran yang Anda inginkan.

Pantau Riwayat:

Jika dalam 5 menit tidak ada pekerja yang mengambil, status menjadi Gagal (Saran: Naikkan tawaran harga).

Jika diterima, pesanan masuk ke Tab Proses.

Setelah pengerjaan selesai, Anda wajib memberikan Penilaian (Rating) atau melakukan Laporan (Report) jika terjadi masalah.

4. Role: Admin
Admin bertanggung jawab atas manajemen ekosistem aplikasi:

Login (Akun dibuatkan oleh Supervisor).

Dashboard: Memantau statistik data pelanggan, pekerja, laporan, dan total order.

Manajemen Status: Berwenang mengubah status_kerja (verifikasi/suspend pekerja).

Kontrol Order: Berwenang membatalkan orderan jika terjadi kecurangan atau masalah teknis.

Manajemen Laporan: Memproses setiap report yang dikirimkan oleh pelanggan.

Catatan Teknis
Sistem Timeout: Pesanan otomatis hangus jika tidak diambil pekerja dalam 5 menit.

Integrasi: Menggunakan WhatsApp API dan Google Maps API.

Keamanan: Pekerja wajib melalui proses verifikasi KTP secara manual oleh Admin.
