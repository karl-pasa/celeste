<?php
/**
 * Standalone PostgreSQL connection test.
 *
 * Bypasses Laravel entirely so that a failure here rules out the framework
 * and its configuration, and a success here proves the fault is in Laravel's
 * config rather than in PHP, PDO, or the network.
 *
 * Put this in the project root and run:  php dbtest.php
 */

$host = 'aws-0-ap-southeast-1.pooler.supabase.com';
$user = 'postgres.tczzlvnmdhoolzsyerjv';
$db   = 'postgres';

// Read the password out of .env rather than pasting it here.
$pass = null;
foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES) as $line) {
    if (str_starts_with(trim($line), 'DB_PASSWORD=')) {
        $pass = trim(substr(trim($line), 12), " \t\"'");
        break;
    }
}

if ($pass === null) {
    exit("Could not read DB_PASSWORD from .env\n");
}

echo "pdo_pgsql loaded : " . (extension_loaded('pdo_pgsql') ? 'yes' : 'NO') . "\n";
echo "password length  : " . strlen($pass) . " characters\n\n";

foreach ([5432, 6543] as $port) {
    echo str_repeat('-', 60) . "\n";
    echo "Port {$port}\n";

    // A short timeout so a failure reports quickly instead of hanging.
    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require;connect_timeout=10";

    $start = microtime(true);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        $elapsed = round((microtime(true) - $start) * 1000);
        echo "  CONNECTED in {$elapsed} ms\n";

        $row = $pdo->query('select current_user, current_database(), version()')->fetch(PDO::FETCH_ASSOC);
        echo "  user     : {$row['current_user']}\n";
        echo "  database : {$row['current_database']}\n";

        $count = $pdo->query('select count(*) from certificates')->fetchColumn();
        echo "  certificates visible : {$count}\n";
    } catch (Throwable $e) {
        $elapsed = round((microtime(true) - $start) * 1000);
        echo "  FAILED after {$elapsed} ms\n";
        echo "  " . $e->getMessage() . "\n";
    }

    echo "\n";
}
