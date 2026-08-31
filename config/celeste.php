<?php

return [
    'institution' => [
        'name'            => env('CELESTE_INSTITUTION', 'Partido State University'),
        'short'           => env('CELESTE_INSTITUTION_SHORT', 'Partido State University'),
        'campus'          => env('CELESTE_CAMPUS', 'Camarines Sur'),
        'registrar_email' => env('CELESTE_REGISTRAR_EMAIL', 'registrar@parsu.edu.ph'),
        'email_domain'    => env('CELESTE_EMAIL_DOMAIN', 'parsu.edu.ph'), 
    ],

    'officials' => [
        'registrar'       => env('CELESTE_REGISTRAR_NAME', ''),
        'president'       => env('CELESTE_PRESIDENT_NAME', ''),
        'records_officer' => env('CELESTE_RECORDS_OFFICER', ''),
    ],

    'hash_pepper' => env('CELESTE_HASH_PEPPER'),

    'analytics' => [
        // Raise a flag once failed checks pass this share of all checks.
        'failure_threshold' => (float) env('CELESTE_FAILURE_THRESHOLD', 0.15),

        // Flag a single certificate verified from this many distinct addresses.
        'spread_threshold'  => (int) env('CELESTE_SPREAD_THRESHOLD', 12),
    ],

    'student_email_domain' => env('CELESTE_STUDENT_EMAIL_DOMAIN', 'parsu.edu.ph'),

    'auth' => [
        'allow_remember'             => (bool) env('CELESTE_ALLOW_REMEMBER', false),
        'require_password_change'    => (bool) env('CELESTE_REQUIRE_PASSWORD_CHANGE', true),
        'require_email_verification' => (bool) env('CELESTE_REQUIRE_EMAIL_VERIFICATION', false),
    ],

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

    'documents' => [
        'diploma'                  => 'University Diploma',
        'honorable_dismissal'      => 'Honorable Dismissal',
        'certificate_of_enrolment' => 'Certificate of Enrolment',
        'transcript_of_records'    => 'Transcript of Records',
    ],
];
