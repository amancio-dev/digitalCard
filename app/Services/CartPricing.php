<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class CartPricing
{
    public static function toCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function toDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Calcula o resumo completo do carrinho/checkout usando exclusivamente centavos inteiros (S10).
     *
     * @param  array<int|string, array{product_id?: int, quantity: int, price_snapshot?: float|null}>  $rawCart
     * @param  Collection<int, Product>|null  $preloadedProducts
     * @return array{
     *   items: list<array{
     *     product: Product,
     *     quantity: int,
     *     unit_price_cents: int,
     *     unit_price: float,
     *     subtotal_cents: int,
     *     subtotal: float,
     *     original_subtotal_cents: int,
     *     original_subtotal: float,
     *     paid_subtotal_cents: int,
     *     cashback_percent: float,
     *     cashback_cents: int,
     *     cashback: float,
     *     price_changed: bool,
     *     previous_price: float|null
     *   }>,
     *   subtotal_cents: int,
     *   subtotalAll: float,
     *   original_total_cents: int,
     *   originalTotal: float,
     *   discount_cents: int,
     *   discountAmount: float,
     *   coupon: Coupon|null,
     *   activeCoupon: string|null,
     *   shipping_cents: int,
     *   shippingCost: float,
     *   total_cents: int,
     *   total: float,
     *   cashback_total_cents: int,
     *   cashbackTotal: float,
     *   price_warnings: list<string>
     * }
     */
    public function calculate(
        array $rawCart,
        ?string $couponCode = null,
        ?User $user = null,
        ?string $cep = null,
        ?Collection $preloadedProducts = null,
    ): array {
        $productIds = array_map('intval', array_keys($rawCart));
        $products = $preloadedProducts ?? Product::with('category')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $maxCashbackPercent = (float) config('digitalcard.max_cashback_percent', 15.0);

        $lineItems = [];
        $subtotalCents = 0;
        $originalTotalCents = 0;
        $priceWarnings = [];

        foreach ($rawCart as $key => $entry) {
            $productId = (int) ($entry['product_id'] ?? $key);
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product || ! $product->is_active || $product->stock < 1) {
                continue;
            }

            $quantity = min(max(1, (int) $entry['quantity']), (int) $product->stock);
            $unitPriceCents = self::toCents((float) $product->price);
            $origUnitCents = self::toCents((float) $product->original_price);

            $lineSubtotalCents = $unitPriceCents * $quantity;
            $lineOrigSubtotalCents = $origUnitCents * $quantity;

            $priceChanged = false;
            $previousPrice = isset($entry['price_snapshot']) ? (float) $entry['price_snapshot'] : null;

            if ($previousPrice !== null && self::toCents($previousPrice) !== $unitPriceCents) {
                $priceChanged = true;
                $priceWarnings[] = sprintf(
                    'O preço de "%s" foi atualizado de R$ %s para R$ %s.',
                    $product->name,
                    number_format($previousPrice, 2, ',', '.'),
                    number_format(self::toDecimal($unitPriceCents), 2, ',', '.')
                );
            }

            $effectiveCbPercent = min(max(0.0, (float) $product->cashback_percent), $maxCashbackPercent);

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'unit_price' => self::toDecimal($unitPriceCents),
                'subtotal_cents' => $lineSubtotalCents,
                'subtotal' => self::toDecimal($lineSubtotalCents),
                'original_subtotal_cents' => $lineOrigSubtotalCents,
                'original_subtotal' => self::toDecimal($lineOrigSubtotalCents),
                'paid_subtotal_cents' => $lineSubtotalCents,
                'cashback_percent' => $effectiveCbPercent,
                'cashback_cents' => 0,
                'cashback' => 0.0,
                'price_changed' => $priceChanged,
                'previous_price' => $previousPrice,
            ];

            $subtotalCents += $lineSubtotalCents;
            $originalTotalCents += $lineOrigSubtotalCents;
        }

        // Resolução e validação do cupom (banco de dados)
        $coupon = null;
        $discountCents = 0;
        $activeCouponCode = null;

        if ($couponCode && $subtotalCents > 0) {
            $normalizedCode = strtoupper(trim($couponCode));
            $candidate = Coupon::where('code', $normalizedCode)->first();

            if ($candidate && $candidate->isValidForUser($user, $subtotalCents)) {
                $coupon = $candidate;
                $activeCouponCode = $candidate->code;

                if ($candidate->type === Coupon::TYPE_PERCENT) {
                    $discountCents = (int) round($subtotalCents * ((float) $candidate->value / 100.0));
                } else {
                    $discountCents = min($subtotalCents, self::toCents((float) $candidate->value));
                }

                // Garante total mínimo de R$ 0,01 quando há itens no carrinho
                $discountCents = min($discountCents, max(0, $subtotalCents - 1));
            }
        }

        $discountedMerchandiseCents = max(0, $subtotalCents - $discountCents);

        // Rateio proporcional do desconto entre os itens e cálculo do cashback SOBRE O VALOR EFETIVAMENTE PAGO
        $cashbackTotalCents = 0;
        $remainingDiscount = $discountCents;
        $lastIndex = count($lineItems) - 1;

        foreach ($lineItems as $idx => &$line) {
            if ($discountCents > 0 && $subtotalCents > 0) {
                $itemDiscount = ($idx === $lastIndex)
                    ? $remainingDiscount
                    : (int) round($discountCents * ($line['subtotal_cents'] / $subtotalCents));
                $itemDiscount = min($itemDiscount, $line['subtotal_cents']);
                $remainingDiscount -= $itemDiscount;
                $line['paid_subtotal_cents'] = max(0, $line['subtotal_cents'] - $itemDiscount);
            }

            $lineCashbackCents = (int) round($line['paid_subtotal_cents'] * ($line['cashback_percent'] / 100.0));
            $line['cashback_cents'] = $lineCashbackCents;
            $line['cashback'] = self::toDecimal($lineCashbackCents);
            $cashbackTotalCents += $lineCashbackCents;
        }
        unset($line);

        // Cálculo de frete (só aplica quando há CEP informado; caso contrário é 0.00 até informar CEP)
        $shippingCents = 0;
        if ($cep !== null && trim($cep) !== '' && $discountedMerchandiseCents > 0) {
            $shippingQuote = app(ShippingCalculator::class)->calculate($cep, $discountedMerchandiseCents);
            $shippingCents = $shippingQuote['cost_cents'];
        }

        $totalCents = $discountedMerchandiseCents + $shippingCents;

        return [
            'items' => $lineItems,
            'subtotal_cents' => $subtotalCents,
            'subtotalAll' => self::toDecimal($subtotalCents),
            'original_total_cents' => $originalTotalCents,
            'originalTotal' => self::toDecimal($originalTotalCents),
            'discount_cents' => $discountCents,
            'discountAmount' => self::toDecimal($discountCents),
            'coupon' => $coupon,
            'activeCoupon' => $activeCouponCode,
            'shipping_cents' => $shippingCents,
            'shippingCost' => self::toDecimal($shippingCents),
            'total_cents' => $totalCents,
            'total' => self::toDecimal($totalCents),
            'cashback_total_cents' => $cashbackTotalCents,
            'cashbackTotal' => self::toDecimal($cashbackTotalCents),
            'price_warnings' => $priceWarnings,
        ];
    }
}
