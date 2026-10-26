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
        'failure_threshold' => (float) env('CELESTE_FAILURE_THRESHOLD', 0.15),
        'spread_threshold'  => (int) env('CELESTE_SPREAD_THRESHOLD', 12),
    ],

    'student_email_domain' => env('CELESTE_STUDENT_EMAIL_DOMAIN', 'parsu.edu.ph'),

    'auth' => [
        'allow_remember'             => (bool) env('CELESTE_ALLOW_REMEMBER', false),
        'require_password_change'    => (bool) env('CELESTE_REQUIRE_PASSWORD_CHANGE', true),
        'require_email_verification' => (bool) env('CELESTE_REQUIRE_EMAIL_VERIFICATION', false),
    ],

    'colleges' => [
        'College of Education',
        'College of Engineering and Computational Sciences',
        'College of Business and Management',
        'College of Science',
        'College of Arts and Humanities',
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
            'Bachelor of Science in Mathematics',
            'Bachelor of Science in Civil Engineering',
            'Bachelor of Science in Sanitary Engineering',
            'Bschelor of Automotive Technology',
            'Bachelor of Engineering Technology in Mechanical Engineering Technology-major in Automotive Technology',
            'Bachelor of Engineering Technology in Mechanical Engineering Technology-major in Refrigeration and Air-Conditioning Technology',
        ],

        'College of Business and Management' => [
            'Bachelor of Science in Accountancy',
            'Bachelor of Science in Business Administration',
            'Bachelor of Science in Entrepreneurship',
            'Bachelor of Science in Economics',
            'Bachelor of Science in Office Administration',
        ],

        'College of Science' => [
            'Bachelor of Science in Biology',
            'Bachelor of Science in Geology',
        ],

        'College of Arts and Humanities' => [
            'Bachelor of Public Administration',
            'Bachelor of Arts in Communication',
        ],

        'College of Hospitality and Tourism Management-San Jose Campus' => [
            'Bachelor of Science in Hospitality Management',
            'Bachelor of Science in Tourism Management',
        ],

        'College of Agribusiness and Community Development-Salogon Campus' => [
            'Bachelor of Science in Agribusiness',
            'Bachelor of Science in Community Development',
        ],

        'College of Public Safety and Community Health-Lagonoy Campus' => [
            'Bachelor of Science in Criminology',
            'Bachelor of Science in Industrial Security Management',
            'Bachelor of Science in Nutrition and Dietetics',
        ],

        'College of Fisheries and Marines Science-Sagñay Campus' => [
            'Bachelor of Science in Fisheries',
            'Bachelor of Science in Marine Biology',
        ],

        'College of Environmental Science and Design-Tinambac Campus' => [
            'Bachelor of Science in Environmental Science',
            'Bachelor of Science in Environmental Planning',
            'Bachelor of Science in Forestry',
        ],

        'College of Sustainable Communities and Ecosystems-Caramoan Campus' => [
            'Bachelor of Automotive Technology',
            'Bachelor of Engineering Technology major in Mechanical Engineering Technology-Automotive Technology',
            'Bachelor of Science in Hospitality Manangement',
            'Bachelor of Science in Biology-Conservation and Restoration Ecology',
            'Bachelor of Science in Tourism Management-Ecotourism',
        ],
    ],

    // Program name (as stored in student_records.program) => short code on the
    // Certificate of Enrolment and the curriculum file name.
    'program_codes' => [
        'Bachelor of Science in Information Technology'  => 'BSIT',
        'Bachelor of Science in Computer Science'        => 'BSCS',
        'Bachelor of Science in Mathematics'             => 'BSMATH',
        'Bachelor of Science in Civil Engineering'       => 'BSCE',
        'Bachelor of Science in Sanitary Engineering'    => 'BSSE',
        'Bachelor of Engineering Technology in Electrical Engineering Technology' => 'BET-EET',
        'Bachelor of Engineering Technology in Mechanical Engineering Technology major in Automotive Technology' => 'BET-MET-AUTO',
        'Bachelor of Engineering Technology in Mechanical Engineering Technology major in Refrigeration and Air-Conditioning Technology' => 'BET-MET-RAC',
        'Bachelor of Elementary Education'               => 'BEEd',
        'Bachelor of Arts in Communication'              => 'BACOMM',
        'Bachelor of Public Administration'              => 'BPA',
        'Bachelor of Secondary Education major in English'          => 'BSED-ENG',
        'Bachelor of Secondary Education major in Filipino'         => 'BSED-FIL',
        'Bachelor of Secondary Education major in Mathematics'      => 'BSED-MATH',
        'Bachelor of Secondary Education major in Science'          => 'BSED-SCI',
        'Bachelor of Secondary Education major in Social Studies'   => 'BSED-SS',
        'Bachelor of Secondary Education major in Values Education' => 'BSED-VE',
    ],

    'academics' => [
        'year_levels' => ['1st Year', '2nd Year', '3rd Year', '4th Year'],
        'sections'    => ['A', 'B', 'C', 'D', 'E'],
    ],

    'documents' => [
        'diploma'                  => 'University Diploma',
        'honorable_dismissal'      => 'Honorable Dismissal',
        'certificate_of_enrolment' => 'Certificate of Enrolment',
        'transcript_of_records'    => 'Transcript of Records',
    ],

    'enrolment' => [
        'tuition_per_unit' => 175.00,
        'lab_per_subject'  => 350.00,

        'misc_fees' => [
            'Registration'         => 50.00,
            'Athletic Fee'         => 65.00,
            'Publication Fee'      => 40.00,
            'Culture and Arts Fee' => 70.00,
            'Medical Fee'          => 25.00,
            'Audio Visual Fee'     => 25.00,
            'Library Fee'          => 100.00,
            'Scuaa Fee'            => 50.00,
            'Dental Fee'           => 25.00,
            'Student Activity Fee' => 180.00,
        ],
    ],
];