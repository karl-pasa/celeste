<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Models\User;
use App\Models\VerificationLog;
use App\Services\CertificateGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = User::updateOrCreate(
            ['username' => 'registrar'],
            [
                'name'      => 'Ma. Teresa L. Ocampo',
                'email'     => 'registrar@parsu.edu.ph',
                'password'  => Hash::make('registrar123'),   // bcrypt
                'role'      => User::ROLE_REGISTRAR,
                'is_active' => true,
            ]
        );

        $records = [
            [
                'student_number' => '2021-00184',
                'first_name'     => 'Andrea',
                'middle_name'    => 'Bautista',
                'last_name'      => 'Ramirez',
                'college'        => 'College of Engineering and Computational Sciences',
                'program'        => 'Bachelor of Science in Information Technology',
                'major'          => 'Web and Mobile Development',
                'status'         => 'graduated',
                'academic_year'  => '2025-2026',
                'semester'       => 'Second Semester',
                'year_level'     => '4th Year',
                'date_admitted'  => '2021-08-16',
                'date_graduated' => '2026-06-12',
                'latin_honor'    => 'Cum Laude',
                'general_weighted_average' => 1.612,
                'role_account'   => ['username' => 'aramirez', 'role' => User::ROLE_STUDENT],
            ],
            [
                'student_number' => '2023-00457',
                'first_name'     => 'Miguel',
                'middle_name'    => 'Santos',
                'last_name'      => 'Delos Reyes',
                'college'        => 'College of Education',
                'program'        => 'Bachelor of Elementary Education',
                'status'         => 'enrolled',
                'academic_year'  => '2026-2027',
                'semester'       => 'First Semester',
                'year_level'     => '3rd Year',
                'date_admitted'  => '2023-08-14',
                'general_weighted_average' => 1.884,
                'role_account'   => ['username' => 'mdelosreyes', 'role' => User::ROLE_STUDENT],
            ],
            [
                'student_number' => '2022-00913',
                'first_name'     => 'Kirsten',
                'middle_name'    => 'Alvarez',
                'last_name'      => 'Pantaleon',
                'college'        => 'College of Business and Management',
                'program'        => 'Bachelor of Science in Business Administration',
                'major'          => 'Financial Management',
                'status'         => 'transferred',
                'academic_year'  => '2025-2026',
                'semester'       => 'First Semester',
                'year_level'     => '3rd Year',
                'date_admitted'  => '2022-08-15',
                'general_weighted_average' => 2.104,
            ],
            [
                'student_number' => '2021-00220',
                'first_name'     => 'Joshua',
                'middle_name'    => 'Nieva',
                'last_name'      => 'Villanueva',
                'college'        => 'College of Engineering and Computational Sciences',
                'program'        => 'Bachelor of Science in Computer Science',
                'status'         => 'graduated',
                'academic_year'  => '2025-2026',
                'semester'       => 'Second Semester',
                'year_level'     => '4th Year',
                'date_admitted'  => '2021-08-16',
                'date_graduated' => '2026-06-12',
                'general_weighted_average' => 1.945,
            ],
            [
                'student_number' => '2024-01102',
                'first_name'     => 'Bea',
                'middle_name'    => 'Cordial',
                'last_name'      => 'Sarmiento',
                'college'        => 'College of Agribusiness and Community Development-Salogon Campus',
                'program'        => 'Bachelor of Science in Agriculture',
                'status'         => 'enrolled',
                'academic_year'  => '2026-2027',
                'semester'       => 'First Semester',
                'year_level'     => '2nd Year',
                'date_admitted'  => '2024-08-12',
                'general_weighted_average' => 1.756,
            ],
        ];

        foreach ($records as $data) {
            $account = $data['role_account'] ?? null;
            unset($data['role_account']);

            $data['grades'] = $this->grades();

            $record = StudentRecord::updateOrCreate(
                ['student_number' => $data['student_number']],
                $data
            );

            if ($account) {
                User::updateOrCreate(
                    ['username' => $account['username']],
                    [
                        'name'           => $record->full_name,
                        'email'          => $account['username'] . '@parsu.edu.ph',
                        'password'       => Hash::make('student123'), // bcrypt
                        'role'           => $account['role'],
                        'student_number' => $record->student_number,
                        'college'        => $record->college,
                        'program'        => $record->program,
                        'is_active'      => true,
                    ]
                );
            }
        }

        // Issue a spread of documents so the dashboard has something to show.
        $generator = app(CertificateGenerator::class);

        $plan = [
            ['2021-00184', Certificate::TYPE_DIPLOMA],
            ['2021-00184', Certificate::TYPE_TOR],
            ['2021-00220', Certificate::TYPE_DIPLOMA],
            ['2023-00457', Certificate::TYPE_ENROLMENT],
            ['2024-01102', Certificate::TYPE_ENROLMENT],
            ['2022-00913', Certificate::TYPE_DISMISSAL],
            ['2022-00913', Certificate::TYPE_TOR],
        ];

        foreach ($plan as [$number, $type]) {
            $student = StudentRecord::where('student_number', $number)->first();

            if ($student && ! Certificate::where('student_record_id', $student->id)->where('document_type', $type)->exists()) {
                $generator->issue($student, $type, $registrar, [
                    'issued_on' => now()->subDays(rand(3, 60))->toDateString(),
                ]);
            }
        }

        $this->seedVerificationHistory();
    }

    /**
     * Plausible verification traffic across the last 45 days so the analytics
     * module and the decision-support flags have real shape to read.
     */
    protected function seedVerificationHistory(): void
    {
        if (VerificationLog::count() > 0) {
            return;
        }

        $certificates = Certificate::all();

        if ($certificates->isEmpty()) {
            return;
        }

        foreach (range(1, 180) as $i) {
            $certificate = $certificates->random();
            $daysAgo = rand(0, 45);

            // Most checks pass; a minority fail, which is what makes the flags interesting.
            $result = match (true) {
                rand(1, 100) <= 84 => 'authentic',
                rand(1, 100) <= 55 => 'not_found',
                rand(1, 100) <= 60 => 'tampered',
                default            => 'revoked',
            };

            $log = VerificationLog::create([
                'certificate_id'      => $result === 'not_found' ? null : $certificate->id,
                'submitted_reference' => $result === 'not_found'
                    ? 'PSU-DIP-2026-' . str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT)
                    : $certificate->verification_token,
                'method'              => ['qr_scan', 'qr_scan', 'qr_scan', 'serial', 'hash'][rand(0, 4)],
                'result'              => $result,
                'document_type'       => $result === 'not_found' ? null : $certificate->document_type,
                'ip_address'          => '112.' . rand(200, 210) . '.' . rand(1, 254) . '.' . rand(1, 254),
                'user_agent'          => 'Mozilla/5.0 (seeded verification traffic)',
            ]);

            $log->forceFill([
                'created_at' => now()->subDays($daysAgo)->subMinutes(rand(0, 1400)),
            ])->save();

            if ($result === 'authentic') {
                $certificate->increment('verification_count');
                $certificate->forceFill(['last_verified_at' => $log->created_at])->save();
            }
        }
    }

    /**
     * A representative set of subject rows for the Transcript of Records.
     */
    protected function grades(): array
    {
        return [
            ['code' => 'GEC 1',  'title' => 'Understanding the Self',                'units' => 3, 'grade' => '1.50', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'First Semester'],
            ['code' => 'GEC 2',  'title' => 'Readings in Philippine History',        'units' => 3, 'grade' => '1.75', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'First Semester'],
            ['code' => 'MATH 1', 'title' => 'Mathematics in the Modern World',       'units' => 3, 'grade' => '1.25', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'First Semester'],
            ['code' => 'PE 1',   'title' => 'Movement Competency Training',          'units' => 2, 'grade' => '1.00', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'First Semester'],
            ['code' => 'IT 101', 'title' => 'Introduction to Computing',             'units' => 3, 'grade' => '1.50', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'Second Semester'],
            ['code' => 'IT 102', 'title' => 'Computer Programming 1',                'units' => 3, 'grade' => '1.75', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'Second Semester'],
            ['code' => 'GEC 3',  'title' => 'The Contemporary World',                'units' => 3, 'grade' => '2.00', 'remarks' => 'Passed', 'academic_year' => '2021-2022', 'semester' => 'Second Semester'],
            ['code' => 'IT 201', 'title' => 'Data Structures and Algorithms',        'units' => 3, 'grade' => '1.75', 'remarks' => 'Passed', 'academic_year' => '2022-2023', 'semester' => 'First Semester'],
            ['code' => 'IT 202', 'title' => 'Object-Oriented Programming',           'units' => 3, 'grade' => '1.50', 'remarks' => 'Passed', 'academic_year' => '2022-2023', 'semester' => 'First Semester'],
            ['code' => 'IT 203', 'title' => 'Database Management Systems',           'units' => 3, 'grade' => '1.25', 'remarks' => 'Passed', 'academic_year' => '2022-2023', 'semester' => 'Second Semester'],
            ['code' => 'IT 204', 'title' => 'Web Systems and Technologies',          'units' => 3, 'grade' => '1.50', 'remarks' => 'Passed', 'academic_year' => '2022-2023', 'semester' => 'Second Semester'],
            ['code' => 'IT 301', 'title' => 'Information Assurance and Security',    'units' => 3, 'grade' => '1.75', 'remarks' => 'Passed', 'academic_year' => '2023-2024', 'semester' => 'First Semester'],
            ['code' => 'IT 302', 'title' => 'Systems Integration and Architecture',  'units' => 3, 'grade' => '2.00', 'remarks' => 'Passed', 'academic_year' => '2023-2024', 'semester' => 'First Semester'],
            ['code' => 'IT 303', 'title' => 'Capstone Project 1',                    'units' => 3, 'grade' => '1.50', 'remarks' => 'Passed', 'academic_year' => '2023-2024', 'semester' => 'Second Semester'],
            ['code' => 'IT 401', 'title' => 'Capstone Project 2',                    'units' => 3, 'grade' => '1.25', 'remarks' => 'Passed', 'academic_year' => '2024-2025', 'semester' => 'First Semester'],
            ['code' => 'IT 402', 'title' => 'Practicum',                             'units' => 6, 'grade' => '1.00', 'remarks' => 'Passed', 'academic_year' => '2024-2025', 'semester' => 'Second Semester'],
        ];
    }
}