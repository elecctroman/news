<?php
namespace App\Controllers\Front;

use App\Core\Cart;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Coupon;
use App\Models\Product;

class CartController extends Controller
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
        $items = $this->cart->items();
        $couponCode = $this->cart->couponCode();
        $coupon = null;
        $discount = 0;
        $subtotal = $this->cart->subtotal();
        if ($couponCode) {
            $coupon = Coupon::findActiveByCode($this->pdo, $couponCode);
            if ($coupon) {
                $discount = Coupon::calculateDiscount($coupon, $subtotal);
            } else {
                $_SESSION['cart_error'] = 'Kupon geçersiz veya süresi dolmuş.';
                $this->cart->setCouponCode(null);
            }
        }

        $total = max($subtotal - $discount, 0);
        $this->view->render('front/cart', [
            'title' => 'Sepetim',
            'cart' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'coupon' => $coupon,
            'csrf' => $this->csrf,
            'message' => $_SESSION['cart_message'] ?? null,
            'error' => $_SESSION['cart_error'] ?? null,
        ]);
        unset($_SESSION['cart_message'], $_SESSION['cart_error']);
    }

    public function add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['cart_error'] = 'Güvenlik doğrulaması başarısız.';
            $this->redirect('/cart');
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $variantId = $_POST['variant_id'] !== '' ? (int) $_POST['variant_id'] : null;
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $product = Product::findById($this->pdo, $productId);
        if (!$product) {
            $_SESSION['cart_error'] = 'Ürün bulunamadı.';
            $this->redirect('/cart');
        }

        $unitPrice = (float) $product['base_price'];
        $type = $product['type'];
        if ($variantId !== null) {
            $variant = null;
            foreach ($product['variants'] as $var) {
                if ((int) $var['id'] === $variantId) {
                    $variant = $var;
                    break;
                }
            }
            if ($variant) {
                $unitPrice += (float) $variant['price_adjustment'];
            } else {
                $variantId = null;
            }
        }

        $this->cart->add($productId, $variantId, $product['name'], $product['currency'] ?? 'TRY', $unitPrice, $quantity, $type);
        $_SESSION['cart_message'] = 'Ürün sepetinize eklendi.';
        $this->redirect('/cart');
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['cart_error'] = 'Güvenlik doğrulaması başarısız.';
            $this->redirect('/cart');
        }
        $rowId = $_POST['row_id'] ?? '';
        $quantity = max(0, (int) ($_POST['quantity'] ?? 1));
        $this->cart->update($rowId, $quantity);
        $_SESSION['cart_message'] = 'Sepet güncellendi.';
        $this->redirect('/cart');
    }

    public function remove(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['cart_error'] = 'Güvenlik doğrulaması başarısız.';
            $this->redirect('/cart');
        }
        $rowId = $_POST['row_id'] ?? '';
        $this->cart->remove($rowId);
        $_SESSION['cart_message'] = 'Ürün sepetten çıkarıldı.';
        $this->redirect('/cart');
    }

    public function applyCoupon(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!$this->csrf->validateToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['cart_error'] = 'Güvenlik doğrulaması başarısız.';
            $this->redirect('/cart');
        }
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        if ($code === '') {
            $this->cart->setCouponCode(null);
            $_SESSION['cart_message'] = 'Kupon kaldırıldı.';
            $this->redirect('/cart');
        }
        $coupon = Coupon::findActiveByCode($this->pdo, $code);
        if (!$coupon) {
            $_SESSION['cart_error'] = 'Kupon bulunamadı.';
        } else {
            $this->cart->setCouponCode($code);
            $_SESSION['cart_message'] = 'Kupon uygulandı.';
        }
        $this->redirect('/cart');
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
