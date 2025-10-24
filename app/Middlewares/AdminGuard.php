<?php
namespace App\Middlewares;

use App\Core\Auth;

class AdminGuard
{
    public function handle(array $params = []): void
    {
        /** @var Auth|null $auth */
        $auth = $GLOBALS['auth'] ?? null;
        if ($auth === null || !$auth->check()) {
            header('Location: /login');
            exit;
        }

        $user = $auth->user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if ($user['role'] !== 'super_admin' && !$auth->hasPermission('access_admin')) {
            http_response_code(403);
            echo 'Yetkisiz erişim';
            exit;
        }

        if (!empty($user['twofa_secret']) && (int) $user['twofa_enabled'] === 1) {
            $verified = $_SESSION['twofa_verified_at'] ?? null;
            if ($verified === null || (time() - $verified) > 3600) {
                $_SESSION['pending_twofa_user'] = $user['id'];
                $_SESSION['twofa_redirect'] = $_SERVER['REQUEST_URI'] ?? '/admin';
                header('Location: /two-factor-challenge');
                exit;
            }
        }
    }
}
