<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    */

    'endpoint' => env('WHATSAPP_ENDPOINT', 'https://api.whatsapp.local/send'),
    'timeout' => env('WHATSAPP_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration - Multi-Tier
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        // NEW: Global limits (all types combined)
        'global' => [
            'hourly' => env('WHATSAPP_HOURLY_LIMIT', 500),
            'daily' => env('WHATSAPP_DAILY_LIMIT', 5000),
        ],

        // NEW: Per-number limits (anti-spam)
        'per_number' => [
            'hourly' => env('WHATSAPP_PER_NUMBER_HOURLY', 2),
            'daily' => env('WHATSAPP_PER_NUMBER_DAILY', 5),
        ],

        // Presensi (real-time)
        'presensi' => [
            'messages_per_minute' => env('WHATSAPP_PRESENSI_RATE', 35),
            'messages_per_hour' => env('WHATSAPP_PRESENSI_HOURLY', 300),
            'messages_per_day' => env('WHATSAPP_PRESENSI_DAILY', 3000),
            'max_delay_minutes' => env('WHATSAPP_PRESENSI_MAX_DELAY', 30),
            'priority_statuses' => ['Terlambat', 'Pulang Cepat'],
        ],

        // Bulk (scheduled background)
        'bulk' => [
            'messages_per_minute' => env('WHATSAPP_BULK_RATE', 20),
            'messages_per_hour' => env('WHATSAPP_BULK_HOURLY', 150),
            'messages_per_day' => env('WHATSAPP_BULK_DAILY', 1500),
            'max_delay_hours' => env('WHATSAPP_BULK_MAX_DELAY', 2),
            'types' => [
                'alfa' => ['priority' => 1, 'extra_delay' => 0],
                'mangkir' => ['priority' => 2, 'extra_delay' => [60, 180]],
                'bolos' => ['priority' => 2, 'extra_delay' => [60, 180]],
            ],
        ],

        // Informasi (broadcast)
        'informasi' => [
            'messages_per_minute' => env('WHATSAPP_INFORMASI_RATE', 25),
            'messages_per_hour' => env('WHATSAPP_INFORMASI_HOURLY', 100),
            'messages_per_day' => env('WHATSAPP_INFORMASI_DAILY', 1000),
            'max_delay_minutes' => env('WHATSAPP_INFORMASI_MAX_DELAY', 60),
            'extra_delay' => [30, 90],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    */

    'circuit_breaker' => [
        'enabled' => env('WHATSAPP_CIRCUIT_BREAKER_ENABLED', true),
        'error_threshold' => env('WHATSAPP_ERROR_THRESHOLD', 50),
        'cooldown_minutes' => env('WHATSAPP_CIRCUIT_BREAKER_COOLDOWN', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'default_queue' => env('WHATSAPP_QUEUE', 'whatsapp'),
        'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WHATSAPP_RETRY_DELAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('WHATSAPP_MONITORING_ENABLED', true),

        'thresholds' => [
            'queue' => [
                'warning' => env('WHATSAPP_QUEUE_WARNING', 5000),
                'critical' => env('WHATSAPP_QUEUE_CRITICAL', 10000),
            ],

            'failed_jobs' => [
                'warning' => env('WHATSAPP_FAILED_WARNING', 20),
                'critical' => env('WHATSAPP_FAILED_CRITICAL', 100),
            ],

            'hourly_messages' => [
                'warning' => env('WHATSAPP_HOURLY_WARNING', 2000),
                'critical' => env('WHATSAPP_HOURLY_CRITICAL', 3000),
            ],
        ],

        'cache_ttl' => env('WHATSAPP_CACHE_TTL', 3600), // 1 hour

        'alerts' => [
            'enabled' => env('WHATSAPP_ALERTS_ENABLED', false),
            'webhook_url' => env('WHATSAPP_ALERT_WEBHOOK'),
            'email_recipients' => env('WHATSAPP_ALERT_EMAILS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'presensi' => [
            'header' => 'PTSP {instansi}',
            'footer' => '© 2022 - {tahun} {instansi}',

            'masuk' => [
                'greeting' => [
                    'siswa' => 'Selamat mengikuti kegiatan pembelajaran hari ini.',
                    'pegawai' => 'Selamat menjalankan tugas dan tanggung jawab Anda.',
                ],
                'bulk_message' => [
                    'siswa' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.',
                    'pegawai' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.',
                ],
            ],

            'pulang' => [
                'greeting' => [
                    'siswa' => 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.',
                    'pegawai' => 'Terima kasih atas dedikasi dan kinerja Anda hari ini.',
                ],
                'bulk_message' => [
                    'siswa' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.',
                    'pegawai' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.',
                ],
            ],
        ],

        'informasi' => [
            'header' => 'PTSP {instansi}',
            'footer' => '© 2022 - {tahun} {instansi}',
            'max_content_length' => env('WHATSAPP_INFO_MAX_LENGTH', 300),

            'greetings' => [
                'siswa' => 'Kepada Bapak/Ibu/Wali Siswa yang terhormat,',
                'pegawai' => 'Kepada Bapak/Ibu yang terhormat,',
            ],

            'closing' => [
                'siswa' => 'Terima kasih atas perhatiannya. Tetap semangat belajar!',
                'pegawai' => 'Terima kasih atas perhatian dan kerjasamanya.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Number Configuration
    |--------------------------------------------------------------------------
    */

    'phone' => [
        'validation' => [
            'enabled' => env('WHATSAPP_PHONE_VALIDATION', true),
            'pattern' => '/^08[0-9]{8,12}$/',
            'country_code' => '62',
        ],

        'normalization' => [
            'remove_country_code' => true,
            'prefix' => '08',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Attachment Configuration
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'enabled' => env('WHATSAPP_ATTACHMENTS_ENABLED', true),
        'max_size' => env('WHATSAPP_MAX_FILE_SIZE', 16777216), // 16MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'storage_path' => 'app/public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', true),
        'level' => env('WHATSAPP_LOG_LEVEL', 'info'),
        'channels' => [
            'success' => env('WHATSAPP_SUCCESS_LOG', 'single'),
            'error' => env('WHATSAPP_ERROR_LOG', 'single'),
        ],
        'context' => [
            'include_payload' => env('WHATSAPP_LOG_PAYLOAD', false),
            'include_response' => env('WHATSAPP_LOG_RESPONSE', true),
        ],
    ],
];
