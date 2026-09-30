<?php
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(env('SESSION_NAME', 'factory_intranet_sess'));
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
            session_start();
        }
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function validCsrf($token): bool
    {
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
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
