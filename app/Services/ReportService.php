<?php
namespace App\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use PDO;

class ReportService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<string,int|float>
     */
    public function dashboardStats(): array
    {
        $today = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('paid','delivered')")
            ->fetchColumn() ?: 0;
        $lowStock = $this->pdo->query("SELECT COUNT(*) FROM products WHERE stock_alert_threshold > 0 AND (SELECT COUNT(*) FROM product_keys WHERE product_id = products.id AND status = 'available') <= stock_alert_threshold")
            ->fetchColumn() ?: 0;
        $tickets = $this->pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open','waiting_admin','waiting_customer','dispute')")
            ->fetchColumn() ?: 0;
        return [
            'sales_today' => (int) $today,
            'low_stock' => (int) $lowStock,
            'pending_tickets' => (int) $tickets,
        ];
    }

    /**
     * @return array<string,int|float>
     */
    public function summary(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = $this->pdo->prepare("SELECT status, SUM(total_amount) AS total, COUNT(*) AS order_count FROM orders WHERE created_at BETWEEN :from AND :to GROUP BY status");
        $stmt->execute([
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
        ]);
        $totals = [
            'gross' => 0.0,
            'refunded' => 0.0,
            'net' => 0.0,
            'orders' => 0,
            'refunds' => 0,
        ];
        while ($row = $stmt->fetch()) {
            $amount = (float) $row['total'];
            $count = (int) $row['order_count'];
            $status = $row['status'];
            if (in_array($status, ['paid', 'delivered'], true)) {
                $totals['gross'] += $amount;
                $totals['net'] += $amount;
                $totals['orders'] += $count;
            }
            if ($status === 'refunded') {
                $totals['refunded'] += $amount;
                $totals['net'] -= $amount;
                $totals['refunds'] += $count;
            }
        }
        return $totals;
    }

    /**
     * @return array<string,array<int,array{date:string,sales:float,refunds:float}>>
     */
    public function chartSeries(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $period = new DatePeriod($from, new DateInterval('P1D'), $to->add(new DateInterval('P1D')));
        $base = [];
        foreach ($period as $date) {
            $base[$date->format('Y-m-d')] = ['date' => $date->format('Y-m-d'), 'sales' => 0.0, 'refunds' => 0.0];
        }

        $stmt = $this->pdo->prepare("SELECT DATE(created_at) AS day, status, SUM(total_amount) AS total FROM orders WHERE created_at BETWEEN :from AND :to GROUP BY day, status");
        $stmt->execute([
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
        ]);
        while ($row = $stmt->fetch()) {
            $day = $row['day'];
            if (!isset($base[$day])) {
                continue;
            }
            if (in_array($row['status'], ['paid', 'delivered'], true)) {
                $base[$day]['sales'] += (float) $row['total'];
            }
            if ($row['status'] === 'refunded') {
                $base[$day]['refunds'] += (float) $row['total'];
            }
        }

        return ['series' => array_values($base)];
    }

    /**
     * @return array<int,array{product:string,total:int,revenue:float}>
     */
    public function topProducts(DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('SELECT p.name AS product, SUM(oi.quantity) AS total, SUM(oi.subtotal) AS revenue FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id INNER JOIN products p ON p.id = oi.product_id WHERE o.created_at BETWEEN :from AND :to AND o.status IN (\'paid\',\'delivered\') GROUP BY oi.product_id ORDER BY revenue DESC LIMIT :limit');
        $stmt->bindValue(':from', $from->format('Y-m-d 00:00:00'));
        $stmt->bindValue(':to', $to->format('Y-m-d 23:59:59'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array{data:array<int,array>,total:int}
     */
    public function paginatedOrders(DateTimeImmutable $from, DateTimeImmutable $to, int $page = 1, int $perPage = 25): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE created_at BETWEEN :from AND :to');
        $countStmt->execute([
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
        ]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT id, user_id, status, total_amount, discount_amount, currency, created_at FROM orders WHERE created_at BETWEEN :from AND :to ORDER BY created_at DESC LIMIT :per OFFSET :offset');
        $stmt->bindValue(':from', $from->format('Y-m-d 00:00:00'));
        $stmt->bindValue(':to', $to->format('Y-m-d 23:59:59'));
        $stmt->bindValue(':per', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

        return ['data' => $data, 'total' => $total];
    }

    public function exportOrdersCsv(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, status, total_amount, discount_amount, currency, created_at FROM orders WHERE created_at BETWEEN :from AND :to ORDER BY created_at DESC');
        $stmt->execute([
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
        ]);
        $rows = $stmt->fetchAll();
        $fh = fopen('php://temp', 'wb+');
        fputcsv($fh, ['ID', 'Kullanıcı', 'Durum', 'Tutar', 'İndirim', 'Para Birimi', 'Oluşturulma']);
        foreach ($rows as $row) {
            fputcsv($fh, [$row['id'], $row['user_id'], $row['status'], $row['total_amount'], $row['discount_amount'], $row['currency'], $row['created_at']]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);
        return $csv;
    }
}
