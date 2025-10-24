<?php
namespace App\Services;

use PDO;

class BackupService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createSqlDump(): string
    {
        $output = "-- Basit SQL Yedeği\nSET FOREIGN_KEY_CHECKS=0;\n";
        $tables = $this->fetchTables();
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        foreach ($tables as $table) {
            $create = '';
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = :name");
                $stmt->execute(['name' => $table]);
                $create = $stmt->fetchColumn();
            } else {
                $stmt = $this->pdo->query('SHOW CREATE TABLE `' . $table . '`');
                $row = $stmt ? $stmt->fetch() : null;
                $create = $row['Create Table'] ?? '';
            }
            if ($create) {
                $output .= "\nDROP TABLE IF EXISTS `{$table}`;\n" . $create . ";\n";
            }
            $stmt = $this->pdo->query('SELECT * FROM `' . $table . '`');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $values = array_map(static fn($value) => $value === null ? 'NULL' : "'" . addslashes((string) $value) . "'", array_values($row));
                $output .= 'INSERT INTO `' . $table . '` VALUES(' . implode(',', $values) . ");\n";
            }
        }
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    public function store(string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }
        $filename = $directory . '/backup_' . date('Ymd_His') . '.sql';
        file_put_contents($filename, $this->createSqlDump());
        return $filename;
    }

    /**
     * @return array<int,string>
     */
    private function fetchTables(): array
    {
        $tables = [];
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                if (!empty($row[0])) {
                    $tables[] = $row[0];
                }
            }
        } else {
            $stmt = $this->pdo->query('SHOW TABLES');
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                if (!empty($row[0])) {
                    $tables[] = $row[0];
                }
            }
        }
        return $tables;
    }
}
