<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\CardTransaction;
use App\Models\CartItem;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\VirtualCard;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\CartPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Finaliza a compra descontando do Cartão Virtual (S5, S9, S10, F2, F4).
     */
    public function checkout(Request $request, CartPricing $pricing): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_cep' => ['nullable', 'string', 'max:15'],
            'card_pin' => ['nullable', 'digits:4'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Idempotência — se já existir pedido com esta chave para o usuário, retorna o pedido existente sem cobrar 2x
        $idempotencyKey = $validated['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existingOrder = Order::where('idempotency_key', $idempotencyKey)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingOrder) {
                return redirect()
                    ->route('orders.show', $existingOrder)
                    ->with('warning', 'Este pedido já foi processado anteriormente (proteção contra clique duplo).');
            }
        }

        $request->session()->put('shipping_address', $validated['shipping_address']);
        if (! empty($validated['shipping_cep'])) {
            $request->session()->put('shipping_cep', $validated['shipping_cep']);
        }

        $sessionCart = $request->session()->get('cart', []);
        $requestedItems = collect($validated['items'] ?? $sessionCart)
            ->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'price_snapshot' => $item['price_snapshot'] ?? ($sessionCart[$item['product_id']]['price_snapshot'] ?? null),
            ])
            ->groupBy('product_id')
            ->map(fn ($items, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $items->sum('quantity'),
                'price_snapshot' => $items->first()['price_snapshot'] ?? null,
            ])
            ->keyBy('product_id')
            ->all();

        if ($requestedItems === []) {
            throw ValidationException::withMessages([
                'cart' => 'Seu carrinho está vazio. Adicione produtos antes de finalizar a compra.',
            ]);
        }

        $userId = (int) $request->user()->id;
        $pinThrottleKey = VirtualCardController::pinThrottleKey($userId);

        if (RateLimiter::tooManyAttempts($pinThrottleKey, VirtualCardController::PIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($pinThrottleKey);
            $minutes = (int) ceil($seconds / 60);

            throw ValidationException::withMessages([
                'card_pin' => "Muitas tentativas incorretas de PIN. Seu cartão foi temporariamente protegido por {$minutes} minuto(s) ({$seconds}s).",
            ]);
        }

        $initialCard = $request->user()->virtualCard()->firstOrCreate([], [
            'card_number' => VirtualCard::generateUniqueCardNumber(),
            'cvv' => VirtualCard::generateCvv(),
            'expiration_date' => VirtualCard::DEFAULT_EXPIRATION_DATE,
            'balance' => 0.00,
            'cashback_earned' => 0.00,
        ]);

        if (! $initialCard->is_active) {
            throw ValidationException::withMessages([
                'card' => 'Seu cartão virtual está bloqueado. Desbloqueie-o para continuar.',
            ]);
        }

        if (empty($initialCard->security_pin)) {
            throw ValidationException::withMessages([
                'card_pin' => 'Por segurança, cadastre um PIN de 4 dígitos no seu Cartão Virtual antes de realizar a primeira compra.',
            ]);
        }

        if (! Hash::check((string) ($validated['card_pin'] ?? ''), $initialCard->security_pin)) {
            RateLimiter::hit($pinThrottleKey, VirtualCardController::PIN_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'card_pin' => 'PIN inválido. Informe o PIN de 4 dígitos do cartão.',
            ]);
        }

        RateLimiter::clear($pinThrottleKey);

        $priceWarnings = [];

        $order = DB::transaction(function () use ($request, $validated, $requestedItems, $pricing, $idempotencyKey, &$priceWarnings) {
            $card = VirtualCard::where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $card->is_active) {
                throw ValidationException::withMessages([
                    'card' => 'Seu cartão virtual está bloqueado. Desbloqueie-o para continuar.',
                ]);
            }

            $lockedProducts = collect();

            foreach ($requestedItems as $productId => $item) {
                $product = Product::where('id', $productId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'stock' => "O produto {$product->name} não está mais disponível.",
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => "Estoque insuficiente para o produto: {$product->name}.",
                    ]);
                }

                $lockedProducts->put($product->id, $product);
            }

            $activeCoupon = ! isset($validated['items']) ? $request->session()->get('cart_coupon') : null;
            $effectiveCep = $validated['shipping_cep'] ?? $request->session()->get('shipping_cep');

            // Cálculo unificado em centavos inteiros via CartPricing
            $summary = $pricing->calculate(
                rawCart: $requestedItems,
                couponCode: $activeCoupon,
                user: $request->user(),
                cep: $effectiveCep,
                preloadedProducts: $lockedProducts,
            );

            $priceWarnings = $summary['price_warnings'];
            $totalCents = $summary['total_cents'];
            $cardBalanceCents = CartPricing::toCents((float) $card->balance);

            if ($cardBalanceCents < $totalCents) {
                throw ValidationException::withMessages([
                    'balance' => 'Saldo insuficiente no cartão virtual. Utilize o PIX Simulado ou envie um comprovante para recarregar.',
                ]);
            }

            foreach ($summary['items'] as $line) {
                /** @var Product $prod */
                $prod = $line['product'];
                $prod->decrement('stock', $line['quantity']);
            }

            $newBalanceCents = $cardBalanceCents - $totalCents;
            $newBalance = CartPricing::toDecimal($newBalanceCents);
            $card->update(['balance' => $newBalance]);

            $order = Order::create([
                'user_id' => $request->user()->id,
                'idempotency_key' => $idempotencyKey,
                'subtotal_amount' => $summary['subtotalAll'],
                'discount_amount' => $summary['discountAmount'],
                'coupon_code' => $summary['activeCoupon'],
                'shipping_cost' => $summary['shippingCost'],
                'total_amount' => $summary['total'],
                'cashback_total' => $summary['cashbackTotal'],
                'status' => Order::STATUS_REQUESTED,
                'shipping_address' => $validated['shipping_address'],
                'shipping_cep' => $effectiveCep,
            ]);

            $orderItemsData = [];
            foreach ($summary['items'] as $line) {
                $orderItemsData[] = [
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'item_status' => Order::STATUS_REQUESTED,
                ];
            }

            $order->items()->createMany($orderItemsData);

            if ($summary['coupon']) {
                $summary['coupon']->increment('used_count');
                CouponRedemption::create([
                    'coupon_id' => $summary['coupon']->id,
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'discount_amount' => $summary['discountAmount'],
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => Order::STATUS_REQUESTED,
                'changed_by' => $request->user()->id,
                'notes' => 'Pedido realizado com saldo do Cartão Virtual.',
                'created_at' => now(),
            ]);

            CardTransaction::create([
                'virtual_card_id' => $card->id,
                'type' => CardTransaction::TYPE_PURCHASE,
                'amount' => -$summary['total'],
                'balance_after' => $newBalance,
                'description' => 'Compra #PED-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            return $order;
        });

        $request->session()->forget(['cart', 'cart_coupon', 'checkout_token']);
        CartItem::where('user_id', $request->user()->id)->delete();

        $request->user()->notify(new OrderStatusUpdatedNotification($order, Order::STATUS_REQUESTED));

        $redirect = redirect()
            ->route('orders.show', $order)
            ->with('success', 'Compra realizada com sucesso! Status: Produto solicitado.');

        if ($priceWarnings !== []) {
            $redirect->with('warning', implode(' ', $priceWarnings));
        }

        return $redirect;
    }

    public function myOrders(Request $request): View
    {
        $orders = $request->user()->orders()->with(['items.product', 'statusHistories'])->latest()->get();

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id || (bool) $request->user()->is_admin, 403);
        $order->load(['items.product', 'user', 'statusHistories.changer']);

        return view('orders.show', compact('order'));
    }

    /**
     * Cancela um pedido pelo cliente enquanto estiver em "Produto solicitado".
     */
    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== Order::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'order' => 'Somente pedidos com status "Produto solicitado" podem ser cancelados diretamente pelo cliente.',
                ]);
            }

            $this->refundAndCancelOrder($lockedOrder, $request->user()->id, 'Cancelado pelo cliente.');
        });

        return back()->with('success', 'Pedido cancelado. O saldo foi estornado no cartão virtual e o estoque devolvido.');
    }

    /**
     * Solicitação de devolução por direito de arrependimento (até 7 dias após entrega).
     */
    public function requestRegretReturn(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->canRequestRegretReturn()) {
                throw ValidationException::withMessages([
                    'order' => 'A solicitação de arrependimento só está disponível para pedidos entregues há no máximo 7 dias corridos.',
                ]);
            }

            $previousStatus = $lockedOrder->status;
            $card = VirtualCard::where('user_id', $lockedOrder->user_id)->lockForUpdate()->firstOrFail();

            foreach ($lockedOrder->items as $item) {
                Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->increment('stock', $item->quantity);
            }

            $refundCents = CartPricing::toCents((float) $lockedOrder->total_amount);
            $cashbackReversalCents = $lockedOrder->cashback_credited_at
                ? CartPricing::toCents((float) $lockedOrder->cashback_total)
                : 0;

            $netCreditCents = max(0, $refundCents - $cashbackReversalCents);
            $newBalanceCents = CartPricing::toCents((float) $card->balance) + $netCreditCents;
            $newBalance = CartPricing::toDecimal($newBalanceCents);
            $newCashbackEarned = max(0.0, round((float) $card->cashback_earned - CartPricing::toDecimal($cashbackReversalCents), 2));

            $card->update([
                'balance' => $newBalance,
                'cashback_earned' => $newCashbackEarned,
            ]);

            CardTransaction::create([
                'virtual_card_id' => $card->id,
                'type' => CardTransaction::TYPE_REFUND,
                'amount' => CartPricing::toDecimal($netCreditCents),
                'balance_after' => $newBalance,
                'description' => 'Estorno por arrependimento (7 dias) #PED-'.str_pad((string) $lockedOrder->id, 6, '0', STR_PAD_LEFT),
            ]);

            $lockedOrder->update(['status' => Order::STATUS_RETURNED]);
            $lockedOrder->items()->update(['item_status' => Order::STATUS_RETURNED]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $previousStatus,
                'to_status' => Order::STATUS_RETURNED,
                'changed_by' => $request->user()->id,
                'notes' => 'Direito de arrependimento (7 dias) exercido pelo cliente.',
                'created_at' => now(),
            ]);

            AuditLog::record(
                'order.returned_regret',
                $lockedOrder,
                ['status' => $previousStatus],
                ['status' => Order::STATUS_RETURNED, 'net_refund' => CartPricing::toDecimal($netCreditCents)],
                $request->user()->id
            );
        });

        $order->user?->notify(new OrderStatusUpdatedNotification($order->fresh(), Order::STATUS_RETURNED));

        return back()->with('success', 'Devolução por arrependimento (7 dias) concluída! O saldo foi estornado e o estoque recomposto.');
    }

    public function adminIndex(): View
    {
        $orders = Order::with(['user', 'items.product', 'statusHistories'])->latest()->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Permite ao administrador avançar o status do pedido na máquina de estados (Solicitado -> Em separação -> Enviado -> Produto entregue) ou cancelar.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = OrderStatus::tryFrom($validated['status']);
        if (! $targetStatus) {
            throw ValidationException::withMessages(['status' => 'Status de pedido inválido.']);
        }

        if ($targetStatus === OrderStatus::DELIVERED) {
            return $this->markAsDelivered($request, $order);
        }

        if ($targetStatus === OrderStatus::CANCELLED) {
            return $this->adminCancelOrder($request, $order);
        }

        DB::transaction(function () use ($request, $order, $targetStatus, $validated) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $lockedOrder->statusEnum();

            if (! $currentStatus->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'order' => "Transição inválida de \"{$currentStatus->value}\" para \"{$targetStatus->value}\".",
                ]);
            }

            $lockedOrder->update([
                'status' => $targetStatus->value,
                'tracking_code' => $validated['tracking_code'] ?? $lockedOrder->tracking_code,
            ]);

            $lockedOrder->items()->update(['item_status' => $targetStatus->value]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $currentStatus->value,
                'to_status' => $targetStatus->value,
                'changed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
            ]);

            AuditLog::record(
                'order.status_updated',
                $lockedOrder,
                ['status' => $currentStatus->value],
                ['status' => $targetStatus->value, 'tracking_code' => $lockedOrder->tracking_code],
                $request->user()->id
            );
        });

        $order->user?->notify(new OrderStatusUpdatedNotification($order->fresh(), $targetStatus->value));

        return back()->with('success', "Status do pedido atualizado para: {$targetStatus->value}.");
    }

    /**
     * Permite ao administrador cancelar um pedido em aberto com estorno integral.
     */
    public function adminCancelOrder(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->statusEnum()->canTransitionTo(OrderStatus::CANCELLED)) {
                throw ValidationException::withMessages([
                    'order' => 'Este pedido não pode mais ser cancelado.',
                ]);
            }

            $this->refundAndCancelOrder($lockedOrder, $request->user()->id, 'Cancelado administrativamente com estorno integral.');
        });

        $order->user?->notify(new OrderStatusUpdatedNotification($order->fresh(), Order::STATUS_CANCELLED));

        return back()->with('success', 'Pedido cancelado pelo administrador. Saldo estornado e estoque devolvido.');
    }

    /**
     * Atualiza o status para "Produto entregue" e credita o cashback uma única vez (idempotente).
     */
    public function markAsDelivered(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_code' => ['nullable', 'string', 'max:100'],
        ]);

        $processed = DB::transaction(function () use ($request, $order, $validated) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === Order::STATUS_DELIVERED) {
                return false;
            }

            $currentStatus = $lockedOrder->statusEnum();
            if (! $currentStatus->canTransitionTo(OrderStatus::DELIVERED)) {
                throw ValidationException::withMessages([
                    'order' => 'Apenas pedidos em andamento ("Produto solicitado", "Em separação" ou "Enviado") podem ser marcados como entregues.',
                ]);
            }

            $previousStatus = $lockedOrder->status;
            $cashbackAmount = round((float) $lockedOrder->cashback_total, 2);
            $shouldCreditCashback = $lockedOrder->cashback_credited_at === null && $cashbackAmount > 0;

            $lockedOrder->update([
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'cashback_credited_at' => $shouldCreditCashback ? now() : $lockedOrder->cashback_credited_at,
                'tracking_code' => $validated['tracking_code'] ?? $lockedOrder->tracking_code,
            ]);

            $lockedOrder->items()->update([
                'item_status' => Order::STATUS_DELIVERED,
            ]);

            if ($shouldCreditCashback) {
                $card = VirtualCard::where('user_id', $lockedOrder->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($card) {
                    $newBalance = round((float) $card->balance + $cashbackAmount, 2);
                    $card->update([
                        'balance' => $newBalance,
                        'cashback_earned' => round((float) $card->cashback_earned + $cashbackAmount, 2),
                    ]);

                    CardTransaction::create([
                        'virtual_card_id' => $card->id,
                        'type' => CardTransaction::TYPE_CASHBACK,
                        'amount' => $cashbackAmount,
                        'balance_after' => $newBalance,
                        'description' => 'Cashback do pedido #PED-'.str_pad((string) $lockedOrder->id, 6, '0', STR_PAD_LEFT),
                    ]);
                }
            }

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $previousStatus,
                'to_status' => Order::STATUS_DELIVERED,
                'changed_by' => $request->user()->id,
                'notes' => 'Entrega confirmada'.(! empty($validated['tracking_code']) ? " (Rastreio: {$validated['tracking_code']})" : '').'.',
                'created_at' => now(),
            ]);

            AuditLog::record(
                'order.delivered',
                $lockedOrder,
                ['status' => $previousStatus],
                [
                    'status' => Order::STATUS_DELIVERED,
                    'tracking_code' => $lockedOrder->tracking_code,
                    'cashback_credited' => $shouldCreditCashback ? $cashbackAmount : 0.0,
                ],
                $request->user()->id
            );

            return true;
        });

        if (! $processed) {
            return back()->with('success', 'Este pedido já consta como "Produto entregue".');
        }

        $order->user?->notify(new OrderStatusUpdatedNotification($order->fresh(), Order::STATUS_DELIVERED));

        return back()->with('success', 'Status atualizado para: Produto entregue. Cashback creditado no cartão do cliente.');
    }

    private function refundAndCancelOrder(Order $lockedOrder, int $actorUserId, string $notes): void
    {
        $previousStatus = $lockedOrder->status;
        $card = VirtualCard::where('user_id', $lockedOrder->user_id)->lockForUpdate()->firstOrFail();
        $refundAmount = round((float) $lockedOrder->total_amount, 2);

        foreach ($lockedOrder->items as $item) {
            Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('stock', $item->quantity);
        }

        $newBalance = round((float) $card->balance + $refundAmount, 2);
        $card->update(['balance' => $newBalance]);

        CardTransaction::create([
            'virtual_card_id' => $card->id,
            'type' => CardTransaction::TYPE_REFUND,
            'amount' => $refundAmount,
            'balance_after' => $newBalance,
            'description' => 'Estorno do pedido #PED-'.str_pad((string) $lockedOrder->id, 6, '0', STR_PAD_LEFT),
        ]);

        $lockedOrder->update(['status' => Order::STATUS_CANCELLED]);
        $lockedOrder->items()->update(['item_status' => Order::STATUS_CANCELLED]);

        OrderStatusHistory::create([
            'order_id' => $lockedOrder->id,
            'from_status' => $previousStatus,
            'to_status' => Order::STATUS_CANCELLED,
            'changed_by' => $actorUserId,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        AuditLog::record(
            'order.cancelled',
            $lockedOrder,
            ['status' => $previousStatus],
            ['status' => Order::STATUS_CANCELLED, 'refund_amount' => $refundAmount],
            $actorUserId
        );
    }
}
