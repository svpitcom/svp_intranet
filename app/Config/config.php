<?php
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $value = trim($value);
        $value = trim($value, "\"'");
        putenv(trim($key) . '=' . $value);
    }
}

function env(string $key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('APP_NAME', env('APP_NAME', 'Factory Intranet'));
define('APP_URL', rtrim(env('APP_URL', '/'), '/'));
define('APP_ENV', env('APP_ENV', 'development'));
define('BASE_PATH', dirname(__DIR__, 2));

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
