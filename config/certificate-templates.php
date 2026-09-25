<?php

use App\Models\Certificate;

/*
|--------------------------------------------------------------------------
| Certificate templates
|--------------------------------------------------------------------------
|
| Each document type is rendered by one of two paths, and which one is used
| depends solely on whether a 'template' key is present below.
|
|   With a 'template' key
|       PdfTemplateStamper imports the approved PDF form as a page background
|       and stamps the values in 'fields' onto it at millimetre coordinates.
|       Use this where the Registrar has an approved fillable form that must
|       be reproduced exactly.
|
|   Without a 'template' key
|       The generator falls back to a Blade view and renders it with Dompdf.
|       Use this where no approved PDF exists, or where the form is long
|       enough that stamping coordinates would be unmanageable.
|
| hasTemplate() reads the 'template' key and checks the file is readable, so
| commenting the key out is what switches a document to the Blade path.
|
| Note that 'page' and 'orientation' below are read by the stamper only. The
| Blade path takes its paper size from the $paper match in
| CertificateGenerator::buildDompdf(), so a document rendered by Blade must
| have its size set there as well. The two are kept in step by hand; a future
| tidy would be to have buildDompdf() read these keys instead.
|
*/

return [

    /*
     | Stamped onto the approved PDF form.
     */
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

    /*
     | Stamped onto the approved PDF form.
     */
    Certificate::TYPE_ENROLMENT => [
        'template'    => storage_path('templates/certificate-of-enrolment.pdf'),
        'page'        => 'A4',
        'orientation' => 'portrait',

        'fields' => [
            ['value' => 'full_name',      'x' => 60, 'y' => 92,  'width' => 120, 'style' => 'B', 'size' => 11, 'upper' => true],
            ['value' => 'student_number', 'x' => 60, 'y' => 100, 'width' => 120, 'size' => 10],
            ['value' => 'program',        'x' => 60, 'y' => 110, 'width' => 130, 'size' => 10],
            ['value' => 'college',        'x' => 60, 'y' => 118, 'width' => 130, 'size' => 10],
            ['value' => 'year_level',     'x' => 60, 'y' => 126, 'width' => 60,  'size' => 10],
            ['value' => 'semester',       'x' => 60, 'y' => 134, 'width' => 80,  'size' => 10],
            ['value' => 'academic_year',  'x' => 60, 'y' => 142, 'width' => 60,  'size' => 10],
            ['type'  => 'qr',             'x' => 20, 'y' => 240, 'size' => 24],
            ['text'  => '{serial}',       'x' => 18, 'y' => 266, 'width' => 30, 'align' => 'C', 'font' => 'Courier', 'size' => 6],
            ['text'  => 'SHA-256 {hash}', 'x' => 50, 'y' => 268, 'width' => 150, 'font' => 'Courier', 'size' => 4.5, 'color' => [138, 148, 173]],
        ],
    ],

    /*
     | Rendered from resources/views/pdf/honorable-dismissal.blade.php.
     |
     | No 'template' key, so hasTemplate() returns false and the generator
     | takes the Blade path. The form is reconstructed rather than stamped
     | because it carries two halves on one sheet, divided by a cut line, and
     | placing that by coordinates would be considerably harder to maintain
     | than a table-based layout.
     |
     | The paper size must also be set in buildDompdf(), which is where the
     | Blade path reads it from.
     */
    Certificate::TYPE_DISMISSAL => [
        'page'        => 'A4',
        'orientation' => 'landscape',
    ],

    /*
     | Rendered from resources/views/pdf/transcript-of-records.blade.php.
     |
     | Printed on long bond, 216 by 330 mm, which buildDompdf() expresses in
     | points because Dompdf has no name for that size. The 'page' value here
     | is not read by the Blade path and is recorded for reference only.
     */
    Certificate::TYPE_TOR => [
        'page'        => 'Long bond (216 x 330 mm)',
        'orientation' => 'portrait',
    ],

];
