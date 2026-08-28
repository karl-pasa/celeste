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
    'page'        => 'Legal',
    'orientation' => 'landscape',

    'fields' => [
        // student_name
        ['value' => 'full_name', 'x' => 61.7, 'y' => 74.8, 'width' => 55.0, 'align' => 'L', 'size' => 10],
        // student_address
        ['value' => 'address', 'x' => 124.5, 'y' => 74.8, 'width' => 33.2, 'align' => 'L', 'size' => 10],
        // year_level
        ['value' => 'year_level', 'x' => 20.8, 'y' => 82.2, 'width' => 9.9, 'align' => 'C', 'size' => 10],
        // program
        ['value' => 'program', 'x' => 72.7, 'y' => 82.2, 'width' => 40.9, 'align' => 'L', 'size' => 10],
        // date_issued
        ['value' => 'issued_on', 'x' => 104.8, 'y' => 56.1, 'width' => 44.4, 'align' => 'L', 'size' => 10],
        // student_signature_printed_name — printed name under the signature line
        ['value' => 'full_name', 'x' => 17.3, 'y' => 109.5, 'width' => 62.8, 'align' => 'C', 'size' => 10],
        // registrar_printed_name — comes from .env, not the payload
        ['text' => '{registrar}', 'x' => 103.4, 'y' => 122.2, 'width' => 52.2, 'align' => 'C', 'size' => 10],

        // ---- Verification block. Sits in the clear area below the
        // ---- signature line, left of the dry seal box, above the OR block.
        ['type' => 'qr', 'x' => 78.0, 'y' => 165.0, 'size' => 24],
        ['text' => '{serial}', 'x' => 105.0, 'y' => 168.0, 'width' => 52, 'font' => 'Courier', 'style' => 'B', 'size' => 7],
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
/*
|--------------------------------------------------------------------------
| Official Transcript of Records  ·  PSU-F-URO-27
|--------------------------------------------------------------------------
| Replace the whole Certificate::TYPE_TOR block in config/certificate-templates.php
| with this one. Every other document type stays as it is.
|
| TYPOGRAPHY
| ----------
| Every field now uses one font and one size, declared once in the constants
| below. Previously each field carried its own — 11pt for the name, 9.5 for the
| address, 8 for the serial — which is what made the filled form look assembled
| from different sources.
|
| FONT is Helvetica, which is metrically compatible with Arial: the same
| character widths, so text occupies the same space on the page. The form's
| printed labels are Arial, so filled values now sit consistently against them.
|
| To change the look of the entire document, change these two values.
*/

$FONT = 'Helvetica',   // core font: Helvetica | Times | Courier
$SIZE = 9.5,           // point size used by every field
$MIN  = 6.5,           // floor when a value is too long for its box
$INK  = [22, 35, 63],  // near-black, matching printed form text

    Certificate::TYPE_TOR => [
        'template'    => storage_path('templates/transcript-of-records.pdf'),
        'page'        => [216, 330],   // long bond, millimetres
        'orientation' => 'portrait',

        'fields' => [

            // ---- Page 1 · identity block ----------------------------------
            ['value' => 'student_number', 'x' => 65.6, 'y' => 49.4, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'full_name',      'x' => 65.6, 'y' => 54.9, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'program',        'x' => 65.6, 'y' => 60.3, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'major',          'x' => 65.6, 'y' => 65.8, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],

            // ---- Page 1 · personal information ----------------------------
            ['value' => 'address',     'x' => 53.6, 'y' => 90.1,  'width' => 144.6,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'gender',      'x' => 53.6, 'y' => 95.4,  'width' => 144.6,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'nationality', 'x' => 53.6, 'y' => 100.7, 'width' => 144.6,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'birth_date',  'x' => 53.6, 'y' => 106.0, 'width' => 144.6,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'birthplace',  'x' => 53.6, 'y' => 111.3, 'width' => 144.6,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],

            // ---- Page 1 · admission and graduation data -------------------
            ['value' => 'date_of_admission', 'x' => 55.7,  'y' => 182.4, 'width' => 50.1,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'date_conferred',    'x' => 151.7, 'y' => 130.9, 'width' => 48.0,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'board_resolution',  'x' => 151.7, 'y' => 135.8, 'width' => 48.0,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'board_date',        'x' => 151.7, 'y' => 140.8, 'width' => 48.0,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'latin_honor',       'x' => 132.3, 'y' => 151.3, 'width' => 67.4,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],

            // ---- Page 1 · signatories and page numbering ------------------
            ['text' => '{records}',   'x' => 19.8,  'y' => 291.7, 'width' => 55.7,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{registrar}', 'x' => 19.8,  'y' => 305.2, 'width' => 55.7,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{page}',      'x' => 173.6, 'y' => 308.3, 'width' => 7.8,
             'align' => 'C', 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{pages}',     'x' => 187.3, 'y' => 308.3, 'width' => 7.8,
             'align' => 'C', 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],

            // ---- Page 1 · verification block ------------------------------
            // Sits in the REMARKS box: clear of the dry seal and both signature
            // lines, and on the cover page where a verifier looks first.
            ['type' => 'qr', 'x' => 20.0, 'y' => 239.0, 'size' => 20, 'page' => 1],

            ['text' => '{serial}', 'x' => 43.0, 'y' => 241.0, 'width' => 60, 'page' => 1,
             'font' => 'Courier', 'style' => 'B', 'size' => 8, 'min_size' => 6,
             'color' => [18, 34, 79]],
            ['text' => 'Scan to verify this transcript', 'x' => 43.0, 'y' => 246.5,
             'width' => 60, 'page' => 1,
             'font' => $FONT, 'size' => 7, 'min_size' => 6, 'color' => [91, 103, 132]],
            ['text' => 'SHA-256 {hash}', 'x' => 43.0, 'y' => 251.0, 'width' => 100, 'page' => 1,
             'font' => 'Courier', 'size' => 4.5, 'min_size' => 3.5, 'color' => [138, 148, 173]],

            // ---- Page 2 · repeated on every table page ---------------------
            ['value' => 'student_number', 'page' => 2, 'x' => 65.6, 'y' => 49.4, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'full_name',      'page' => 2, 'x' => 65.6, 'y' => 54.9, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'program',        'page' => 2, 'x' => 65.6, 'y' => 60.3, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['value' => 'major',          'page' => 2, 'x' => 65.6, 'y' => 65.8, 'width' => 99.5,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{records}',   'page' => 2, 'x' => 19.8,  'y' => 291.7, 'width' => 55.7,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{registrar}', 'page' => 2, 'x' => 19.8,  'y' => 305.2, 'width' => 55.7,
             'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{page}',      'page' => 2, 'x' => 173.6, 'y' => 308.3, 'width' => 7.8,
             'align' => 'C', 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
            ['text' => '{pages}',     'page' => 2, 'x' => 187.3, 'y' => 308.3, 'width' => 7.8,
             'align' => 'C', 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
        ],

        /*
        | The repeating subject table.
        |
        | 31 ruled lines, the first at y 95.2, each 4.77 mm below the last.
        | A term heading consumes one of those lines.
        |
        | Columns share the same font and size as the fields above, so the
        | table does not read as a different document from its own header.
        */
        'rows' => [
            'page'          => 2,
            'start_y'       => 95.2,
            'row_height'    => 4.77,
            'per_page'      => 31,
            'term_headings' => true,

            'term_heading' => [
                'x' => 18.3, 'width' => 130, 'align' => 'L',
                'font' => $FONT, 'style' => 'B',
                'size' => $SIZE, 'min_size' => $MIN,
                'color' => [18, 34, 79],
            ],

            'columns' => [
                ['key' => 'code',    'x' => 18.3,  'width' => 33.9, 'align' => 'L',
                 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
                ['key' => 'title',   'x' => 53.6,  'width' => 97.4, 'align' => 'L',
                 'font' => $FONT, 'size' => $SIZE, 'min_size' => 5.5, 'color' => $INK],
                ['key' => 'grade',   'x' => 152.4, 'width' => 15.5, 'align' => 'C',
                 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
                ['key' => 'removal', 'x' => 169.3, 'width' => 15.5, 'align' => 'C',
                 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK],
                ['key' => 'units',   'x' => 186.3, 'width' => 13.4, 'align' => 'C',
                 'font' => $FONT, 'size' => $SIZE, 'min_size' => $MIN,
                 'decimals' => 1, 'color' => $INK],
            ],

            'closing' => [
                'text'  => 'x x x x x x x x x   TRANSCRIPT CLOSED   x x x x x x x x x',
                'x' => 17.6, 'y' => 244.1, 'width' => 182, 'align' => 'C',
                'font' => $FONT, 'style' => 'B',
                'size' => $SIZE, 'min_size' => $MIN, 'color' => $INK,
            ],
        ],
    ],
];