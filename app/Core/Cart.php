<?php
namespace App\Core;

/**
 * Basit sepet yöneticisi.
 */
class Cart
{
    private const SESSION_KEY = 'cart_items';
    private const COUPON_KEY = 'cart_coupon';

    /**
     * @return array<int, array>
     */
    public function items(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public function add(int $productId, ?int $variantId, string $name, string $currency, float $unitPrice, int $quantity, string $type): void
    {
        $items = $this->items();
        $rowId = $this->rowId($productId, $variantId);
        if (isset($items[$rowId])) {
            $items[$rowId]['qty'] += $quantity;
        } else {
            $items[$rowId] = [
                'row_id' => $rowId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $name,
                'currency' => $currency,
                'unit_price' => $unitPrice,
                'qty' => $quantity,
                'type' => $type,
            ];
        }

        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function update(string $rowId, int $quantity): void
    {
        $items = $this->items();
        if (!isset($items[$rowId])) {
            return;
        }
        if ($quantity <= 0) {
            unset($items[$rowId]);
        } else {
            $items[$rowId]['qty'] = $quantity;
        }
        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function remove(string $rowId): void
    {
        $items = $this->items();
        unset($items[$rowId]);
        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::COUPON_KEY]);
    }

    public function subtotal(): float
    {
        $total = 0.0;
        foreach ($this->items() as $item) {
            $total += (float) $item['unit_price'] * (int) $item['qty'];
        }
        return round($total, 2);
    }

    public function currency(): string
    {
        $items = $this->items();
        if ($items) {
            return $items[array_key_first($items)]['currency'];
        }
        return 'TRY';
    }

    public function setCouponCode(?string $code): void
    {
        if ($code === null || $code === '') {
            unset($_SESSION[self::COUPON_KEY]);
            return;
        }
        $_SESSION[self::COUPON_KEY] = strtoupper($code);
    }

    public function couponCode(): ?string
    {
        return $_SESSION[self::COUPON_KEY] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->items());
    }

    private function rowId(int $productId, ?int $variantId): string
    {
        return $productId . ':' . ($variantId ?? 'base');
    }
}
