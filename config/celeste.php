<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Institution
    |--------------------------------------------------------------------------
    | Printed on every generated document and shown on the public portal.
    */
    'institution' => [
        'name'            => env('CELESTE_INSTITUTION', 'Partido State University'),
        'short'           => env('CELESTE_INSTITUTION_SHORT', 'Partido State University'),
        'campus'          => env('CELESTE_CAMPUS', 'Camarines Sur'),
        'registrar_email' => env('CELESTE_REGISTRAR_EMAIL', 'registrar@parsu.edu.ph'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signatories
    |--------------------------------------------------------------------------
    | Names printed above the signature lines. Change these in .env rather than
    | in code so a change of officials does not require a deployment.
    */
    'officials' => [
        'registrar'       => env('CELESTE_REGISTRAR_NAME', 'RAUL G. BRADECINA, Ph.D.'),
        'president'       => env('CELESTE_PRESIDENT_NAME', 'THE UNIVERSITY PRESIDENT'),
        'records_officer' => env('CELESTE_RECORDS_OFFICER', 'RECORDS OFFICER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hash pepper
    |--------------------------------------------------------------------------
    | Server-side key mixed into every certificate fingerprint via HMAC-SHA256.
    | Someone who knows every printed field still cannot forge a matching hash
    | without this value. Set it once, then never rotate it — changing it
    | invalidates every certificate already issued.
    */
    'hash_pepper' => env('CELESTE_HASH_PEPPER', 'change-this-before-going-live'),

    /*
    |--------------------------------------------------------------------------
    | Decision support thresholds
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        // Raise a flag once failed checks pass this share of all checks.
        'failure_threshold' => (float) env('CELESTE_FAILURE_THRESHOLD', 0.15),

        // Flag a single certificate verified from this many distinct addresses.
        'spread_threshold'  => (int) env('CELESTE_SPREAD_THRESHOLD', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Colleges
    |--------------------------------------------------------------------------
    | Offered in the batch generation filter so a cohort can be selected before
    | any record carrying that college exists. Colleges already present in
    | student_records are merged in automatically, so an imported spelling that
    | differs from this list still appears rather than silently disappearing
    | from the dropdown.
    |
    | The order here is the order shown in the dropdown: Goa campus colleges
    | first, then the satellite campuses. Alphabetical sorting would scatter
    | the campuses through the list and make a long dropdown harder to scan.
    |
    | Edit this list to match your registrar's official naming.
    */
    'colleges' => [
        // Goa main campus
        'College of Education',
        'College of Engineering and Computational Sciences',
        'College of Business and Management',
        'College of Science',
        'College of Arts and Humanities',

        // Satellite campuses
        'College of Hospitality and Tourism Management-San Jose Campus',
        'College of Agribusiness and Community Development-Salogon Campus',
        'College of Public Safety and Community Health-Lagonoy Campus',
        'College of Fisheries and Marines Science-Sagñay Campus',
        'College of Environmental Science and Design-Tinambac Campus',
        'College of Sustainable Communities and Ecosystems-Caramoan Campus',
    ],

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'diploma'                  => 'University Diploma',
        'honorable_dismissal'      => 'Honorable Dismissal',
        'certificate_of_enrolment' => 'Certificate of Enrolment',
        'transcript_of_records'    => 'Transcript of Records',
    ],
];