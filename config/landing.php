<?php

$appHost = parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [
    'custom_domain_scheme' => env('CUSTOM_DOMAIN_SCHEME', 'https'),
    'custom_domain_target' => env('CUSTOM_DOMAIN_TARGET', $appHost),
    'custom_domain_verify_timeout' => (int) env('CUSTOM_DOMAIN_VERIFY_TIMEOUT', 6),
    'app_hosts' => array_values(array_filter(array_unique([
        strtolower($appHost),
        'localhost',
        '127.0.0.1',
    ]))),
];
