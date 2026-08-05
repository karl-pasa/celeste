<?php

/**
 * Vercel entry point.
 *
 * Vercel's filesystem is read-only apart from /tmp, so the compiled view
 * directory has to exist before Laravel boots. Sessions and cache are pointed
 * at the database in vercel.json for the same reason.
 */

foreach (['/tmp/views', '/tmp/celeste-storage/certificates/files', '/tmp/celeste-storage/certificates/qr'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

require __DIR__ . '/../public/index.php';
