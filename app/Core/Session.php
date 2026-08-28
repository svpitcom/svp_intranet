<?php
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(env('SESSION_NAME', 'factory_intranet_sess'));
            session_start();
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function flash(string $key, ?string $message = null)
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
