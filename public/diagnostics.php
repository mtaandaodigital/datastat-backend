<?php
// DataStat Laravel Diagnostics - Temporary script for cPanel use
// PURPOSE:
// 1) Clear/rebuild Laravel caches and ensure storage link using PHP 8.3 binary
// 2) Test DB connection using .env config
// 3) Test whether given user credentials are valid (without logging in)
//
// IMPORTANT:
// - Place this file temporarily, run it once, then DELETE it for security.
// - You can pass ?email=...&password=... to override defaults below.
// - Optional: add ?token=YOUR_SECRET to prevent accidental invocation (set SECRET_TOKEN below).

// ===================== USER EDITABLE VARIABLES =====================
$PHP83_BIN_CANDIDATES = [
    '/opt/cpanel/ea-php83/root/usr/bin/php', // Most common on cPanel (EA4)
    '/usr/bin/php83',
    '/usr/local/bin/php83',
    '/usr/bin/php8.3',
    '/usr/local/bin/php8.3',
];

// Default credentials to test (can be overridden via GET)
$DEFAULT_EMAIL = 'info@datastatresearch.com';
$DEFAULT_PASSWORD = 'D@t@2025';

// Optional simple protection token: set a value here, then call with ?token=the-same
$SECRET_TOKEN = '';
// ==================================================================

header('Content-Type: text/plain');

// Basic token gate if configured
if ($SECRET_TOKEN !== '') {
    if (!isset($_GET['token']) || $_GET['token'] !== $SECRET_TOKEN) {
        http_response_code(403);
        echo "Forbidden: invalid token. Delete this file if not needed.\n";
        exit;
    }
}

// Resolve project base path (this file is expected in public/)
$publicPath = __DIR__;
$basePath = realpath($publicPath . DIRECTORY_SEPARATOR . '..');
$artisan = $basePath . DIRECTORY_SEPARATOR . 'artisan';

function line($label, $value) {
    echo str_pad($label, 32, ' ', STR_PAD_RIGHT) . ": " . $value . "\n";
}

function run_cmd($bin, $args, $cwd) {
    $cmd = $bin . ' ' . implode(' ', array_map('escapeshellarg', $args));
    $descriptorSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($proc)) {
        return [1, 'Unable to start process'];
    }
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($proc);
    return [$exit, trim($stdout . "\n" . $stderr)];
}

function find_php83_binary($candidates) {
    foreach ($candidates as $bin) {
        if (is_file($bin) && is_executable($bin)) {
            return $bin;
        }
    }
    // Fallback to current PHP; warn later
    return PHP_BINARY;
}

// Gather environment info
line('PHP running this script', PHP_VERSION . ' (' . PHP_BINARY . ')');
line('Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'N/A');
line('Base path', $basePath ?: 'N/A');
line('Artisan path', $artisan);

if (!is_file($artisan)) {
    echo "\nERROR: Could not find artisan at: $artisan\nMove this script to the Laravel public/ folder.\n";
    exit(1);
}

$php83 = find_php83_binary($PHP83_BIN_CANDIDATES);
$usingFallback = $php83 === PHP_BINARY;
line('Selected PHP 8.3 binary', $php83);
if ($usingFallback) {
    echo "WARNING: Using current PHP binary because no PHP 8.3 candidate was found.\n";
}

// 1) Clear/rebuild caches and ensure storage link
$artisanCmds = [
    ['optimize:clear'],
    ['storage:link'],
    ['config:cache'],
    ['route:cache'],
    ['view:cache'],
];

echo "\n=== Running artisan maintenance (via PHP 8.3 binary if available) ===\n";
foreach ($artisanCmds as $args) {
    array_unshift($args, $artisan); // prepend artisan path
    [$code, $out] = run_cmd($php83, $args, $basePath);
    line('Command', $php83 . ' ' . basename($artisan) . ' ' . implode(' ', array_slice($args, 1)));
    echo $out . "\n";
    if ($code !== 0) {
        echo "(non-zero exit code: $code)\n";
    }
}

// For DB/Auth tests we must run under PHP >= 8.2 to satisfy Composer's platform check.
// We'll create a temporary runner script and execute it using the selected PHP 8.3 binary.

// Collect credentials (allow override via query string)
$email = isset($_GET['email']) ? (string)$_GET['email'] : $DEFAULT_EMAIL;
$password = isset($_GET['password']) ? (string)$_GET['password'] : $DEFAULT_PASSWORD;

echo "\n=== Preparing Laravel DB/Auth tests (via PHP 8.3) ===\n";
$runnerPath = $basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'diag_runner.php';
$runnerCode = <<<'PHP'
<?php
// Auto-removed temporary runner. Executed via php8.3
$basePath = realpath(__DIR__ . '/..');
require $basePath . '/vendor/autoload.php';
$app = require $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

[$script, $email, $password] = $argv + [null, '', ''];

echo "=== Environment / DB Config ===\n";
$env = env('APP_ENV');
$dbConn = config('database.default');
$dbCfg = config('database.connections.' . $dbConn) ?? [];
echo 'APP_ENV                         : ' . $env . "\n";
echo 'DB_CONNECTION                   : ' . $dbConn . "\n";
echo 'DB_HOST                         : ' . ($dbCfg['host'] ?? '') . "\n";
echo 'DB_PORT                         : ' . ($dbCfg['port'] ?? '') . "\n";
echo 'DB_DATABASE                     : ' . ($dbCfg['database'] ?? '') . "\n";
echo 'DB_USERNAME                     : ' . ($dbCfg['username'] ?? '') . "\n";

// DB test
try {
    DB::connection()->getPdo();
    DB::select('SELECT 1 AS ok');
    echo "\n=== DB Connection Test ===\n";
    echo "SUCCESS: Connected to database and executed SELECT 1.\n";
} catch (Throwable $e) {
    echo "\n=== DB Connection Test ===\n";
    echo "FAIL: Could not connect or query. Error: " . $e->getMessage() . "\n";
}

// Auth validation
echo "\n=== Auth Credentials Validation ===\n";
echo 'Testing email                   : ' . $email . "\n";
try {
    $valid = Auth::validate(['email' => $email, 'password' => $password]);
    if ($valid) {
        echo "SUCCESS: The provided password is valid for this account.\n";
    } else {
        echo "FAIL: Invalid credentials for the given account.\n";
    }
} catch (Throwable $e) {
    echo "ERROR during validation: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";
PHP;

// Write runner
if (@file_put_contents($runnerPath, $runnerCode) === false) {
    echo "ERROR: Unable to write temporary runner at $runnerPath\n";
    exit(1);
}

// Execute runner with php8.3 and pass credentials
[$code, $out] = run_cmd($php83, [$runnerPath, $email, $password], $basePath);
echo $out . "\n";
if ($code !== 0) {
    echo "(runner exit code: $code)\n";
}

// Cleanup
@unlink($runnerPath);

echo "\n=== Finished (outer script) ===\n";

// EOF