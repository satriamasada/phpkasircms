# POS Premium (PHP Kasir)

Aplikasi Point of Sale (Kasir) premium berbasis web yang dikembangkan menggunakan PHP native, MySQL, dan Bootstrap 5. Aplikasi ini dilengkapi dengan sistem Role-Based Access Control (RBAC) dinamis, manajemen utang piutang, pelaporan penjualan, dan dukungan Dark Mode/Light Mode.

## 🚀 Fitur Utama

### 1. Sistem RBAC (Role-Based Access Control) Dinamis
- Manajemen multi-role untuk setiap pengguna.
- Pemisahan hak akses modul (misalnya Admin, Manager, Cashier).
- Dynamic permission checking pada setiap halaman.

### 2. Transaksi & POS (Point of Sale)
- Halaman kasir (POS) yang responsif.
- Dukungan berbagai jenis pembayaran (`Cash`, `Card`, `Transfer`, `Credit`).
- Pencetakan struk/invoice otomatis.

### 3. Manajemen Utang Piutang (Debt Management)
- Pencatatan otomatis jika jenis pembayaran adalah `Credit` (Kredit/Utang).
- Halaman khusus untuk mencatat cicilan atau pelunasan utang.
- Rekapitulasi sisa utang yang belum dibayar.

### 4. Master Data
- **Produk (Products):** Manajemen inventori, harga modal, harga jual, dan stok.
- **Supplier (Pemasok):** Manajemen data pemasok barang.
- **Pelanggan (Customers):** Manajemen relasi pelanggan (member).

### 5. Pelaporan (Reports)
- Laporan penjualan harian, bulanan, atau rentang tanggal spesifik.
- Rekap modal, pendapatan kotor, pendapatan bersih, dan jenis pembayaran.

### 6. User Interface UX
- Menggunakan **Bootstrap 5** dan **jQuery**.
- **Dark Mode & Light Mode** (pengaturan tema disimpan di Local Storage browser).
- Flash messages untuk notifikasi sukses/error yang dinamis.

---

## 🛠️ Stack Teknologi

- **Backend:** PHP 8+ (Native)
- **Database:** MySQL / MariaDB
- **Frontend / UI:** 
  - Bootstrap 5.3 CDN
  - jQuery 3.7+
  - FontAwesome 6 (Icons)
  - Google Fonts (Outfit)

---

## ⚙️ Panduan Instalasi (Development)

Berikut adalah langkah-langkah untuk menjalankan aplikasi `phpkasir` secara lokal:

1. **Clone atau Extract Repositori**
   Letakkan file aplikasi di dalam direktori server lokal Anda (misal: `c:\laragon\www\belajarphpkasir` atau `htdocs\belajarphpkasir`).

2. **Buat Database SQL**
   - Buka MySQL client favorit Anda (phpMyAdmin, DBeaver, HeidiSQL, dsb).
   - Buat database baru, contoh: `pos_rbac` atau sesuaikan dengan keinginan Anda.
   - Import file `database_final.sql` ke dalam database tersebut. File ini sudah berisi schema tabel dan data *dummy/seeding*.

3. **Konfigurasi Database**
   - Buka file `includes/db.php`.
   - Sesuaikan konfigurasi koneksi database:
     ```php
     $host = 'localhost';
     $user = 'root'; // User database Anda
     $pass = '';     // Password database Anda
     $db   = 'pos_rbac'; // Nama database yang dibuat di langkah 2
     ```

4. **Akses Aplikasi**
   - Buka browser dan pergi ke `http://localhost/belajarphpkasir`

### Data Login Demo (Default)

File SQL sudah menyediakan berbagai level akses (Password dalam plaintext untuk tujuan demo, disarankan menggunakan *hashing* untuk produksi):

| Role / Level | Username | Password | Keterangan |
| --- | --- | --- | --- |
| **Admin** | `admin` | `admin123` | Akses penuh ke seluruh fitur dan pengaturan rbac |
| **Manager** | `manager_user` | `manager123` | Akses manajemen master data dan laporan |
| **Cashier** | `cashier_user` | `cashier123` | Akses ke halaman POS kasir dan pelanggan |

---

## 📂 Struktur Direktori

```text
/belajarphpkasir
├── assets/                 # (Jika ada) asset CSS/JS tambahan
├── backups/                # Direktori penyimpanan hasil backup database
├── docs/                   # (opsional) file dokumentasi lainnya
├── includes/               # File konfigurasi inti
│   ├── db.php              # Koneksi ke database
│   └── functions.php       # Kumpulan fungsi PHP utility/helper dan RBAC
├── layouts/                # Bagian kerangka UI (Frontend)
│   ├── header.php          # Tag head, sidebar navigasi, topbar, dan cek login
│   └── footer.php          # Footer HTML dan library penutup
├── modules/                # (opsional) jika sistem dikembangkan modular
├── index.php               # Halaman Dashboard
├── pos.php                 # Halaman utama Point of Sale
├── debts.php               # Halaman Manajemen Utang Piutang (Kredit)
├── products.php            # Halaman Manajemen Produk Inventory
├── customers.php           # Halaman Data Pelanggan
├── suppliers.php           # Halaman Data Supplier
├── reports.php             # Halaman Laporan (Sales dsb)
├── rbac.php                # Halaman Manajemen Roles & Permissions
├── users.php               # Halaman Manajemen User
├── login.php               # Halaman otentikasi login
├── logout.php              # Proses keluar aplikasi
├── print_invoice.php       # Logika untuk menampilkan dan mencetak struk kasir
├── backup_db.php           # Utilitas/Skrip otomatis backup database SQL
├── tools_generate_demo.php # Tools helper
└── database_final.sql      # Schema & Dummy Database
```

---

## 🔐 Keamanan Tambahan (Opsional & Direkomendasikan)

Untuk deployment versi _Production_, sangat disarankan menerapkan hal berikut:
1. **Password Hashing:** Saat ini sistem `user` menggunakan *plain text* password agar mempermudah pemahaman saat *belajar*. Ganti query registrasi/login menggunakan implementasi `password_hash()` dan `password_verify()` dari PHP.
2. **Prepared Statements:** Pastikan query pada sistem telah menggunakan pendekatan PDO atau `mysqli_stmt` untuk mencegah ancaman **SQL Injection**. (Beberapa bagian di fungsi mungkin harus direvisi jika masih menggunakan penggabungan string query).
3. **Directory Protection:** Lindungi folder `includes/` atau `backups/` menggunakan `.htaccess` dari akses secara langsung `Deny from all`.

## 🧑‍💻 Hak Cipta
Aplikasi Kasir Pembelajaran (Belajar PHP Kasir) - Dibuat untuk tujuan edukasi dan pengembangan dasar sistem Point of Sale berbasis Web.
