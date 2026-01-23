# Panduan Deployment ke Production Server

Berikut adalah langkah-langkah untuk men-deploy **Aplikasi RT Jimpitan Bot** ke server production (VPS/Ubuntu).

## 1. Persyaratan Server (Prerequisites)

Pastikan server Anda sudah terinstall:
- **PHP** >= 8.2
- **Composer** (PHP Package Manager)
- **Node.js** >= 18 (disarankan pakai `nvm`)
- **MySQL / MariaDB**
- **Git**
- **Nginx / Apache** (sebagai Web Server)
- **Supervisor** (Opsional, untuk queue worker Laravel)
- **PM2** (Wajib, untuk menjalankan WA Gateway)

---

## 2. Clone Repository

Masuk ke folder web root Anda (misal `/var/www`):

```bash
cd /var/www
git clone https://github.com/jharrvis/aplikasi-rt.git
cd aplikasi-rt
```

---

## 3. Setup Backend (Laravel)

Masuk ke folder backend:

```bash
cd backend
```

### a. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### b. Setup Environment Variable (.env)
Copy file `.env.example` ke `.env` dan sesuaikan konfigurasinya:

```bash
cp .env.example .env
nano .env
```

**Penting untuk diubah:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://domain-anda.com`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (Sesuaikan dengan database Anda)
- `OPENROUTER_API_KEY` (Isi dengan API Key AI Anda)
- `WA_API_URL=http://localhost:3000` (Default WA Gateway port)

### c. Generate Key & Link Storage
```bash
php artisan key:generate
php artisan storage:link
```

### d. Setup Database
Pastikan database sudah dibuat di MySQL, lalu jalankan migrasi dan seeder:
```bash
php artisan migrate --seed --force
```

### e. Setup Permissions
Pastikan web server (misal `www-data`) bisa menulis ke folder storage:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### f. Scheduler (Cron Job)
Untuk fitur Reminder Otomatis & Backup, tambahkan cron job:

```bash
crontab -e
```
Isi baris berikut:
```bash
* * * * * cd /var/www/aplikasi-rt/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Setup WhatsApp Gateway

Masuk ke folder wa-gateway:

```bash
cd ../wa-gateway
```

### a. Install Dependencies
```bash
npm install
```

### b. Jalankan dengan PM2
Gunakan PM2 agar service tetap jalan di background:

```bash
npm install -g pm2
pm2 start index.js --name "wa-gateway"
pm2 save
pm2 startup
```

### c. Scan QR Code
Lihat log untuk scan QR code pertama kali:
```bash
pm2 logs wa-gateway
```
Scan QR yang muncul di terminal menggunakan WhatsApp di HP Anda.

---

## 5. Konfigurasi Web Server (Nginx Example)

Buat blok server Nginx baru untuk backend Laravel:

```nginx
server {
    listen 80;
    server_name domain-anda.com;
    root /var/www/aplikasi-rt/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Restart Nginx:
```bash
sudo systemctl restart nginx
```

---

## 6. Selesai! 🎉

Cek status:
1. Akses dashboard di `https://domain-anda.com/dashboard`
2. Coba kirim pesan "cek" atau "menu" ke bot WhatsApp.

---
**Catatan Penting:**
- Pastikan port `3000` (WA Gateway) tidak terekspos ke publik firewall jika tidak diperlukan, cukup diakses oleh Laravel via `localhost`.
