<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Mailer;

class AuthController extends Controller
{
    public function login(): void
    {
        $errors = [];
        $status = null;
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        $limiter = $GLOBALS['limiter'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } elseif ($limiter && !$limiter->hit('login:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 5, 60)) {
                $errors[] = 'Çok fazla giriş denemesi. Bir dakika sonra tekrar deneyin.';
            } else {
                $result = $this->auth?->attempt($_POST['email'] ?? '', $_POST['password'] ?? '') ?? 'invalid';
                if ($result === 'ok') {
                    header('Location: /account');
                    exit;
                }
                if ($result === '2fa') {
                    $_SESSION['twofa_redirect'] = '/account';
                    header('Location: /two-factor-challenge');
                    exit;
                }
                $errors[] = 'E-posta veya parola hatalı.';
            }
        }

        $this->view->render('front/auth/login', [
            'title' => 'Giriş Yap',
            'errors' => $errors,
            'status' => $status,
        ]);
    }

    public function register(): void
    {
        $errors = [];
        $status = null;
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        /** @var Mailer|null $mailer */
        $mailer = $GLOBALS['mailer'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } elseif (empty($_POST['email']) || empty($_POST['password'])) {
                $errors[] = 'E-posta ve parola zorunludur.';
            } else {
                try {
                    $registration = $this->auth?->register($_POST['email'], $_POST['password'], $_POST['name'] ?? '') ?? null;
                    if ($registration) {
                        $verifyLink = $this->config['app']['url'] . '/verify-email?token=' . urlencode($registration['token']);
                        if ($mailer) {
                            $mailer->send(
                                $_POST['email'],
                                'E-posta doğrulama',
                                '<p>Hesabınızı doğrulamak için <a href="' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '">tıklayın</a>.</p>',
                                'Hesabınızı doğrulamak için şu bağlantıya gidin: ' . $verifyLink
                            );
                        }
                        $status = 'Kayıt tamamlandı, lütfen e-posta adresinizi doğrulayın.';
                    }
                } catch (\PDOException $e) {
                    if (str_contains($e->getMessage(), 'Duplicate')) {
                        $errors[] = 'Bu e-posta adresi ile zaten bir hesap mevcut.';
                    } else {
                        $errors[] = 'Kayıt sırasında hata: ' . $e->getMessage();
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Kayıt sırasında hata: ' . $e->getMessage();
                }
            }
        }

        $this->view->render('front/auth/register', [
            'title' => 'Kayıt Ol',
            'errors' => $errors,
            'status' => $status,
        ]);
    }

    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';
        $verified = false;
        if ($token !== '') {
            $verified = $this->auth?->verifyEmail($token) ?? false;
        }

        $this->view->render('front/auth/verify', [
            'title' => 'E-posta Doğrulama',
            'verified' => $verified,
        ]);
    }

    public function logout(): void
    {
        $this->auth?->logout();
        header('Location: /');
        exit;
    }

    public function requestReset(): void
    {
        $errors = [];
        $status = null;
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        /** @var Mailer|null $mailer */
        $mailer = $GLOBALS['mailer'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } else {
                $token = $this->auth?->createPasswordReset($_POST['email'] ?? '') ?? null;
                if ($token) {
                    $resetLink = $this->config['app']['url'] . '/reset-password?token=' . urlencode($token);
                    if ($mailer) {
                        $mailer->send(
                            $_POST['email'],
                            'Parola sıfırlama',
                            '<p>Parolanızı sıfırlamak için <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">bağlantıya tıklayın</a>.</p>',
                            'Parolanızı sıfırlamak için bağlantı: ' . $resetLink
                        );
                    }
                    $status = 'Eğer kayıtlı bir hesap varsa sıfırlama bağlantısı gönderildi.';
                } else {
                    $status = 'Eğer kayıtlı bir hesap varsa sıfırlama bağlantısı gönderildi.';
                }
            }
        }

        $this->view->render('front/auth/request-reset', [
            'title' => 'Parola Sıfırla',
            'errors' => $errors,
            'status' => $status,
        ]);
    }

    public function resetPassword(): void
    {
        $errors = [];
        $status = null;
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        $token = $_GET['token'] ?? ($_POST['token'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } elseif ($token === '') {
                $errors[] = 'Token eksik.';
            } elseif (empty($_POST['password'])) {
                $errors[] = 'Yeni parola zorunludur.';
            } elseif ($this->auth?->resetPassword($token, $_POST['password'] ?? '') ?? false) {
                $status = 'Parolanız güncellendi, giriş yapabilirsiniz.';
            } else {
                $errors[] = 'Token geçersiz veya süresi dolmuş.';
            }
        }

        $this->view->render('front/auth/reset', [
            'title' => 'Parola Sıfırlama',
            'token' => $token,
            'errors' => $errors,
            'status' => $status,
        ]);
    }

    public function twoFactorChallenge(): void
    {
        if (!$this->auth?->pendingTwoFactor()) {
            header('Location: /account');
            exit;
        }

        $errors = [];
        $csrf = $GLOBALS['csrf'] ?? new Csrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } elseif ($this->auth?->verifyTwoFactor($_POST['code'] ?? '') ?? false) {
                $target = $_SESSION['twofa_redirect'] ?? '/account';
                unset($_SESSION['twofa_redirect']);
                header('Location: ' . $target);
                exit;
            } else {
                $errors[] = 'Kod doğrulanamadı.';
            }
        }

        $this->view->render('front/auth/twofactor', [
            'title' => '2FA Doğrulama',
            'errors' => $errors,
        ]);
    }
}
