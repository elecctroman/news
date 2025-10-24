<?php
namespace App\Controllers\Front;

use App\Core\Cart;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\PaymentService;

class CheckoutController extends Controller
{
    private Cart $cart;
    private Csrf $csrf;

    public function __construct()
    {
        parent::__construct();
        $this->cart = new Cart();
        $this->csrf = $GLOBALS['csrf'] ?? new Csrf();
    }

    public function index(): void
    {
        if ($this->cart->isEmpty()) {
            header('Location: /cart');
            return;
        }
        $user = $this->auth?->user();
        $addresses = $user ? UserAddress::list($this->pdo, (int) $user['id']) : [];
        $coupon = null;
        $subtotal = $this->cart->subtotal();
        $discount = 0;
        if ($this->cart->couponCode()) {
            $coupon = Coupon::findActiveByCode($this->pdo, $this->cart->couponCode());
            if ($coupon) {
                $discount = Coupon::calculateDiscount($coupon, $subtotal);
            }
        }
        $total = max($subtotal - $discount, 0);

        $this->view->render('front/checkout', [
            'title' => 'Ödeme',
            'cart' => $this->cart->items(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'coupon' => $coupon,
            'csrf' => $this->csrf,
            'addresses' => $addresses,
            'errors' => $_SESSION['checkout_errors'] ?? [],
            'currency' => $this->cart->currency(),
        ]);
        unset($_SESSION['checkout_errors']);
    }

    public function process(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if ($this->cart->isEmpty()) {
            header('Location: /cart');
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['checkout_errors'] = ['Güvenlik doğrulaması başarısız.'];
            header('Location: /checkout');
            return;
        }
        $user = $this->auth?->user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $billingName = trim($_POST['billing_name'] ?? '');
        $billingAddress = trim($_POST['billing_address'] ?? '');
        $billingCity = trim($_POST['billing_city'] ?? '');
        $billingCountry = trim($_POST['billing_country'] ?? '');
        $gateway = $_POST['payment_method'] ?? 'mock';
        $simulate = $_POST['simulate'] ?? 'success';
        $errors = [];
        if ($billingName === '' || $billingAddress === '' || $billingCity === '' || $billingCountry === '') {
            $errors[] = 'Fatura bilgileri zorunludur.';
        }
        if ($errors) {
            $_SESSION['checkout_errors'] = $errors;
            header('Location: /checkout');
            return;
        }

        UserAddress::save($this->pdo, (int) $user['id'], [
            'type' => 'billing',
            'full_name' => $billingName,
            'line1' => $billingAddress,
            'city' => $billingCity,
            'country' => $billingCountry,
            'line2' => $_POST['billing_line2'] ?? null,
            'tax_number' => $_POST['billing_tax'] ?? null,
        ]);

        $items = $this->cart->items();
        $subtotal = $this->cart->subtotal();
        $coupon = null;
        $discount = 0;
        if ($this->cart->couponCode()) {
            $coupon = Coupon::findActiveByCode($this->pdo, $this->cart->couponCode());
            if ($coupon) {
                $discount = Coupon::calculateDiscount($coupon, $subtotal);
            }
        }
        $total = max($subtotal - $discount, 0);
        $idempotencyKey = hash('sha256', session_id() . microtime(true));
        $orderId = Order::createWithItems($this->pdo, (int) $user['id'], $items, [
            'total' => $total,
            'discount' => $discount,
            'currency' => $this->cart->currency(),
        ], $coupon, $gateway, $idempotencyKey);
        $order = Order::findById($this->pdo, $orderId);
        if (!$order) {
            $_SESSION['checkout_errors'] = ['Sipariş oluşturulamadı.'];
            header('Location: /checkout');
            return;
        }

        $paymentService = new PaymentService($this->pdo, $this->config);
        $response = $paymentService->initiate($order, $gateway, [
            'callback_url' => '/payment/mock',
            'simulate' => $simulate,
        ]);
        $_SESSION['pending_order_id'] = $orderId;
        header('Location: ' . $response['redirect_url'] . '&simulate=' . urlencode($simulate) . '&gateway=' . urlencode($gateway));
        exit;
    }

    public function success(): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $orderId = $_SESSION['order_success_id'] ?? null;
        if ($orderId === null) {
            header('Location: /account/orders');
            return;
        }
        unset($_SESSION['order_success_id']);
        $order = Order::findWithItems($this->pdo, (int) $orderId, (int) $user['id']);
        if ($order === null) {
            header('Location: /account/orders');
            return;
        }
        $this->view->render('front/order-success', [
            'title' => 'Sipariş Başarılı',
            'order' => $order,
        ]);
    }
}
