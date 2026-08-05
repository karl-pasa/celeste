<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        /*
        |----------------------------------------------------------------------
        | Local
        |----------------------------------------------------------------------
        | Generated certificates and QR images are written here.
        |
        | The root is configurable because serverless hosts mount the project
        | read-only and allow writes only to /tmp. On Vercel, set
        | FILESYSTEM_LOCAL_ROOT=/tmp/celeste-storage — the directory is wiped
        | between invocations, which is fine: the PDF is only a rendering of
        | the hashed payload, and CertificateController regenerates it on
        | demand whenever the file is missing.
        |
        | Locally, leave this unset and files persist normally under storage/app.
        */
        'local' => [
            'driver' => 'local',
            'root'   => env('FILESYSTEM_LOCAL_ROOT', storage_path('app')),
            'serve'  => true,
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => env('FILESYSTEM_PUBLIC_ROOT', storage_path('app/public')),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        /*
        | For a real deployment, point certificate storage at object storage so
        | files persist. Set FILESYSTEM_DISK=s3 and fill in the credentials —
        | Supabase Storage is S3-compatible and needs no code changes.
        */
        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
