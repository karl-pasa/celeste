<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Carbon;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfTemplateStamper
{
    public function __construct(protected QrCodeService $qr) {}

    public function hasTemplate(string $documentType): bool
    {
        $path = config("certificate-templates.{$documentType}.template");

        return $path !== null && is_readable($path);
    }

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
            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage(
                    $size['orientation'] ?? ($config['orientation'] ?? 'portrait'),
                    [$size['width'], $size['height']]
                );
                $pdf->useTemplate($template);

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

        if ($text === null || $text === '') {
            return;
        }

        if ($field['upper'] ?? false) {
            $text = mb_strtoupper($text);
        }

        $encoded = $this->encode($text);
        $color = $field['color'] ?? [22, 35, 63];
        $font = $field['font'] ?? 'Helvetica';
        $style = $field['style'] ?? '';

        $width = (float) ($field['width'] ?? 0);

        if ($width <= 0) {
            $width = $pageWidth - (float) $field['x'];
        }

        $size = $this->fitToWidth($pdf, $encoded, $field, $font, $style, $width);

        $pdf->SetFont($font, $style, $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);

        $pdf->SetXY((float) $field['x'], (float) $field['y']);
        $pdf->Cell($width, 6, $encoded, 0, 0, $field['align'] ?? 'L');
    }

    protected function fitToWidth(
        Fpdi $pdf,
        string $text,
        array $field,
        string $font,
        string $style,
        float $width,
    ): float {
        $size = (float) ($field['size'] ?? 10);
        $min = (float) ($field['min_size'] ?? 7);
        $available = max(1.0, $width - 1.0);   // 1mm of breathing room

        $pdf->SetFont($font, $style, $size);

        while ($size > $min && $pdf->GetStringWidth($text) > $available) {
            $size -= 0.25;
            $pdf->SetFont($font, $style, $size);
        }

        if ($pdf->GetStringWidth($text) > $available) {
            $this->overflows[] = [
                'text'  => $text,
                'width' => $width,
                'size'  => $size,
            ];
        }

        return $size;
    }

    /**
     * Fields whose text could not be made to fit. Read this after render()
     * when tuning a template.
     *
     * @var array<int, array{text:string, width:float, size:float}>
     */
    protected array $overflows = [];

    public function overflows(): array
    {
        return $this->overflows;
    }

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

    protected function encode(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);

        return $converted === false ? $text : $converted;
    }
}