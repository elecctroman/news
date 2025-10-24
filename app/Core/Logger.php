<?php
namespace App\Core;

class Logger
{
    public function __construct(private string $path)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $line = sprintf('[%s] %s %s %s%s',
            date('Y-m-d H:i:s'),
            $level,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE),
            PHP_EOL
        );

        file_put_contents($this->path . '/app.log', $line, FILE_APPEND);
    }
}
