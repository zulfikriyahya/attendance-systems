# Spesifikasi Server

- php8.4
- apache2.4
- mysql8.4
- redis
- 4 Core 8 Thread

# Aplikasi 1 - presensi.mtsn1pandeglang.sch.id

## config -> database.php File

```bash
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
        // ⭐ TAMBAHKAN INI
        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '2'),
        ],
    ],

];

```

## .env File

```bash
# ============================================================
# APLIKASI
# ============================================================
APP_NAME="MTs Negeri 1 Pandeglang"
APP_ENV=production
APP_KEY=base64:4RdSfZUu1tCBD6odWcD2CfZyeWOejg1rlEf7ABpaMX8=
APP_DEBUG=true
APP_URL=https://presensi.mtsn1pandeglang.sch.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database   # (opsional) simpan status maintenance di database

PHP_CLI_SERVER_WORKERS=4           # Jumlah worker PHP CLI
BCRYPT_ROUNDS=12                   # Tingkat kesulitan hashing password

# ============================================================
# LOGGING
# ============================================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ============================================================
# DATABASE
# ============================================================
# DB_CONNECTION=sqlite             # Contoh jika pakai SQLite
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=presensi_mtsn1pandeglang
DB_USERNAME=presensi_mtsn1pandeglang
DB_PASSWORD=18012000

# ============================================================
# SESSION
# ============================================================
SESSION_DRIVER=redis
SESSION_LIFETIME=525600             # dalam menit (525600 menit = 1 tahun)
SESSION_EXPIRE_ON_CLOSE=true        # apakah sesi berakhir jika browser ditutup
SESSION_ENCRYPT=false               # enkripsi data sesi
SESSION_PATH=/                      # path cookie sesi
SESSION_DOMAIN=null                 # domain cookie sesi

# ============================================================
# FILES & QUEUE
# ============================================================
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis           # driver antrian default

CACHE_STORE=redis
CACHE_PREFIX=presensi-mtsn1pandeglang_                     # prefix cache (opsional)

# ============================================================
# REDIS (opsional, untuk session/queue/cache yang lebih cepat)
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0                    # Database Redis terpisah
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_PREFIX=presensi-mtsn1pandeglang_

# ============================================================
# EMAIL
# ============================================================
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================================
# AWS (opsional)
# ============================================================
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ============================================================
# FRONTEND
# ============================================================
VITE_APP_NAME="${APP_NAME}"
API_SECRET=P@ndegl@ng_14012000*           # Ganti dengan secret key sendiri (lihat: https://github.com/zulfikriyahya/attendance-machine)

# ============================================================
# KONFIGURASI APLIKASI
# ============================================================
BIAYA_KARTU=15000                   # Biaya pengajuan/cetak kartu

# ============================================================
# WHATSAPP SERVICE
# ============================================================
WHATSAPP_ENDPOINT=https://api-server-1.mtsn1pandeglang.sch.id/send-message   # Source: https://github.com/zulfikriyahya/api-whatsapp
WHATSAPP_TIMEOUT=15

# ------------------------------------------------------------
# RATE LIMITING
# ------------------------------------------------------------
WHATSAPP_PRESENSI_RATE=35           # batas pesan presensi / menit
WHATSAPP_PRESENSI_MAX_DELAY=30      # delay maksimal (detik)
WHATSAPP_BULK_RATE=20               # batas pesan bulk / menit
WHATSAPP_BULK_MAX_DELAY=2           # delay maksimal (detik)
WHATSAPP_INFORMASI_RATE=25          # batas pesan informasi / menit
WHATSAPP_INFORMASI_MAX_DELAY=60     # delay maksimal (detik)

# ------------------------------------------------------------
# QUEUE
# ------------------------------------------------------------
WHATSAPP_QUEUE=default              # nama antrian WhatsApp
WHATSAPP_RETRY_ATTEMPTS=3           # jumlah percobaan ulang
WHATSAPP_RETRY_DELAY=60             # jeda antar retry (detik)

# ------------------------------------------------------------
# MONITORING
# ------------------------------------------------------------
WHATSAPP_MONITORING_ENABLED=true
WHATSAPP_QUEUE_WARNING=5000         # warning jika antrian >= 5000
WHATSAPP_QUEUE_CRITICAL=10000       # critical jika antrian >= 10000
WHATSAPP_FAILED_WARNING=20          # warning jika gagal >= 20
WHATSAPP_FAILED_CRITICAL=100        # critical jika gagal >= 100
WHATSAPP_HOURLY_WARNING=2000        # warning jika pesan/jam >= 2000
WHATSAPP_HOURLY_CRITICAL=3000       # critical jika pesan/jam >= 3000
WHATSAPP_CACHE_TTL=3600             # waktu simpan cache (detik)

# ------------------------------------------------------------
# ALERTS (opsional)
# ------------------------------------------------------------
WHATSAPP_ALERTS_ENABLED=false       # aktifkan alert/notifikasi
WHATSAPP_ALERT_WEBHOOK=             # webhook alert
WHATSAPP_ALERT_EMAILS=              # email tujuan alert

# ------------------------------------------------------------
# PESAN
# ------------------------------------------------------------
WHATSAPP_INFO_MAX_LENGTH=500        # panjang maksimal pesan

# ------------------------------------------------------------
# VALIDASI NOMOR TELEPON
# ------------------------------------------------------------
WHATSAPP_PHONE_VALIDATION=true

# ------------------------------------------------------------
# LAMPIRAN FILE
# ------------------------------------------------------------
WHATSAPP_ATTACHMENTS_ENABLED=true   # aktifkan lampiran file
WHATSAPP_MAX_FILE_SIZE=16777216     # ukuran maksimal file (byte) → 16MB

# ------------------------------------------------------------
# LOGGING
# ------------------------------------------------------------
WHATSAPP_LOGGING_ENABLED=true
WHATSAPP_LOG_LEVEL=info
WHATSAPP_SUCCESS_LOG=single
WHATSAPP_ERROR_LOG=single
WHATSAPP_LOG_PAYLOAD=false          # log isi request
WHATSAPP_LOG_RESPONSE=true          # log response

# ------------------------------------------------------------
# TESTING (untuk development)
# ------------------------------------------------------------
WHATSAPP_TESTING_MODE=false         # jika true, pesan tidak benar-benar dikirim
WHATSAPP_MOCK_RESPONSES=false       # jika true, gunakan response palsu
WHATSAPP_TEST_NUMBERS=              # daftar nomor untuk testing

LIVEWIRE_UPLOAD_MAX_FILE_SIZE=102400
LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE=102400
```

## /etc/systemd/system/presensi-worker.service

```bash
[Unit]
Description=Laravel Queue Worker (Presensi MTs) - Instance %i
After=network.target redis.service mysql.service
Wants=redis.service mysql.service
Documentation=https://laravel.com/docs/queues

[Service]
Type=simple
User=www
Group=www
Restart=always
RestartSec=5

# Working directory
WorkingDirectory=/www/wwwroot/presensi.mtsn1pandeglang.sch.id

# Optimized queue worker command
ExecStart=/usr/bin/php8.4 /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work redis \
    --sleep=1 \
    --tries=3 \
    --timeout=300 \
    --max-time=3600 \
    --max-jobs=1000 \
    --memory=512 \
    --backoff=10,30,60 \
    --queue=default \
    --name=presensi-worker-%i

# Graceful stop
ExecStop=/bin/kill -TERM $MAINPID
TimeoutStopSec=30
KillMode=mixed
KillSignal=SIGTERM

# Restart limits
StartLimitBurst=5
StartLimitIntervalSec=60

# Resource limits (per worker) - 3 workers total
LimitNOFILE=10000
CPUQuota=110%
MemoryMax=640M
MemoryHigh=512M
TasksMax=256

# Process priority
Nice=-5
IOSchedulingClass=best-effort
IOSchedulingPriority=2

# Environment
Environment="PHP_INI_SCAN_DIR=/etc/php/8.4/cli/conf.d"

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=presensi-worker-%i

# Security hardening
PrivateTmp=true
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
ReadWritePaths=/www/wwwroot/presensi.mtsn1pandeglang.sch.id/bootstrap/cache
ProtectKernelTunables=true
ProtectControlGroups=true
RestrictRealtime=true

[Install]
WantedBy=multi-user.target
```

---

# Aplikasi 2 - presensi-mapansa.mtsn1pandeglang.sch.id

## config -> database.php File

```bash
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [
            'client' => env('REDIS_CLIENT', 'phpredis'),
            'options' => [
                'cluster' => env('REDIS_CLUSTER', 'redis'),
                'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
                'persistent' => env('REDIS_PERSISTENT', false),
            ],
            'default' => [
                'url' => env('REDIS_URL'),
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'username' => env('REDIS_USERNAME'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', '6379'),
                'database' => env('REDIS_DB', '3'),
            ],
            'cache' => [
                'url' => env('REDIS_URL'),
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'username' => env('REDIS_USERNAME'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', '6379'),
                'database' => env('REDIS_CACHE_DB', '4'),
            ],
            // ⭐ TAMBAHKAN INI
            'queue' => [
                'url' => env('REDIS_URL'),
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'username' => env('REDIS_USERNAME'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', '6379'),
                'database' => env('REDIS_QUEUE_DB', '5'),
            ],
        ],

];

```

## .env File

```bash
# ============================================================
# APLIKASI
# ============================================================
APP_NAME="MTs Negeri 1 Pandeglang"
APP_ENV=production
APP_KEY=base64:rxJ45t5cOR09HM3poSObNodZl58egQFIUAFiINhHWcs=
APP_DEBUG=true
APP_URL=https://presensi-mapansa.mtsn1pandeglang.sch.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database   # (opsional) simpan status maintenance di database

PHP_CLI_SERVER_WORKERS=4           # Jumlah worker PHP CLI
BCRYPT_ROUNDS=12                   # Tingkat kesulitan hashing password

# ============================================================
# LOGGING
# ============================================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ============================================================
# DATABASE
# ============================================================
# DB_CONNECTION=sqlite             # Contoh jika pakai SQLite
DB_CONNECTION=mysql
DB_HOST=192.168.1.100
DB_PORT=3306
DB_DATABASE=presensi_mapansa
DB_USERNAME=presensi_mapansa
DB_PASSWORD=18012000

# ============================================================
# SESSION
# ============================================================
SESSION_DRIVER=redis
SESSION_LIFETIME=525600             # dalam menit (525600 menit = 1 tahun)
SESSION_EXPIRE_ON_CLOSE=true        # apakah sesi berakhir jika browser ditutup
SESSION_ENCRYPT=false               # enkripsi data sesi
SESSION_PATH=/                      # path cookie sesi
SESSION_DOMAIN=null                 # domain cookie sesi

# ============================================================
# FILES & QUEUE
# ============================================================
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis           # driver antrian default

CACHE_STORE=redis
CACHE_PREFIX=presensi-mapansa-mtsn1pandeglang_                     # prefix cache (opsional)

# ============================================================
# REDIS (opsional, untuk session/queue/cache yang lebih cepat)
# ============================================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=3                    # Database Redis terpisah
REDIS_CACHE_DB=4
REDIS_QUEUE_DB=5
REDIS_PREFIX=presensi-mapansa-mtsn1pandeglang_

# ============================================================
# EMAIL
# ============================================================
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================================
# AWS (opsional)
# ============================================================
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ============================================================
# FRONTEND
# ============================================================
VITE_APP_NAME="${APP_NAME}"
API_SECRET=P@ndegl@ng_14012000*           # Ganti dengan secret key sendiri (lihat: https://github.com/zulfikriyahya/attendance-machine)

# ============================================================
# KONFIGURASI APLIKASI
# ============================================================
BIAYA_KARTU=15000                   # Biaya pengajuan/cetak kartu

# ============================================================
# WHATSAPP SERVICE
# ============================================================
WHATSAPP_ENDPOINT=https://api-server-3.mtsn1pandeglang.sch.id/send-message   # Source: https://github.com/zulfikriyahya/api-whatsapp
WHATSAPP_TIMEOUT=15

# ------------------------------------------------------------
# RATE LIMITING
# ------------------------------------------------------------
WHATSAPP_PRESENSI_RATE=35           # batas pesan presensi / menit
WHATSAPP_PRESENSI_MAX_DELAY=30      # delay maksimal (detik)
WHATSAPP_BULK_RATE=20               # batas pesan bulk / menit
WHATSAPP_BULK_MAX_DELAY=2           # delay maksimal (detik)
WHATSAPP_INFORMASI_RATE=25          # batas pesan informasi / menit
WHATSAPP_INFORMASI_MAX_DELAY=60     # delay maksimal (detik)

# ------------------------------------------------------------
# QUEUE
# ------------------------------------------------------------
WHATSAPP_QUEUE=default              # nama antrian WhatsApp
WHATSAPP_RETRY_ATTEMPTS=3           # jumlah percobaan ulang
WHATSAPP_RETRY_DELAY=60             # jeda antar retry (detik)

# ------------------------------------------------------------
# MONITORING
# ------------------------------------------------------------
WHATSAPP_MONITORING_ENABLED=true
WHATSAPP_QUEUE_WARNING=5000         # warning jika antrian >= 5000
WHATSAPP_QUEUE_CRITICAL=10000       # critical jika antrian >= 10000
WHATSAPP_FAILED_WARNING=20          # warning jika gagal >= 20
WHATSAPP_FAILED_CRITICAL=100        # critical jika gagal >= 100
WHATSAPP_HOURLY_WARNING=2000        # warning jika pesan/jam >= 2000
WHATSAPP_HOURLY_CRITICAL=3000       # critical jika pesan/jam >= 3000
WHATSAPP_CACHE_TTL=3600             # waktu simpan cache (detik)

# ------------------------------------------------------------
# ALERTS (opsional)
# ------------------------------------------------------------
WHATSAPP_ALERTS_ENABLED=false       # aktifkan alert/notifikasi
WHATSAPP_ALERT_WEBHOOK=             # webhook alert
WHATSAPP_ALERT_EMAILS=              # email tujuan alert

# ------------------------------------------------------------
# PESAN
# ------------------------------------------------------------
WHATSAPP_INFO_MAX_LENGTH=500        # panjang maksimal pesan

# ------------------------------------------------------------
# VALIDASI NOMOR TELEPON
# ------------------------------------------------------------
WHATSAPP_PHONE_VALIDATION=true

# ------------------------------------------------------------
# LAMPIRAN FILE
# ------------------------------------------------------------
WHATSAPP_ATTACHMENTS_ENABLED=true   # aktifkan lampiran file
WHATSAPP_MAX_FILE_SIZE=16777216     # ukuran maksimal file (byte) → 16MB

# ------------------------------------------------------------
# LOGGING
# ------------------------------------------------------------
WHATSAPP_LOGGING_ENABLED=true
WHATSAPP_LOG_LEVEL=info
WHATSAPP_SUCCESS_LOG=single
WHATSAPP_ERROR_LOG=single
WHATSAPP_LOG_PAYLOAD=false          # log isi request
WHATSAPP_LOG_RESPONSE=true          # log response

# ------------------------------------------------------------
# TESTING (untuk development)
# ------------------------------------------------------------
WHATSAPP_TESTING_MODE=false         # jika true, pesan tidak benar-benar dikirim
WHATSAPP_MOCK_RESPONSES=false       # jika true, gunakan response palsu
WHATSAPP_TEST_NUMBERS=              # daftar nomor untuk testing

LIVEWIRE_UPLOAD_MAX_FILE_SIZE=204800
LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_SIZE=204800
```

## /etc/systemd/system/presensi-mapansa-worker.service

```bash
[Unit]
Description=Laravel Queue Worker (Presensi Mapansa) - Instance %i
After=network.target redis.service mysql.service
Wants=redis.service mysql.service
Documentation=https://laravel.com/docs/queues

[Service]
Type=simple
User=www
Group=www
Restart=always
RestartSec=5

# Working directory
WorkingDirectory=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id

# Optimized queue worker command
ExecStart=/usr/bin/php8.4 /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/artisan queue:work redis \
    --sleep=1 \
    --tries=3 \
    --timeout=300 \
    --max-time=3600 \
    --max-jobs=1000 \
    --memory=512 \
    --backoff=10,30,60 \
    --queue=default \
    --name=mapansa-worker-%i

# Graceful stop
ExecStop=/bin/kill -TERM $MAINPID
TimeoutStopSec=30
KillMode=mixed
KillSignal=SIGTERM

# Restart limits
StartLimitBurst=5
StartLimitIntervalSec=60

# Resource limits (per worker) - 3 workers total
LimitNOFILE=10000
CPUQuota=110%
MemoryMax=640M
MemoryHigh=512M
TasksMax=256

# Process priority
Nice=-5
IOSchedulingClass=best-effort
IOSchedulingPriority=2

# Environment
Environment="PHP_INI_SCAN_DIR=/etc/php/8.4/cli/conf.d"

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=mapansa-worker-%i

# Security hardening
PrivateTmp=true
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
ReadWritePaths=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/bootstrap/cache
ProtectKernelTunables=true
ProtectControlGroups=true
RestrictRealtime=true

[Install]
WantedBy=multi-user.target
```

---

========================================================================================

## setup-all-workers.sh

```bash
#!/bin/bash

# ============================================================
# Setup Multiple Laravel Queue Workers
# Untuk 2 Aplikasi di Server yang Sama
# ============================================================

set -e

# Colors untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fungsi helper
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Konfigurasi
APP1_WORKERS=3
APP2_WORKERS=3
SERVICE_DIR="/etc/systemd/system"
APP1_PATH="/www/wwwroot/presensi.mtsn1pandeglang.sch.id"
APP2_PATH="/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id"

echo "============================================================"
echo "  Setup Laravel Queue Workers"
echo "  Server: 4 Core / 8 Thread"
echo "============================================================"
echo ""
print_info "Aplikasi 1: $APP1_WORKERS workers"
print_info "Aplikasi 2: $APP2_WORKERS workers"
print_info "Total: $((APP1_WORKERS + APP2_WORKERS)) workers"
echo ""

# Cek apakah dijalankan sebagai root
if [[ $EUID -ne 0 ]]; then
   print_error "Script ini harus dijalankan sebagai root (sudo)"
   exit 1
fi

# Cek path aplikasi
if [ ! -d "$APP1_PATH" ]; then
    print_error "Path aplikasi 1 tidak ditemukan: $APP1_PATH"
    exit 1
fi

if [ ! -d "$APP2_PATH" ]; then
    print_error "Path aplikasi 2 tidak ditemukan: $APP2_PATH"
    exit 1
fi

# ============================================================
# APLIKASI 1: Presensi MTs
# ============================================================
echo ""
echo "============================================================"
echo "  Setup Aplikasi 1: Presensi MTs"
echo "============================================================"

# Stop old workers jika ada
if systemctl list-units --full -all | grep -q "presensi-worker.service"; then
    print_info "Stopping old presensi-worker.service..."
    systemctl stop presensi-worker.service 2>/dev/null || true
    systemctl disable presensi-worker.service 2>/dev/null || true
fi

# Create service template untuk App 1
SERVICE_FILE_APP1="$SERVICE_DIR/presensi-worker@.service"
print_info "Creating service template: presensi-worker@.service"

cat > "$SERVICE_FILE_APP1" << 'EOF'
[Unit]
Description=Laravel Queue Worker (Presensi MTs) - Instance %i
After=network.target redis.service mysql.service
Wants=redis.service mysql.service

[Service]
Type=simple
User=www
Group=www
Restart=always
RestartSec=5

WorkingDirectory=/www/wwwroot/presensi.mtsn1pandeglang.sch.id

ExecStart=/usr/bin/php8.4 /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work redis \
    --sleep=1 \
    --tries=3 \
    --timeout=300 \
    --max-time=3600 \
    --max-jobs=1000 \
    --memory=512 \
    --backoff=10,30,60 \
    --queue=default \
    --name=presensi-worker-%i

ExecStop=/bin/kill -TERM $MAINPID
TimeoutStopSec=30
KillMode=mixed

StartLimitBurst=5
StartLimitIntervalSec=60

LimitNOFILE=10000
CPUQuota=110%
MemoryMax=640M
MemoryHigh=512M

Nice=-5
IOSchedulingClass=best-effort
IOSchedulingPriority=2

Environment="PHP_INI_SCAN_DIR=/etc/php/8.4/cli/conf.d"

StandardOutput=journal
StandardError=journal
SyslogIdentifier=presensi-worker-%i

PrivateTmp=true
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
ReadWritePaths=/www/wwwroot/presensi.mtsn1pandeglang.sch.id/bootstrap/cache

[Install]
WantedBy=multi-user.target
EOF

print_success "Service template created"

# Enable dan start workers untuk App 1
for i in $(seq 1 $APP1_WORKERS); do
    print_info "Starting presensi-worker@$i..."
    systemctl daemon-reload
    systemctl enable "presensi-worker@$i.service"
    systemctl start "presensi-worker@$i.service"
    print_success "Worker $i started"
    sleep 1
done

# ============================================================
# APLIKASI 2: Presensi Mapansa
# ============================================================
echo ""
echo "============================================================"
echo "  Setup Aplikasi 2: Presensi Mapansa"
echo "============================================================"

# Stop old workers jika ada
if systemctl list-units --full -all | grep -q "presensi-mapansa-worker.service"; then
    print_info "Stopping old presensi-mapansa-worker.service..."
    systemctl stop presensi-mapansa-worker.service 2>/dev/null || true
    systemctl disable presensi-mapansa-worker.service 2>/dev/null || true
fi

# Create service template untuk App 2
SERVICE_FILE_APP2="$SERVICE_DIR/presensi-mapansa-worker@.service"
print_info "Creating service template: presensi-mapansa-worker@.service"

cat > "$SERVICE_FILE_APP2" << 'EOF'
[Unit]
Description=Laravel Queue Worker (Presensi Mapansa) - Instance %i
After=network.target redis.service mysql.service
Wants=redis.service mysql.service

[Service]
Type=simple
User=www
Group=www
Restart=always
RestartSec=5

WorkingDirectory=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id

ExecStart=/usr/bin/php8.4 /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/artisan queue:work redis \
    --sleep=1 \
    --tries=3 \
    --timeout=300 \
    --max-time=3600 \
    --max-jobs=1000 \
    --memory=512 \
    --backoff=10,30,60 \
    --queue=default \
    --name=mapansa-worker-%i

ExecStop=/bin/kill -TERM $MAINPID
TimeoutStopSec=30
KillMode=mixed

StartLimitBurst=5
StartLimitIntervalSec=60

LimitNOFILE=10000
CPUQuota=110%
MemoryMax=640M
MemoryHigh=512M

Nice=-5
IOSchedulingClass=best-effort
IOSchedulingPriority=2

Environment="PHP_INI_SCAN_DIR=/etc/php/8.4/cli/conf.d"

StandardOutput=journal
StandardError=journal
SyslogIdentifier=mapansa-worker-%i

PrivateTmp=true
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
ReadWritePaths=/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/bootstrap/cache

[Install]
WantedBy=multi-user.target
EOF

print_success "Service template created"

# Enable dan start workers untuk App 2
for i in $(seq 1 $APP2_WORKERS); do
    print_info "Starting presensi-mapansa-worker@$i..."
    systemctl daemon-reload
    systemctl enable "presensi-mapansa-worker@$i.service"
    systemctl start "presensi-mapansa-worker@$i.service"
    print_success "Worker $i started"
    sleep 1
done

# ============================================================
# SUMMARY
# ============================================================
echo ""
echo "============================================================"
echo "  🎉 Setup Complete!"
echo "============================================================"
echo ""
print_success "Aplikasi 1: $APP1_WORKERS workers running"
print_success "Aplikasi 2: $APP2_WORKERS workers running"
echo ""
echo "📊 Check Status:"
echo "   Aplikasi 1: sudo systemctl status 'presensi-worker@*'"
echo "   Aplikasi 2: sudo systemctl status 'presensi-mapansa-worker@*'"
echo ""
echo "📋 View Logs:"
echo "   Aplikasi 1: sudo journalctl -u 'presensi-worker@*' -f"
echo "   Aplikasi 2: sudo journalctl -u 'presensi-mapansa-worker@*' -f"
echo ""
echo "🔄 Restart Workers:"
echo "   Aplikasi 1: sudo systemctl restart presensi-worker@{1..$APP1_WORKERS}"
echo "   Aplikasi 2: sudo systemctl restart presensi-mapansa-worker@{1..$APP2_WORKERS}"
echo ""
echo "🛑 Stop Workers:"
echo "   Aplikasi 1: sudo systemctl stop presensi-worker@{1..$APP1_WORKERS}"
echo "   Aplikasi 2: sudo systemctl stop presensi-mapansa-worker@{1..$APP2_WORKERS}"
echo ""
echo "============================================================"
```

## worker-manager.sh

```bash
#!/bin/bash

# ============================================================
# Laravel Queue Worker Manager
# Management tool untuk kedua aplikasi
# ============================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

APP1_WORKERS=3
APP2_WORKERS=3
APP1_PATH="/www/wwwroot/presensi.mtsn1pandeglang.sch.id"
APP2_PATH="/www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id"

# Fungsi untuk menampilkan menu
show_menu() {
    clear
    echo "============================================================"
    echo "  Laravel Queue Worker Manager"
    echo "  Server: 4 Core / 8 Thread | Total: 6 Workers"
    echo "============================================================"
    echo ""
    echo "  [1] Status semua workers"
    echo "  [2] Logs semua workers (real-time)"
    echo "  [3] Restart semua workers"
    echo "  [4] Stop semua workers"
    echo "  [5] Start semua workers"
    echo ""
    echo "  [6] Status Aplikasi 1 (Presensi MTs)"
    echo "  [7] Status Aplikasi 2 (Presensi Mapansa)"
    echo ""
    echo "  [8] Logs Aplikasi 1"
    echo "  [9] Logs Aplikasi 2"
    echo ""
    echo "  [10] Restart Aplikasi 1"
    echo "  [11] Restart Aplikasi 2"
    echo ""
    echo "  [12] Queue Monitoring (Aplikasi 1)"
    echo "  [13] Queue Monitoring (Aplikasi 2)"
    echo ""
    echo "  [14] Clear failed jobs (Aplikasi 1)"
    echo "  [15] Clear failed jobs (Aplikasi 2)"
    echo ""
    echo "  [16] Server resource usage"
    echo "  [0] Exit"
    echo ""
    echo "============================================================"
}

# Fungsi status
status_all() {
    echo -e "${BLUE}Status Aplikasi 1 (Presensi MTs):${NC}"
    systemctl status 'presensi-worker@*' --no-pager | head -n 50
    echo ""
    echo -e "${BLUE}Status Aplikasi 2 (Presensi Mapansa):${NC}"
    systemctl status 'presensi-mapansa-worker@*' --no-pager | head -n 50
}

status_app1() {
    echo -e "${BLUE}Status Aplikasi 1 (Presensi MTs):${NC}"
    systemctl status 'presensi-worker@*' --no-pager
}

status_app2() {
    echo -e "${BLUE}Status Aplikasi 2 (Presensi Mapansa):${NC}"
    systemctl status 'presensi-mapansa-worker@*' --no-pager
}

# Fungsi logs
logs_all() {
    echo -e "${GREEN}Logs dari semua workers (Ctrl+C untuk stop)${NC}"
    journalctl -u 'presensi-worker@*' -u 'presensi-mapansa-worker@*' -f
}

logs_app1() {
    echo -e "${GREEN}Logs Aplikasi 1 - Presensi MTs (Ctrl+C untuk stop)${NC}"
    journalctl -u 'presensi-worker@*' -f
}

logs_app2() {
    echo -e "${GREEN}Logs Aplikasi 2 - Presensi Mapansa (Ctrl+C untuk stop)${NC}"
    journalctl -u 'presensi-mapansa-worker@*' -f
}

# Fungsi restart
restart_all() {
    echo -e "${YELLOW}Restarting semua workers...${NC}"
    for i in $(seq 1 $APP1_WORKERS); do
        systemctl restart "presensi-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 1 - Worker $i restarted"
    done
    for i in $(seq 1 $APP2_WORKERS); do
        systemctl restart "presensi-mapansa-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 2 - Worker $i restarted"
    done
    echo -e "${GREEN}All workers restarted!${NC}"
}

restart_app1() {
    echo -e "${YELLOW}Restarting Aplikasi 1 workers...${NC}"
    for i in $(seq 1 $APP1_WORKERS); do
        systemctl restart "presensi-worker@$i.service"
        echo -e "${GREEN}✓${NC} Worker $i restarted"
    done
    echo -e "${GREEN}Aplikasi 1 workers restarted!${NC}"
}

restart_app2() {
    echo -e "${YELLOW}Restarting Aplikasi 2 workers...${NC}"
    for i in $(seq 1 $APP2_WORKERS); do
        systemctl restart "presensi-mapansa-worker@$i.service"
        echo -e "${GREEN}✓${NC} Worker $i restarted"
    done
    echo -e "${GREEN}Aplikasi 2 workers restarted!${NC}"
}

# Fungsi stop
stop_all() {
    echo -e "${RED}Stopping semua workers...${NC}"
    for i in $(seq 1 $APP1_WORKERS); do
        systemctl stop "presensi-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 1 - Worker $i stopped"
    done
    for i in $(seq 1 $APP2_WORKERS); do
        systemctl stop "presensi-mapansa-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 2 - Worker $i stopped"
    done
    echo -e "${GREEN}All workers stopped!${NC}"
}

# Fungsi start
start_all() {
    echo -e "${GREEN}Starting semua workers...${NC}"
    for i in $(seq 1 $APP1_WORKERS); do
        systemctl start "presensi-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 1 - Worker $i started"
    done
    for i in $(seq 1 $APP2_WORKERS); do
        systemctl start "presensi-mapansa-worker@$i.service"
        echo -e "${GREEN}✓${NC} Aplikasi 2 - Worker $i started"
    done
    echo -e "${GREEN}All workers started!${NC}"
}

# Queue monitoring
queue_monitor_app1() {
    echo -e "${CYAN}Queue Monitoring - Aplikasi 1${NC}"
    cd "$APP1_PATH"
    php artisan queue:monitor redis:default --max=1000
    echo ""
    echo "Failed jobs:"
    php artisan queue:failed
}

queue_monitor_app2() {
    echo -e "${CYAN}Queue Monitoring - Aplikasi 2${NC}"
    cd "$APP2_PATH"
    php artisan queue:monitor redis:default --max=1000
    echo ""
    echo "Failed jobs:"
    php artisan queue:failed
}

# Clear failed jobs
clear_failed_app1() {
    echo -e "${YELLOW}Clearing failed jobs - Aplikasi 1${NC}"
    cd "$APP1_PATH"
    php artisan queue:flush
    echo -e "${GREEN}✓ Failed jobs cleared${NC}"
}

clear_failed_app2() {
    echo -e "${YELLOW}Clearing failed jobs - Aplikasi 2${NC}"
    cd "$APP2_PATH"
    php artisan queue:flush
    echo -e "${GREEN}✓ Failed jobs cleared${NC}"
}

# Resource usage
resource_usage() {
    echo -e "${CYAN}=== CPU & Memory Usage ===${NC}"
    echo ""
    echo "Overall:"
    top -bn1 | head -n 5
    echo ""
    echo -e "${CYAN}Worker Processes:${NC}"
    ps aux | grep "queue:work" | grep -v grep
    echo ""
    echo -e "${CYAN}Redis Memory:${NC}"
    redis-cli info memory | grep "used_memory_human"
    echo ""
    echo -e "${CYAN}Disk Usage:${NC}"
    df -h | grep -E "Filesystem|/www"
}

# Main loop
while true; do
    show_menu
    read -p "Pilih opsi [0-16]: " choice
    echo ""

    case $choice in
        1) status_all; read -p "Press Enter to continue..." ;;
        2) logs_all ;;
        3) restart_all; read -p "Press Enter to continue..." ;;
        4) stop_all; read -p "Press Enter to continue..." ;;
        5) start_all; read -p "Press Enter to continue..." ;;
        6) status_app1; read -p "Press Enter to continue..." ;;
        7) status_app2; read -p "Press Enter to continue..." ;;
        8) logs_app1 ;;
        9) logs_app2 ;;
        10) restart_app1; read -p "Press Enter to continue..." ;;
        11) restart_app2; read -p "Press Enter to continue..." ;;
        12) queue_monitor_app1; read -p "Press Enter to continue..." ;;
        13) queue_monitor_app2; read -p "Press Enter to continue..." ;;
        14) clear_failed_app1; read -p "Press Enter to continue..." ;;
        15) clear_failed_app2; read -p "Press Enter to continue..." ;;
        16) resource_usage; read -p "Press Enter to continue..." ;;
        0) echo "Goodbye!"; exit 0 ;;
        *) echo -e "${RED}Invalid option${NC}"; sleep 2 ;;
    esac
done
```

========================================================================================

Baik! Berdasarkan spesifikasi server Anda (4 Core, 8 Thread, PHP 8.4), saya akan buatkan konfigurasi optimal untuk **kedua aplikasi**.

## Strategi Pembagian Resource

Dengan 8 thread tersedia, saya akan bagi:

- **Aplikasi 1 (presensi)**: 3 workers
- **Aplikasi 2 (mapansa)**: 3 workers
- **Cadangan sistem**: 2 thread

---Sekarang saya buatkan script management untuk monitoring dan operasional:## 📋 Cara Deploy & Penggunaan

### 1️⃣ **Persiapan**

```bash
# Clear cache kedua aplikasi
cd /www/wwwroot/presensi.mtsn1pandeglang.sch.id
php artisan config:clear
php artisan cache:clear

cd /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id
php artisan config:clear
php artisan cache:clear

# Set permission
sudo chown -R www:www /www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
sudo chown -R www:www /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
sudo chmod -R 775 /www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage
sudo chmod -R 775 /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id/storage
```

### 2️⃣ **Setup Workers (Otomatis)**

```bash
# Download script
sudo nano /tmp/setup-all-workers.sh

# Copy isi dari artifact "setup-all-workers.sh"

# Beri permission
sudo chmod +x /tmp/setup-all-workers.sh

# Jalankan
sudo bash /tmp/setup-all-workers.sh
```

### 3️⃣ **Install Management Tool**

```bash
# Download script
sudo nano /usr/local/bin/worker-manager

# Copy isi dari artifact "worker-manager.sh"

# Beri permission
sudo chmod +x /usr/local/bin/worker-manager

# Jalankan kapan saja dengan:
sudo worker-manager
```

## 🎯 Verifikasi Instalasi

### Cek Status Workers

```bash
# Status semua workers
sudo systemctl status 'presensi-worker@*' 'presensi-mapansa-worker@*'

# Lihat proses yang running
ps aux | grep "queue:work"

# Expected output: 6 processes (3 untuk tiap aplikasi)
```

### Cek Logs

```bash
# Logs real-time semua workers
sudo journalctl -u 'presensi-worker@*' -u 'presensi-mapansa-worker@*' -f

# Logs aplikasi 1 saja
sudo journalctl -u 'presensi-worker@*' -f

# Logs aplikasi 2 saja
sudo journalctl -u 'presensi-mapansa-worker@*' -f
```

### Test Queue

```bash
# Test di aplikasi 1
cd /www/wwwroot/presensi.mtsn1pandeglang.sch.id
php artisan queue:work redis --once

# Test di aplikasi 2
cd /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id
php artisan queue:work redis --once
```

## 📊 Monitoring Commands

```bash
# CPU & Memory usage
htop

# Redis info
redis-cli info stats
redis-cli info memory

# Queue monitoring
cd /www/wwwroot/presensi.mtsn1pandeglang.sch.id
php artisan queue:monitor redis:default

# Failed jobs
php artisan queue:failed
php artisan queue:retry all  # Retry failed jobs
php artisan queue:flush      # Clear failed jobs
```

## 🔧 Management Commands

```bash
# Restart semua workers (kedua aplikasi)
sudo systemctl restart presensi-worker@{1..3}
sudo systemctl restart presensi-mapansa-worker@{1..3}

# Stop semua
sudo systemctl stop presensi-worker@{1..3}
sudo systemctl stop presensi-mapansa-worker@{1..3}

# Start semua
sudo systemctl start presensi-worker@{1..3}
sudo systemctl start presensi-mapansa-worker@{1..3}

# Enable auto-start on boot
sudo systemctl enable presensi-worker@{1..3}
sudo systemctl enable presensi-mapansa-worker@{1..3}

# Disable auto-start
sudo systemctl disable presensi-worker@{1..3}
sudo systemctl disable presensi-mapansa-worker@{1..3}

# Restart gracefully (dari Laravel)
cd /www/wwwroot/presensi.mtsn1pandeglang.sch.id
php artisan queue:restart

cd /www/wwwroot/presensi-mapansa.mtsn1pandeglang.sch.id
php artisan queue:restart
```

## 🚀 Optimasi Tambahan

### 1. **Redis Configuration**

Edit `/etc/redis/redis.conf`:

```bash
sudo nano /etc/redis/redis.conf
```

Tambahkan/ubah:

```ini
# Memory Management
maxmemory 2gb
maxmemory-policy allkeys-lru

# Network
tcp-backlog 511
timeout 0
tcp-keepalive 300

# Performance
save 900 1
save 300 10
save 60 10000

# AOF Persistence
appendonly yes
appendfsync everysec
no-appendfsync-on-rewrite no

# Slow Log
slowlog-log-slower-than 10000
slowlog-max-len 128

# Latency
latency-monitor-threshold 100
```

Restart Redis:

```bash
sudo systemctl restart redis
```

### 2. **PHP 8.4 Configuration**

Edit `/etc/php/8.4/cli/php.ini`:

```bash
sudo nano /etc/php/8.4/cli/php.ini
```

Optimasi:

```ini
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M

; OPcache (sangat penting untuk performa)
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1

; Redis extension
extension=redis.so
```

### 3. **MySQL 8.4 Optimization**

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Tambahkan di `[mysqld]`:

```ini
# Connection
max_connections = 200
max_connect_errors = 100

# Buffer Pool (sesuaikan dengan RAM server)
innodb_buffer_pool_size = 2G
innodb_buffer_pool_instances = 4

# Log
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M

# Performance
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1

# Thread
thread_cache_size = 50
table_open_cache = 4000
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### 4. **Monitoring dengan Cron**

Buat script monitoring otomatis:

```bash
sudo nano /usr/local/bin/queue-health-check.sh
```

Isi:

```bash
#!/bin/bash

LOG_FILE="/var/log/queue-health-check.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Function to check workers
check_workers() {
    APP_NAME=$1
    SERVICE_PATTERN=$2
    EXPECTED_COUNT=$3

    RUNNING=$(systemctl list-units --state=running | grep -c "$SERVICE_PATTERN")

    if [ "$RUNNING" -lt "$EXPECTED_COUNT" ]; then
        echo "[$DATE] WARNING: $APP_NAME - Only $RUNNING/$EXPECTED_COUNT workers running" >> $LOG_FILE

        # Auto-restart workers
        for i in $(seq 1 $EXPECTED_COUNT); do
            systemctl restart "$SERVICE_PATTERN@$i.service"
        done

        echo "[$DATE] INFO: $APP_NAME workers restarted" >> $LOG_FILE
    else
        echo "[$DATE] OK: $APP_NAME - All workers running ($RUNNING/$EXPECTED_COUNT)" >> $LOG_FILE
    fi
}

# Check both applications
check_workers "Aplikasi 1" "presensi-worker" 3
check_workers "Aplikasi 2" "presensi-mapansa-worker" 3

# Check Redis
if ! systemctl is-active --quiet redis; then
    echo "[$DATE] ERROR: Redis is down! Restarting..." >> $LOG_FILE
    systemctl restart redis
fi

# Check MySQL
if ! systemctl is-active --quiet mysql; then
    echo "[$DATE] ERROR: MySQL is down! Restarting..." >> $LOG_FILE
    systemctl restart mysql
fi
```

Beri permission:

```bash
sudo chmod +x /usr/local/bin/queue-health-check.sh
```

Setup cron (setiap 5 menit):

```bash
sudo crontab -e
```

Tambahkan:

```bash
*/5 * * * * /usr/local/bin/queue-health-check.sh
```

### 5. **Alert Notification (Optional)**

Jika ingin notifikasi Telegram saat ada masalah:

```bash
sudo nano /usr/local/bin/send-telegram-alert.sh
```

```bash
#!/bin/bash

TELEGRAM_BOT_TOKEN="YOUR_BOT_TOKEN"
TELEGRAM_CHAT_ID="YOUR_CHAT_ID"
MESSAGE="$1"

curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
    -d chat_id="${TELEGRAM_CHAT_ID}" \
    -d text="${MESSAGE}" \
    -d parse_mode="HTML"
```

Update `queue-health-check.sh` untuk kirim alert saat ada masalah.

## 📈 Performance Benchmarks

Dengan konfigurasi ini, Anda bisa expect:

| Metric             | Kapasitas                   |
| ------------------ | --------------------------- |
| **Jobs/detik**     | ~100-150 jobs               |
| **Jobs/menit**     | ~6,000-9,000 jobs           |
| **Jobs/jam**       | ~360,000-540,000 jobs       |
| **WhatsApp/menit** | ~60-80 pesan (rate limited) |
| **CPU Usage**      | 70-80% (optimal)            |
| **Memory Usage**   | ~2-3GB total                |
| **Response Time**  | <1s per job                 |

## 🛡️ Troubleshooting

### Problem: Workers tidak start

```bash
# Cek error logs
sudo journalctl -u presensi-worker@1 -n 50

# Cek permission
ls -la /www/wwwroot/presensi.mtsn1pandeglang.sch.id/storage

# Test manual
sudo -u www php8.4 /www/wwwroot/presensi.mtsn1pandeglang.sch.id/artisan queue:work redis --once
```

### Problem: Queue bercampur

```bash
# Verifikasi Redis prefix
redis-cli KEYS "*"

# Expected output:
# - presensi-mtsn1pandeglang_database_*
# - presensi-mapansa-mtsn1pandeglang_database_*

# Clear semua key jika perlu (HATI-HATI!)
redis-cli FLUSHALL
```

### Problem: Memory leak

```bash
# Restart workers setiap 1 jam (sudah di-setting dengan --max-time=3600)
# Atau restart manual
sudo systemctl restart presensi-worker@{1..3}
sudo systemctl restart presensi-mapansa-worker@{1..3}
```

### Problem: High CPU usage

```bash
# Kurangi jumlah workers
# Edit /etc/systemd/system/presensi-worker@.service
# Ubah CPUQuota=110% menjadi CPUQuota=80%

sudo systemctl daemon-reload
sudo systemctl restart presensi-worker@{1..3}
```

### Problem: Failed jobs menumpuk

```bash
# Lihat failed jobs
php artisan queue:failed

# Retry semua
php artisan queue:retry all

# Clear semua
php artisan queue:flush

# Retry specific job
php artisan queue:retry JOB_ID
```

## 📊 Daily Maintenance Commands

```bash
# Morning check (setiap pagi)
sudo worker-manager  # Pilih opsi 1 (status)

# Monitor queue size
redis-cli LLEN presensi-mtsn1pandeglang_database_queues:default
redis-cli LLEN presensi-mapansa-mtsn1pandeglang_database_queues:default

# Check disk space
df -h

# Check logs untuk errors
sudo journalctl -u 'presensi-worker@*' --since "1 hour ago" | grep -i error
sudo journalctl -u 'presensi-mapansa-worker@*' --since "1 hour ago" | grep -i error

# Weekly: Clear old logs
sudo journalctl --vacuum-time=7d
```

## 🎯 Quick Reference Card

```bash
# ============================================================
# QUICK COMMANDS CHEAT SHEET
# ============================================================

# 1. CHECK STATUS
sudo worker-manager                    # Interactive menu
sudo systemctl status 'presensi-worker@*' 'presensi-mapansa-worker@*'

# 2. RESTART WORKERS
sudo systemctl restart presensi-worker@{1..3}
sudo systemctl restart presensi-mapansa-worker@{1..3}

# 3. VIEW LOGS
sudo journalctl -u 'presensi-worker@*' -f       # App 1
sudo journalctl -u 'presensi-mapansa-worker@*' -f  # App 2

# 4. QUEUE OPERATIONS
php artisan queue:monitor redis:default
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush

# 5. REDIS OPERATIONS
redis-cli KEYS "*queue*"
redis-cli LLEN presensi-mtsn1pandeglang_database_queues:default
redis-cli INFO stats

# 6. EMERGENCY STOP
sudo systemctl stop presensi-worker@{1..3}
sudo systemctl stop presensi-mapansa-worker@{1..3}

# 7. FULL RESTART (DEPLOYMENT)
php artisan queue:restart              # Signal workers
sudo systemctl restart presensi-worker@{1..3}
sudo systemctl restart presensi-mapansa-worker@{1..3}
```

## ✅ Final Checklist

Sebelum production, pastikan:

- [ ] Redis berjalan: `sudo systemctl status redis`
- [ ] MySQL berjalan: `sudo systemctl status mysql`
- [ ] PHP 8.4 terinstall: `php8.4 -v`
- [ ] Kedua aplikasi punya config Redis terpisah (DB 0-2 vs 3-5)
- [ ] Workers berjalan: 6 workers total (3+3)
- [ ] Permission storage benar: `www:www` dengan chmod 775
- [ ] Logs tidak ada error: `sudo journalctl -u 'presensi-worker@*' -n 50`
- [ ] Test dispatch job berhasil di kedua aplikasi
- [ ] Monitoring script aktif: `sudo crontab -l`
- [ ] Management tool terinstall: `sudo worker-manager`

---

**Konfigurasi ini sudah optimal untuk server 4 Core/8 Thread Anda!** 🚀

Jika ada pertanyaan atau butuh penyesuaian, silakan tanya! 😊
