<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use Throwable;

final class AuthController
{
    public function loginForm(): void
    {
        if (Session::user()) {
            header('Location: /');
            return;
        }
        require dirname(__DIR__, 2) . '/views/auth/login.php';
    }

    public function login(): void
    {
        Session::start();
        if (!Session::validateCsrf($_POST['csrf_token'] ?? null)) {
            $this->showError('La sesión del formulario expiró. Intente nuevamente.');
            return;
        }
        $usuario = trim((string)($_POST['usuario'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($usuario === '' || $password === '') {
            $this->showError('Ingrese usuario y contraseña.');
            return;
        }

        try {
            $stmt = Database::getConnection()->prepare(
                'SELECT u.id, u.nombre, u.usuario, u.password, u.rol_id, COALESCE(r.es_admin, 0) AS es_admin
                 FROM usuarios u LEFT JOIN roles r ON r.id = u.rol_id
                 WHERE u.usuario = :usuario AND u.estado = 1 LIMIT 1'
            );
            $stmt->execute(['usuario' => $usuario]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, (string)$user['password'])) {
                $this->showError('Usuario o contraseña incorrectos.');
                return;
            }

            Session::login($user);
            header('Location: /');
        } catch (Throwable $e) {
            error_log('Error de autenticación: ' . $e->getMessage());
            $this->showError('No fue posible iniciar sesión. Verifique la configuración del sistema.');
        }
    }

    public function logout(): void
    {
        Session::logout();
        header('Location: /login');
    }

    private function showError(string $message): void
    {
        http_response_code(422);
        require dirname(__DIR__, 2) . '/views/auth/login.php';
    }
}
