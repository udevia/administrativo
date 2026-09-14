<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('mi_erp_session');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
    }

    public static function user(): ?array
    {
        self::start();
        return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])
            ? $_SESSION['auth_user']
            : null;
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'id' => (int)$user['id'],
            'nombre' => (string)$user['nombre'],
            'usuario' => (string)$user['usuario'],
            'rol_id' => (int)($user['rol_id'] ?? 1),
            'es_admin' => (bool)($user['es_admin'] ?? false),
        ];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    }

    public static function validateCsrf(?string $token): bool
    {
        self::start();
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function isExpired(int $timeout = 28800): bool
    {
        self::start();
        return isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout;
    }

    public static function touch(): void
    {
        self::start();
        $_SESSION['last_activity'] = time();
    }
}
