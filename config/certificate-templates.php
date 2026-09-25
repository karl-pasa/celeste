<?php

use App\Models\Certificate;

return [

    Certificate::TYPE_DIPLOMA => [
        'template'    => storage_path('templates/diploma.pdf'),
        'page'        => 'A4',
        'orientation' => 'landscape',

        'fields' => [
            [
                'value' => 'full_name',
                'x' => 0, 'y' => 96, 'width' => 297,
                'align' => 'C', 'font' => 'Times', 'style' => 'B',
                'size' => 26, 'color' => [18, 34, 79], 'upper' => true,
            ],
            [
                'value' => 'program',
                'x' => 0, 'y' => 122, 'width' => 297,
                'align' => 'C', 'font' => 'Times', 'style' => 'B', 'size' => 15,
            ],
            [
                'value' => 'latin_honor',
                'x' => 0, 'y' => 134, 'width' => 297,
                'align' => 'C', 'font' => 'Times', 'style' => 'I',
                'size' => 12, 'color' => [150, 101, 15],
            ],
            [
                'type' => 'qr',
                'x' => 252, 'y' => 168, 'size' => 26,
            ],
            [
                'text' => '{serial}',
                'x' => 246, 'y' => 195, 'width' => 38,
                'align' => 'C', 'font' => 'Courier', 'size' => 6,
                'color' => [91, 103, 132],
            ],
        ],
    ],

    Certificate::TYPE_ENROLMENT => [
        'template'    => storage_path('templates/certificate-of-enrolment.pdf'),
        'page'        => 'A4',
        'orientation' => 'portrait',

        'fields' => [
            ['value' => 'full_name',     'x' => 60, 'y' => 92,  'width' => 120, 'style' => 'B', 'size' => 11, 'upper' => true],
            ['value' => 'student_number','x' => 60, 'y' => 100, 'width' => 120, 'size' => 10],
            ['value' => 'program',       'x' => 60, 'y' => 110, 'width' => 130, 'size' => 10],
            ['value' => 'college',       'x' => 60, 'y' => 118, 'width' => 130, 'size' => 10],
            ['value' => 'year_level',    'x' => 60, 'y' => 126, 'width' => 60,  'size' => 10],
            ['value' => 'semester',      'x' => 60, 'y' => 134, 'width' => 80,  'size' => 10],
            ['value' => 'academic_year', 'x' => 60, 'y' => 142, 'width' => 60,  'size' => 10],
            ['type'  => 'qr',            'x' => 20, 'y' => 240, 'size' => 24],
            ['text'  => '{serial}',      'x' => 18, 'y' => 266, 'width' => 30, 'align' => 'C', 'font' => 'Courier', 'size' => 6],
            ['text'  => 'SHA-256 {hash}','x' => 50, 'y' => 268, 'width' => 150, 'font' => 'Courier', 'size' => 4.5, 'color' => [138, 148, 173]],
        ],
    ],

Certificate::TYPE_DISMISSAL => [
    'page'        => 'A4',
    'orientation' => 'landscape',
],

    Certificate::TYPE_TOR => [
        'page'        => 'Letter',
        'orientation' => 'portrait',
    ],
];
