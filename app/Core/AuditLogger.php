<?php
namespace App\Core;

use PDO;

class AuditLogger
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function record(?int $userId, string $action, array $context = []): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_logs(user_id, action, ip_address, user_agent, context) VALUES(:user_id, :action, :ip, :ua, :context)');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
