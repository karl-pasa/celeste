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
        'email_domain'    => env('CELESTE_EMAIL_DOMAIN', 'parsu.edu.ph'), 
    ],

    /*
    |--------------------------------------------------------------------------
    | Signatories
    |--------------------------------------------------------------------------
    | Names printed above the signature lines. Change these in .env rather than
    | in code so a change of officials does not require a deployment.
    */
    'officials' => [
        'registrar'       => env('CELESTE_REGISTRAR_NAME', ''),
        'president'       => env('CELESTE_PRESIDENT_NAME', ''),
        'records_officer' => env('CELESTE_RECORDS_OFFICER', ''),
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
    'hash_pepper' => env('CELESTE_HASH_PEPPER'),

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
    | Student accounts
    |--------------------------------------------------------------------------
    | Students sign in with their university email address. Where a record has
    | no address on file, one is derived as <student number>@<domain> so the
    | account can still be created.
    |
    | The initial password is the student number. See the warning in
    | celeste:create-student-accounts before using this in earnest -- a student
    | number is printed on ID cards and appears on every document, so it is a
    | starting credential, not a lasting one.
    */
    'student_email_domain' => env('CELESTE_STUDENT_EMAIL_DOMAIN', 'parsu.edu.ph'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    | allow_remember — remember-me issues a cookie valid for years, bypassing
    | session lifetime. On the Registrar's shared counter that is a standing
    | credential left on the machine, so it is off unless a deployment on
    | personal devices opts in.
    |
    | require_password_change — while a user's password_changed_at is null they
    | are still on their provisioned credential. For students that is their
    | student number, printed on every document this system issues.
    |
    | require_email_verification — leave false until mail is configured, or
    | students will be locked out of a system that cannot send them a link.
    */
    'auth' => [
        'allow_remember'             => (bool) env('CELESTE_ALLOW_REMEMBER', false),
        'require_password_change'    => (bool) env('CELESTE_REQUIRE_PASSWORD_CHANGE', true),
        'require_email_verification' => (bool) env('CELESTE_REQUIRE_EMAIL_VERIFICATION', false),
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
    | Programs by college
    |--------------------------------------------------------------------------
    | Choosing a college on the batch generation page narrows the program
    | dropdown to that college's offerings.
    |
    | Prefilled from ParSU's published program offerings -- CHECK THIS AGAINST
    | YOUR REGISTRAR'S OFFICIAL LIST before relying on it. The university does
    | not publish a complete college-by-college breakdown, so some placements
    | here are inferred.
    |
    | A college with no entry here falls back to whatever programs appear in
    | student_records for that college, so an unlisted college still works.
    |
    | The keys must match the entries in 'colleges' above exactly, including
    | the campus suffixes.
    */
    'programs' => [

        'College of Education' => [
            'Bachelor of Elementary Education',
            'Bachelor of Secondary Education major in English',
            'Bachelor of Secondary Education major in Filipino',
            'Bachelor of Secondary Education major in Mathematics',
            'Bachelor of Secondary Education major in Science',
            'Bachelor of Secondary Education major in Social Studies',
            'Bachelor of Secondary Education major in Values Education',
        ],

        'College of Engineering and Computational Sciences' => [
            'Bachelor of Science in Information Technology',
            'Bachelor of Science in Computer Science',
            'Bachelor of Science in Civil Engineering',
            'Bachelor of Science in Sanitary Engineering',
            'Bachelor of Engineering Technology major in Electrical Engineering Technology',
        ],

        'College of Business and Management' => [
            'Bachelor of Science in Accountancy',
            'Bachelor of Science in Business Administration major in Financial Management',
            'Bachelor of Science in Entrepreneurship',
            'Bachelor of Science in Economics',
            'Bachelor of Science in Office Administration',
        ],

        'College of Science' => [
            'Bachelor of Science in Biology',
            'Bachelor of Science in Geology',
            'Bachelor of Science in Mathematics',
            'Bachelor of Science in Environmental Science',
        ],

        'College of Arts and Humanities' => [
            'Bachelor of Arts in Communication',
            'Bachelor of Arts in English Language Studies',
        ],

        'College of Hospitality and Tourism Management-San Jose Campus' => [
            'Bachelor of Science in Hospitality Management',
            'Bachelor of Science in Tourism Management',
        ],

        'College of Agribusiness and Community Development-Salogon Campus' => [
            'Bachelor of Science in Agriculture',
            'Bachelor of Science in Agribusiness',
            'Bachelor of Science in Community Development',
        ],

        'College of Public Safety and Community Health-Lagonoy Campus' => [
            'Bachelor of Science in Criminology',
            'Bachelor of Science in Public Health',
        ],

        'College of Fisheries and Marines Science-Sagñay Campus' => [
            'Bachelor of Science in Fisheries',
            'Bachelor of Science in Marine Biology',
        ],

        'College of Environmental Science and Design-Tinambac Campus' => [
            'Bachelor of Science in Environmental Science',
        ],

        'College of Sustainable Communities and Ecosystems-Caramoan Campus' => [
            'Bachelor of Science in Community Development',
        ],
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
