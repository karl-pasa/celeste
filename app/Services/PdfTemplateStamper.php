<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Carbon;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Stamps certificate data onto the Registrar's own PDF forms.
 *
 * The alternative — rebuilding an official document in HTML — never quite
 * matches, and registrar forms are often pre-designed, legally worded, and
 * already approved. This imports the approved PDF as a page background and
 * writes only the variable fields on top, so the layout is exactly the one
 * the University signed off on.
 *
 * Everything printed here comes from $certificate->payload, which is the
 * hashed snapshot. That is deliberate: if a value is printed from anywhere
 * else it is not covered by the fingerprint, and altering it in the database
 * would not be detected on verification.
 */
class PdfTemplateStamper
{
    public function __construct(protected QrCodeService $qr) {}

    /**
     * Is a usable template configured for this document type?
     */
    public function hasTemplate(string $documentType): bool
    {
        $path = config("certificate-templates.{$documentType}.template");

        return $path !== null && is_readable($path);
    }

    /**
     * Render the certificate onto its template and return the PDF bytes.
     */
    public function render(Certificate $certificate): string
    {
        $config = config("certificate-templates.{$certificate->document_type}");
        $path = $config['template'] ?? null;

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException(
                "No readable PDF template for {$certificate->document_type}. "
                . 'Check the path in config/certificate-templates.php.'
            );
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        try {
            $pageCount = $pdf->setSourceFile($path);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Could not read the PDF template: ' . $e->getMessage()
                . ' — free FPDI reads PDF 1.4 and earlier. See USING-YOUR-PDF-TEMPLATES.md for how to downgrade the file.',
                previous: $e
            );
        }

        $qrFile = null;

        try {
            // Import every page of the template, so multi-page forms survive.
            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage(
                    $size['orientation'] ?? ($config['orientation'] ?? 'portrait'),
                    [$size['width'], $size['height']]
                );
                $pdf->useTemplate($template);

                // Fields are stamped on page 1 unless they name another page.
                foreach ($config['fields'] ?? [] as $field) {
                    if (($field['page'] ?? 1) !== $page) {
                        continue;
                    }

                    if (($field['type'] ?? 'text') === 'qr') {
                        $qrFile ??= $this->qr->toTempFile($certificate, 600);
                        $this->placeQr($pdf, $field, $qrFile);
                        continue;
                    }

                    $this->placeText($pdf, $field, $certificate, $size['width']);
                }
            }

            return $pdf->Output('S');
        } finally {
            if ($qrFile && file_exists($qrFile)) {
                unlink($qrFile);
            }
        }
    }

    protected function placeQr(Fpdi $pdf, array $field, string $file): void
    {
        $size = (float) ($field['size'] ?? 25);

        $pdf->Image($file, (float) $field['x'], (float) $field['y'], $size, $size, 'PNG');
    }

    protected function placeText(Fpdi $pdf, array $field, Certificate $certificate, float $pageWidth): void
    {
        $text = $this->resolve($field, $certificate);

        // A field with nothing behind it prints nothing, rather than an empty box.
        if ($text === null || $text === '') {
            return;
        }

        if ($field['upper'] ?? false) {
            $text = mb_strtoupper($text);
        }

        $color = $field['color'] ?? [22, 35, 63];

        $pdf->SetFont(
            $field['font'] ?? 'Helvetica',
            $field['style'] ?? '',
            (float) ($field['size'] ?? 10)
        );
        $pdf->SetTextColor($color[0], $color[1], $color[2]);

        $width = (float) ($field['width'] ?? 0);

        if ($width <= 0) {
            $width = $pageWidth - (float) $field['x'];
        }

        $pdf->SetXY((float) $field['x'], (float) $field['y']);
        $pdf->Cell($width, 6, $this->encode($text), 0, 0, $field['align'] ?? 'L');
    }

    /**
     * Resolve a field to its printed string, from the hashed payload.
     */
    protected function resolve(array $field, Certificate $certificate): ?string
    {
        $payload = $certificate->payload ?? [];

        if (isset($field['value'])) {
            $value = $payload[$field['value']] ?? null;

            return $this->format($field['value'], $value);
        }

        if (! isset($field['text'])) {
            return null;
        }

        $replacements = [
            '{serial}'     => $certificate->serial_number,
            '{hash}'       => $certificate->content_hash,
            '{short_hash}' => $certificate->shortHash(),
            '{verify_url}' => $certificate->verificationUrl(),
            '{date}'       => $certificate->issued_on?->format('F j, Y') ?? '',
        ];

        foreach ($payload as $key => $value) {
            if (! is_array($value)) {
                $replacements['{' . $key . '}'] = (string) $this->format($key, $value);
            }
        }

        return strtr($field['text'], $replacements);
    }

    /**
     * Dates are stored ISO for hashing stability but should read naturally
     * on a printed certificate.
     */
    protected function format(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_contains($key, 'date') || $key === 'issued_on') {
            try {
                return Carbon::parse((string) $value)->format('F j, Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    /**
     * FPDF's core fonts are Latin-1. Philippine names carry Ñ and accents
     * often enough that dropping them silently would be a real defect, so
     * convert rather than letting them render as noise.
     */
    protected function encode(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);

        return $converted === false ? $text : $converted;
    }
}
