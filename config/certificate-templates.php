<?php

use App\Models\Certificate;

/*
|--------------------------------------------------------------------------
| Certificate PDF templates
|--------------------------------------------------------------------------
|
| Drop your own official PDFs into storage/templates/ and describe where each
| field should be printed. When a template file exists for a document type,
| CELESTE stamps the fields onto your PDF and ignores the Blade template
| entirely. Leave 'template' as null (or delete the file) to fall back to the
| built-in Blade layouts.
|
| ALL COORDINATES ARE IN MILLIMETRES from the TOP-LEFT corner of the page,
| so you can measure them off a printed copy with a ruler.
|
| A4 portrait  = 210 x 297 mm
| A4 landscape = 297 x 210 mm
| Long bond    = 216 x 330 mm
|
| To find coordinates without guessing, run:
|     php artisan celeste:calibrate diploma
| That prints your template with a labelled 10 mm grid over it.
|
|--------------------------------------------------------------------------
| Field options
|--------------------------------------------------------------------------
| type    text (default) | qr | serial
| value   a key from the hashed payload, e.g. 'full_name', 'program'
| text    a literal string instead of a payload key. Supports the
|         placeholders {serial}, {hash}, {short_hash}, {verify_url}, {date},
|         and any payload key wrapped in braces, e.g. {full_name}
| x, y    position in mm from the top-left
| width   box width in mm; needed for centring. 0 means "to the page edge"
| align   L | C | R  (default L)
| font    Helvetica | Times | Courier   (PDF core fonts, always available)
| style   '' | B | I | BI
| size    font size in points
| color   [r, g, b]
| upper   true to force uppercase
|
| Only the fields listed here get printed. Anything absent from the payload
| is skipped silently rather than printing an empty box.
|
*/

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
            [
                'text' => 'Verify at {verify_url}',
                'x' => 14, 'y' => 196, 'width' => 160,
                'font' => 'Helvetica', 'size' => 5.5, 'color' => [138, 148, 173],
            ],
            [
                'text' => 'SHA-256 {hash}',
                'x' => 14, 'y' => 200, 'width' => 200,
                'font' => 'Courier', 'size' => 4.5, 'color' => [138, 148, 173],
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
        'template'    => storage_path('templates/honorable-dismissal.pdf'),
        'page'        => 'A4',
        'orientation' => 'portrait',

        'fields' => [
            ['value' => 'full_name',     'x' => 60, 'y' => 92,  'width' => 120, 'style' => 'B', 'size' => 11, 'upper' => true],
            ['value' => 'student_number','x' => 60, 'y' => 100, 'width' => 120, 'size' => 10],
            ['value' => 'program',       'x' => 60, 'y' => 110, 'width' => 130, 'size' => 10],
            ['value' => 'college',       'x' => 60, 'y' => 118, 'width' => 130, 'size' => 10],
            ['value' => 'date_admitted', 'x' => 60, 'y' => 126, 'width' => 60,  'size' => 10],
            ['type'  => 'qr',            'x' => 20, 'y' => 240, 'size' => 24],
            ['text'  => '{serial}',      'x' => 18, 'y' => 266, 'width' => 30, 'align' => 'C', 'font' => 'Courier', 'size' => 6],
        ],
    ],

    /*
     | The Transcript of Records is intentionally NOT template-stamped by
     | default. Its grade table has a variable number of rows spanning an
     | unknown number of pages, which fixed coordinates cannot express.
     | The Blade template flows and paginates automatically.
     |
     | If your transcript form must be used, set 'template' to the file path
     | and add a 'rows' block describing where the table starts and how tall
     | each line is. See USING-YOUR-PDF-TEMPLATES.md.
     */
    Certificate::TYPE_TOR => [
        'template'    => null,
        'page'        => 'A4',
        'orientation' => 'portrait',
        'fields'      => [],
    ],
];
