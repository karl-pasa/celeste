<?php

namespace App\Services;

use App\Models\Certificate;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{

    protected const QUIET_ZONE = 2;

    protected const FOREGROUND = [18, 34, 79];

    protected const BACKGROUND = [255, 255, 255];

    public function payloadUrl(Certificate $certificate): string
    {
        return route('verify.token', $certificate->verification_token);
    }

    public function render(Certificate $certificate, int $size = 320): string
    {
        return $this->renderText($this->payloadUrl($certificate), $size);
    }

    public function renderText(string $text, int $size = 320): string
    {
        $matrix  = $this->matrixFor($text);
        $modules = count($matrix);
        $total   = $modules + (self::QUIET_ZONE * 2);

        $moduleSize = max(1, (int) floor($size / $total));
        $dimension  = $total * $moduleSize;

        return $this->encodePng($matrix, $moduleSize, $dimension);
    }

    protected function matrixFor(string $text): array
    {
        $byteMatrix = Encoder::encode($text, $this->errorCorrectionLevel())->getMatrix();

        $width  = $byteMatrix->getWidth();
        $height = $byteMatrix->getHeight();

        $matrix = [];

        for ($y = 0; $y < $height; $y++) {
            $row = [];

            for ($x = 0; $x < $width; $x++) {
                $row[] = $byteMatrix->get($x, $y) === 1;
            }

            $matrix[] = $row;
        }

        return $matrix;
    }

    protected function encodePng(array $matrix, int $moduleSize, int $dimension): string
    {
        $fg = pack('C3', ...self::FOREGROUND);
        $bg = pack('C3', ...self::BACKGROUND);

        $quiet  = self::QUIET_ZONE * $moduleSize;
        $blank  = str_repeat($bg, $dimension);
        $raw    = '';

        for ($i = 0; $i < $quiet; $i++) {
            $raw .= "\x00" . $blank;
        }

        foreach ($matrix as $row) {
            $line = str_repeat($bg, $quiet);

            foreach ($row as $filled) {
                $line .= str_repeat($filled ? $fg : $bg, $moduleSize);
            }

            $line .= str_repeat($bg, $quiet);

            for ($i = 0; $i < $moduleSize; $i++) {
                $raw .= "\x00" . $line;
            }
        }

        for ($i = 0; $i < $quiet; $i++) {
            $raw .= "\x00" . $blank;
        }

        $ihdr = pack('N2', $dimension, $dimension)
            . pack('C5', 8, 2, 0, 0, 0);   // bit depth 8, colour type 2 (RGB), no interlace

        return "\x89PNG\r\n\x1a\n"
            . $this->chunk('IHDR', $ihdr)
            . $this->chunk('IDAT', gzcompress($raw, 9))
            . $this->chunk('IEND', '');
    }

    protected function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            . $type
            . $data
            . pack('N', crc32($type . $data));
    }

    protected function errorCorrectionLevel(): ErrorCorrectionLevel
    {
        return enum_exists(ErrorCorrectionLevel::class)
            ? constant(ErrorCorrectionLevel::class . '::H')
            : ErrorCorrectionLevel::H();
    }

    public function store(Certificate $certificate): string
    {
        $path = "certificates/qr/{$certificate->verification_token}.png";

        try {
            Storage::disk('local')->put($path, $this->render($certificate));
            $certificate->forceFill(['qr_path' => $path])->save();
        } catch (\Throwable $e) {
            report($e);

            return '';
        }

        return $path;
    }

    public function dataUri(Certificate $certificate, int $size = 320): string
    {
        return 'data:image/png;base64,' . base64_encode($this->render($certificate, $size));
    }

    public function toTempFile(Certificate $certificate, int $size = 600): string
    {
        $path = tempnam(sys_get_temp_dir(), 'celeste_qr_') . '.png';

        file_put_contents($path, $this->render($certificate, $size));

        return $path;
    }
}
