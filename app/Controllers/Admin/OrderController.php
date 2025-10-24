<?php
namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Order;
use App\Services\DeliveryService;

class OrderController extends Controller
{
    public function index(): void
    {
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        $messages = [];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } else {
                $action = $_POST['action'] ?? '';
                $orderId = (int) ($_POST['order_id'] ?? 0);
                $logger = new AuditLogger($this->pdo);
                if ($action === 'update_status' && $orderId > 0) {
                    try {
                        Order::changeStatus($this->pdo, $orderId, $_POST['status'] ?? 'pending');
                        $messages[] = 'Sipariş durumu güncellendi.';
                        $logger->record($this->auth?->user()['id'] ?? null, 'order_status_change', ['order_id' => $orderId, 'status' => $_POST['status'] ?? 'pending']);
                    } catch (\Throwable $throwable) {
                        $errors[] = $throwable->getMessage();
                    }
                } elseif ($action === 'resend_delivery' && $orderId > 0) {
                    $order = Order::findById($this->pdo, $orderId);
                    if ($order) {
                        $crypto = $GLOBALS['crypto'] ?? null;
                        if ($crypto instanceof \App\Core\Crypto) {
                            $deliveryService = new DeliveryService($this->pdo, $crypto, $GLOBALS['mailer'] ?? null);
                            $full = Order::findWithItems($this->pdo, $orderId);
                            if ($full) {
                                $deliveryService->fulfillOrder($full);
                                $messages[] = 'Teslimat yeniden tetiklendi.';
                                $logger->record($this->auth?->user()['id'] ?? null, 'order_resend', ['order_id' => $orderId]);
                            }
                        }
                    }
                }
            }
        }

        $orders = Order::allWithUser($this->pdo);
        $this->view->render('admin/orders', [
            'title' => 'Sipariş Yönetimi',
            'orders' => $orders,
            'csrf' => $csrf,
            'messages' => $messages,
            'errors' => $errors,
        ], 'admin');
    }
}
