<?php

namespace Database\Seeders;

use App\Models\StudentRecord;
use Illuminate\Database\Seeder;

/**
 * Sample students for every program that has a program of study loaded
 * (a CSV in database/data/curriculum named after the program's code).
 *
 * Per program: 4 year levels x 5 sections x 10 students = 200 students,
 * all enrolled, with every field the documents print. College, program,
 * year levels and sections come from config/celeste.php, so each value
 * matches the dropdowns exactly.
 *
 * Safe to run again: rows are matched on student_number.
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
        $years = array_slice(array_values(
            config('celeste.academics.year_levels', ['1st Year', '2nd Year', '3rd Year', '4th Year'])), 0, 4);

        $sections = array_slice(array_values(
            config('celeste.academics.sections', [])), 0, 5);
        if (count($sections) < 5) {
            $sections = ['A', 'B', 'C', 'D', 'E'];
        }

        $codes = config('celeste.program_codes', []);
        $now   = now()->toDateTimeString();
        $rows  = [];
        $n     = 0;

        foreach (config('celeste.programs', []) as $college => $programs) {
            foreach ($programs as $program) {
                $code = $codes[$program] ?? null;

                if (! $code || ! is_file(database_path("data/curriculum/{$code}.csv"))) {
                    continue;   // no program of study loaded for this one
                }

                foreach ($years as $yearIdx => $yearLevel) {
                    foreach ($sections as $section) {
                        for ($k = 0; $k < 10; $k++) {
                            $n++;
                            $rows[] = $this->student($n, $college, $program, $yearIdx, $yearLevel, $section)
                                + ['created_at' => $now, 'updated_at' => $now];
                        }
                    }
                }
            }
        }

        // One upsert per 200 rows instead of one query per student: the
        // database is remote, and thousands of single inserts would crawl.
        foreach (array_chunk($rows, 200) as $chunk) {
            StudentRecord::upsert(
                $chunk,
                ['student_number'],
                array_values(array_diff(array_keys($chunk[0]), ['student_number', 'created_at'])),
            );
        }

        $this->command?->info(count($rows) . ' sample students added or refreshed.');
    }

    private function student(int $n, string $college, string $program,
                             int $yearIdx, string $yearLevel, string $section): array
    {
        $female   = $n % 2 === 0;
        $first    = ($female ? self::FEMALE : self::MALE)[intdiv($n, 2) % 20];
        $last     = self::SURNAMES[($n * 7 + intdiv($n, 20)) % count(self::SURNAMES)];
        $middle   = self::MIDDLE[($n * 3 + intdiv($n, 50)) % count(self::MIDDLE)];
        [$city, $barangays, $zip] = self::PLACES[$n % count(self::PLACES)];
        $barangay = $barangays[$n % count($barangays)];

        // Entry year follows the year level: 1st year entered in 2026, 4th in 2023.
        $entry = 2026 - $yearIdx;
        $studentNumber = sprintf('%d-%05d', $entry, $n);

        $birth = sprintf('%d-%02d-%02d', $entry - 18 - ($n % 3), 1 + $n % 12, 1 + ($n * 5) % 27);
        $admitted = $entry . '-08-' . sprintf('%02d', 8 + $n % 10);

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
            'academic_year' => '2026-2027',
            'semester'      => '1st Semester',
            'status'        => 'enrolled',

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

        // Graduation fields stay empty for students still enrolled. They are
        // listed so every row has the same columns, which upsert requires.
        $data += [
            'date_graduated' => null, 'date_conferred' => null,
            'board_resolution_no' => null, 'board_resolution_date' => null,
            'latin_honor' => null, 'awards' => null,
            'general_weighted_average' => null,
        ];

        return ['student_number' => $studentNumber] + $data;
    }
}