<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Services\CertificateGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BatchDownloadController extends Controller
{
    /** One click: every document of the batch, zipped, named "<serial>_<student number>.pdf". */
    public function __invoke(CertificateBatch $batch, CertificateGenerator $generator): BinaryFileResponse
    {
        $certs = Certificate::with('studentRecord:id,student_number,last_name')
            ->where('batch_id', $batch->id)->orderBy('id')->get();

        abort_if($certs->isEmpty(), 404, 'This batch has no documents.');

        $zipPath = tempnam(sys_get_temp_dir(), 'batch');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        foreach ($certs as $c) {
            // Use the stored PDF; render it again only if the file is missing.
            $binary = ($c->file_path && Storage::disk('local')->exists($c->file_path))
                ? Storage::disk('local')->get($c->file_path)
                : $generator->renderBinary($c);

            $name = $c->serial_number . '_' . ($c->studentRecord->student_number ?? $c->id) . '.pdf';
            $zip->addFromString($name, $binary);
        }
        $zip->close();

        AuditLog::record('batch.downloaded', $batch, ['documents' => $certs->count()]);

        return response()->download($zipPath, Str::slug($batch->reference) . '.zip')->deleteFileAfterSend(true);
    }
}
