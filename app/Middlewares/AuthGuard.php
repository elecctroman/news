<?php
namespace App\Middlewares;

use App\Core\Auth;

class AuthGuard
{
    public function handle(array $params = []): void
    {
        /** @var Auth|null $auth */
        $auth = $GLOBALS['auth'] ?? null;
        if ($auth === null || !$auth->check()) {
            header('Location: /login');
            exit;
        }
    }
}
