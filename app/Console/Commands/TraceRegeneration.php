<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\CertificateGenerator;
use App\Services\CertificateHashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Proves whether regenerating a document mutates the stored record.
 *
 * The reported symptom is that a certificate verifies correctly when first
 * issued, and fails afterwards even though its serial number and fingerprint
 * are unchanged. That combination means the fingerprint column was not
 * rewritten but the payload it was computed over was. This command captures
 * the raw payload column before and after a regeneration and reports the
 * difference directly, rather than inferring it.
 */
class TraceRegeneration extends Command
{
    protected $signature = 'celeste:trace-regen {serial? : A serial, otherwise the newest certificate}';

    protected $description = 'Detect whether regenerating a document alters the stored payload';

    public function handle(CertificateGenerator $generator, CertificateHashService $hasher): int
    {
        $certificate = $this->argument('serial')
            ? Certificate::where('serial_number', $this->argument('serial'))->first()
            : Certificate::latest('id')->first();

        if (! $certificate) {
            $this->error('  No certificate found.');

            return self::FAILURE;
        }

        $id = $certificate->id;
        $this->newLine();
        $this->line("  {$certificate->serial_number}");
        $this->line('  ' . str_repeat('─', 66));

        // ---- Capture the raw column values BEFORE regeneration ----------
        $before = DB::table('certificates')->where('id', $id)->first();

        $this->newLine();
        $this->line('  <options=bold>Before regeneration</>');
        $this->line('     content_hash : ' . substr($before->content_hash, 0, 32) . '…');
        $this->line('     file_hash    : ' . ($before->file_hash ? substr($before->file_hash, 0, 32) . '…' : 'null'));
        $this->line('     payload bytes: ' . strlen((string) $before->payload));

        $storedOk = hash_equals(
            (string) $before->content_hash,
            $hasher->hash((array) json_decode((string) $before->payload, true))
        );
        $this->line('     verifies now : ' . ($storedOk ? 'YES' : 'NO'));

        if (! $storedOk) {
            $this->newLine();
            $this->error('  This certificate already fails before regeneration is attempted.');
            $this->line('  The fault is in issuance rather than in regeneration; run');
            $this->line('  celeste:check-integrity to identify the cause.');

            return self::FAILURE;
        }

        // ---- Regenerate exactly as the interface would ------------------
        $this->newLine();
        $this->line('  <options=bold>Regenerating…</>');

        try {
            $generator->renderPdf($certificate->fresh());
        } catch (\Throwable $e) {
            $this->error('     Render failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        // ---- Capture the raw column values AFTER -----------------------
        $after = DB::table('certificates')->where('id', $id)->first();

        $this->newLine();
        $this->line('  <options=bold>After regeneration</>');
        $this->line('     content_hash : ' . substr($after->content_hash, 0, 32) . '…');
        $this->line('     file_hash    : ' . ($after->file_hash ? substr($after->file_hash, 0, 32) . '…' : 'null'));
        $this->line('     payload bytes: ' . strlen((string) $after->payload));

        $afterOk = hash_equals(
            (string) $after->content_hash,
            $hasher->hash((array) json_decode((string) $after->payload, true))
        );
        $this->line('     verifies now : ' . ($afterOk ? 'YES' : 'NO'));

        // ---- Report what changed ---------------------------------------
        $this->newLine();
        $this->line('  ' . str_repeat('─', 66));

        $hashChanged    = $before->content_hash !== $after->content_hash;
        $payloadChanged = (string) $before->payload !== (string) $after->payload;
        $fileChanged    = $before->file_hash !== $after->file_hash;

        $this->line('  content_hash changed : ' . ($hashChanged ? 'YES' : 'no'));
        $this->line('  payload changed      : ' . ($payloadChanged ? 'YES' : 'no'));
        $this->line('  file_hash changed    : ' . ($fileChanged ? 'YES' : 'no'));

        if ($payloadChanged && ! $hashChanged) {
            $this->newLine();
            $this->error('  CONFIRMED: regeneration rewrote the payload but left the fingerprint.');
            $this->newLine();
            $this->line('  The stored payload is no longer the one the fingerprint was computed');
            $this->line('  over, so verification must fail. This is the reported symptom.');
            $this->newLine();

            // Show the first point of divergence so the cause is visible.
            $b = (string) $before->payload;
            $a = (string) $after->payload;
            $len = min(strlen($b), strlen($a));
            $i = 0;
            while ($i < $len && $b[$i] === $a[$i]) {
                $i++;
            }
            $from = max(0, $i - 60);

            $this->line("  First difference at character {$i}:");
            $this->newLine();
            $this->line('  <options=bold>before</>  …' . substr($b, $from, 130));
            $this->newLine();
            $this->line('  <options=bold>after </>  …' . substr($a, $from, 130));

            return self::FAILURE;
        }

        if ($fileChanged) {
            $this->newLine();
            $this->warn('  file_hash changed on re-render.');
            $this->line('  Rendered PDF bytes are not reproducible, most often because the');
            $this->line('  generator embeds a creation timestamp in the document metadata.');
            $this->line('  This matters only if verification compares the file rather than the');
            $this->line('  payload; check whether verifyFile() is called during verification.');
        }

        if (! $payloadChanged && ! $hashChanged && $afterOk) {
            $this->newLine();
            $this->info('  Regeneration left the record intact and it still verifies.');
            $this->line('  The fault lies elsewhere. Scan the QR and compare the token in the');
            $this->line('  resolved URL against certificates.verification_token for this row.');
        }

        return self::SUCCESS;
    }
}
