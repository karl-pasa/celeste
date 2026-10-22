<?php

namespace Database\Seeders;

use App\Models\CurriculumSubject;
use Illuminate\Database\Seeder;

/** Loads every database/data/curriculum/*.csv (one file per program). Safe to re-run. */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(database_path('data/curriculum/*.csv')) as $file) {
            $h = fopen($file, 'r');
            $cols = fgetcsv($h);
            while (($r = fgetcsv($h)) !== false) {
                $row = array_combine($cols, $r);
                CurriculumSubject::updateOrCreate(
                    ['program_code' => $row['program'], 'year_level' => (int) $row['year'],
                     'term' => $row['term'], 'code' => $row['code']],
                    ['title' => $row['title'], 'unit' => $row['unit'], 'lec' => $row['lec'],
                     'lab' => $row['lab'], 'ch' => $row['ch'], 'prereq' => $row['prereq'] ?: null,
                     'sort' => (int) $row['sort']]
                );
            }
            fclose($h);
        }
    }
}
