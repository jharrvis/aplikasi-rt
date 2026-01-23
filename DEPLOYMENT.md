# Panduan Deployment ke Server HestiaCP

Berikut adalah langkah-langkah deploy **Aplikasi RT Jimpitan Bot** menggunakan panel **HestiaCP** dengan domain **bot.mcimedia.net**.

## 1. Persiapan di Panel HestiaCP

1.  **Add Web Domain**:
    *   Masuk ke HestiaCP sebagai user (misal: `admin`).
    *   Ke menu **WEB** -> **Add Web Domain**.
    *   Domain: `bot.mcimedia.net`.
    *   Centang **Create DNS zone** (jika DNS diurus Hestia).
    *   Centang **Enable mail for this domain** (opsional).
    *   Save.
2.  **Setup SSL**:
    *   Edit domain `bot.mcimedia.net` tadi.
    *   Centang **Enable SSL for this domain**.
    *   Pilih **Use Lets Encrypt** -> **Enable Automatic HTTPS Rewrite**.
    *   Save.
3.  **Buat Database**:
    *   Ke menu **DB** -> **Add Database**.
    *   Database: `admin_aplikasi_rt` (prefix user otomatis, misal `admin_`).
    *   User: `admin_rt_user`.
    *   Password: *(Catat password ini)*.
    *   Save.

---

## 2. Setup Repository via SSH

Login ke VPS via SSH (gunakan user pemilik domain, misal `admin`).

```bash
ssh admin@ip-server-anda
```

### a. Clone Repository
Kita tidak clone langsung ke `public_html`, tapi ke folder terpisah agar lebih rapi karena ada backend & gateway.

```bash
# Pastikan di home directory
cd /home/admin/web/bot.mcimedia.net

# Hapus public_html bawaan (kosong)
rm -rf public_html

# Clone repo
git clone https://github.com/jharrvis/aplikasi-rt.git source_code

# Buat Symlink: public_html -> backend/public
ln -s source_code/backend/public public_html
```
*Sekarang saat akses `bot.mcimedia.net`, Hestia akan melayani file dari folder Laravel public.*

---

## 3. Setup Backend (Laravel)

Masuk ke folder backend:

```bash
cd /home/admin/web/bot.mcimedia.net/source_code/backend
```

### a. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### b. Setup Environment (.env)
```bash
cp .env.example .env
nano .env
```
Sesuaikan konfigurasi:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bot.mcimedia.net

DB_DATABASE=admin_aplikasi_rt  # Sesuaikan nama DB di Hestia
DB_USERNAME=admin_rt_user      # Sesuaikan user DB di Hestia
DB_PASSWORD=password_db_anda

OPENROUTER_API_KEY=sk-or-xxxx
WA_API_URL=http://localhost:3000
```

### c. Finalisasi Laravel
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --seed --force
```

### d. Fix Permissions
Pastikan user Hestia bisa menulis log/storage (biasanya sudah aman karena kita login sebagai user tersebut, tapi pastikan ownership benar).
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 4. Setup WhatsApp Gateway (Node.js)

Masuk ke folder gateway:
```bash
cd /home/admin/web/bot.mcimedia.net/source_code/wa-gateway
```

### a. Install Node Modules
```bash
npm install
```

### b. Setup PM2
Kita gunakan PM2 untuk menjalankan service WA agar tidak mati.
*(Jika PM2 belum ada, minta root install: `npm install -g pm2`)*

```bash
pm2 start index.js --name "wa-bot-rt"
pm2 save
pm2 startup
```
*(Ikuti instruksi `pm2 startup` jika diminta menjalankan command sebagai root)*

### c. Scan QR Code
Lihat log untuk scan QR:
```bash
pm2 logs wa-bot-rt
```
Scan pakai HP Anda.

---

## 5. Cron Job (Scheduler)

Agar Reminder & Backup jalan, setup cron job di HestiaCP.

1.  Ke menu **CRON** di HestiaCP.
2.  Klik **Add Cron Job**.
3.  Command:
    ```bash
    cd /home/admin/web/bot.mcimedia.net/source_code/backend && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
    ```
    *(Pastikan path php `/usr/bin/php` benar, atau gunakan `php` saja)*
4.  Jadwal: Set semua bintang `* * * * *` (Run every minute).
5.  Save.

---

## Selesai! 🎉

1.  Buka **https://bot.mcimedia.net/dashboard**.
2.  Test WA Bot.

### Catatan Update (Pull Terbaru)
Jika ada update di GitHub, cukup jalankan:
```bash
cd /home/admin/web/bot.mcimedia.net/source_code
git pull
cd backend
php artisan migrate --force
pm2 restart wa-bot-rt
```
