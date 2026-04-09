# Dokumentasi Teknis POS Premium RBAC (v1.1)

Sistem Point of Sale (POS) ini adalah aplikasi berbasis web yang dirancang dengan arsitektur modern, mengutamakan performa tinggi untuk data besar, serta keamanan berbasis peran (RBAC) yang dinamis dan dukungan multi-cabang.

---

## 1. Arsitektur Teknologi (Stack Teknologi)

- **Backend**: PHP 8.x (Native dengan PDO untuk keamanan dari SQL Injection).
- **Frontend**: HTML5, Vanilla CSS3 (Custom Variables), Bootstrap 5.3.
- **Konfigurasi**: Menggunakan `.env` untuk manajemen kredensial sensitif dan Kode Lisensi.
- **Database**: MySQL/MariaDB (Innodb Engine).
- **Pustaka Utama**:
  - **DataTables 1.13.6**: Pemrosesan tabel di sisi server (Server-Side).
  - **Chart.js**: Visualisasi data analitik di Dasbor.
  - **Font Awesome 6.4**: Ikonografi sistem.

---

## 2. Fitur Utama (Modul Inti)

### A. Dasbor & Analitik Seketika (Real-time)
Memberikan ringkasan keuangan instan dengan Kartu KPI (Pendapatan, Laba, Piutang) dan grafik tren penjualan historis hingga 5 tahun.

### B. Multi-Cabang (Manajemen Outlet)
Sistem mendukung pengelolaan banyak outlet/toko dalam satu instalasi. Setiap transaksi, stok, dan pengguna dapat dikaitkan dengan cabang tertentu, memungkinkan konsolidasi data yang efisien.

### C. Sistem Lisensi & Tingkatan (Tier)
Sistem memiliki kontrol lisensi berbasis tingkatan yang membatasi kapasitas penggunaan:
- **Standard**: Terbatas pada 2 Pengguna, 1 Cabang, dan tanpa akses laporan analitik mendalam.
- **Professional**: Pengguna tidak terbatas, hingga 5 Cabang, dan akses penuh ke seluruh fitur laporan.

### D. Matriks RBAC Dinamis
Manajemen izin akses kini lebih fleksibel dengan antarmuka matriks. Administrator dapat mencentang izin spesifik untuk setiap peran (Manajer, Kasir, dll) secara instan tanpa perlu mengubah kode program.

### E. Point of Sale (POS) & Penjualan
- **Multi-Metode Pembayaran**: Tunai, Kartu, Transfer, dan Piutang (Kredit).
- **Sistem Faktur**: Penomoran otomatis unik per transaksi.
- **Cetak Nota**: Nota struk dioptimalkan untuk printer termal melalui browser.

### F. Manajemen Piutang (Debt Management)
Memungkinkan pelacakan tagihan pelanggan, pembayaran parsial (cicilan), dan riwayat pembayaran lengkap dengan indikator visual yang jelas.

---

## 3. Alur Kerja Sistem

1. **Pengaturan Otomatis**: Sistem akan secara otomatis mendeteksi jika basis data belum terinstal dan mengarahkan pengguna ke halaman `install.php`.
2. **Validasi Lisensi**: Setiap permintaan halaman memvalidasi status lisensi dari file `.env`. Jika lisensi kedaluwarsa atau tidak valid, akses akan dikunci ke halaman `license_expired.php`.
3. **Kesadaran Cabang**: Sesi menyimpan `active_branch_id` untuk menyaring data yang relevan dengan cabang yang sedang dikelola oleh pengguna saat itu.
4. **Mesin Cadangan Otomatis**: Skrip `backup_db.php` menyediakan fungsionalitas pencadangan basis data cepat yang dapat diunduh langsung oleh Admin.

---

## 4. Struktur Basis Data Penting

- **`branches`**: Menyimpan informasi lokasi outlet/cabang.
- **`roles` & `permissions`**: Definisi peran dan izin fitur.
- **`role_permissions`**: Tabel persimpangan untuk pemetaan RBAC dinamis.
- **`users`**: Akun pengguna dengan relasi ke peran.
- **`products`**: Data stok dengan `cost_price` (harga modal) dan `price` (harga jual).
- **`sales`**: Data induk transaksi penjualan.
- **`debt_payments`**: Log transaksi cicilan piutang.

---

## 5. Panduan Konfigurasi

1. Salin `.env.example` menjadi `.env`.
2. Sesuaikan kredensial `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`.
3. Masukkan `LICENSE_KEY` yang valid (Standard/Professional).
4. Akses `install.php` melalui browser untuk inisialisasi awal.

---

_Dokumentasi ini diperbarui secara berkala sesuai dengan perkembangan fitur sistem._


