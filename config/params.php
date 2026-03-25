<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'storage' => [
        'driver' => $_ENV['STORAGE_DRIVER'] ?? getenv('STORAGE_DRIVER') ?: 'local',
    ],
    's3' => [
        'bucket' => $_ENV['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: '',
        'accessKey' => $_ENV['S3_ACCESS_KEY'] ?? getenv('S3_ACCESS_KEY') ?: '',
        'secretKey' => $_ENV['S3_SECRET_KEY'] ?? getenv('S3_SECRET_KEY') ?: '',
        'region' => $_ENV['S3_REGION'] ?? getenv('S3_REGION') ?: 'ru-central1',
        'endpoint' => $_ENV['S3_ENDPOINT'] ?? getenv('S3_ENDPOINT') ?: 'https://storage.yandexcloud.net',
        'pathPrefix' => $_ENV['S3_PATH_PREFIX'] ?? getenv('S3_PATH_PREFIX') ?: '',
    ],
    // SMS configuration (smspilot.ru)
    'sms' => [
        'apiKey' => $_ENV['SMS_API_KEY'] ?? getenv('SMS_API_KEY') ?: '',
        'apiUrl' => $_ENV['SMS_API_URL'] ?? getenv('SMS_API_URL') ?: 'https://smspilot.ru/api.php',
        //'isEmulator' => ($_ENV['SMS_IS_EMULATOR'] ?? getenv('SMS_IS_EMULATOR')) === '1' || ($_ENV['SMS_API_KEY'] ?? getenv('SMS_API_KEY')) === 'XXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZXXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZ1234',
    ],
];
