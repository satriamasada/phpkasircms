# Prosedur Deployment & Reset Sistem POS

Dokumen ini menjelaskan langkah-langkah untuk melakukan deployment bersih atau reset total sistem POS dari awal (Fresh Install).

## ⚠️ PERINGATAN
Prosedur ini akan **MENGHAPUS SEMUA DATA TRANSAKSI** yang ada. Pastikan Anda telah melakukan backup jika data lama masih diperlukan.

---

## Langkah 1: Reset Struktur Database (Fresh Install)
Gunakan file `database_final.sql` untuk menghapus database lama dan membuat struktur baru beserta data master default (Admin, Roles, Permissions).

**Cara Menjalankan:**
1. Buka Terminal atau Command Prompt.
2. Jalankan perintah MySQL berikut:
   ```powershell
   mysql -u root -p < c:\laragon\www\belajarphp\database_final.sql
   ```
   *(Tekan Enter jika tidak ada password, atau masukkan password database Anda).*

**Hasil:**
- Database `pos_rbac` dibuat ulang.
- 4 User default dibuat (admin, manager_user, cashier_user, multi_user).
- Data master (Suppliers, Customers, & 4 Demo Products) tersedia.

---

## Langkah 2: Mengisi Data Simulasi Transaksi (Opsional)
Jika Anda ingin sistem langsung terlihat memiliki riwayat penjualan (untuk keperluan demo atau testing grafik), jalankan skrip generator.

**Cara Menjalankan via Browser:**
1. Login ke aplikasi sebagai **admin** (user: `admin`, pass: `admin123`).
2. Buka URL: `http://localhost/belajarphp/tools_generate_demo.php`

**Cara Menjalankan via CLI (Lebih Cepat):**
```powershell
php c:\laragon\www\belajarphp\tools_generate_demo.php
```

---

## Langkah 3: Verifikasi Sistem Siap Pakai
Setelah database siap, pastikan hal-hal berikut:

1. **Koneksi Database**: Cek file `includes/db.php`, pastikan `$dbname`, `$username`, dan `$password` sesuai dengan server Anda.
2. **Izin Folder Backup**: Pastikan folder `backups/` memiliki izin tulis (writable).
3. **Login Pertama**: Gunakan kredensial berikut:
   - **Username**: `admin`
   - **Password**: `admin123`

---

## Langkah 4: Maintenance Rutin
Sistem telah dikonfigurasi untuk melakukan **Auto-Backup** setiap kali pengguna Logout. File backup akan tersimpan di folder `backups/` dengan format tanggal.
