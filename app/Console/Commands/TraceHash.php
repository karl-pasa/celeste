<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\CertificateHashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use ReflectionProperty;

class TraceHash extends Command
{
    protected $signature = 'celeste:trace-hash {serial? : A serial, otherwise the newest certificate}';

    protected $description = 'Show exactly where a payload changes between hashing and storage';

    public function handle(CertificateHashService $hasher): int
    {
        $certificate = $this->argument('serial')
            ? Certificate::where('serial_number', $this->argument('serial'))->first()
            : Certificate::latest('id')->first();

        if (! $certificate) {
            $this->error('  No certificate found.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("  {$certificate->serial_number}");
        $this->line('  ' . str_repeat('─', 64));

        $payload = $certificate->payload;
        $a = $hasher->hash($payload);
        $b = $hasher->hash($payload);

        $this->newLine();
        $this->line('  <options=bold>1. Determinism</>');
        $this->line('     hash #1 : ' . substr($a, 0, 32));
        $this->line('     hash #2 : ' . substr($b, 0, 32));

        if (! hash_equals($a, $b)) {
            $this->newLine();
            $this->error('     The same input hashed twice gives different answers.');
            $this->line('     Something random or time-based is going into hash().');
            $this->line('     No amount of regenerating will fix this — the hash service');
            $this->line('     itself needs correcting.');

            return self::FAILURE;
        }

        $this->info('     Stable.');

        $this->newLine();
        $this->line('  <options=bold>2. Pepper as the service sees it</>');

        $pepper = null;

        foreach (['pepper', 'key', 'secret'] as $name) {
            if (property_exists($hasher, $name)) {
                $prop = new ReflectionProperty($hasher, $name);
                $prop->setAccessible(true);
                $pepper = (string) $prop->getValue($hasher);
                $this->line("     \${$name} : " . ($pepper === '' ? 'EMPTY' : strlen($pepper) . ' chars'));
                break;
            }
        }

        if ($pepper === null) {
            $this->line('     No pepper property found — it is read inside hash() instead.');
            $this->line('     If that call is env() rather than config(), it returns null once');
            $this->line('     config:cache has run, which changes the key between requests.');
        }

        $this->newLine();
        $this->line('  <options=bold>3. Canonical form, written vs read back</>');

        $rawJson = DB::table('certificates')->where('id', $certificate->id)->value('payload');
        $fromDb  = is_string($rawJson) ? json_decode($rawJson, true) : $rawJson;

        $canonical = $this->canonicalOf($hasher, $payload);
        $canonicalDb = $this->canonicalOf($hasher, (array) $fromDb);

        if ($canonical === null) {
            $this->line('     canonicalise() is not reachable by reflection; comparing raw JSON.');
            $canonical   = json_encode($payload);
            $canonicalDb = json_encode($fromDb);
        }

        if ($canonical === $canonicalDb) {
            $this->info('     Identical. The payload survives the round trip unchanged.');
            $this->newLine();
            $this->error('  So the stored hash was computed over something else entirely.');
            $this->line('  Most likely the pepper differed when this certificate was issued.');
            $this->line('  Delete the test certificates and generate one now, with the current');
            $this->line('  configuration, then run this again.');

            return self::FAILURE;
        }

        $len = min(strlen($canonical), strlen($canonicalDb));
        $i = 0;

        while ($i < $len && $canonical[$i] === $canonicalDb[$i]) {
            $i++;
        }

        $from = max(0, $i - 60);

        $this->error("     They differ at character {$i}.");
        $this->newLine();
        $this->line('     <options=bold>as hashed</>');
        $this->line('     …' . substr($canonical, $from, 140));
        $this->newLine();
        $this->line('     <options=bold>as stored</>');
        $this->line('     …' . substr($canonicalDb, $from, 140));
        $this->newLine();
        $this->line('     lengths: ' . strlen($canonical) . ' vs ' . strlen($canonicalDb));

        return self::FAILURE;
    }

    private function canonicalOf(CertificateHashService $hasher, array $payload): ?string
    {
        foreach (['canonicalise', 'canonicalize', 'canonical', 'normalise', 'normalize'] as $name) {
            if (! method_exists($hasher, $name)) {
                continue;
            }

            $method = new ReflectionMethod($hasher, $name);
            $method->setAccessible(true);

            $result = $method->invoke($hasher, $payload);

            return is_string($result) ? $result : json_encode($result);
        }

        return null;
    }
}
