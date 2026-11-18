<?php

/**
 * Ví dụ cấu hình component trong config/web.php của ứng dụng Yii2.
 * Credentials và base URL đọc từ biến môi trường, không ghi trực tiếp vào source.
 */

return [
    'components' => [
        'payswitch' => [
            'class' => \Haidev6199\PaySwitch\Components\PaySwitchComponent::class,
            // Để trống thì đọc từ env: PAYSWITCH_BASE_URL, PAYSWITCH_TIMEOUT, PAYSWITCH_CONNECT_TIMEOUT,
            // PAYSWITCH_SSL_VERIFY, PAYSWITCH_RETRY_READS
            'baseUrl' => getenv('PAYSWITCH_BASE_URL') ?: null,
            'timeout' => 30,
            'connectTimeout' => 5,
            'sslVerify' => true,
        ],
    ],
];
