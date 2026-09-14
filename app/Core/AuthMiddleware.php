<?php
declare(strict_types=1);

namespace App\Core;

final class AuthMiddleware
{
    public static function requireAuthentication(string $path): bool
    {
        Session::start();

        if ($path === 'login' || $path === 'logout' || str_starts_with($path, 'webhook/')) {
            return true;
        }

        $user = Session::user();
        if ($user && !Session::isExpired()) {
            Session::touch();
            return true;
        }

        Session::logout();
        if (str_starts_with($path, 'api/')) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Sesión requerida.']);
        } else {
            header('Location: /login');
        }
        return false;
    }
}
