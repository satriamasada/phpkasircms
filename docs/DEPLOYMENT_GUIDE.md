# Prosedur Deployment & Reset Sistem POS

Dokumen ini menjelaskan langkah-langkah untuk melakukan deployment bersih atau reset total sistem POS premium.

## ⚠️ PERINGATAN
Prosedur reset akan **MENGHAPUS SEMUA DATA TRANSAKSI**. Pastikan Anda telah mengunduh cadangan (backup) melalui menu Sistem sebelum melanjutkan jika data lama masih diperlukan.

---

## Langkah 1: Persiapan Lingkungan (Environment)
Berbeda dengan versi lama, konfigurasi sekarang terpusat pada file `.env`.

1. Pastikan file `.env` sudah ada di folder root.
2. Jika belum, salin dari `.env.example`:
   ```powershell
   cp .env.example .env
   ```
3. Buka `.env` dan sesuaikan `DB_NAME`, `DB_USER`, dan `DB_PASS` sesuai server tujuan.

---

## Langkah 2: Reset / Instalasi Bersih via Browser (Direkomendasikan)
Sistem dilengkapi dengan installer otomatis yang menangani pembuatan tabel dan data master.

1. Buka browser dan arahkan ke alamat aplikasi.
2. Jika sistem mendeteksi basis data kosong, Anda akan diarahkan ke `install.php`.
3. Klik tombol **"Mulai Instalasi"**. Sistem akan mengimpor `database_final.sql` secara otomatis.
4. **Login Pertama**:
   - **Username**: `admin`
   - **Password**: `admin123`

---

## Langkah 3: Reset via Utilitas Basis Data (Manual)
Jika Anda ingin melakukan reset saat aplikasi sudah berjalan:

1. Login sebagai **Admin**.
2. Buka URL: `http://localhost/belajarphpkasir/setup_database.php`.
3. Gunakan fitur **"Reset & Reinstall"** untuk membersihkan basis data dan mengulang proses instalasi.

---

## Langkah 4: Mengisi Data Simulasi (Opsional)
Untuk keperluan pengujian performa grafik dan laporan:

1. Buka URL: `http://localhost/belajarphpkasir/tools_generate_demo.php`.
2. Skrip ini akan mengisi data transaksi simulasi untuk periode 5 tahun terakhir.

---

## Langkah 5: Verifikasi Akhir
Pastikan hal-hal berikut setelah deployment:

1. **Izin Folder**: Folder `backups/` harus memiliki izin tulis (`writable`) agar fitur cadangan berfungsi.
2. **Kode Lisensi**: Pastikan `LICENSE_KEY` di `.env` sudah terisi dengan kunci yang valid untuk membuka fitur-fitur premium (Multi-cabang/Laporan).
3. **Zona Waktu**: Periksa pengaturan waktu di `includes/functions.php` jika diperlukan sinkronisasi waktu server.

---

_Hubungi Tim Dukungan Teknis jika Anda mengalami kendala pada proses deployment._

