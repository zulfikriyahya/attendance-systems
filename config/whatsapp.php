<?php

return [
    'rate_limits' => [
        'presensi' => [
            'messages_per_minute' => 35,
            'max_delay_minutes' => 30,
        ],
        'informasi' => [
            'messages_per_minute' => 25,
            'max_delay_minutes' => 60,
        ],
    ],
    
    'message_templates' => [
        'informasi' => [
            'max_content_length' => 200,
            'include_attachment' => true,
        ]
    ],
    
    'monitoring' => [
        'log_failed_messages' => true,
        'cache_statistics' => true,
    ]
];
