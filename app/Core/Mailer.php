<?php
namespace App\Core;

class Mailer
{
    public function __construct(private array $config)
    {
    }

    public function send(string $to, string $subject, string $html, string $text): bool
    {
        $from = $this->config['mail']['from'] ?? 'no-reply@localhost';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $from
        ];

        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
}
