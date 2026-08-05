<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use setasign\Fpdi\Fpdi;

/**
 * Prints a template with a labelled millimetre grid over it.
 *
 * Finding coordinates by generating, squinting, and nudging by 5mm is slow.
 * With a grid you read the position straight off the page and type it into
 * config/certificate-templates.php once.
 */
class CalibrateTemplate extends Command
{
    protected $signature = 'celeste:calibrate
                            {type : diploma, certificate_of_enrolment, honorable_dismissal, or transcript_of_records}
                            {--spacing=10 : Grid spacing in millimetres}';

    protected $description = 'Overlay a measuring grid on a PDF template to find field coordinates';

    public function handle(): int
    {
        $type = $this->argument('type');

        if (! array_key_exists($type, Certificate::types())) {
            $this->error("Unknown document type '{$type}'.");
            $this->line('Valid types: ' . implode(', ', array_keys(Certificate::types())));

            return self::FAILURE;
        }

        $path = config("certificate-templates.{$type}.template");

        if (! $path || ! is_readable($path)) {
            $this->error("No readable template found for {$type}.");
            $this->line('Expected a PDF at: ' . ($path ?: '(not configured)'));
            $this->newLine();
            $this->line('Put your form there, or set the path in config/certificate-templates.php.');

            return self::FAILURE;
        }

        $spacing = max(5, (int) $this->option('spacing'));

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        try {
            $pageCount = $pdf->setSourceFile($path);
        } catch (\Throwable $e) {
            $this->error('Could not read that PDF: ' . $e->getMessage());
            $this->newLine();
            $this->line('Free FPDI reads PDF 1.4 and earlier. To downgrade the file, see');
            $this->line('USING-YOUR-PDF-TEMPLATES.md, section "When FPDI refuses your PDF".');

            return self::FAILURE;
        }

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template);

            $this->drawGrid($pdf, $size['width'], $size['height'], $spacing);
        }

        $directory = storage_path('app/calibration');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $output = $directory . DIRECTORY_SEPARATOR . $type . '-grid.pdf';
        $pdf->Output('F', $output);

        $this->newLine();
        $this->info('Grid written to ' . $output);
        $this->newLine();
        $this->line('Open it, find where each field belongs, and read off the x and y labels.');
        $this->line('x runs left to right, y runs top to bottom, both in millimetres.');
        $this->line('Put those numbers into config/certificate-templates.php, then run:');
        $this->newLine();
        $this->line('  php artisan celeste:preview ' . $type);

        return self::SUCCESS;
    }

    protected function drawGrid(Fpdi $pdf, float $width, float $height, int $spacing): void
    {
        $pdf->SetFont('Helvetica', '', 4);

        // Minor lines every `spacing` mm, major every 50mm and labelled.
        for ($x = 0; $x <= $width; $x += $spacing) {
            $major = $x % 50 === 0;

            $pdf->SetDrawColor(...($major ? [200, 60, 80] : [150, 190, 230]));
            $pdf->SetLineWidth($major ? 0.25 : 0.1);
            $pdf->Line($x, 0, $x, $height);

            if ($major && $x > 0) {
                $pdf->SetTextColor(200, 60, 80);
                $pdf->SetXY($x + 0.6, 1);
                $pdf->Cell(12, 3, 'x' . $x, 0, 0, 'L');
            }
        }

        for ($y = 0; $y <= $height; $y += $spacing) {
            $major = $y % 50 === 0;

            $pdf->SetDrawColor(...($major ? [200, 60, 80] : [150, 190, 230]));
            $pdf->SetLineWidth($major ? 0.25 : 0.1);
            $pdf->Line(0, $y, $width, $y);

            if ($major && $y > 0) {
                $pdf->SetTextColor(200, 60, 80);
                $pdf->SetXY(1, $y + 0.6);
                $pdf->Cell(12, 3, 'y' . $y, 0, 0, 'L');
            }
        }

        // Page dimensions in the corner, so you can confirm the paper size.
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->SetTextColor(200, 60, 80);
        $pdf->SetXY(2, $height - 6);
        $pdf->Cell(60, 4, sprintf('%.0f x %.0f mm  |  grid %dmm', $width, $height, $spacing), 0, 0, 'L');
    }
}
