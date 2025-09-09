# Attendance Systems + RFID + Laravel + FilamentPHP

[![Laravel](https://img.shields.io/badge/laravel-12-red.svg)](https://laravel.com/)
[![FilamentPHP](https://img.shields.io/badge/filament-3.x-blueviolet.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://www.php.net/)
[![Made for Education](https://img.shields.io/badge/made%20for-education-blue.svg)](https://mtsn1pandeglang.sch.id)

---

## Tentang Proyek

**Attendance Systems (Presensi RFID)** adalah sistem presensi otomatis berbasis teknologi RFID yang dikembangkan untuk **MTs Negeri 1 Pandeglang**. Dibangun menggunakan Laravel 12, FilamentPHP 3.x, dan PHP 8.4+, sistem ini dirancang khusus untuk kebutuhan presensi siswa dan guru di lingkungan madrasah yang modern dan terintegrasi.

> MTs Negeri 1 Pandeglang

## About the Project

**Attendance Systems (RFID Presence)** is an RFID-based attendance system developed for **MTs Negeri 1 Pandeglang**. Built with Laravel 12, FilamentPHP 3.x, and PHP 8.4+, it's specifically designed for student and teacher attendance needs in a modern and integrated madrasah environment.

> MTs Negeri 1 Pandeglang

---

## Teknologi / Technology Stack

| Kategori         | Teknologi                       |
| ---------------- | ------------------------------- |
| Backend          | Laravel 12, PHP 8.4+            |
| Admin Panel      | FilamentPHP 3.x                 |
| Frontend         | Blade, TailwindCSS              |
| Database         | MySQL / MariaDB                 |
| RFID Integration | Serial input / REST API gateway |

---

## Instalasi / Installation

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/zulfikriyahya/attendance-systems.git
cd attendance-systems
composer install
npm install && npm run build
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi seperti database, mail, dan lainnya.

### 3. Migrasi dan Seeder Database

```bash
php artisan migrate --seed
```

### 4. Jalankan Server

```bash
php artisan serve
```

---

## Setup Otomatisasi / Scheduling

### 1. Tambahkan Crontab

```bash
sudo crontab -e
```

Isi dengan baris berikut:

```bash
* * * * * cd /path/to/attendance-systems && /usr/bin/php artisan presensi:set-ketidakhadiran >> /dev/null 2>&1
```

### 2. Tambahkan Laravel Queue Worker (opsional)

```bash
sudo nano /etc/systemd/system/laravel-worker.service
```

Isi file dengan:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/attendance-systems/artisan queue:work --daemon --tries=3 --timeout=120
WorkingDirectory=/var/www/attendance-systems
StandardOutput=append:/var/log/laravel-worker.log
StandardError=append:/var/log/laravel-worker-error.log

[Install]
WantedBy=multi-user.target
```

Aktifkan service:

```bash
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable --now laravel-worker
sudo systemctl enable --now cron
```

---

## Login Admin Default

| Email                                             | Password |
| ------------------------------------------------- | -------- |
| [admin@mtsn1pandeglang.sch.id](mailto:adm@mtsn1pandeglang.sch.id) | P@ssw0rd |

> **Segera ganti kredensial ini setelah login pertama.**

---

## Testing & Code Quality

Jalankan unit test:

```bash
php artisan test
```

Cek dan format kode dengan Laravel Pint:

```bash
./vendor/bin/pint
```

---

## Lisensi / License

Proyek ini adalah sistem presensi yang dikembangkan untuk keperluan pendidikan di **MTs Negeri 1 Pandeglang**.

Penggunaan sistem ini bebas untuk institusi pendidikan dengan tetap mencantumkan kredit kepada pengembang asal.

Lihat detail lisensi di [LICENSE.md](./LICENSE.md)

---

## Kontak / Contact

Untuk informasi lebih lanjut, saran, atau kerja sama pengembangan sistem:

**MTs Negeri 1 Pandeglang**
[adm@mtsn1pandeglang.sch.id](mailto:adm@mtsn1pandeglang.sch.id)
[https://mtsn1pandeglang.sch.id](https://mtsn1pandeglang.sch.id)
Jl. Raya Labuan Km. 5.7 - Kaduhejo, Pandeglang, Banten