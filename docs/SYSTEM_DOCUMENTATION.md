# Dokumentasi Teknis POS Premium RBAC (v1.0)

Sistem Point of Sale (POS) ini adalah aplikasi berbasis web yang dirancang dengan arsitektur modern, mengutamakan performa tinggi untuk data besar, serta keamanan berbasis peran (RBAC).

---

## 1. Arsitektur Teknologi (Tech Stack)

- **Backend**: PHP 8.x (Native dengan PDO untuk keamanan SQL Injection).
- **Frontend**: HTML5, Vanilla CSS3 (Custom Variables), Bootstrap 5.3.
- **Database**: MySQL/MariaDB (Innodb Engine).
- **Library Utama**:
  - **DataTables 1.13.6**: Digunakan untuk pemrosesan tabel di sisi server (Server-Side).
  - **Chart.js**: Digunakan untuk visualisasi data analitik di Dashboard.
  - **Font Awesome 6.4**: Untuk ikonografi sistem.

---

## 2. Fitur Utama (Core Modules)

### A. Dashboard & Analitik Real-time

Memberikan ringkasan keuangan instan:

- **KPI Cards**: Total Pendapatan, Laba Bruto (Total Jual - Harga Modal), Total Piutang Aktif, dan Cash-In.
- **Graphic Insights**: Visualisasi tren penjualan bulanan dan perbandingan tahunan (Data historis s/d 5 tahun).

### B. Point of Sale (POS) & Penjualan

- **Multi-Metode Pembayaran**: Mendukung Tunai (Cash), Kartu (Card), Transfer Bank, dan Piutang (Credit).
- **Sistem Invoice Otomatis**: Pembuatan nomor invoice unik (`INV-YYYYMMDD...`).
- **Cetak Nota**: Terintegrasi dengan fitur cetak browser yang dioptimalkan untuk struk kasir.

### C. Manajemen Piutang (Debt Management)

- **Tracking Otomatis**: Memantau tagihan pelanggan yang belum lunas.
- **Partial Payment**: Mendukung sistem cicilan/angsuran dengan pencatatan riwayat pembayaran yang mendalam.
- **Status Badge**: Penanda visual otomatis (LUNAS/PIUTANG).

### D. Laporan Penjualan (Server-Side)

- **High Performance**: Menggunakan Server-Side DataTables untuk menangani puluhan ribu transaksi secara instan.
- **Filtering & Search**: Pencarian global berdasarkan No. Invoice, Nama Pelanggan, atau Nama Kasir.

### E. RBAC (Role-Based Access Control)

Sistem izin bertingkat:

- **Administrator**: Akses penuh ke seluruh sistem, termasuk pengaturan peran dan user.
- **Manager**: Fokus pada manajemen stok (Products), Supplier, dan monitoring laporan.
- **Cashier**: Dibatasi hanya pada modul POS dan Manajemen Pelanggan.

---

## 3. Struktur Database Penting

- **`roles` & `permissions`**: Menyimpan definisi peran dan izin sistem.
- **`users`**: Informasi akun dengan dukungan password (plain text sesuai permintaan user).
- **`products`**: Menyimpan data stok beserta `cost_price` (harga modal) dan `price` (harga jual).
- **`sales`**: Tabel utama transaksi.
- **`debt_payments`**: Tabel transaksi cicilan piutang.

---

## 4. Keunggulan Sistem

1. **Light & Dark Mode**: Seluruh UI adaptif terhadap preferensi tema pengguna.
2. **Data Generator**: Tersedia skrip `tools_generate_demo.php` untuk mempopulasi data simulasi 5 tahun secara otomatis.
3. **Optimized for Big Data**: Penggunaan AJAX Fetch dan DataTables Server-side memastikan aplikasi tidak lambat saat data membesar.
4. **Auto-Backup**: Sistem pencadangan database otomatis yang terintegrasi pada alur kerja pengguna (misal: saat logout).

---

## 5. Panduan Instalasi Cepat

1. Import `database_final.sql` ke MySQL.
2. Sesuaikan konfigurasi database di `includes/db.php`.
3. Login sebagai `admin` dengan password `admin123`.
4. (Opsional) Jalankan `tools_generate_demo.php` untuk mengisi data awal.

---

_Dokumentasi ini dibuat secara otomatis oleh Antigravity AI Assistant._
