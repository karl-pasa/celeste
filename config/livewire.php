<?php

return [
    'class_namespace' => 'App\\Livewire',
    'view_path' => resource_path('views/livewire'),
    'layout' => 'layouts.app',
    'lazy_placeholder' => null,

    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['file', 'max:12288'],   // 12MB — CSV imports only
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => ['csv', 'txt'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => ['show_progress_bar' => true, 'progress_bar_color' => '#22a94a'],
    'inject_morph_markers' => true,
    'pagination_theme' => 'bootstrap',
];
