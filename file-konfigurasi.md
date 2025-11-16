# Services Settings

## Service Default MTs Negeri 1 Pandeglang

```bash
[Unit]
Description=WORKER DEFAULT MTSN1
After=network.target
[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=10
ExecStart=/usr/bin/php /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work --queue=default --sleep=5 --tries=3 --max-time=36000 --timeout=120 --memory=768
WorkingDirectory=/www/wwwroot/presensi.mtsn1pandeglang.sch.id
StandardOutput=append:/var/log/laravel-worker-mtsn1-default.log
StandardError=append:/var/log/laravel-worker-mtsn1-default-error.log

MemoryMax=1024M

[Install]
WantedBy=multi-user.target
```

## Service Whatsapp MTs Negeri 1 Pandeglang

```bash
[Unit]
Description=WORKER WHATSAPP MTSN1
After=network.target
[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=10
ExecStart=/usr/bin/php /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work --queue=whatsapp --sleep=5 --tries=3 --max-time=36000 --timeout=120 --memory=768
WorkingDirectory=/www/wwwroot/presensi.mtsn1pandeglang.sch.id
StandardOutput=append:/var/log/laravel-worker-mtsn1-whatsapp.log
StandardError=append:/var/log/laravel-worker-mtsn1-whatsapp-error.log

MemoryMax=1024M

[Install]
WantedBy=multi-user.target
```

### Running Services

```bash
sudo systemctl enable --now default-mtsn1.services
sudo systemctl enable --now whatsapp-mtsn1.services
```
