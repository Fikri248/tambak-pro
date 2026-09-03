# Tambak-Pro — Setup 2 Laptop via Kabel LAN

Dokumentasi ini menjelaskan konfigurasi `tambak-pro` menggunakan dua laptop yang terhubung langsung melalui kabel LAN.

Arsitektur yang digunakan:

- **Laptop 2** = aplikasi / UI
  - Laravel
  - Vite
  - Browser
  - PHP
- **Laptop 1** = database server
  - XAMPP
  - MySQL / MariaDB
  - Database `tambak_pro`

Laravel dan Vite **tidak berjalan di Laptop 1**.

MySQL **tidak perlu berjalan di Laptop 2**.

---

# 1. Arsitektur

```text
                         LAPTOP 2
              APLIKASI / UI / DEVELOPMENT
┌─────────────────────────────────────────────────────┐
│                                                     │
│  Browser                                            │
│     │                                               │
│     ▼                                               │
│  Laravel                                            │
│  http://127.0.0.1:8000                              │
│     │                                               │
│     ├──────────────► Vite                           │
│     │                http://localhost:5173          │
│     │                CSS / JS / HMR                 │
│     │                                               │
│  Ethernet                                           │
│  192.168.10.2                                       │
│     │                                               │
└─────┼───────────────────────────────────────────────┘
      │
      │ Kabel LAN
      │
      │ MySQL TCP 3306
      │
┌─────▼───────────────────────────────────────────────┐
│                                                     │
│                         LAPTOP 1                    │
│                      DATABASE SERVER                │
│                                                     │
│  Ethernet                                           │
│  192.168.10.1                                       │
│                                                     │
│  XAMPP                                              │
│    └── MySQL / MariaDB :3306                        │
│          │                                          │
│          └── database: tambak_pro                   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

Alur aplikasi:

```text
Browser Laptop 2
        │
        ▼
Laravel Laptop 2
127.0.0.1:8000
        │
        ├────────► Vite Laptop 2
        │           localhost:5173
        │
        └────────► Kabel LAN
                    │
                    ▼
                  MySQL Laptop 1
                  192.168.10.1:3306
```

---

# 2. Pembagian Fungsi Laptop

## Laptop 2 — Aplikasi/UI

Laptop 2 menjalankan:

```text
Laravel
Vite
Browser
```

Software yang dibutuhkan:

- Git
- PHP
- Composer
- Node.js
- npm
- Browser
- XAMPP jika PHP menggunakan instalasi XAMPP

Contoh lokasi project:

```text
C:\xampp\htdocs\tambak-pro
```

Di Laptop 2:

```text
Apache XAMPP : tidak wajib
MySQL XAMPP  : tidak perlu
```

Laravel dijalankan menggunakan:

```bash
php artisan serve
```

---

## Laptop 1 — Database Server

Laptop 1 menjalankan:

```text
XAMPP MySQL / MariaDB
```

Yang wajib menyala:

```text
MySQL
```

Apache tidak wajib.

Apache hanya diperlukan jika ingin menggunakan `phpMyAdmin` melalui browser di Laptop 1.

---

# 3. Hubungkan Kedua Laptop Dengan Kabel LAN

Hubungkan:

```text
Laptop 2 Ethernet
        │
        │ Kabel LAN
        │
Laptop 1 Ethernet
```

Untuk komunikasi langsung antar laptop, gunakan IP statis agar alamat database tidak berubah.

---

# 4. Konfigurasi IP Laptop 1 — Database Server

Laptop 1 akan menggunakan:

```text
IP Address      : 192.168.10.1
Subnet Mask     : 255.255.255.0
Default Gateway : kosong
DNS             : kosong
```

Buka:

```text
Control Panel
→ Network and Internet
→ Network Connections
→ Ethernet
→ Properties
→ Internet Protocol Version 4 (TCP/IPv4)
```

Set:

```text
192.168.10.1
255.255.255.0
```

---

# 5. Konfigurasi IP Laptop 2 — Aplikasi/UI

Laptop 2 menggunakan:

```text
IP Address      : 192.168.10.2
Subnet Mask     : 255.255.255.0
Default Gateway : kosong
DNS             : kosong
```

Jadi:

```text
Laptop 1 = 192.168.10.1
Laptop 2 = 192.168.10.2
```

---

# 6. Wi-Fi Tetap Bisa Digunakan

Kabel LAN hanya dipakai untuk komunikasi:

```text
Laravel Laptop 2
        ↕
MySQL Laptop 1
```

Wi-Fi tetap bisa digunakan untuk internet.

Contoh:

```text
Laptop 2
├── Wi-Fi
│   └── Internet
└── Ethernet
    └── 192.168.10.2

Laptop 1
├── Wi-Fi
│   └── Internet
└── Ethernet
    └── 192.168.10.1
```

---

# 7. Tes Koneksi Antar Laptop

Dari Laptop 2:

```bat
ping 192.168.10.1
```

Expected:

```text
Reply from 192.168.10.1
```

Dari Laptop 1:

```bat
ping 192.168.10.2
```

Expected:

```text
Reply from 192.168.10.2
```

Jika gagal:

- cek kabel LAN
- cek IP
- cek subnet
- cek Windows Firewall
- set network profile ke `Private`

---

# 8. Set Ethernet Sebagai Private Network

Di kedua laptop, gunakan network profile:

```text
Private
```

Bukan:

```text
Public
```

Jangan mematikan Windows Firewall secara keseluruhan.

---

# 9. Konfigurasi XAMPP Laptop 1

Laptop 1 adalah database server.

Buka:

```text
XAMPP Control Panel
```

Start:

```text
MySQL
```

Kondisi ideal:

```text
Apache : OPTIONAL
MySQL  : RUNNING
```

---

# 10. Konfigurasi MySQL Laptop 1 Agar Bisa Diakses Laptop 2

Buka:

```text
XAMPP
→ MySQL
→ Config
→ my.ini
```

Cari:

```ini
bind-address=127.0.0.1
```

Jika ada, ubah menjadi:

```ini
bind-address=192.168.10.1
```

Karena:

```text
192.168.10.1 = IP Ethernet Laptop 1
```

Alternatif:

```ini
bind-address=0.0.0.0
```

Tetapi untuk setup dua laptop, lebih baik:

```ini
bind-address=192.168.10.1
```

Setelah itu restart MySQL.

---

# 11. Cek Port MySQL Laptop 1

Default MySQL:

```text
3306
```

Jalankan di Laptop 1:

```bat
netstat -ano | findstr :3306
```

Expected:

```text
192.168.10.1:3306
```

atau konfigurasi listen yang sesuai.

---

# 12. Windows Firewall Laptop 1

Laptop 1 harus mengizinkan Laptop 2 mengakses MySQL.

Buat inbound rule:

```text
Protocol : TCP
Port     : 3306
Profile  : Private
```

Lebih aman jika remote IP dibatasi hanya:

```text
192.168.10.2
```

Jangan expose port `3306` ke jaringan publik.

---

# 13. Buat Database

Di Laptop 1:

```sql
CREATE DATABASE tambak_pro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Database:

```text
tambak_pro
```

---

# 14. Buat User MySQL Khusus Laravel

Jangan gunakan:

```text
root@localhost
```

untuk koneksi dari Laptop 2.

Buat user:

```sql
CREATE USER 'tambak_app'@'192.168.10.2'
IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';
```

Berikan permission:

```sql
GRANT ALL PRIVILEGES
ON tambak_pro.*
TO 'tambak_app'@'192.168.10.2';

FLUSH PRIVILEGES;
```

Artinya:

```text
tambak_app
hanya boleh digunakan dari Laptop 2
```

---

# 15. Kenapa Host MySQL = 192.168.10.2?

Karena:

```text
192.168.10.2
```

adalah Laptop 2 yang menjalankan Laravel.

Jadi:

```sql
'tambak_app'@'192.168.10.2'
```

artinya:

```text
izinkan user tambak_app
mengakses MySQL dari Laptop 2
```

---

# 16. Hindari %

Sebaiknya jangan gunakan:

```sql
'tambak_app'@'%'
```

jika tidak diperlukan.

Lebih aman menggunakan:

```sql
'tambak_app'@'192.168.10.2'
```

---

# 17. Tes Port MySQL Dari Laptop 2

Di Laptop 2 buka PowerShell:

```powershell
Test-NetConnection 192.168.10.1 -Port 3306
```

Expected:

```text
TcpTestSucceeded : True
```

Jika:

```text
False
```

cek:

1. MySQL Laptop 1 hidup
2. IP Laptop 1 benar
3. `bind-address`
4. Firewall
5. Port MySQL
6. Kabel LAN
7. Subnet

---

# 18. Clone Project Di Laptop 2

Project dijalankan dari Laptop 2.

Contoh:

```bat
cd /d C:\xampp\htdocs
```

Clone:

```bash
git clone https://github.com/Fikri248/tambak-pro.git
```

Masuk:

```bat
cd /d "C:\xampp\htdocs\tambak-pro"
```

---

# 19. Install Dependency Laravel

Laptop 2:

```bash
composer install
```

---

# 20. Install Dependency Frontend

Laptop 2:

```bash
npm ci
```

Jika diperlukan:

```bash
npm install
```

Tetapi untuk clone dengan `package-lock.json`, lebih disarankan:

```bash
npm ci
```

---

# 21. Membuat .env

Jika `.env` belum ada:

```bat
copy .env.example .env
```

Kemudian:

```bash
php artisan key:generate
```

---

# 22. Konfigurasi .env Laptop 2

Contoh:

```env
APP_NAME="Tambak Pro"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=192.168.10.1
DB_PORT=3306
DB_DATABASE=tambak_pro
DB_USERNAME=tambak_app
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Paling penting:

```env
DB_HOST=192.168.10.1
```

Karena:

```text
192.168.10.1 = Laptop 1 = database server
```

---

# 23. Jangan Gunakan 127.0.0.1 Untuk DB_HOST

Jangan:

```env
DB_HOST=127.0.0.1
```

Karena Laravel berjalan di Laptop 2.

`127.0.0.1` berarti:

```text
Laptop 2 sendiri
```

Yang benar:

```env
DB_HOST=192.168.10.1
```

---

# 24. Bersihkan Cache Laravel

Setelah mengubah `.env`:

```bash
php artisan optimize:clear
```

---

# 25. Tes Laravel Ke MySQL Laptop 1

Laptop 2:

```bash
php artisan migrate:status
```

Jika berhasil, berarti:

```text
Laravel Laptop 2
        │
        │ kabel LAN
        ▼
MySQL Laptop 1
192.168.10.1:3306
```

sudah terhubung.

---

# 26. Migration

Jika database masih kosong:

```bash
php artisan migrate
```

Command dijalankan dari Laptop 2, tetapi tabel dibuat di Laptop 1.

Alurnya:

```text
Laptop 2
php artisan migrate
        │
        ▼
Laravel
        │
        ▼
DB_HOST=192.168.10.1
        │
        │ Kabel LAN
        ▼
Laptop 1
MySQL
        │
        ▼
tambak_pro
```

---

# 27. Peringatan migrate:fresh

Command:

```bash
php artisan migrate:fresh
```

akan menghapus seluruh tabel database yang ditunjuk `.env`.

Karena:

```env
DB_HOST=192.168.10.1
```

maka yang dihapus adalah database di Laptop 1.

Gunakan hanya jika memang ingin reset database.

Jangan digunakan pada database yang berisi data penting.

---

# 28. Registrasi Setelah Database Kosong

Setelah:

```bash
php artisan migrate:fresh
```

tanpa seed, project tetap mendukung registrasi Admin pertama.

Buka:

```text
http://127.0.0.1:8000/register
```

Flow:

```text
Register
→ role Admin dibuat jika belum ada
→ user ACTIVE dibuat
→ login otomatis
→ session regenerate
→ /dashboard
```

---

# 29. Vite Laptop 2

Vite berjalan di Laptop 2.

Browser juga berjalan di Laptop 2.

Karena itu tidak perlu konfigurasi HMR LAN.

Gunakan konfigurasi portable:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),

        tailwindcss(),
    ],

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

---

# 30. Jangan Hardcode IP Database Di Vite

Jangan:

```js
hmr: {
    host: '192.168.10.1',
}
```

Karena:

```text
192.168.10.1
```

adalah MySQL Laptop 1.

Bukan Vite.

---

# 31. Jangan Gunakan hostz

Config ini salah:

```js
server: {
    hostz: true,
}
```

`hostz` bukan konfigurasi Vite.

Untuk arsitektur ini, bahkan:

```js
host: true
```

tidak diperlukan.

---

# 32. public/hot

Saat:

```bash
npm run dev
```

Vite dapat membuat:

```text
public/hot
```

`hot` adalah file, bukan folder.

Contoh:

```text
http://localhost:5173
```

File tersebut:

- runtime only
- tidak perlu diedit manual
- tidak boleh di-hardcode
- seharusnya di-ignore Git

---

# 33. Jalankan Laravel Laptop 2

Terminal 1:

```bat
cd /d "C:\xampp\htdocs\tambak-pro"
```

Kemudian:

```bash
php artisan serve
```

Expected:

```text
http://127.0.0.1:8000
```

---

# 34. Jalankan Vite Laptop 2

Terminal 2:

```bat
cd /d "C:\xampp\htdocs\tambak-pro"
```

Kemudian:

```bash
npm run dev
```

Expected:

```text
VITE ready

Local:
http://localhost:5173
```

---

# 35. Buka Aplikasi

Browser Laptop 2:

```text
http://127.0.0.1:8000
```

Jangan gunakan:

```text
http://localhost:5173
```

sebagai halaman aplikasi.

Port:

```text
8000
```

adalah Laravel.

Port:

```text
5173
```

adalah Vite development asset server.

---

# 36. Flow Development

```text
Browser Laptop 2
http://127.0.0.1:8000
          │
          ▼
Laravel :8000
          │
          ├──────────────► Vite :5173
          │                  CSS
          │                  JS
          │                  HMR
          │
          │
          └──────────────► Kabel LAN
                              │
                              ▼
                         MySQL Laptop 1
                         192.168.10.1:3306
```

---

# 37. start-tambak.bat Laptop 2

Buat:

```text
start-tambak.bat
```

Isi:

```bat
@echo off
title Tambak-Pro - Laravel

echo ==========================================
echo       START TAMBAK-PRO LARAVEL
echo ==========================================
echo.

echo [1] Masuk ke folder project...
cd /d "C:\xampp\htdocs\tambak-pro"

if errorlevel 1 (
    echo.
    echo ERROR: Folder project tidak ditemukan.
    pause
    exit /b 1
)

echo Folder saat ini:
cd

echo.
echo [2] Mengecek PHP...
"C:\xampp\php\php.exe" --version

if errorlevel 1 (
    echo.
    echo ERROR: PHP XAMPP tidak dapat dijalankan.
    pause
    exit /b 1
)

echo.
echo [3] Menjalankan Laravel...
echo.
echo URL aplikasi:
echo http://127.0.0.1:8000
echo.

"C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000

echo.
echo ==========================================
echo       LARAVEL BERHENTI
echo ==========================================
echo.

pause
```

Tidak menyalakan:

```text
Apache Laptop 2
MySQL Laptop 2
```

---

# 38. start-vite.bat Laptop 2

Buat:

```text
start-vite.bat
```

Isi:

```bat
@echo off
title Tambak-Pro - Vite

echo ==========================================
echo        TAMBAK-PRO VITE SERVER
echo ==========================================
echo.

echo [1] Masuk ke folder project...
cd /d "C:\xampp\htdocs\tambak-pro"

if errorlevel 1 (
    echo.
    echo ERROR: Folder project tidak ditemukan.
    pause
    exit /b 1
)

echo Folder saat ini:
cd

echo.
echo [2] Mengecek Node.js...
"C:\Program Files\nodejs\node.exe" --version

if errorlevel 1 (
    echo.
    echo ERROR: Node.js tidak ditemukan.
    pause
    exit /b 1
)

echo.
echo [3] Mengecek NPM...
call "C:\Program Files\nodejs\npm.cmd" --version

if errorlevel 1 (
    echo.
    echo ERROR: NPM tidak ditemukan.
    pause
    exit /b 1
)

echo.
echo [4] Menjalankan Vite...
echo.

call "C:\Program Files\nodejs\npm.cmd" run dev

echo.
echo ==========================================
echo       VITE BERHENTI
echo ==========================================
echo.

pause
```

---

# 39. Startup Harian

## Laptop 1

1. Nyalakan Laptop 1.
2. Hubungkan kabel LAN.
3. Buka XAMPP.
4. Start MySQL.

Expected:

```text
MySQL [Running]
```

---

## Laptop 2

1. Nyalakan Laptop 2.
2. Hubungkan kabel LAN.
3. Tes:

```bat
ping 192.168.10.1
```

4. Tes MySQL:

```powershell
Test-NetConnection 192.168.10.1 -Port 3306
```

5. Jalankan:

```text
start-tambak.bat
```

6. Jalankan:

```text
start-vite.bat
```

7. Buka:

```text
http://127.0.0.1:8000
```

---

# 40. Kondisi XAMPP Laptop 1

```text
Laptop 1

Apache : OPTIONAL
MySQL  : ON
```

---

# 41. Kondisi XAMPP Laptop 2

```text
Laptop 2

Apache : OFF
MySQL  : OFF
```

PHP dari XAMPP tetap dapat dipakai tanpa menyalakan Apache:

```text
C:\xampp\php\php.exe
```

---

# 42. Mode Tanpa npm run dev

Jika tidak ingin menjalankan Vite dev server:

```bash
npm run build
```

Setelah itu:

```bash
php artisan serve
```

Laravel menggunakan:

```text
public/build
```

Browser tetap:

```text
http://127.0.0.1:8000
```

---

# 43. Development Mode

Jalankan dua proses:

```bash
php artisan serve
```

dan:

```bash
npm run dev
```

Keuntungan:

```text
HMR
Live CSS
Live JavaScript
```

---

# 44. Jangan Jalankan Dua Vite

Pastikan hanya satu Vite untuk project.

Cek port:

```bat
netstat -ano | findstr :5173
```

Jika ada proses lama, Vite baru bisa pindah ke:

```text
5174
5175
```

dan dapat membingungkan proses debugging.

---

# 45. Troubleshooting CSS Tidak Load

Jika:

```text
php artisan serve
```

normal tetapi:

```text
php artisan serve
+
npm run dev
```

rusak, cek:

```text
public/hot
```

Saat Vite hidup, harus menunjuk ke Vite Laptop 2.

Contoh valid:

```text
http://localhost:5173
```

Jangan sampai menunjuk IP lama yang tidak aktif.

---

# 46. Browser Network Audit

Buka:

```text
F12
→ Network
```

Dalam mode Vite harus berhasil:

```text
@vite/client
resources/css/app.css
resources/js/app.js
```

Expected:

```text
HTTP 200
```

CSS:

```text
text/css
```

JavaScript:

```text
text/javascript
```

---

# 47. Troubleshooting Database Connection Refused

Jika muncul:

```text
SQLSTATE[HY000] [2002]
Connection refused
```

cek:

```text
1. Kabel LAN
2. ping 192.168.10.1
3. MySQL Laptop 1
4. Port 3306
5. bind-address
6. Firewall Laptop 1
7. DB_HOST Laptop 2
8. Username
9. Password
10. Permission host MySQL
```

---

# 48. Troubleshooting Ping

Jika:

```bat
ping 192.168.10.1
```

gagal, jangan debugging Laravel dulu.

Pastikan:

```text
Laptop 1
192.168.10.1
255.255.255.0

Laptop 2
192.168.10.2
255.255.255.0
```

---

# 49. Troubleshooting Port 3306

Laptop 2:

```powershell
Test-NetConnection 192.168.10.1 -Port 3306
```

Jika:

```text
TcpTestSucceeded : False
```

cek:

- MySQL Laptop 1
- Firewall
- bind-address
- port
- LAN

---

# 50. Troubleshooting Access Denied

Jika:

```text
Access denied for user 'tambak_app'@'192.168.10.2'
```

cek Laptop 1:

```sql
SELECT User, Host
FROM mysql.user;
```

Pastikan:

```text
tambak_app | 192.168.10.2
```

Jika belum:

```sql
CREATE USER 'tambak_app'@'192.168.10.2'
IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_KUAT';

GRANT ALL PRIVILEGES
ON tambak_pro.*
TO 'tambak_app'@'192.168.10.2';

FLUSH PRIVILEGES;
```

---

# 51. Troubleshooting Unknown Database

Jika:

```text
Unknown database 'tambak_pro'
```

buat di Laptop 1:

```sql
CREATE DATABASE tambak_pro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

# 52. APP_URL Laptop 2

Gunakan:

```env
APP_URL=http://127.0.0.1:8000
```

Setelah perubahan:

```bash
php artisan optimize:clear
```

---

# 53. File Yang Tidak Boleh Di-Commit

Jangan commit:

```text
.env
public/hot
node_modules
vendor
```

sesuai kebijakan `.gitignore`.

---

# 54. Checklist Laptop 1 — Database Server

```text
[ ] Ethernet = 192.168.10.1
[ ] Network profile = Private
[ ] XAMPP terinstall
[ ] MySQL hidup
[ ] MySQL listen pada interface Ethernet
[ ] TCP 3306 diizinkan firewall
[ ] Database tambak_pro tersedia
[ ] User tambak_app tersedia
[ ] User mengizinkan host 192.168.10.2
```

---

# 55. Checklist Laptop 2 — Aplikasi/UI

```text
[ ] Ethernet = 192.168.10.2
[ ] ping 192.168.10.1 berhasil
[ ] port 3306 berhasil
[ ] Source code tambak-pro tersedia
[ ] Composer dependency terinstall
[ ] npm dependency terinstall
[ ] .env tersedia
[ ] DB_HOST=192.168.10.1
[ ] APP_URL=http://127.0.0.1:8000
[ ] Laravel berjalan
[ ] Vite berjalan
[ ] Browser membuka 127.0.0.1:8000
```

---

# 56. Shutdown

Laptop 2:

```text
1. Stop Vite dengan Ctrl+C
2. Stop Laravel dengan Ctrl+C
```

Laptop 1:

```text
3. Stop MySQL melalui XAMPP
4. Shutdown jika diperlukan
```

---

# 57. Konfigurasi Final Laptop 1

```text
Role:
Database Server

Ethernet:
192.168.10.1

XAMPP MySQL:
ON

MySQL:
192.168.10.1:3306

Database:
tambak_pro

Database User:
tambak_app

Allowed Host:
192.168.10.2

Apache:
Optional
```

---

# 58. Konfigurasi Final Laptop 2

```text
Role:
Aplikasi / UI / Development

Ethernet:
192.168.10.2

Project:
C:\xampp\htdocs\tambak-pro

Laravel:
127.0.0.1:8000

Vite:
localhost:5173

Browser:
http://127.0.0.1:8000

XAMPP Apache:
OFF

XAMPP MySQL:
OFF
```

---

# 59. .env Final Laptop 2

```env
APP_NAME="Tambak Pro"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=192.168.10.1
DB_PORT=3306
DB_DATABASE=tambak_pro
DB_USERNAME=tambak_app
DB_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

---

# 60. vite.config.js Final Laptop 2

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),

        tailwindcss(),
    ],

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

---

# 61. Ringkasan Final

```text
LAPTOP 2
192.168.10.2

Browser
   │
   ▼
Laravel :8000
   │
   ├──────────────► Vite :5173
   │
   │
   └──────────────► Kabel LAN
                      │
                      ▼
LAPTOP 1
192.168.10.1
MySQL :3306
   │
   ▼
tambak_pro
```

Yang melewati kabel LAN hanya:

```text
Laravel Laptop 2
↕
MySQL Laptop 1
```

Sedangkan:

```text
Browser ↔ Laravel
Browser ↔ Vite
Laravel ↔ Vite
```

semuanya terjadi secara lokal di Laptop 2.

Konfigurasi akhir:

```text
Laptop 1
= Database Server

Laptop 2
= Aplikasi + Laravel + Vite + Browser
```

# 62. Urutan Startup Yang Disarankan

Agar tidak bingung saat menyalakan sistem setiap hari, gunakan urutan berikut.

## Laptop 1 — Database Server

1. Nyalakan Laptop 1.
2. Hubungkan kabel LAN.
3. Pastikan Ethernet menggunakan:

```text
192.168.10.1
```

4. Buka XAMPP Control Panel.
5. Start MySQL.

Expected:

```text
MySQL [Running]
```

Apache:

```text
Optional
```

---

## Laptop 2 — Aplikasi/UI

1. Nyalakan Laptop 2.
2. Hubungkan kabel LAN.
3. Pastikan Ethernet menggunakan:

```text
192.168.10.2
```

4. Tes Laptop 1:

```bat
ping 192.168.10.1
```

Expected:

```text
Reply from 192.168.10.1
```

5. Tes port database:

```powershell
Test-NetConnection 192.168.10.1 -Port 3306
```

Expected:

```text
TcpTestSucceeded : True
```

6. Jalankan:

```text
start-tambak.bat
```

7. Jalankan:

```text
start-vite.bat
```

8. Buka browser:

```text
http://127.0.0.1:8000
```

---

# 63. Urutan Shutdown Yang Disarankan

Shutdown sebaiknya dilakukan dengan urutan:

## Laptop 2

Stop Vite:

```text
Ctrl + C
```

Stop Laravel:

```text
Ctrl + C
```

Tutup browser jika diperlukan.

---

## Laptop 1

Setelah aplikasi tidak digunakan:

```text
XAMPP
→ Stop MySQL
```

Setelah MySQL berhenti dengan normal, Laptop 1 dapat dimatikan.

Jangan mematikan Laptop 1 secara paksa ketika MySQL sedang melakukan proses
write.

---

# 64. Cara Memastikan Laravel Menggunakan Database Laptop 1

Di Laptop 2 jalankan:

```bash
php artisan migrate:status
```

Jika command berhasil, Laravel sudah dapat berkomunikasi dengan database Laptop
1.

Bisa juga gunakan:

```bash
php artisan tinker
```

Kemudian:

```php
DB::connection()->getPdo();
```

Jika tidak menghasilkan exception, koneksi database berhasil.

Keluar dari Tinker:

```php
exit
```

---

# 65. Cek Database Yang Sedang Digunakan

Di Laptop 2:

```bash
php artisan tinker
```

Kemudian:

```php
DB::connection()->getDatabaseName();
```

Expected:

```text
tambak_pro
```

Ini penting untuk memastikan Laravel tidak tanpa sengaja menggunakan database
lokal lain.

---

# 66. Cek Host Database Laravel

Pastikan `.env` Laptop 2:

```env
DB_HOST=192.168.10.1
```

Setelah mengubah `.env`, selalu jalankan:

```bash
php artisan optimize:clear
```

Jika tidak, Laravel dapat masih menggunakan konfigurasi lama dari cache.

---

# 67. Jangan Menyalakan MySQL Laptop 2 Untuk Menghindari Kebingungan

Karena database utama berada di Laptop 1, disarankan MySQL Laptop 2:

```text
OFF
```

Tujuannya agar tidak terjadi kondisi seperti:

```text
Laravel salah konfigurasi
DB_HOST=127.0.0.1

↓
Laravel diam-diam terhubung ke MySQL Laptop 2
```

Padahal seharusnya:

```text
DB_HOST=192.168.10.1
```

---

# 68. Database Server Tidak Membutuhkan Source Code

Laptop 1 tidak perlu mempunyai:

```text
Laravel
Node.js
Vite
source code tambak-pro
```

Laptop 1 cukup memiliki:

```text
XAMPP
MySQL / MariaDB
database tambak_pro
```

Semua source code tetap berada di Laptop 2.

---

# 69. Vite Tidak Mengakses Database

Perlu dibedakan:

```text
Vite
```

tidak melakukan koneksi langsung ke MySQL.

Vite hanya menangani:

```text
CSS
JavaScript
Hot Module Replacement
Frontend development assets
```

Alur database tetap:

```text
Browser
   ↓
Laravel
   ↓
MySQL
```

Bukan:

```text
Browser
   ↓
Vite
   ↓
MySQL
```

---

# 70. Peran Laravel

Laravel di Laptop 2 menangani:

```text
Authentication
Authorization
Session
Business Logic
Validation
Database Query
CRUD
Reports
Exports
Dashboard
API internal jika ada
```

Laravel yang berkomunikasi langsung dengan:

```text
192.168.10.1:3306
```

---

# 71. Peran Vite

Vite di Laptop 2 menangani development asset:

```text
resources/css/app.css
resources/js/app.js
```

dan fitur:

```text
HMR
Live Reload
CSS rebuild
JavaScript rebuild
```

Normalnya berjalan di:

```text
localhost:5173
```

---

# 72. Peran MySQL Laptop 1

Laptop 1 menyimpan data seperti:

```text
users
roles
locations
vendors
vendor_types
commodities
feed_items
commodity_batches
pond_stocks
stocking_transactions
stock_movements
stock_adjustments
feeding_transactions
chart_of_accounts
sessions
cache
jobs
audit logs
dan tabel lainnya
```

Seluruh data persisten berada di Laptop 1.

---

# 73. Dampak Jika Kabel LAN Dicabut

Jika kabel LAN terputus:

```text
Browser Laptop 2
Laravel Laptop 2
Vite Laptop 2
```

masih dapat berjalan.

Tetapi Laravel tidak dapat mengakses:

```text
MySQL Laptop 1
```

Akibatnya halaman yang membutuhkan database dapat menghasilkan error koneksi.

Contoh:

```text
SQLSTATE[HY000] [2002]
Connection timed out
```

atau:

```text
Connection refused
```

Setelah kabel tersambung kembali dan koneksi database normal, Laravel biasanya
dapat kembali digunakan tanpa restart.

---

# 74. Dampak Jika MySQL Laptop 1 Mati

Jika MySQL Laptop 1 dihentikan:

```text
Laravel
→ gagal mengakses database
```

Vite tetap dapat berjalan.

Browser tetap dapat membuka koneksi ke Laravel, tetapi halaman aplikasi yang
membutuhkan database akan gagal.

Solusi:

```text
Laptop 1
→ XAMPP
→ Start MySQL
```

---

# 75. Dampak Jika Vite Mati

Jika `npm run dev` sedang digunakan dan Vite tiba-tiba mati, Laravel dapat
kehilangan akses ke development asset.

Jika file:

```text
public/hot
```

masih menunjuk ke Vite yang sudah mati, CSS/JavaScript dapat gagal dimuat.

Solusi:

1. Pastikan Vite benar-benar berhenti.
2. Jalankan kembali:

```bash
npm run dev
```

atau gunakan built assets:

```bash
npm run build
```

---

# 76. Built Asset Mode

Jika aplikasi tidak sedang dikembangkan, lebih sederhana menggunakan:

```bash
npm run build
```

Setelah build:

```bash
php artisan serve
```

Tanpa:

```bash
npm run dev
```

Laravel akan mengambil asset dari:

```text
public/build
```

Mode ini cocok untuk:

```text
demo
presentasi
pengujian stabil
```

---

# 77. Development Mode

Untuk development aktif gunakan:

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Browser:

```text
http://127.0.0.1:8000
```

Mode ini cocok ketika sedang:

```text
mengubah Blade
mengubah CSS
mengubah JavaScript
mengubah UI
```

---

# 78. Rekomendasi Mode Untuk Presentasi

Untuk presentasi yang tidak membutuhkan live-edit frontend, disarankan:

```bash
npm run build
```

Kemudian:

```bash
php artisan serve
```

Keuntungannya:

```text
tidak bergantung pada Vite dev server
tidak ada HMR
lebih sedikit proses
lebih kecil kemungkinan asset dev terputus
```

Database tetap:

```text
Laptop 1
192.168.10.1:3306
```

---

# 79. Rekomendasi Mode Untuk Development

Untuk development:

```text
Laptop 1:
MySQL ON

Laptop 2:
Laravel ON
Vite ON
Browser ON
```

Command Laptop 2:

```bash
php artisan serve
```

dan:

```bash
npm run dev
```

---

# 80. Firewall Yang Dibutuhkan

Untuk arsitektur ini, koneksi antar laptop yang penting adalah:

```text
Laptop 2
        ↓
Laptop 1 TCP 3306
```

Jadi firewall utama yang perlu dikonfigurasi berada di:

```text
Laptop 1
```

Inbound:

```text
TCP 3306
```

Remote IP:

```text
192.168.10.2
```

Profile:

```text
Private
```

---

# 81. Port Yang Digunakan

| Service | Laptop | Port |
|---|---|---:|
| Laravel | Laptop 2 | 8000 |
| Vite | Laptop 2 | 5173 |
| MySQL/MariaDB | Laptop 1 | 3306 |
| Apache | Laptop 1 | 80/443 jika digunakan |

Port:

```text
8000
5173
```

tidak perlu dibuka ke Laptop 1 karena browser dan aplikasi semuanya berada di
Laptop 2.

Yang harus bisa dilewati kabel LAN adalah:

```text
3306
```

---

# 82. Security Dasar Database

Jangan menggunakan user MySQL seperti:

```text
root
```

untuk aplikasi Laravel melalui LAN jika tidak diperlukan.

Gunakan:

```text
tambak_app
```

dengan akses hanya ke:

```text
tambak_pro
```

dan hanya dari:

```text
192.168.10.2
```

---

# 83. Jangan Membuka MySQL Ke Semua Host

Hindari:

```sql
'tambak_app'@'%'
```

kecuali benar-benar dibutuhkan.

Prefer:

```sql
'tambak_app'@'192.168.10.2'
```

---

# 84. Jangan Membuka Firewall Ke Public Network

Rule port 3306 sebaiknya:

```text
Profile:
Private

Remote IP:
192.168.10.2
```

Jangan:

```text
Any IP
Public
```

jika tidak diperlukan.

---

# 85. Backup Database Laptop 1

Karena semua data berada di Laptop 1, backup utama dilakukan dari Laptop 1.

Contoh menggunakan `mysqldump`:

```bat
"C:\xampp\mysql\bin\mysqldump.exe" ^
-u root ^
-p ^
tambak_pro > C:\backup\tambak_pro.sql
```

Jika menggunakan user khusus backup, sesuaikan username.

Pastikan folder:

```text
C:\backup
```

sudah tersedia.

---

# 86. Restore Database

PERINGATAN:

Restore akan memasukkan data ke database target.

Contoh:

```bat
"C:\xampp\mysql\bin\mysql.exe" ^
-u root ^
-p ^
tambak_pro < C:\backup\tambak_pro.sql
```

Pastikan database target benar sebelum restore.

---

# 87. Backup Sebelum Migration Besar

Sebelum menjalankan perubahan schema penting dari Laptop 2:

```bash
php artisan migrate
```

disarankan membuat backup database terlebih dahulu jika database Laptop 1 sudah
berisi data penting.

Terutama sebelum:

```text
migration besar
normalisasi tabel
perubahan foreign key
perubahan schema produksi
```

---

# 88. Jangan Sembarangan Menjalankan migrate:fresh

Command:

```bash
php artisan migrate:fresh
```

akan:

```text
DROP seluruh tabel
+
menjalankan migration dari awal
```

Karena database Laravel berada di Laptop 1, maka data Laptop 1 yang akan
terhapus.

Gunakan hanya jika memang ingin reset database.

---

# 89. migrate:fresh Tanpa Seeder

Jika database memang sengaja ingin dikosongkan:

```bash
php artisan migrate:fresh
```

Project mendukung registrasi Admin pertama tanpa membutuhkan:

```bash
php artisan db:seed
```

Buka:

```text
http://127.0.0.1:8000/register
```

---

# 90. Database Seeder

Jika development membutuhkan dataset demo:

```bash
php artisan db:seed
```

Pastikan database yang sedang dituju memang database development.

Selalu cek:

```env
DB_HOST
DB_DATABASE
```

sebelum seeding.

---

# 91. Cek .env Sebelum Command Destruktif

Sebelum:

```bash
php artisan migrate:fresh
```

atau:

```bash
php artisan db:seed
```

cek:

```env
DB_HOST=192.168.10.1
DB_DATABASE=tambak_pro
```

Pastikan itu memang database yang ingin dimodifikasi.

---

# 92. Git Workflow Laptop 2

Source code berada di Laptop 2.

Contoh update:

```bash
git status
```

Kemudian:

```bash
git pull
```

Setelah source berubah, jika dependency PHP berubah:

```bash
composer install
```

Jika dependency frontend berubah:

```bash
npm ci
```

Jika migration baru tersedia:

```bash
php artisan migrate
```

Jika frontend production asset dibutuhkan:

```bash
npm run build
```

---

# 93. Setelah Git Pull

Urutan aman secara umum:

```bash
git pull
composer install
npm ci
php artisan optimize:clear
php artisan migrate
npm run build
```

Tidak semua command selalu diperlukan.

Contoh:

Jika tidak ada perubahan Composer:

```text
composer install
```

mungkin tidak diperlukan.

Jika tidak ada perubahan migration:

```text
php artisan migrate
```

tidak akan melakukan perubahan.

---

# 94. Jangan Commit .env

`.env` berisi konfigurasi lokal Laptop 2 seperti:

```env
DB_HOST=192.168.10.1
DB_USERNAME=tambak_app
DB_PASSWORD=...
```

Jangan commit file tersebut ke Git.

Gunakan:

```text
.env.example
```

untuk template konfigurasi umum.

---

# 95. Jangan Commit public/hot

`public/hot` adalah runtime marker Vite.

File itu dapat berisi alamat dev server lokal seperti:

```text
http://localhost:5173
```

Jangan commit.

---

# 96. Jangan Hardcode IP Laptop Pada Source Code

IP:

```text
192.168.10.1
```

sebaiknya hanya berada pada konfigurasi environment/database yang memang
membutuhkannya.

Jangan hardcode ke:

```text
Controller
Model
Blade
JavaScript
vite.config.js
```

Database host harus berasal dari:

```env
DB_HOST
```

---

# 97. Jika IP LAN Ingin Diganti

Misalnya ingin menggunakan:

```text
Laptop 1 = 10.10.10.1
Laptop 2 = 10.10.10.2
```

maka bagian yang harus disesuaikan antara lain:

Laptop 1:

```ini
bind-address=10.10.10.1
```

MySQL user:

```sql
'tambak_app'@'10.10.10.2'
```

Firewall remote IP:

```text
10.10.10.2
```

Laptop 2 `.env`:

```env
DB_HOST=10.10.10.1
```

Vite tidak perlu diubah.

---

# 98. Jika Laptop 1 Juga Menggunakan Wi-Fi

Pastikan Laravel tetap menggunakan:

```env
DB_HOST=192.168.10.1
```

bukan IP Wi-Fi Laptop 1.

Dengan begitu traffic database akan lewat interface kabel LAN.

---

# 99. Kenapa Menggunakan Subnet Khusus

Contoh:

```text
192.168.10.1
192.168.10.2
```

membuat komunikasi antar kedua laptop terpisah secara jelas dari Wi-Fi.

Contoh:

```text
Wi-Fi:
192.168.1.x

LAN:
192.168.10.x
```

Sehingga routing lebih mudah dipahami.

---

# 100. Final Architecture

```text
                         LAPTOP 2
                    192.168.10.2
┌──────────────────────────────────────────────┐
│                                              │
│ Browser                                      │
│    │                                         │
│    ▼                                         │
│ Laravel :8000                                │
│    │                                         │
│    ├──────────────► Vite :5173               │
│    │                  │                      │
│    │                  ├─ CSS                 │
│    │                  ├─ JavaScript          │
│    │                  └─ HMR                 │
│    │                                         │
└────┼─────────────────────────────────────────┘
     │
     │ Ethernet
     │ Kabel LAN
     │ TCP 3306
     │
┌────▼─────────────────────────────────────────┐
│                                              │
│                    LAPTOP 1                  │
│                 192.168.10.1                 │
│                                              │
│ XAMPP                                        │
│   └── MySQL / MariaDB :3306                  │
│         │                                    │
│         ▼                                    │
│     tambak_pro                               │
│                                              │
└──────────────────────────────────────────────┘
```

---

# 101. Final Role

## Laptop 1

```text
DATABASE SERVER
```

Menjalankan:

```text
XAMPP MySQL / MariaDB
```

IP:

```text
192.168.10.1
```

Port:

```text
3306
```

---

## Laptop 2

```text
APPLICATION / UI
```

Menjalankan:

```text
Laravel
Vite
Browser
```

IP Ethernet:

```text
192.168.10.2
```

Laravel:

```text
http://127.0.0.1:8000
```

Vite:

```text
http://localhost:5173
```

---

# 102. Final Daily Command

Laptop 1:

```text
XAMPP
→ Start MySQL
```

Laptop 2 Terminal 1:

```bash
php artisan serve
```

Laptop 2 Terminal 2:

```bash
npm run dev
```

Laptop 2 Browser:

```text
http://127.0.0.1:8000
```

---

# 103. Final Connection Test

Laptop 2:

```bat
ping 192.168.10.1
```

Kemudian:

```powershell
Test-NetConnection 192.168.10.1 -Port 3306
```

Expected:

```text
Ping:
Success

TCP 3306:
TcpTestSucceeded : True
```

Setelah itu:

```bash
php artisan migrate:status
```

Jika ketiganya berhasil:

```text
LAN
MySQL
Laravel database connection
```

sudah siap.

---

# 104. Quick Troubleshooting Flow

Jika aplikasi bermasalah, cek urutan:

```text
Apakah Laptop 1 hidup?
        │
        ▼
Apakah kabel LAN terhubung?
        │
        ▼
ping 192.168.10.1 berhasil?
        │
        ▼
TCP 3306 berhasil?
        │
        ▼
MySQL Laptop 1 hidup?
        │
        ▼
DB_HOST=192.168.10.1?
        │
        ▼
php artisan migrate:status berhasil?
        │
        ▼
Laravel hidup?
        │
        ▼
Vite hidup?
        │
        ▼
Browser 127.0.0.1:8000
```

Jangan langsung mengubah source code sebelum menentukan apakah masalah berasal
dari:

```text
LAN
MySQL
Laravel
atau Vite
```

---

# 105. Quick Reference

```text
===============================================
LAPTOP 1 — DATABASE SERVER
===============================================

Ethernet:
192.168.10.1

Subnet:
255.255.255.0

XAMPP MySQL:
ON

MySQL Port:
3306

Database:
tambak_pro

Database User:
tambak_app

Allowed Client:
192.168.10.2


===============================================
LAPTOP 2 — APLIKASI / UI
===============================================

Ethernet:
192.168.10.2

Subnet:
255.255.255.0

Laravel:
127.0.0.1:8000

Vite:
localhost:5173

Project:
C:\xampp\htdocs\tambak-pro

Database Host:
192.168.10.1

Browser:
http://127.0.0.1:8000


===============================================
STARTUP
===============================================

Laptop 1:
Start XAMPP MySQL

Laptop 2:
start-tambak.bat
start-vite.bat

Browser:
http://127.0.0.1:8000


===============================================
CONNECTION TEST
===============================================

ping 192.168.10.1

Test-NetConnection 192.168.10.1 -Port 3306

php artisan migrate:status
```

---

# 106. Kesimpulan

Arsitektur final sistem adalah:

```text
Laptop 2
= aplikasi/UI

Laptop 1
= database server
```

Laptop 2 menjalankan:

```text
Browser
Laravel
Vite
```

Laptop 1 menjalankan:

```text
XAMPP MySQL / MariaDB
```

Komunikasi database dilakukan melalui kabel LAN:

```text
Laptop 2
192.168.10.2

        │
        │ Kabel LAN
        │ TCP 3306
        ▼

Laptop 1
192.168.10.1
```

Laravel menggunakan:

```env
DB_HOST=192.168.10.1
```

Vite tetap lokal di Laptop 2:

```text
localhost:5173
```

Browser juga lokal di Laptop 2:

```text
127.0.0.1:8000
```

Dengan demikian:

```text
Browser ↔ Laravel
= lokal Laptop 2

Browser ↔ Vite
= lokal Laptop 2

Laravel ↔ MySQL
= kabel LAN Laptop 2 ↔ Laptop 1
```

Konfigurasi ini menjaga peran kedua laptop tetap jelas:

```text
Laptop 1
→ menyimpan dan melayani database

Laptop 2
→ menjalankan seluruh aplikasi dan UI
```
