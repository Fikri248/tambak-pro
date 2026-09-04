# Tambak Pro

Tambak Pro adalah aplikasi web untuk mencatat dan memantau kegiatan operasional tambak. Aplikasi dibangun dengan Laravel 12 dan Blade untuk mengelola hierarki lokasi, komoditas, Vendor, Barang/Item, Batch, posisi stok per petak, transaksi operasional, riwayat, serta laporan.

## Tentang Aplikasi

Tambak Pro membantu kegiatan budidaya perairan melalui satu alur data yang saling terhubung:

- Struktur lokasi bertingkat **Area → Tambak → Petak**.
- Master komoditas, Vendor, serta Barang/Item.
- Batch komoditas dan posisi stok terkini pada setiap petak.
- Pembibitan, pemindahan stok, perubahan jumlah, pembelian, dan penggunaan Barang/Item.
- Riwayat transaksi terpadu dan AuditLog untuk keterlacakan.
- Dashboard analitik dan laporan operasional dalam format web, CSV, XLSX, Print, dan PDF.

Kode bisnis seperti kode lokasi, Vendor, komoditas, Barang/Item, transaksi, dan Batch dibuat otomatis oleh server. Pengguna tidak perlu mengetik kode identitas saat membuat data.

## Fitur Utama

- Autentikasi pengguna aktif, registrasi Admin mandiri melalui URL langsung, Remember Me, Logout aman, dan perubahan password mandiri.
- Dua role: **Admin** dan **Manager**.
- Dashboard KPI, grafik stok, tren aktivitas transaksi, dan aktivitas terbaru.
- Pengelolaan master data dengan pencarian, filter, detail, dan status aktif/nonaktif.
- Kontak WhatsApp pada nomor Vendor yang valid.
- CRUD modal dengan fallback halaman penuh tanpa JavaScript.
- Tabel responsif dan pilihan jumlah baris 25, 50, 100, atau 500.
- Sidebar desktop yang dapat diciutkan serta drawer navigasi pada perangkat mobile.
- Transaksi stok atomik dengan validasi saldo dan pemeriksaan dependensi sebelum perubahan/penghapusan.
- Ekspor laporan CSV/XLSX, halaman Print, dan unduhan PDF.
- Infrastruktur session, cache, dan queue berbasis database.

### Registrasi Admin

Registrasi tersedia bagi guest dengan membuka `/register` secara langsung dan tidak ditautkan dari halaman Login, navbar, sidebar, maupun Dashboard. Role dan status tidak dipilih oleh pengguna: server selalu membuat akun sebagai **Admin** berstatus **ACTIVE**.

Setelah registrasi berhasil, pengguna langsung diautentikasi menggunakan session normal, session ID diregenerasi, lalu masuk ke Dashboard sebagai Admin. Registrasi tidak mengaktifkan Remember Me; setelah session berakhir, pengguna masuk kembali melalui halaman Login seperti biasa.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP, Laravel 12 |
| Tampilan | Blade, Tailwind CSS 4, Vanilla JavaScript |
| Build frontend | Vite 7 |
| Grafik | Chart.js 4 |
| Database | MySQL atau MariaDB |
| Spreadsheet | maatwebsite/excel dan PhpSpreadsheet |
| PDF | barryvdh/laravel-dompdf |
| Testing | PHPUnit 11 |
| Code style | Laravel Pint |

## Persyaratan Sistem

Siapkan perangkat berikut sebelum instalasi:

- Git.
- PHP **8.2–8.x** sesuai constraint `^8.2` pada `composer.json`.
- Composer 2.
- Node.js `^20.19.0` atau `>=22.12.0`, sesuai engine Vite 7.3.6.
- npm yang tersedia bersama instalasi Node.js.
- Server MySQL atau MariaDB.

Ekstensi PHP yang digunakan dependency proyek mencakup `bcmath`, `dom`, `fileinfo`, `gd`, `iconv`, `openssl`, `simplexml`, `xml`, `xmlreader`, `xmlwriter`, dan `zip`. Koneksi MySQL memerlukan PDO MySQL (`pdo_mysql`). Setelah dependency terpasang, periksa platform dengan:

```bash
composer check-platform-reqs
```

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Fikri248/tambak-pro.git
```

### 2. Masuk ke Folder Project

```bash
cd tambak-pro
```

### 3. Install Dependency Backend

```bash
composer install
```

### 4. Install Dependency Frontend

Untuk pengembangan lokal:

```bash
npm install
```

Untuk instalasi reproducible berdasarkan `package-lock.json`, misalnya pada CI atau deployment:

```bash
npm ci
```

### 5. Buat File Environment

Linux/macOS:

```bash
cp .env.example .env
```

Windows Command Prompt:

```bat
copy .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

File `.env` berisi konfigurasi lokal dan rahasia aplikasi. Jangan commit file tersebut ke repository.

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Buat Database

Buat database kosong bernama `tambak_pro`. Contoh melalui MySQL/MariaDB CLI:

```bash
mysql -u root -p
```

Kemudian jalankan SQL berikut:

```sql
CREATE DATABASE tambak_pro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

Ketik `exit` untuk keluar. Database juga dapat dibuat melalui phpMyAdmin, Adminer, HeidiSQL, atau aplikasi database lain.

### 8. Konfigurasi Database dan URL

Sesuaikan bagian berikut pada `.env`:

```dotenv
APP_NAME="Tambak Pro"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tambak_pro
DB_USERNAME=root
DB_PASSWORD=
```

`APP_KEY` akan terisi setelah `php artisan key:generate`. Sesuaikan username, password, host, dan port dengan instalasi database lokal. Konfigurasi `mariadb` juga tersedia bila ingin memilih driver tersebut secara eksplisit.

### 9. Jalankan Migrasi dan Seeder

Pada instalasi baru:

```bash
php artisan migrate --seed
```

Perintah tersebut membuat seluruh tabel, termasuk `sessions`, `cache`, `jobs`, dan tabel domain Tambak Pro, kemudian mengisi role, akun demo, master awal, transaksi, saldo stok, dan AuditLog. Pada environment `local` atau `testing`, proses ini juga membuat dataset demo besar secara otomatis.

Untuk menghapus seluruh isi database lalu membuat ulang data awal:

```bash
php artisan migrate:fresh --seed
```

> **Peringatan:** `migrate:fresh` menghapus seluruh tabel dan data pada database yang sedang terhubung. Gunakan hanya pada database development/testing yang aman untuk direset.

### 10. Jalankan Frontend

Untuk development dengan hot reload, buka terminal kedua:

```bash
npm run dev
```

Atau buat aset statis tanpa menjalankan Vite development server:

```bash
npm run build
```

### 11. Jalankan Laravel

Pada terminal pertama:

```bash
php artisan serve
```

Buka [http://127.0.0.1:8000](http://127.0.0.1:8000). Biarkan `php artisan serve` tetap berjalan. Jika memakai `npm run dev`, biarkan terminal Vite tetap aktif juga.

## Konfigurasi `.env`

Nilai penting pada `.env.example`:

| Variabel | Nilai awal | Keterangan |
|---|---|---|
| `APP_ENV` | `local` | Lingkungan aplikasi. |
| `APP_DEBUG` | `true` | Tampilkan detail error hanya saat development. |
| `APP_URL` | `http://localhost` | Ubah ke URL yang benar, misalnya `http://127.0.0.1:8000`. |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu aplikasi dan laporan. |
| `DB_CONNECTION` | `mysql` | Koneksi database utama. |
| `DB_DATABASE` | `tambak_pro` | Nama database lokal. |
| `SESSION_DRIVER` | `database` | Session disimpan pada tabel `sessions`. |
| `SESSION_LIFETIME` | `120` | Masa aktif session dalam menit. |
| `CACHE_STORE` | `database` | Cache dan lock memakai tabel database. |
| `QUEUE_CONNECTION` | `database` | Queue memakai tabel `jobs` bila job dikirim. |
| `MAIL_MAILER` | `log` | Email lokal ditulis ke log, bukan dikirim. |

Konfigurasi session default juga menggunakan cookie HTTP-only dan SameSite `lax`. Untuk HTTPS production, aktifkan `SESSION_SECURE_COOKIE=true`.

### Queue Worker

Konfigurasi queue database sudah tersedia, tetapi alur bisnis biasa saat ini tidak membutuhkan queue worker agar aplikasi dapat digunakan. Jalankan worker hanya ketika ada job yang memang dikirim ke queue:

```bash
php artisan queue:work
```

Script berikut tersedia untuk menjalankan server, queue listener, Pail, dan Vite secara bersamaan:

```bash
composer run dev
```

Perintah tersebut lebih berat daripada menjalankan `php artisan serve` dan `npm run dev` pada dua terminal, sehingga bersifat opsional.

## Database dan Seeder

### Seeder Default

`DatabaseSeeder` memanggil seeder dalam urutan yang menjaga relasi data:

1. Role dan user.
2. Hierarki lokasi.
3. Vendor, komoditas, dan Barang/Item.
4. Commodity Batch.
5. Pembibitan, perubahan jumlah, pemindahan stok, dan penggunaan Barang/Item.
6. Saldo `pond_stocks`.
7. AuditLog.
8. Dataset `LargeDemoSeeder` khusus environment `local` atau `testing`.

Seeder menggunakan identifier tetap dan `updateOrCreate` sehingga dapat dijalankan ulang untuk data awal. `UserSeeder` tidak menimpa password atau status akun target yang sudah ada.

Menjalankan seeder default tanpa migrasi:

```bash
php artisan db:seed
```

### Large Demo Seeder

`LargeDemoSeeder` dipanggil otomatis oleh `DatabaseSeeder` pada environment `local` dan `testing`. Artinya, `php artisan migrate --seed` atau `php artisan migrate:fresh --seed` pada lingkungan tersebut langsung menghasilkan dataset besar. Seeder tetap dapat dijalankan secara eksplisit bila perlu:

```bash
php artisan db:seed --class=LargeDemoSeeder
```

Seeder ini tidak pernah dijalankan otomatis pada production dan akan menolak eksekusi langsung di luar environment `local` atau `testing`. Data menggunakan namespace `LDM-*` dan mencakup:

- 5 Area, 25 Tambak, dan 500 Petak.
- 500 Vendor.
- 500 komoditas.
- 500 Barang/Item.
- 500 Batch.
- Masing-masing 500 transaksi Pembibitan, Pemindahan Stok, Perubahan Jumlah, dan Penggunaan Barang/Item.
- Saldo stok dan AuditLog yang sesuai dengan transaksi demo.

Transaksi baru pada dataset besar dibagikan secara round-robin dan deterministik kepada sepuluh akun Admin, sehingga setiap Admin memperoleh sekitar 50 transaksi per jenis beserta AuditLog yang konsisten. Seeder bersifat transactional, deterministic, dan idempotent selama Petak atau Batch `LDM-*` belum dipakai oleh transaksi biasa. Actor pada riwayat yang sudah ada tidak ditulis ulang saat seeder dijalankan kembali. Jika namespace atau ledger demo sudah dipakai data non-demo, seeder membatalkan proses untuk melindungi konsistensi stok dan riwayat.

## Akun dan Role

Seeder default menyediakan akun **khusus local/demo** berikut:

| Nama | Email | Password awal | Role |
|---|---|---|---|
| Abel | `abel@tambak.local` | `password` | Admin |
| Admin 01 | `admin01@tambak.local` | `password` | Admin |
| Admin 02 | `admin02@tambak.local` | `password` | Admin |
| Admin 03 | `admin03@tambak.local` | `password` | Admin |
| Admin 04 | `admin04@tambak.local` | `password` | Admin |
| Admin 05 | `admin05@tambak.local` | `password` | Admin |
| Admin 06 | `admin06@tambak.local` | `password` | Admin |
| Admin 07 | `admin07@tambak.local` | `password` | Admin |
| Admin 08 | `admin08@tambak.local` | `password` | Admin |
| Admin 09 | `admin09@tambak.local` | `password` | Admin |

Kesepuluh akun dapat menggunakan Remember Me dan mengubah password sendiri melalui menu akun di kanan Navbar. Perubahan password memverifikasi password saat ini, mempertahankan session aktif, menghapus session database lain milik akun tersebut, serta membatalkan kredensial Remember Me lama.

Role Manager tetap tersedia untuk kompatibilitas dan kebutuhan akun non-demo, dengan akses baca master serta pengelolaan transaksi. Seeder tidak menyediakan akun demo Manager dan `vendor@tambak.local` bukan lagi akun aktif.

> Jangan gunakan email atau password demo sebagai kredensial production. Deployment production harus membuat dan mengelola kredensialnya sendiri serta tidak menjalankan demo seeder tanpa keputusan eksplisit.

## Menu Aplikasi

Urutan menu berikut sesuai dengan Sidebar saat ini.

### Dashboard

Menampilkan KPI utama, posisi stok terkini, grafik berdasarkan Tambak dan komoditas, tren pembibitan/kematian/biaya pembelian Barang/Item, aktivitas transaksi, serta AuditLog terbaru. Tren biaya pembelian memakai `SUM(item_purchase_transactions.total_cost)` berdasarkan `transaction_date`. Grafik Aktivitas Transaksi membandingkan hitungan Pembibitan, Pemindahan, Perubahan Jumlah, dan Pembelian Barang/Item; seri pembelian memakai `COUNT(item_purchase_transactions.id)`. Filter periode memengaruhi data historis dan aktivitas; filter Tambak hanya memengaruhi analitik yang memiliki hubungan lokasi, sedangkan biaya dan aktivitas pembelian tetap mencakup seluruh pembelian pada periode terpilih. Stok saat ini tetap memakai posisi `pond_stocks` terkini.

### Tambak

Mengelola struktur lokasi **Area → Tambak → Petak**. Halaman detail memperlihatkan hubungan induk-anak, status, stok terkini, dan aktivitas pada lokasi. Lokasi yang masih memiliki stok atau turunan aktif dilindungi dari penonaktifan yang tidak aman.

### Komoditas

Mengelola jenis komoditas budidaya, kategori, satuan, status, Batch terkait, dan agregasi stok terkini. Kode komoditas dibuat otomatis oleh sistem.

### Vendor

Mengelola penyedia bibit, pakan, obat, jasa, atau beberapa jenis kebutuhan sekaligus. Detail Vendor menampilkan keterkaitan Batch dan kebutuhan operasional. Nomor Indonesia yang valid dapat dibuka melalui action WhatsApp.

### Barang/Item

Mengelola master Barang/Item, termasuk Jenis Barang/Item, satuan, harga acuan, dan Vendor utama. Jenis disimpan pada lookup database; Admin dapat menambah, mengubah nama, dan menghapus jenis custom yang belum digunakan. Jenis canonical Pakan, Nutrisi, Obat, dan Lainnya mempertahankan semantic `FEED`, `NUTRITION`, `MEDICINE`, dan `OTHER`. Kode Barang/Item dibuat otomatis sesuai semantic dan tetap menjadi identitas record ketika data diedit.

### Chart of Accounts

Mengelola master akun untuk kebutuhan akuntansi. **Nomor Akun diinput manual oleh Admin**, wajib numerik, disimpan sebagai teks agar nol di depan tetap utuh, dan harus unik. Deskripsi, Tipe Akun, serta Laporan Keuangan memakai dropdown berbasis database; ketiganya menyediakan **Tambah Baru** yang langsung menambahkan dan memilih opsi tanpa menutup form utama. Modul ini belum mencakup jurnal, buku besar, posting debit-kredit, saldo, transaksi akuntansi, atau laporan akuntansi.

### Pembibitan

Mencatat bibit masuk ke Petak. Proses ini membuat Commodity Batch, transaksi pembibitan, saldo stok, dan AuditLog secara atomik. Nilai per satuan dihitung oleh server dari jumlah serta total biaya.

### Pemindahan Stok

Memindahkan stok suatu Batch dari Petak asal ke Petak tujuan. Sistem memvalidasi saldo, mencegah asal dan tujuan yang sama, lalu memperbarui kedua posisi stok serta riwayat audit.

### Perubahan Jumlah

Mencatat kematian, kehilangan, koreksi masuk, atau perubahan lain. Sistem menyimpan jumlah sebelum/sesudah dan mencegah stok menjadi negatif.

### Pembelian Barang/Item

Mencatat kuantitas dan biaya pengadaan dari Vendor aktif. Nomor transaksi dibuat otomatis, total dihitung oleh server dari jumlah × harga satuan, dan mutasi dicatat pada AuditLog. Modul ini belum membuat saldo inventori Barang/Item dan belum melakukan posting jurnal atau buku besar.

### Penggunaan Barang/Item

Mencatat penggunaan Barang/Item untuk Petak/Batch, termasuk kuantitas, harga satuan, total biaya, dan Vendor bila tersedia. Transaksi ini tidak mengurangi `pond_stocks` komoditas maupun saldo inventori Barang/Item.

### Riwayat Transaksi

Menggabungkan Pembibitan, Pemindahan Stok, Perubahan Jumlah, Pembelian Barang/Item, dan Penggunaan Barang/Item dalam satu timeline. Data dapat dicari dan difilter berdasarkan jenis, lokasi, komoditas, pengguna, serta periode, kemudian dibuka ke detail transaksi sumber.

### Laporan Operasional

Menu Laporan Operasional menampilkan sembilan laporan: Stok Saat Ini, Pembibitan, Pemindahan Stok, Perubahan Jumlah, Pembelian Barang/Item, Barang/Item, Vendor, Komoditas, serta Tambak & Petak. Setiap laporan mendukung filter yang relevan, tampilan web, pilihan 25/50/100/500 baris, Print, PDF, CSV, dan XLSX. Laporan Barang/Item membaca master `feed_items`, sedangkan Laporan Pembelian Barang/Item membaca catatan pengadaan tanpa menciptakan saldo inventori atau posting akuntansi. Secara backend tersedia sepuluh keluarga laporan: implementasi Laporan Penggunaan Barang/Item tetap dipertahankan untuk kompatibilitas riwayat dan akses langsung, tetapi tidak lagi ditampilkan sebagai kartu pada menu Laporan Operasional.

## Hak Akses dan Jobdesk

### Admin

Admin bertanggung jawab atas master data dan transaksi operasional aplikasi:

- Membuat, melihat, mengubah, mengaktifkan, atau menonaktifkan master Tambak, Komoditas, Vendor, Barang/Item, dan Chart of Accounts, termasuk mengelola Jenis Barang/Item serta pilihan akuntansi.
- Membuat, melihat, mengubah, dan menghapus Pembibitan, Pemindahan Stok, Perubahan Jumlah, Pembelian Barang/Item, dan Penggunaan Barang/Item selama aturan keselamatan bisnis mengizinkan.
- Memeriksa Dashboard, Riwayat Transaksi, AuditLog yang ditampilkan, dan Laporan.
- Melakukan export, Print, dan PDF untuk kebutuhan operasional.
- Menjaga kelengkapan master, ketepatan transaksi, serta konsistensi stok dan riwayat operasional.

### Manager

Role Manager tetap mendukung transaksi operasional, tetapi tidak memiliki akun demo bawaan:

- Melihat Dashboard serta daftar/detail master tanpa mengubahnya.
- Membuat, melihat, mengubah, dan menghapus Pembibitan, Pemindahan Stok, Perubahan Jumlah, Pembelian Barang/Item, dan Penggunaan Barang/Item sesuai aturan bisnis.
- Menelusuri Riwayat Transaksi.
- Melihat, memfilter, mengekspor, mencetak, dan mengunduh laporan.
- Tidak membuat, mengubah, mengaktifkan, atau menonaktifkan master data.
- Dapat mengubah password akun sendiri.

## Matriks Otorisasi

| Kemampuan | Admin | Manager |
|---|:---:|:---:|
| Membuka Dashboard | Ya | Ya |
| Master Tambak | Kelola | Lihat |
| Master Komoditas | Kelola | Lihat |
| Master Vendor | Kelola | Lihat |
| Master Barang/Item | Kelola | Lihat |
| Jenis Barang/Item | Kelola | Lihat sebagai pilihan |
| Master Chart of Accounts | Kelola | Lihat |
| Pembibitan | Kelola | Kelola |
| Pemindahan Stok | Kelola | Kelola |
| Perubahan Jumlah | Kelola | Kelola |
| Pembelian Barang/Item | Kelola | Kelola |
| Penggunaan Barang/Item | Kelola | Kelola |
| Melihat Riwayat Transaksi | Ya | Ya |
| Melihat dan memfilter Laporan | Ya | Ya |
| Export CSV/XLSX | Ya | Ya |
| Print/PDF | Ya | Ya |
| Mengubah password sendiri | Ya | Ya |
| Logout | Ya | Ya |

Otorisasi ditegakkan pada server melalui middleware kemampuan dan validasi request. Menyembunyikan tombol pada Blade hanya bagian dari pengalaman pengguna, bukan satu-satunya lapisan keamanan.

## Export dan Laporan

Laporan mengambil data dari tabel transaksi dan posisi stok yang relevan. Pilihan output:

- **Web:** tabel terfilter dengan pagination.
- **CSV:** stream UTF-8 yang dilindungi dari formula injection spreadsheet.
- **XLSX:** workbook melalui maatwebsite/excel dan PhpSpreadsheet.
- **Print:** halaman HTML mandiri untuk dialog cetak browser.
- **PDF:** dokumen A4 landscape melalui DomPDF dengan resource remote, JavaScript, dan eksekusi PHP dinonaktifkan.

Print, PDF, dan export memuat seluruh hasil filter, bukan hanya halaman pagination yang sedang terlihat. Gunakan rentang tanggal yang wajar pada dataset besar.

## Perintah Development

### Menjalankan aplikasi

```bash
php artisan serve
npm run dev
```

### Build frontend

```bash
npm run build
```

### Database

```bash
php artisan migrate
php artisan migrate --seed
php artisan db:seed
php artisan db:seed --class=LargeDemoSeeder
```

### Inspeksi route dan konfigurasi

```bash
php artisan route:list -v
php artisan about
```

### Cache aplikasi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

Setelah mengubah `.env` atau file konfigurasi pada development, jalankan `php artisan optimize:clear` agar nilai lama tidak tetap terbaca dari cache.

## Troubleshooting

### `No application encryption key has been specified`

Pastikan `.env` sudah dibuat, lalu jalankan:

```bash
php artisan key:generate
php artisan optimize:clear
```

### `Unknown database`, `Access denied`, atau koneksi database gagal

- Pastikan service MySQL/MariaDB berjalan.
- Pastikan database `tambak_pro` sudah dibuat.
- Periksa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
- Setelah mengubah `.env`, jalankan `php artisan optimize:clear`.

### `could not find driver`

Aktifkan ekstensi PDO MySQL (`pdo_mysql`) pada `php.ini`, restart proses PHP/web server, lalu verifikasi:

```bash
php -m
composer check-platform-reqs
```

### Tabel `sessions`, `cache`, atau `jobs` tidak ditemukan

Driver default memakai database. Jalankan seluruh migration:

```bash
php artisan migrate
```

### `Vite manifest not found`

Install dependency frontend lalu pilih salah satu mode:

```bash
npm install
npm run dev
```

atau:

```bash
npm run build
```

Jika Vite development server sudah berhenti tetapi Laravel masih mencoba mengaksesnya, hapus file `public/hot` yang tersisa lalu jalankan `npm run build` atau mulai ulang `npm run dev`.

### Perubahan `.env` tidak terbaca

```bash
php artisan optimize:clear
```

Pastikan perintah dijalankan dari root project dan proses server di-restart bila perlu.

### Folder `storage` atau `bootstrap/cache` tidak dapat ditulis

Berikan izin tulis kepada user yang menjalankan PHP/web server. Jangan memberikan izin global berlebihan pada production.

### Port 8000 sudah digunakan

Gunakan port lain:

```bash
php artisan serve --port=8001
```

Kemudian sesuaikan `APP_URL`, misalnya `http://127.0.0.1:8001`.

### LargeDemoSeeder menolak berjalan

Pastikan `APP_ENV` adalah `local` atau `testing`. Seeder juga sengaja berhenti ketika namespace, Petak, atau Batch demo sudah dipakai data non-demo. Jangan mengubah pembatasan ini untuk memaksa overwrite data.

### Login akun demo gagal

Pastikan seeder default sudah dijalankan:

```bash
php artisan db:seed
```

Seeder mempertahankan password akun target yang sudah ada. Jika password pernah diubah, gunakan password terbaru. Reset penuh dengan `migrate:fresh --seed` hanya boleh dilakukan bila seluruh data development aman untuk dihapus.
