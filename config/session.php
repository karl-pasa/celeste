<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Lifetime
    |--------------------------------------------------------------------------
    | Minutes of inactivity before the session ends. The framework default of
    | 120 is generous for a registrar's counter, where one machine serves many
    | people in sequence and the previous person has usually walked away.
    |
    | expire_on_close discards the cookie when the browser closes, which covers
    | the common case of someone shutting the lid and leaving.
    */
    'lifetime' => (int) env('SESSION_LIFETIME', 30),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', true),

    /*
    | Encrypt the session payload at rest. With the database driver the payload
    | sits in a table that anyone with a database read can query; encryption
    | means a read does not yield session contents.
    */
    'encrypt' => env('SESSION_ENCRYPT', true),

    'files'      => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table'      => env('SESSION_TABLE', 'sessions'),
    'store'      => env('SESSION_STORE'),
    'lottery'    => [2, 100],

    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'celeste'), '_') . '_session'),
    'path'   => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),

    /*
    | Send the session cookie over HTTPS only. Defaults to true anywhere other
    | than local development, so a production deployment cannot accidentally
    | transmit a session cookie in clear.
    */
    'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') !== 'local'),

    /*
    | JavaScript must never read the session cookie. Nothing in this system
    | needs to, and blocking it removes session theft as an outcome of any XSS.
    */
    'http_only' => true,

    /*
    | 'lax' lets the cookie ride ordinary top-level navigation — including the
    | QR verification links people open from a phone — while withholding it
    | from cross-site POSTs. Defence in depth alongside the CSRF token.
    */
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => false,
];
