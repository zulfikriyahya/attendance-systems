# Services Settings

## Service MTs Negeri 1 Pandeglang

```bash
sudo nano /etc/systemd/system/presensi-mtsn1.service
```

```bash
[Unit]
Description=WORKER MTSN1
After=network.target
[Service]
User=www
Group=www
Restart=always
RestartSec=10
ExecStart=/usr/bin/php /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work --queue=default,whatsapp --sleep=5 --tries=3 --max-time=36000 --timeout=120 --memory=768
WorkingDirectory=/www/wwwroot/presensi.mtsn1pandeglang.sch.id
StandardOutput=append:/var/log/laravel-worker-mtsn1.log
StandardError=append:/var/log/laravel-worker-mtsn1-error.log

MemoryMax=1024M

[Install]
WantedBy=multi-user.target
```

### Running Services

```bash
sudo systemctl enable --now presensi-mtsn1.service
sudo chown -R www:www /www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
sudo chmod -R 775 /www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
```

---

## Service MA Negeri 1 Pandeglang

```bash
sudo nano /etc/systemd/system/presensi-man1.service
```

```bash
[Unit]
Description=WORKER MAN1
After=network.target
[Service]
User=www
Group=www
Restart=always
RestartSec=10
ExecStart=/usr/bin/php /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/artisan queue:work --queue=default,whatsapp --sleep=5 --tries=3 --max-time=36000 --timeout=120 --memory=768
WorkingDirectory=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id
StandardOutput=append:/var/log/laravel-worker-man1.log
StandardError=append:/var/log/laravel-worker-man1-error.log

MemoryMax=1024M

[Install]
WantedBy=multi-user.target
```

### Running Services

```bash
sudo systemctl enable --now presensi-man1.service
sudo chown -R www:www /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
sudo chmod -R 775 /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
```

---

## Service MTsS Darul Huda Pusat

```bash
sudo nano /etc/systemd/system/presensi-dhpusat.service
```

```bash
[Unit]
Description=WORKER DH PUSAT
After=network.target
[Service]
User=www
Group=www
Restart=always
RestartSec=10
ExecStart=/usr/bin/php /www/wwwroot/presensi-dhpusat.mtsn1pandeglang.sch.id/artisan queue:work --queue=default,whatsapp --sleep=5 --tries=3 --max-time=36000 --timeout=120 --memory=768
WorkingDirectory=/www/wwwroot/presensi-dhpusat.mtsn1pandeglang.sch.id
StandardOutput=append:/var/log/laravel-worker-dhpusat.log
StandardError=append:/var/log/laravel-worker-dhpusat-error.log

MemoryMax=1024M

[Install]
WantedBy=multi-user.target
```

### Running Services

```bash
sudo systemctl enable --now presensi-dhpusat.service
sudo chown -R www:www /www/wwwroot/presensi-dhpusat.mtsn1pandeglang.sch.id/storage
sudo chmod -R 775 /www/wwwroot/presensi-dhpusat.mtsn1pandeglang.sch.id/storage
```

```bash
sudo composer update
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan view:clear
sudo php artisan queue:clear
sudo php artisan queue:clear --queue=whatsapp
sudo php artisan queue:flush
sudo php artisan optimize:clear
sudo php artisan optimize
sudo php artisan filament:optimize

sudo systemctl stop presensi-dhpusat.service
sudo systemctl stop presensi-man1.service
sudo systemctl stop presensi-mtsn1.service
sudo systemctl disable presensi-dhpusat.service
sudo systemctl disable presensi-man1.service
sudo systemctl disable presensi-mtsn1.service
sudo systemctl enable --now presensi-dhpusat.service
sudo systemctl enable --now presensi-man1.service
sudo systemctl enable --now presensi-mtsn1.service
sudo systemctl status presensi-dhpusat.service
sudo systemctl status presensi-man1.service
sudo systemctl status presensi-mtsn1.service
```
