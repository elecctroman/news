<?php
namespace App\Controllers\Front;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Totp;
use App\Models\Order;
use App\Models\OrderItemDelivery;
use App\Models\UserAddress;
use App\Services\DeliveryService;

class AccountController extends Controller
{
    public function index(): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $orders = Order::listForUser($this->pdo, (int) $user['id']);
        $addresses = UserAddress::list($this->pdo, (int) $user['id']);

        $this->view->render('front/account', [
            'title' => 'Hesabım',
            'user' => $user,
            'orders' => array_slice($orders, 0, 5),
            'addresses' => $addresses,
        ]);
    }

    public function orders(): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $orders = Order::listForUser($this->pdo, (int) $user['id']);
        $this->view->render('front/orders', [
            'title' => 'Siparişlerim',
            'orders' => $orders,
        ]);
    }

    public function orderDetail(int $orderId): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $order = Order::findWithItems($this->pdo, $orderId, (int) $user['id']);
        if (!$order) {
            http_response_code(404);
            $this->view->render('front/product-not-found', ['title' => 'Sipariş bulunamadı']);
            return;
        }
        $crypto = $GLOBALS['crypto'] ?? null;
        $mailer = $GLOBALS['mailer'] ?? null;
        $deliveryService = $crypto instanceof \App\Core\Crypto ? new DeliveryService($this->pdo, $crypto, $mailer) : null;
        $deliveries = [];
        foreach ($order['items'] as &$item) {
            $delivery = OrderItemDelivery::findByOrderItem($this->pdo, (int) $item['id']);
            if ($delivery && $deliveryService) {
                $payload = $deliveryService->getPayload($delivery);
                $deliveries[$item['id']] = ['delivery' => $delivery, 'payload' => $payload];
            }
        }
        unset($item);

        $this->view->render('front/order-detail', [
            'title' => 'Sipariş #' . $orderId,
            'order' => $order,
            'deliveries' => $deliveries,
            'csrf' => $GLOBALS['csrf'] ?? new Csrf(),
            'messages' => $_SESSION['order_messages'] ?? [],
        ]);
        unset($_SESSION['order_messages']);
    }

    public function downloadInvoice(int $orderId): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $order = Order::findWithItems($this->pdo, $orderId, (int) $user['id']);
        if (!$order) {
            http_response_code(404);
            echo 'Sipariş bulunamadı.';
            return;
        }
        $pdf = $this->buildSimplePdf($order);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice-' . $orderId . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        $logger = new AuditLogger($this->pdo);
        $logger->record((int) $user['id'], 'download_invoice', ['order_id' => $orderId]);
        exit;
    }

    public function downloadInvoiceCsv(int $orderId): void
    {
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $order = Order::findWithItems($this->pdo, $orderId, (int) $user['id']);
        if (!$order) {
            http_response_code(404);
            echo 'Sipariş bulunamadı.';
            return;
        }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="invoice-' . $orderId . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ürün', 'Adet', 'Birim Fiyat', 'Ara Toplam']);
        foreach ($order['items'] as $item) {
            fputcsv($out, [$item['product_name'], $item['quantity'], $item['unit_price'], $item['subtotal']]);
        }
        fclose($out);
        $logger = new AuditLogger($this->pdo);
        $logger->record((int) $user['id'], 'download_invoice_csv', ['order_id' => $orderId]);
        exit;
    }

    public function generateLink(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        $user = $this->auth?->user();
        if ($user === null) {
            header('Location: /login');
            return;
        }
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['order_messages'][] = 'Güvenlik doğrulaması başarısız.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/account'));
            return;
        }
        $itemId = (int) ($_POST['order_item_id'] ?? 0);
        $delivery = OrderItemDelivery::findByOrderItem($this->pdo, $itemId);
        if (!$delivery) {
            $_SESSION['order_messages'][] = 'Teslimat bulunamadı.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/account'));
            return;
        }
        $crypto = $GLOBALS['crypto'] ?? null;
        if (!$crypto instanceof \App\Core\Crypto) {
            $_SESSION['order_messages'][] = 'Bağlantı oluşturulamadı.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/account'));
            return;
        }
        $deliveryService = new DeliveryService($this->pdo, $crypto, $GLOBALS['mailer'] ?? null);
        $token = $deliveryService->generateOneTimeLink((int) $delivery['id']);
        $_SESSION['order_messages'][] = 'Tek kullanımlık bağlantı oluşturuldu: ' . $this->config['app']['url'] . '/account/delivery/' . $token;
        $logger = new AuditLogger($this->pdo);
        $logger->record((int) $user['id'], 'generate_delivery_link', ['order_item_id' => $itemId]);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/account'));
        exit;
    }

    public function viewDelivery(string $token): void
    {
        $delivery = OrderItemDelivery::consumeLink($this->pdo, $token);
        if (!$delivery) {
            http_response_code(410);
            $this->view->render('front/product-not-found', ['title' => 'Bağlantı geçersiz']);
            return;
        }
        $crypto = $GLOBALS['crypto'] ?? null;
        if (!$crypto instanceof \App\Core\Crypto) {
            http_response_code(500);
            echo 'Teslimat görüntülenemedi.';
            return;
        }
        $service = new DeliveryService($this->pdo, $crypto, $GLOBALS['mailer'] ?? null);
        $payload = $service->getPayload($delivery);
        $this->view->render('front/delivery-view', [
            'title' => 'Teslimat Detayı',
            'payload' => $payload,
        ]);
    }

    /**
     * @param array<string,mixed> $order
     */
    private function buildSimplePdf(array $order): string
    {
        $lines = [
            'Sipariş #' . $order['id'],
            'Tarih: ' . ($order['created_at'] ?? date('Y-m-d')),
            'Durum: ' . $order['status'],
            'Toplam: ' . number_format((float) $order['total_amount'], 2) . ' ' . $order['currency'],
            'Kalemler:',
        ];
        foreach ($order['items'] as $item) {
            $lines[] = sprintf('- %s x%d = %0.2f', $item['product_name'], $item['quantity'], $item['subtotal']);
        }

        $content = "BT\n/F1 12 Tf\n50 800 Td\n";
        foreach ($lines as $line) {
            $content .= '(' . $this->escapePdfText($line) . ") Tj\n0 -16 Td\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
        $objects[] = "2 0 obj<< /Type /Pages /Count 1 /Kids [3 0 R] >>endobj\n";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n";
        $objects[] = "4 0 obj<< /Length " . strlen($content) . " >>stream\n$content\nendstream endobj\n";
        $objects[] = "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

        $pdf = "%PDF-1.4\n";
        $offset = strlen($pdf);
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = $offset;
            $pdf .= $object;
            $offset += strlen($object);
        }
        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d %05d f \n", 0, 65535);
        $running = strlen("%PDF-1.4\n");
        foreach ($objects as $object) {
            $pdf .= sprintf("%010d %05d n \n", $running, 0);
            $running += strlen($object);
        }
        $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF";
        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    public function security(): void
    {
        $csrf = $GLOBALS['csrf'] ?? new Csrf();
        $user = $this->auth?->user();
        $message = null;
        $errors = [];

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik tokenı.';
            } else {
                $action = $_POST['action'] ?? '';
                if ($action === 'enable') {
                    $secret = $_SESSION['2fa_setup_secret'] ?? Totp::generateSecret();
                    $_SESSION['2fa_setup_secret'] = $secret;
                    $code = $_POST['code'] ?? '';
                    if (Totp::verify($secret, $code)) {
                        $recoveryCodes = [];
                        for ($i = 0; $i < 5; $i++) {
                            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(4)));
                        }
                        $this->auth?->enableTwoFactor((int) $user['id'], $secret, $recoveryCodes);
                        unset($_SESSION['2fa_setup_secret']);
                        $_SESSION['twofa_verified_at'] = time();
                        $_SESSION['recovery_codes_once'] = $recoveryCodes;
                        $message = 'İki adımlı doğrulama etkinleştirildi.';
                        $user = $this->auth?->user();
                    } else {
                        $errors[] = 'Kod doğrulanamadı.';
                    }
                } elseif ($action === 'disable') {
                    $this->auth?->disableTwoFactor((int) $user['id']);
                    unset($_SESSION['2fa_setup_secret']);
                    $message = 'İki adımlı doğrulama kapatıldı.';
                    $user = $this->auth?->user();
                }
            }
        }

        $secret = $_SESSION['2fa_setup_secret'] ?? $user['twofa_secret'] ?? Totp::generateSecret();
        $_SESSION['2fa_setup_secret'] = $secret;
        $qrUri = Totp::getQrUri($secret, $user['email'], $this->config['app']['name']);
        $recoveryCodes = $_SESSION['recovery_codes_once'] ?? [];
        unset($_SESSION['recovery_codes_once']);

        $this->view->render('front/security', [
            'title' => 'Güvenlik',
            'user' => $user,
            'secret' => $secret,
            'qr_uri' => $qrUri,
            'message' => $message,
            'errors' => $errors,
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
