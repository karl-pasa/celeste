<?php

foreach (['/tmp/views', '/tmp/celeste-storage/certificates/files', '/tmp/celeste-storage/certificates/qr'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

require __DIR__ . '/../public/index.php';
