<?php
namespace App\Controllers\Front;

use App\Core\Cart;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Crypto;
use App\Core\Mailer;
use App\Models\OrderItemDelivery;
use App\Services\DeliveryService;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    private Csrf $csrf;

    public function __construct()
    {
        parent::__construct();
        $this->csrf = $GLOBALS['csrf'] ?? new Csrf();
    }

    public function mock(): void
    {
        $limiter = $GLOBALS['limiter'] ?? null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($limiter && !$limiter->hit('payment:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'), 5, 60)) {
                http_response_code(429);
                echo 'Çok fazla deneme yapıldı. Lütfen daha sonra tekrar deneyin.';
                return;
            }
            $this->handleMockCallback();
            return;
        }

        $token = $_GET['token'] ?? null;
        $amount = $_GET['amount'] ?? null;
        $currency = $_GET['currency'] ?? null;
        if (!$token) {
            http_response_code(400);
            echo 'Geçersiz ödeme isteği.';
            return;
        }

        $this->view->render('front/payment-mock', [
            'title' => 'Mock Ödeme',
            'token' => $token,
            'amount' => $amount,
            'currency' => $currency,
            'csrf' => $this->csrf,
        ]);
    }

    private function handleMockCallback(): void
    {
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Token doğrulanamadı.';
            return;
        }
        $token = $_POST['token'] ?? '';
        $status = $_POST['result'] ?? 'failed';
        $paymentService = new PaymentService($this->pdo, $this->config);
        $result = $paymentService->complete($token, $status);
        $order = $result['order'];
        if ($result['status'] === 'succeeded' && $order) {
            $mailer = $GLOBALS['mailer'] ?? null;
            $crypto = $GLOBALS['crypto'] ?? null;
            if ($crypto instanceof Crypto) {
                $deliveryService = new DeliveryService($this->pdo, $crypto, $mailer instanceof Mailer ? $mailer : null);
                $deliveries = $deliveryService->fulfillOrder($order);
                if (!empty($deliveries)) {
                    foreach ($deliveries as $delivery) {
                        OrderItemDelivery::markViewed($this->pdo, (int) $delivery['id']);
                    }
                }
            }
            if (isset($_SESSION['pending_order_id']) && (int) $_SESSION['pending_order_id'] === (int) $order['id']) {
                (new Cart())->clear();
                unset($_SESSION['pending_order_id']);
            }
            $_SESSION['order_success_id'] = $order['id'];
            header('Location: /order-success?order_id=' . $order['id']);
            exit;
        }

        header('Location: /checkout?failed=1');
        exit;
    }
}
