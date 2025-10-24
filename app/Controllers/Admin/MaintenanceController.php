<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Setting;
use App\Services\BackupService;
use App\Services\SecurityScanner;

class MaintenanceController extends Controller
{
    private Csrf $csrf;

    public function __construct()
    {
        parent::__construct();
        $this->csrf = $GLOBALS['csrf'] ?? new Csrf();
    }

    public function index(): void
    {
        $user = $this->auth?->user();
        if (!$user || $user['role'] !== 'super_admin') {
            http_response_code(403);
            echo 'Bu sayfa yalnızca süper admin içindir.';
            return;
        }

        $settings = Setting::all($this->pdo);
        $errors = [];
        $success = null;
        $scanResults = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrf->validateToken($_POST['_csrf'] ?? '')) {
                $errors[] = 'Güvenlik anahtarı doğrulanamadı.';
            } else {
                $action = $_POST['action'] ?? '';
                if ($action === 'maintenance') {
                    $mode = ($_POST['maintenance_mode'] ?? 'off') === 'on' ? 'on' : 'off';
                    $whitelist = trim($_POST['maintenance_whitelist'] ?? '');
                    Setting::set($this->pdo, 'maintenance_mode', $mode);
                    Setting::set($this->pdo, 'maintenance_whitelist', $whitelist);
                    $settings['maintenance_mode'] = $mode;
                    $settings['maintenance_whitelist'] = $whitelist;
                    $success = 'Bakım modu ayarları güncellendi.';
                } elseif ($action === 'backup') {
                    $backup = new BackupService($this->pdo);
                    $path = $backup->store(dirname(__DIR__, 3) . '/storage/backups');
                    $success = 'Veritabanı yedeği hazırlandı: ' . basename($path);
                } elseif ($action === 'scan') {
                    $scanner = new SecurityScanner(dirname(__DIR__, 3));
                    $scanResults = $scanner->run();
                    $success = 'Güvenlik taraması tamamlandı.';
                }
            }
        }

        $this->view->render('admin/maintenance', [
            'title' => 'Bakım & Güvenlik',
            'csrf' => $this->csrf,
            'settings' => $settings,
            'errors' => $errors,
            'success' => $success,
            'scanResults' => $scanResults,
        ], 'admin');
    }
}
