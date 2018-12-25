<?php

$allowedOriginList = [
    'http://*.qq.com',
    'https://*.qq.com',
    'http://*.weixin.qq.com',
    'https://*.weixin.qq.com',
    'http://open.weixin.qq.com',
    'https://open.weixin.qq.com',
    'http://*.papitube.com',
    'http://*.mttop.cn',
    'https://*.mttop.cn',
    'https://*.papitube.com',
    'http://localhost:8080'
];
if (env('API_DEBUG')) {
    $allowedOriginList = ['*'];
}

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */
   
    'supportsCredentials' => false,
    'allowedOrigins' => ['*'],
    'allowedOriginsPatterns' => [],
    'allowedHeaders' => ['*'],
    'allowedMethods' => ['*'],
    'exposedHeaders' => [],
    'maxAge' => 0,

];
