<?php

return [
    'default_expiration_time_in_days' => 1,
    'private_key_path' => storage_path(env('CLOUDFRONT_PRIVATE_KEY_PATH', 'cloudfront/private_key.pem')),
    'key_pair_id' => env('CLOUDFRONT_KEY_PAIR_ID', ''),
    'version' => env('CLOUDFRONT_API_VERSION', 'latest'),
];
