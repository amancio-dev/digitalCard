<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\CartPricing;
use App\Services\ShippingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public const COUPONS = [
        'DIGITAL10' => [
            'code' => 'DIGITAL10',
            'type' => 'percent',
            'value' => 10.0,
            'min_total' => 0.0,
            'label' => '10% OFF no DigitalCard',
        ],
        'BEMVINDO50' => [
            'code' => 'BEMVINDO50',
            'type' => 'fixed',
            'value' => 50.0,
            'min_total' => 500.0,
            'label' => 'R$ 50,00 OFF em compras acima de R$ 500',
        ],
        'CASHBACKTURBO' => [
            'code' => 'CASHBACKTURBO',
            'type' => 'percent',
            'value' => 5.0,
            'min_total' => 0.0,
            'label' => '5% OFF Imediato + Cashback Proporcional',
        ],
    ];

    public function index(Request $request, CartPricing $pricing, ShippingCalculator $shippingCalculator): View
    {
        if ($request->user()) {
            self::syncSessionWithDatabase($request);
        }

        $cart = $request->session()->get('cart', []);
        $activeCoupon = $request->session()->get('cart_coupon');
        $cep = $request->session()->get('shipping_cep');

        $summary = $pricing->calculate(
            rawCart: $cart,
            couponCode: $activeCoupon,
            user: $request->user(),
            cep: $cep,
        );

        if ($activeCoupon && ! $summary['activeCoupon']) {
            $request->session()->forget('cart_coupon');
        }

        $checkoutToken = (string) Str::uuid();
        $request->session()->put('checkout_token', $checkoutToken);

        // Exibe apenas os models Coupon válidos para o usuário e subtotal atual
        $availableCoupons = Coupon::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->filter(fn (Coupon $coupon) => $coupon->isValidForUser($request->user(), $summary['subtotal_cents']))
            ->values();

        $placeholderCouponCode = $availableCoupons->first()?->code
            ?? Coupon::where('is_active', true)->orderBy('code')->value('code')
            ?? 'DIGITAL10';

        $shippingQuote = $cep && $summary['subtotal_cents'] > 0
            ? $shippingCalculator->calculate($cep, max(0, $summary['subtotal_cents'] - $summary['discount_cents']))
            : null;

        return view('cart.index', [
            'items' => $summary['items'],
            'subtotalAll' => $summary['subtotalAll'],
            'originalTotal' => $summary['originalTotal'],
            'discountAmount' => $summary['discountAmount'],
            'activeCoupon' => $summary['activeCoupon'],
            'availableCoupons' => $availableCoupons,
            'placeholderCouponCode' => $placeholderCouponCode,
            'shippingCost' => $summary['shippingCost'],
            'shippingQuote' => $shippingQuote,
            'total' => $summary['total'],
            'cashbackTotal' => $summary['cashbackTotal'],
            'priceWarnings' => $summary['price_warnings'],
            'checkoutToken' => $checkoutToken,
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = $request->session()->get('cart', []);
        $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($product->stock < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Este produto está sem estoque no momento.',
            ]);
        }

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Estoque insuficiente. Disponível: {$product->stock} unidade(s).",
            ]);
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'quantity' => $newQuantity,
            'price_snapshot' => (float) $product->price,
        ];

        $request->session()->put('cart', $cart);

        if ($request->user()) {
            CartItem::updateOrCreate(
                ['user_id' => $request->user()->id, 'product_id' => $product->id],
                ['quantity' => $newQuantity, 'price_snapshot' => (float) $product->price]
            );
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('cart.index')->with('success', "{$product->name} pronto para finalizar a compra!");
        }

        return back()->with('success', "{$product->name} adicionado ao carrinho.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (! $product->is_active) {
            $cart = $request->session()->get('cart', []);
            unset($cart[$product->id]);
            $request->session()->put('cart', $cart);

            if ($request->user()) {
                CartItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();
            }

            abort(404, 'Este produto não está mais ativo no catálogo.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = $request->session()->get('cart', []);

        if ((int) $validated['quantity'] === 0) {
            unset($cart[$product->id]);
            $request->session()->put('cart', $cart);

            if ($request->user()) {
                CartItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();
            }

            return back()->with('success', 'Item removido do carrinho.');
        }

        if ((int) $validated['quantity'] > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => "Estoque insuficiente. Disponível: {$product->stock} unidade(s).",
            ]);
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'quantity' => (int) $validated['quantity'],
            'price_snapshot' => (float) ($cart[$product->id]['price_snapshot'] ?? $product->price),
        ];

        $request->session()->put('cart', $cart);

        if ($request->user()) {
            CartItem::updateOrCreate(
                ['user_id' => $request->user()->id, 'product_id' => $product->id],
                [
                    'quantity' => (int) $validated['quantity'],
                    'price_snapshot' => (float) ($cart[$product->id]['price_snapshot'] ?? $product->price),
                ]
            );
        }

        return back()->with('success', 'Quantidade atualizada.');
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        if ($request->user()) {
            CartItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();
        }

        return back()->with('success', 'Item removido do carrinho.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(['cart', 'cart_coupon']);

        if ($request->user()) {
            CartItem::where('user_id', $request->user()->id)->delete();
        }

        return back()->with('success', 'Carrinho esvaziado.');
    }

    public function applyCoupon(Request $request, CartPricing $pricing): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->input('coupon_code')));

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            return back()->with('error', "O cupom \"{$code}\" é inválido ou está inativo.");
        }

        $summary = $pricing->calculate(
            rawCart: $request->session()->get('cart', []),
            couponCode: null,
            user: $request->user(),
        );

        if (! $coupon->isValidForUser($request->user(), $summary['subtotal_cents'])) {
            if ($summary['subtotal_cents'] < CartPricing::toCents((float) $coupon->min_total)) {
                return back()->with(
                    'warning',
                    "O cupom {$code} exige valor mínimo de R$ ".number_format((float) $coupon->min_total, 2, ',', '.').' no carrinho.'
                );
            }

            return back()->with('error', "O cupom \"{$code}\" expirou ou atingiu o limite máximo de utilizações.");
        }

        $request->session()->put('cart_coupon', $code);

        return back()->with('success', "Cupom {$code} ({$coupon->label}) aplicado com sucesso!");
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $request->session()->forget('cart_coupon');

        return back()->with('success', 'Cupom de desconto removido.');
    }

    public static function mergeSessionIntoDatabase(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        self::syncSessionWithDatabase($request);

        $sessionWishlist = array_map('intval', $request->session()->get('wishlist', []));
        foreach ($sessionWishlist as $productId) {
            if (Product::where('id', $productId)->exists()) {
                Wishlist::firstOrCreate([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }

        $allWishlisted = Wishlist::where('user_id', $user->id)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $request->session()->put('wishlist', $allWishlisted);
    }

    public static function syncSessionWithDatabase(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $sessionCart = $request->session()->get('cart', []);

        foreach ($sessionCart as $productId => $entry) {
            $pid = (int) ($entry['product_id'] ?? $productId);
            $product = Product::find($pid);
            if (! $product || ! $product->is_active || $product->stock < 1) {
                continue;
            }

            $qty = min((int) $entry['quantity'], (int) $product->stock);
            $snapshot = (float) ($entry['price_snapshot'] ?? $product->price);

            CartItem::updateOrCreate(
                ['user_id' => $user->id, 'product_id' => $pid],
                ['quantity' => $qty, 'price_snapshot' => $snapshot]
            );
        }

        $mergedCart = [];
        foreach (CartItem::where('user_id', $user->id)->with('product')->get() as $item) {
            if ($item->product && $item->product->is_active && $item->product->stock > 0) {
                $qty = min((int) $item->quantity, (int) $item->product->stock);
                $mergedCart[$item->product_id] = [
                    'product_id' => $item->product_id,
                    'quantity' => $qty,
                    'price_snapshot' => (float) ($item->price_snapshot ?? $item->product->price),
                ];
            }
        }

        $request->session()->put('cart', $mergedCart);
    }
}
