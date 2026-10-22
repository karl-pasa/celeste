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

    Certificate::TYPE_DIPLOMA => [
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

    Certificate::TYPE_DISMISSAL => [
        'page'        => 'A4',
        'orientation' => 'landscape',
    ],

    Certificate::TYPE_TOR => [
        'page'        => 'Long bond (216 x 330 mm)',
        'orientation' => 'portrait',
    ],

];
