<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Order;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $report = new ReportService($this->pdo);
        $stats = $report->dashboardStats();
        $chartPoints = array_map(static fn ($row) => (int) ($row['total'] ?? 0), Order::totalsByDay($this->pdo, 6));
        $this->view->render('admin/dashboard', [
            'title' => 'Yönetim Paneli',
            'stats' => $stats,
            'chartPoints' => $chartPoints,
        ], 'admin');
    }
}
