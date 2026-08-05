<?php

namespace App\Services;

use App\Models\Certificate;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the QR that is stamped onto every generated document.
 *
 * The QR encodes a public verification URL, not the certificate data itself.
 * That keeps the code small enough to survive photocopying, and means a scan
 * always resolves against the live database rather than trusting printed ink.
 *
 * ---------------------------------------------------------------------------
 * Why this writes the PNG bytes by hand
 * ---------------------------------------------------------------------------
 * Three renderers were ruled out:
 *
 *   - simple-qrcode's PNG output routes through Imagick, which is painful to
 *     install on Windows.
 *   - GD works locally but is absent from Vercel's PHP runtime, so anything
 *     built on it works in development and dies in production.
 *   - SVG needs no extension but leans on DomPDF's partial SVG support.
 *
 * A QR is a grid of solid squares, and PNG is a simple container format, so
 * this encodes one directly: raw scanlines, deflated with zlib, wrapped in
 * IHDR/IDAT/IEND chunks. zlib is compiled into PHP everywhere, so this has
 * no extension requirement at all and behaves identically on every host.
 *
 * Truecolour (type 2) with no alpha channel is deliberate: DomPDF parses that
 * PNG variant directly and only falls back to GD for images with alpha.
 */
class QrCodeService
{
    /** Modules of white space around the code. */
    protected const QUIET_ZONE = 2;

    /** CELESTE navy, matching the printed letterhead. */
    protected const FOREGROUND = [18, 34, 79];

    protected const BACKGROUND = [255, 255, 255];

    public function payloadUrl(Certificate $certificate): string
    {
        return route('verify.token', $certificate->verification_token);
    }

    /**
     * Render the QR as raw PNG bytes at roughly the requested pixel size.
     *
     * The size snaps to a whole number of modules. A QR whose modules land on
     * fractional pixels blurs under a phone camera, and a blurry code on a
     * printed diploma is a support ticket forever.
     */
    public function render(Certificate $certificate, int $size = 320): string
    {
        return $this->renderText($this->payloadUrl($certificate), $size);
    }

    /**
     * Encode arbitrary text. Useful for previews and the calibration command.
     */
    public function renderText(string $text, int $size = 320): string
    {
        $matrix  = $this->matrixFor($text);
        $modules = count($matrix);
        $total   = $modules + (self::QUIET_ZONE * 2);

        $moduleSize = max(1, (int) floor($size / $total));
        $dimension  = $total * $moduleSize;

        return $this->encodePng($matrix, $moduleSize, $dimension);
    }

    /**
     * Encode the URL and return the module matrix as booleans, [row][column].
     * Pure PHP -- no image extension involved.
     */
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

    /**
     * Build a truecolour 8-bit PNG from the module matrix.
     *
     * Each scanline is prefixed with filter byte 0 (None). Blocky, highly
     * repetitive content like a QR compresses well enough that a smarter
     * filter would not repay the complexity.
     */
    protected function encodePng(array $matrix, int $moduleSize, int $dimension): string
    {
        $fg = pack('C3', ...self::FOREGROUND);
        $bg = pack('C3', ...self::BACKGROUND);

        $quiet  = self::QUIET_ZONE * $moduleSize;
        $blank  = str_repeat($bg, $dimension);
        $raw    = '';

        // Top quiet zone
        for ($i = 0; $i < $quiet; $i++) {
            $raw .= "\x00" . $blank;
        }

        foreach ($matrix as $row) {
            $line = str_repeat($bg, $quiet);

            foreach ($row as $filled) {
                $line .= str_repeat($filled ? $fg : $bg, $moduleSize);
            }

            $line .= str_repeat($bg, $quiet);

            // Repeat the scanline once per pixel of module height
            for ($i = 0; $i < $moduleSize; $i++) {
                $raw .= "\x00" . $line;
            }
        }

        // Bottom quiet zone
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

    /**
     * A PNG chunk: length, type, data, and a CRC over type + data.
     */
    protected function chunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            . $type
            . $data
            . pack('N', crc32($type . $data));
    }

    /**
     * Level H tolerates about 30% damage, so the code still reads when the
     * university dry seal overlaps a corner or the document is photocopied.
     *
     * BaconQrCode 2.x exposes this as a static factory; 3.x made it a native
     * enum. Support both so a routine `composer update` cannot break issuance.
     */
    protected function errorCorrectionLevel(): ErrorCorrectionLevel
    {
        return enum_exists(ErrorCorrectionLevel::class)
            ? constant(ErrorCorrectionLevel::class . '::H')
            : ErrorCorrectionLevel::H();
    }

    public function store(Certificate $certificate): string
    {
        $path = "certificates/qr/{$certificate->verification_token}.png";

        Storage::disk('local')->put($path, $this->render($certificate));

        $certificate->forceFill(['qr_path' => $path])->save();

        return $path;
    }

    /**
     * Base64 data URI -- what the Blade PDF templates embed inline, so the
     * renderer never has to resolve an external file path.
     */
    public function dataUri(Certificate $certificate, int $size = 320): string
    {
        return 'data:image/png;base64,' . base64_encode($this->render($certificate, $size));
    }

    /**
     * Write the QR to a temporary file. FPDI/FPDF can only place images from
     * a path, so the PDF template stamper needs this.
     */
    public function toTempFile(Certificate $certificate, int $size = 600): string
    {
        $path = tempnam(sys_get_temp_dir(), 'celeste_qr_') . '.png';

        file_put_contents($path, $this->render($certificate, $size));

        return $path;
    }
}
