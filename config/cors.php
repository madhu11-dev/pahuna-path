<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Use the exact origin of your React app
    'allowed_origins' => ['http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,
    // Must be true if sending credentials (cookies, auth headers)
    'supports_credentials' => true,

];
