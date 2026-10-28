<?php

namespace Database\Seeders;

use App\Models\StudentRecord;
use Illuminate\Database\Seeder;

/**
 * Adds 10 sample students to every program that has a program of study
 * loaded (a CSV in database/data/curriculum named after the program's code).
 *
 * College, program, year levels and sections are read from config/celeste.php,
 * so every value matches the dropdowns exactly.
 *
 * Per program: 6 enrolled students spread over the year levels (enrolment
 * certificate) and 4 graduated students with full graduation data (diploma,
 * transcript, honorable dismissal). Safe to run again: students are matched
 * on student_number, so nothing is duplicated.
 *
 *   php artisan db:seed --class=SampleStudentSeeder
 */
class SampleStudentSeeder extends Seeder
{
    private const MALE = ['Juan Carlo','Mark Anthony','John Paul','Jericho','Kevin','Christian','Angelo','Rey','Joshua','Nathaniel','Jerome','Ramon','Paolo','Dennis','Carlo','Emmanuel','Gabriel','Francis','Joseph','Miguel'];
    private const FEMALE = ['Maria Angelica','Jennifer','Kristine','Angela','Rhea','Mary Grace','Jasmine','Katrina','Camille','Princess','Lovely','Shiela','Nicole','Janine','Hazel','Michelle','Bea','Aileen','Joy','Marites'];
    private const SURNAMES = ['Dela Cruz','Reyes','Santos','Garcia','Bautista','Ramos','Mendoza','Aquino','Villanueva','Navarro','Salazar','Orbista','Belmonte','Nicolas','Alcantara','Tabuzo','Rosales','Perez','Castillo','Moreno','Buenaventura','Ocampo','Lopez','Pasa','Delos Santos','Abella','Bonifacio','Cabrera','Esplana','Gonzales','Hernandez','Imperial','Jacob','Laurente','Macalalag','Nuñez','Obias','Padilla','Quiñones','Rivera','Sabio','Tolentino','Ubaldo','Valencia','Yap','Zamora','Almario','Bagacina','Candelaria','Dizon'];
    private const MIDDLE = ['Cruz','Bautista','Santos','Reyes','Garcia','Lopez','Ramos','Torres','Flores','Mercado','Castro','Aguilar','Manalo','Soriano','Dimaano'];

    // Goa and nearby towns of Camarines Sur: [city, [barangays], zip]
    private const PLACES = [
        ['Goa',      ['Poblacion', 'San Isidro', 'Tagongtong', 'Bagumbayan'], '4422'],
        ['Tigaon',   ['Poblacion', 'San Isidro', 'Tinawagan'],               '4421'],
        ['Lagonoy',  ['Poblacion', 'Sabang', 'Bagacay'],                     '4429'],
        ['Sagñay',   ['Poblacion', 'Baya', 'Tinambac'],                      '4423'],
        ['San Jose', ['Poblacion', 'Mabini', 'Salvacion'],                   '4423'],
    ];

    public function run(): void
    {
        $years    = array_values(config('celeste.academics.year_levels', ['1st Year', '2nd Year', '3rd Year', '4th Year']));
        $sections = array_values(config('celeste.academics.sections', ['A', 'B']));
        $codes    = config('celeste.program_codes', []);
        $seq      = 0;
        $made     = 0;

        foreach (config('celeste.programs', []) as $college => $programs) {
            foreach ($programs as $program) {
                $code = $codes[$program] ?? null;

                if (! $code || ! is_file(database_path("data/curriculum/{$code}.csv"))) {
                    continue;   // no program of study loaded for this one
                }

                for ($i = 0; $i < 10; $i++) {
                    $seq++;
                    $this->student($seq, $i, $college, $program, $code, $years, $sections);
                    $made++;
                }
            }
        }

        $this->command?->info("{$made} sample students added or refreshed.");
    }

    private function student(int $n, int $i, string $college, string $program, string $code,
                             array $years, array $sections): void
    {
        $female   = $n % 2 === 0;
        $first    = ($female ? self::FEMALE : self::MALE)[intdiv($n, 2) % 20];
        $last     = self::SURNAMES[($n * 7) % count(self::SURNAMES)];
        $middle   = self::MIDDLE[($n * 3) % count(self::MIDDLE)];
        [$city, $barangays, $zip] = self::PLACES[$n % count(self::PLACES)];
        $barangay = $barangays[$n % count($barangays)];

        // 0-5 enrolled across year levels, 6-9 graduated
        $graduated = $i >= 6;
        $yearIdx   = $graduated ? 3 : [0, 0, 1, 1, 2, 3][$i];
        $yearLevel = $years[min($yearIdx, count($years) - 1)] ?? '1st Year';
        $section   = $sections[$n % count($sections)] ?? null;

        // Entry year: enrolled students by year level, graduates in 2022
        $entry = $graduated ? 2022 : 2026 - $yearIdx;
        $studentNumber = sprintf('%d-%05d', $entry, 100 + $n);

        $birth = sprintf('%d-%02d-%02d', $entry - 18 - ($n % 3), 1 + $n % 12, 1 + ($n * 5) % 27);
        $admitted = $entry . '-08-' . sprintf('%02d', 8 + $n % 10);

        $gwa = round(1.15 + (($n * 13) % 60) / 100, 3);       // 1.15 - 1.74
        $honor = match (true) {
            ! $graduated => null,
            $gwa <= 1.25 => 'Summa Cum Laude',
            $gwa <= 1.45 => 'Magna Cum Laude',
            $gwa <= 1.75 => 'Cum Laude',
            default      => null,
        };

        $slug  = strtolower(preg_replace('/[^a-z]/i', '', $first . $last));
        $email = substr($slug, 0, 14) . sprintf('%03d', $n) . '.pbox@parsu.edu.ph';
        $school = ['Goa National High School', 'Tigaon National High School', 'Lagonoy National High School', 'Sagñay National High School'][$n % 4];

        $data = [
            'last_name'      => $last,
            'first_name'     => $first,
            'middle_name'    => $middle,
            'suffix'         => null,
            'gender'         => $female ? 'Female' : 'Male',
            'civil_status'   => 'Single',
            'birth_date'     => $birth,
            'birthplace'     => $city . ', Camarines Sur',
            'nationality'    => 'Filipino',
            'religion'       => 'Roman Catholic',
            'email'          => $email,
            'contact_number' => sprintf('09%02d %03d %04d', 17 + $n % 6, 100 + $n % 800, 1000 + ($n * 37) % 9000),

            'perm_province' => 'Camarines Sur', 'perm_city' => $city,
            'perm_barangay' => $barangay,       'perm_zip'  => $zip,
            'temp_province' => 'Camarines Sur', 'temp_city' => $city,
            'temp_barangay' => $barangay,       'temp_zip'  => $zip,
            'address'       => "{$barangay}, {$city}, Camarines Sur {$zip}",

            'college'       => $college,
            'program'       => $program,
            'major'         => null,
            'year_level'    => $yearLevel,
            'section'       => $section,
            'academic_year' => $graduated ? '2025-2026' : '2026-2027',
            'semester'      => $graduated ? '2nd Semester' : '1st Semester',
            'status'        => $graduated ? 'graduated' : 'enrolled',

            'admission_type'         => 'New',
            'date_admitted'          => $admitted,
            'adm_new_school'         => $school,
            'adm_new_address'        => $city . ', Camarines Sur',
            'adm_new_course'         => 'Senior High School',
            'adm_new_year_graduated' => (string) ($entry - 1),

            'nstp_serial_no'        => 'NSTP-' . $entry . '-' . sprintf('%05d', $n),
            'program_accreditation' => 'PACUCOA Level I Accredited',
            'granted_transfer_credentials' => null,
            'remarks'               => null,
        ];

        if ($graduated) {
            $data += [
                'date_graduated'           => '2026-06-12',
                'date_conferred'           => '2026-07-18',
                'board_resolution_no'      => 'BOR Res. No. ' . (30 + $n % 20) . ', s. 2026',
                'board_resolution_date'    => '2026-06-26',
                'latin_honor'              => $honor,
                'awards'                   => $honor ? "Academic Excellence Awardee" : null,
                'general_weighted_average' => $gwa,
            ];
        } else {
            $data += [
                'date_graduated' => null, 'date_conferred' => null,
                'board_resolution_no' => null, 'board_resolution_date' => null,
                'latin_honor' => null, 'awards' => null,
                'general_weighted_average' => null,
            ];
        }

        StudentRecord::updateOrCreate(['student_number' => $studentNumber], $data);
    }
}