<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ReportService;
use DateInterval;
use DateTimeImmutable;

class ReportController extends Controller
{
    public function index(): void
    {
        $report = new ReportService($this->pdo);
        $fromParam = $_GET['from'] ?? null;
        $toParam = $_GET['to'] ?? null;
        $from = $this->parseDate($fromParam) ?? (new DateTimeImmutable('now'))->sub(new DateInterval('P6D'));
        $to = $this->parseDate($toParam) ?? new DateTimeImmutable('now');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $summary = $report->summary($from, $to);
        $chart = $report->chartSeries($from, $to);
        $topProducts = $report->topProducts($from, $to);
        $orders = $report->paginatedOrders($from, $to, $page);
        $totalPages = max(1, (int) ceil($orders['total'] / 25));

        $this->view->render('admin/reports', [
            'title' => 'Raporlar',
            'summary' => $summary,
            'chart' => $chart,
            'topProducts' => $topProducts,
            'orders' => $orders['data'],
            'page' => $page,
            'totalPages' => $totalPages,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'admin');
    }

    public function export(): void
    {
        $report = new ReportService($this->pdo);
        $fromParam = $_GET['from'] ?? null;
        $toParam = $_GET['to'] ?? null;
        $from = $this->parseDate($fromParam) ?? (new DateTimeImmutable('now'))->sub(new DateInterval('P6D'));
        $to = $this->parseDate($toParam) ?? new DateTimeImmutable('now');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $csv = $report->exportOrdersCsv($from, $to);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv"');
        echo $csv;
        exit;
    }

    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date ?: null;
    }
}
