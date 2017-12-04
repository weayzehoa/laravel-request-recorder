<?php
/**
 * littleBookBoy/laravel-request-recorder Config
 */
return [
    /**
     * - enabled : true or false
     * - group : route middleware group name
     * - except : 僅記錄除了這些方法之外的請求, 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
     */
    'recorder' => [
        'enabled' => true,
        'group' => 'api',
        'except' => ['']
    ]
];