<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Loads the Philippine Standard Geographic Code into the local database.
 *
 * Source: https://github.com/Tenasia/ph-psgc -- the PSA's quarterly PSGC
 * release published as JSON. The release tag is pinned rather than tracking
 * the latest, so re-running this command a year from now reproduces the same
 * data unless the tag is deliberately changed. Place names do change: cities
 * are created, municipalities are renamed, barangays are split. A student
 * record holds the name as it was entered, which is what the registrar's
 * paper form holds too.
 *
 *     php artisan celeste:import-psgc
 *     php artisan celeste:import-psgc --tag=2026-Q3
 */
class ImportPsgc extends Command
{
    protected $signature = 'celeste:import-psgc
                            {--tag=2026-Q2 : PSGC release tag to import}
                            {--fresh : Empty the tables first}';

    protected $description = 'Import Philippine provinces, cities and barangays';

    protected string $base;

    public function handle(): int
    {
        $tag = (string) $this->option('tag');
        $this->base = "https://cdn.jsdelivr.net/gh/Tenasia/ph-psgc@{$tag}/psgc";

        $this->info("Importing PSGC {$tag}");

        $index = $this->get('/index.json');
        if ($index === null) {
            $this->error('Could not read index.json. Check the tag and your connection.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('ph_barangays')->delete();
            DB::table('ph_cities')->delete();
            DB::table('ph_provinces')->delete();
            $this->line('Tables emptied.');
        }

        // ── provinces ────────────────────────────────────────────────
        // Independent cities (Manila's component cities, Cebu City and so
        // on) sit directly under a region with no province above them.
        // They are stored as provinces so that the first dropdown offers
        // every valid starting point, not only the 82 true provinces.
        $areas = [];

        foreach ($this->regions($index) as $region) {
            $regionCode = (string) ($region['code'] ?? '');
            $regionName = (string) ($region['name'] ?? '');

            foreach (['provinces', 'independent'] as $key) {
                foreach ($region[$key] ?? [] as $area) {
                    $code = (string) ($area['code'] ?? '');
                    if ($code === '') {
                        continue;
                    }

                    $areas[$code] = [
                        'code'        => $code,
                        'name'        => (string) ($area['name'] ?? ''),
                        'region_code' => $regionCode,
                        'region_name' => $regionName,
                    ];
                }
            }
        }

        if ($areas === []) {
            $this->error('index.json parsed but no provinces were found. '
                . 'The file layout may have changed.');
            return self::FAILURE;
        }

        DB::table('ph_provinces')->upsert(
            array_values($areas), ['code'], ['name', 'region_code', 'region_name']
        );
        $this->line('Provinces and independent cities: ' . count($areas));

        // ── cities and barangays, one area file at a time ───────────
        $bar = $this->output->createProgressBar(count($areas));
        $bar->start();

        $cityCount = 0;
        $brgyCount = 0;

        foreach (array_keys($areas) as $provinceCode) {
            $area = $this->get("/areas/{$provinceCode}.json");
            $bar->advance();

            if ($area === null) {
                continue;
            }

            $cities = [];
            $brgys  = [];

            foreach ($this->cities($area) as $city) {
                $cityCode = (string) ($city['code'] ?? '');
                if ($cityCode === '') {
                    continue;
                }

                $cities[] = [
                    'code'          => $cityCode,
                    'province_code' => $provinceCode,
                    'name'          => (string) ($city['name'] ?? ''),
                    'type'          => (string) ($city['type'] ?? ''),
                ];

                foreach ($city['barangays'] ?? [] as $barangay) {
                    // Published as [code, name] pairs, but tolerate an
                    // object shape in case a later release changes it.
                    [$bCode, $bName] = is_array($barangay) && isset($barangay[0])
                        ? [(string) $barangay[0], (string) ($barangay[1] ?? '')]
                        : [(string) ($barangay['code'] ?? ''),
                           (string) ($barangay['name'] ?? '')];

                    if ($bCode === '') {
                        continue;
                    }

                    $brgys[] = [
                        'code'      => $bCode,
                        'city_code' => $cityCode,
                        'name'      => $bName,
                    ];
                }
            }

            if ($cities !== []) {
                DB::table('ph_cities')->upsert(
                    $cities, ['code'], ['province_code', 'name', 'type']
                );
                $cityCount += count($cities);
            }

            // Chunked: a single insert of several thousand rows exceeds
            // Postgres's bound-parameter limit.
            foreach (array_chunk($brgys, 500) as $chunk) {
                DB::table('ph_barangays')->upsert(
                    $chunk, ['code'], ['city_code', 'name']
                );
            }
            $brgyCount += count($brgys);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Cities and municipalities: {$cityCount}");
        $this->info("Barangays: {$brgyCount}");

        if ($brgyCount === 0) {
            $this->warn('No barangays were imported. Run with -v and check '
                . 'the shape of one area file before relying on this data.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * index.json may hold the regions at the top level or under a key.
     */
    protected function regions(array $index): array
    {
        if (isset($index['regions']) && is_array($index['regions'])) {
            return $index['regions'];
        }

        return array_values(array_filter(
            $index,
            fn ($v) => is_array($v) && isset($v['code']),
        ));
    }

    protected function cities(array $area): array
    {
        foreach (['cities', 'municipalities', 'children', 'data'] as $key) {
            if (isset($area[$key]) && is_array($area[$key])) {
                return $area[$key];
            }
        }

        return array_values(array_filter(
            $area,
            fn ($v) => is_array($v) && isset($v['code']),
        ));
    }

    protected function get(string $path): ?array
    {
        try {
            $response = Http::timeout(30)->retry(2, 500)->get($this->base . $path);
        } catch (\Throwable $e) {
            $this->warn("Request failed for {$path}: " . $e->getMessage());
            return null;
        }

        if (! $response->successful()) {
            $this->warn("HTTP {$response->status()} for {$path}");
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }
}
