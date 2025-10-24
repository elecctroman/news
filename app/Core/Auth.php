<?php
namespace App\Core;

use DateInterval;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class Auth
{
    private ?array $cachedUser = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $sql = 'SELECT u.id, u.email, u.name, u.role_id, r.name AS role, u.email_verified_at, u.twofa_secret, u.twofa_enabled
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }

        $user['permissions'] = $this->permissionsForRole((int) $user['role_id']);
        $this->cachedUser = $user;

        return $user;
    }

    public function register(string $email, string $password, string $name = ''): array
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $roleStmt = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name');
        $roleStmt->execute(['name' => 'customer']);
        $roleId = $roleStmt->fetchColumn();
        if ($roleId === false) {
            throw new RuntimeException('Varsayılan müşteri rolü bulunamadı.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO users(email, password, role_id, name) VALUES(:email, :password, :role_id, :name)');
            $stmt->execute([
                'email' => $email,
                'password' => $hash,
                'role_id' => $roleId,
                'name' => $name,
            ]);
            $userId = (int) $this->pdo->lastInsertId();
            $token = $this->storeToken($userId, 'verify');
            $this->pdo->commit();

            return ['id' => $userId, 'token' => $token];
        } catch (\Throwable $throwable) {
            $this->pdo->rollBack();
            throw $throwable;
        }
    }

    public function verifyEmail(string $token): bool
    {
        $data = $this->consumeToken($token, 'verify');
        if ($data === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $data['user_id']]);

        return $stmt->rowCount() > 0;
    }

    public function createPasswordReset(string $email): ?string
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $userId = $stmt->fetchColumn();

        if ($userId === false) {
            return null;
        }

        return $this->storeToken((int) $userId, 'reset', new DateInterval('PT1H'));
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $data = $this->consumeToken($token, 'reset');
        if ($data === null) {
            return false;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute([
            'password' => $hash,
            'id' => $data['user_id'],
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return string one of ok|2fa|invalid
     */
    public function attempt(string $email, string $password): string
    {
        $stmt = $this->pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return 'invalid';
        }

        if (!empty($user['twofa_secret']) && (int) $user['twofa_enabled'] === 1) {
            $_SESSION['pending_twofa_user'] = (int) $user['id'];
            return '2fa';
        }

        $this->completeLogin((int) $user['id']);
        return 'ok';
    }

    public function verifyTwoFactor(string $code): bool
    {
        $pending = $_SESSION['pending_twofa_user'] ?? null;
        if ($pending === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT id, twofa_secret, twofa_recovery_codes FROM users WHERE id = :id');
        $stmt->execute(['id' => $pending]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        if (!empty($user['twofa_secret']) && Totp::verify($user['twofa_secret'], $code)) {
            unset($_SESSION['pending_twofa_user']);
            $this->completeLogin((int) $user['id']);
            $_SESSION['twofa_verified_at'] = time();
            return true;
        }

        if (!empty($user['twofa_recovery_codes'])) {
            $codes = json_decode($user['twofa_recovery_codes'], true) ?: [];
            foreach ($codes as $index => $hashed) {
                if (password_verify($code, $hashed)) {
                    unset($codes[$index]);
                    $stmt = $this->pdo->prepare('UPDATE users SET twofa_recovery_codes = :codes WHERE id = :id');
                    $stmt->execute([
                        'codes' => json_encode(array_values($codes), JSON_THROW_ON_ERROR),
                        'id' => $user['id'],
                    ]);
                    unset($_SESSION['pending_twofa_user']);
                    $this->completeLogin((int) $user['id']);
                    $_SESSION['twofa_verified_at'] = time();
                    return true;
                }
            }
        }

        return false;
    }

    public function enableTwoFactor(int $userId, string $secret, array $recoveryCodes): void
    {
        $hashedCodes = array_map(static fn ($code) => password_hash($code, PASSWORD_DEFAULT), $recoveryCodes);
        $stmt = $this->pdo->prepare('UPDATE users SET twofa_secret = :secret, twofa_recovery_codes = :codes, twofa_enabled = 1 WHERE id = :id');
        $stmt->execute([
            'secret' => $secret,
            'codes' => json_encode($hashedCodes, JSON_THROW_ON_ERROR),
            'id' => $userId,
        ]);
        $this->cachedUser = null;
    }

    public function disableTwoFactor(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET twofa_secret = NULL, twofa_recovery_codes = NULL, twofa_enabled = 0 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $this->cachedUser = null;
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare('DELETE FROM user_sessions WHERE session_id = :session');
            $stmt->execute(['session' => session_id()]);
        }

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        if ($user['role'] === 'super_admin') {
            return true;
        }

        return in_array($permission, $user['permissions'], true);
    }

    public function isEmailVerified(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user['email_verified_at'] !== null;
    }

    public function pendingTwoFactor(): bool
    {
        return isset($_SESSION['pending_twofa_user']);
    }

    private function permissionsForRole(int $roleId): array
    {
        $stmt = $this->pdo->prepare('SELECT permission FROM role_permissions WHERE role_id = :id');
        $stmt->execute(['id' => $roleId]);
        return array_column($stmt->fetchAll(), 'permission');
    }

    private function completeLogin(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['twofa_verified_at'] = time();

        $stmt = $this->pdo->prepare('INSERT INTO user_sessions(user_id, session_id, user_agent, ip_address) VALUES(:user_id, :session_id, :ua, :ip)');
        $stmt->execute([
            'user_id' => $userId,
            'session_id' => session_id(),
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 250),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    }

    private function storeToken(int $userId, string $type, ?DateInterval $ttl = null): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = null;
        if ($ttl !== null) {
            $expiresAt = (new DateTimeImmutable('now'))->add($ttl)->format('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare('INSERT INTO user_tokens(user_id, token, type, expires_at) VALUES(:user_id, :token, :type, :expires_at)');
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'type' => $type,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    private function consumeToken(string $token, string $type): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, expires_at FROM user_tokens WHERE token = :token AND type = :type');
        $stmt->execute([
            'token' => $token,
            'type' => $type,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        if ($row['expires_at'] !== null && new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) {
            $this->deleteToken((int) $row['id']);
            return null;
        }

        $this->deleteToken((int) $row['id']);
        return $row;
    }

    private function deleteToken(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_tokens WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
