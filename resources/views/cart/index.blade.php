<x-app-layout :metaTitle="'Carrinho de compras'" :metaDescription="'Revise os itens do seu carrinho e finalize sua compra com o saldo do cartão virtual.'">
    @php
        $defaultAddr = auth()->check() ? auth()->user()->addresses()->where('is_default', true)->first() : null;
        $savedAddress = session('shipping_address') ?: ($defaultAddr ? $defaultAddr->formattedLine() : '');
        $savedCep = session('shipping_cep') ?: ($defaultAddr->cep ?? '');
        $card = auth()->check() ? auth()->user()->virtualCard : null;
        $balance = (float) ($card->balance ?? 0);
        $hasBalance = $card && $balance >= $total;

        $freeShippingMin = (float) config('digitalcard.free_shipping_min_total', 299.00);
        $merchandiseNet = max(0.0, round(($subtotalAll ?? 0) - ($discountAmount ?? 0), 2));
        $missingForFreeShipping = max(0.0, round($freeShippingMin - $merchandiseNet, 2));
        $freeShippingProgress = min(100, (int) round(($merchandiseNet / max(1.0, $freeShippingMin)) * 100));
    @endphp

    <div class="max-w-[1440px] mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-line">
            <div>
                <p class="text-xs font-medium text-slate-500">1 Carrinho — 2 Entrega — 3 Pagamento</p>
                <h1 class="font-serif text-3xl sm:text-4xl text-ink mt-1">Carrinho de compras</h1>
            </div>

            <a href="{{ route('catalog.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-line bg-surface hover:border-ink px-4 py-2 text-xs font-medium text-ink transition">
                <x-icon name="arrow-left" class="size-3.5" />
                <span>Continuar comprando</span>
            </a>
        </div>

        @if(! empty($priceWarnings))
            <div role="alert" class="rounded-lg border border-line bg-surface p-4 text-xs text-ink space-y-1">
                <p class="font-semibold">Aviso de atualização de preço:</p>
                @foreach($priceWarnings as $warn)
                    <p>{{ $warn }}</p>
                @endforeach
            </div>
        @endif

        @if (empty($items))
            <div class="rounded-lg border border-line bg-surface p-12 text-center space-y-3">
                <h2 class="font-serif text-2xl text-ink">Seu carrinho está vazio</h2>
                <p class="mx-auto max-w-sm text-xs text-slate-600">Explore os departamentos da loja e adicione produtos ao seu carrinho.</p>
                <a href="{{ route('catalog.index') }}"
                   class="mt-2 inline-block rounded-md bg-ink hover:bg-slate-800 px-5 py-2.5 text-xs font-medium text-paper transition">
                    Ver catálogo
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
                <div class="lg:col-span-8 space-y-6">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-4 text-xs">
                            @if($missingForFreeShipping <= 0)
                                <p class="font-medium text-money-600 inline-flex items-center gap-1.5">
                                    <x-icon name="check" class="size-3.5" />
                                    <span>Frete grátis liberado para este pedido</span>
                                </p>
                            @else
                                <p class="text-slate-700">
                                    Faltam <strong class="font-mono tabular-nums text-ink">R$ {{ number_format($missingForFreeShipping, 2, ',', '.') }}</strong> para frete grátis
                                </p>
                            @endif
                            <span class="font-mono tabular-nums text-xs text-slate-500">{{ $freeShippingProgress }}%</span>
                        </div>
                        <div class="w-full h-1 bg-line rounded-full overflow-hidden">
                            <div class="h-full bg-money-600 transition-all duration-300" style="width: {{ $freeShippingProgress }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-line bg-surface overflow-hidden">
                        <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                            <h2 class="text-xs font-medium text-slate-600 tabular-nums">
                                {{ count($items) }} {{ count($items) === 1 ? 'produto selecionado' : 'produtos selecionados' }}
                            </h2>
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-danger-600 hover:underline">
                                    Esvaziar carrinho
                                </button>
                            </form>
                        </div>

                        <div class="divide-y divide-line">
                            @foreach ($items as $item)
                                @php($product = $item['product'])
                                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center justify-between">
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <a href="{{ route('catalog.product', $product->slug) }}" class="w-20 shrink-0 rounded-md overflow-hidden border border-line">
                                            <x-product-image :product="$product" size="thumb" />
                                        </a>

                                        <div class="min-w-0 flex-1 space-y-1">
                                            <span class="text-xs text-slate-500 block">{{ $product->category->name ?? 'Catálogo' }}</span>
                                            <a href="{{ route('catalog.product', $product->slug) }}"
                                               class="block text-sm font-medium leading-snug text-ink hover:underline line-clamp-2">
                                                {{ $product->name }}
                                            </a>
                                            <div class="flex flex-wrap items-center gap-3 pt-0.5 text-xs">
                                                <span class="text-slate-600 tabular-nums">
                                                    Unitário: <strong class="font-mono text-ink">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</strong>
                                                </span>
                                                <span class="font-medium text-money-600 tabular-nums">
                                                    + R$ {{ number_format($item['cashback'], 2, ',', '.') }} de cashback
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-5 pt-2 sm:pt-0 border-t sm:border-t-0 border-line">
                                        <div class="flex items-center rounded-md border border-line bg-paper">
                                            @if ($item['quantity'] > 1)
                                                <form action="{{ route('cart.update', $product) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                                                    <button type="submit" aria-label="Diminuir quantidade" class="px-2.5 py-1 text-sm font-medium text-ink hover:bg-surface">−</button>
                                                </form>
                                            @else
                                                <form action="{{ route('cart.remove', $product) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" aria-label="Remover item" class="px-2.5 py-1 text-sm font-medium text-danger-600 hover:bg-surface">−</button>
                                                </form>
                                            @endif

                                            <span class="min-w-[32px] border-x border-line bg-surface px-2 py-1 text-center font-mono tabular-nums text-xs font-medium text-ink">
                                                {{ $item['quantity'] }}
                                            </span>

                                            <form action="{{ route('cart.update', $product) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                                <button type="submit" aria-label="Aumentar quantidade" {{ $item['quantity'] >= $product->stock ? 'disabled' : '' }}
                                                        class="px-2.5 py-1 text-sm font-medium text-ink hover:bg-surface disabled:cursor-not-allowed disabled:text-slate-400">+</button>
                                            </form>
                                        </div>

                                        <div class="text-right min-w-[100px]">
                                            @if(($item['original_subtotal'] ?? 0) > $item['subtotal'])
                                                <span class="block text-xs text-slate-500 line-through font-mono tabular-nums">
                                                    R$ {{ number_format($item['original_subtotal'], 2, ',', '.') }}
                                                </span>
                                            @endif
                                            <span class="block font-mono tabular-nums text-sm font-semibold text-ink">
                                                R$ {{ number_format($item['subtotal'], 2, ',', '.') }}
                                            </span>
                                            <form action="{{ route('cart.remove', $product) }}" method="POST" class="mt-0.5">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-danger-600 hover:underline">Remover</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-surface rounded-lg border border-line p-5 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-xs font-semibold text-ink">Cupom de desconto</h3>
                            @if(! empty($activeCoupon))
                                <form action="{{ route('cart.coupon.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-danger-600 hover:underline">
                                        Remover ({{ $activeCoupon }})
                                    </button>
                                </form>
                            @endif
                        </div>

                        <form action="{{ route('cart.coupon.apply') }}" method="POST" class="flex gap-2">
                            @csrf
                            <label for="coupon_code_input" class="sr-only">Código do cupom</label>
                            <input id="coupon_code_input" type="text" name="coupon_code" value="{{ $activeCoupon ?? '' }}"
                                   placeholder="Código do cupom"
                                   class="flex-1 rounded-md border-line bg-paper text-xs font-mono py-2 px-3 focus:border-ink focus:ring-ink">
                            <button type="submit" class="border border-line bg-surface hover:border-ink text-ink font-medium text-xs px-4 py-2 rounded-md transition">
                                Aplicar
                            </button>
                        </form>

                        @if(! empty($availableCoupons) && count($availableCoupons) > 0)
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                                <span>Disponíveis:</span>
                                @foreach($availableCoupons as $coupon)
                                    <form action="{{ route('cart.coupon.apply') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="coupon_code" value="{{ $coupon->code }}">
                                        <button type="submit"
                                                class="font-mono underline hover:text-ink {{ ($activeCoupon ?? '') === $coupon->code ? 'font-semibold text-money-600' : 'text-ink' }}">
                                            {{ $coupon->code }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="sticky top-24 space-y-4 rounded-lg border border-line bg-surface p-6">
                        <h3 class="border-b border-line pb-3 font-serif text-2xl text-ink">Resumo do pedido</h3>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal</span>
                                <span class="font-mono tabular-nums font-medium text-ink">R$ {{ number_format($subtotalAll ?? $total, 2, ',', '.') }}</span>
                            </div>

                            @if(($discountAmount ?? 0) > 0)
                                <div class="flex justify-between text-money-600 font-medium">
                                    <span>Desconto ({{ $activeCoupon }})</span>
                                    <span class="font-mono tabular-nums">− R$ {{ number_format($discountAmount, 2, ',', '.') }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-slate-600">
                                <span>Frete {{ $savedCep ? '(CEP '.$savedCep.')' : '' }}</span>
                                @if($missingForFreeShipping <= 0)
                                    <span class="font-medium text-money-600">Grátis</span>
                                @elseif(($shippingCost ?? 0) > 0)
                                    <span class="font-mono tabular-nums font-medium text-ink">R$ {{ number_format($shippingCost, 2, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-500">Informe o CEP</span>
                                @endif
                            </div>

                            <div class="flex justify-between text-money-600 font-medium">
                                <span>Cashback na entrega</span>
                                <span class="font-mono tabular-nums">+ R$ {{ number_format($cashbackTotal, 2, ',', '.') }}</span>
                            </div>

                            <div class="flex items-baseline justify-between border-t border-line pt-3 text-sm font-semibold text-ink">
                                <span>Total</span>
                                <span class="font-mono tabular-nums text-2xl text-ink">R$ {{ number_format($total, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        @auth
                            <div class="border-t border-line pt-3 text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600">Saldo no cartão:</span>
                                    <span class="font-mono tabular-nums font-semibold {{ $hasBalance ? 'text-money-600' : 'text-danger-600' }}">
                                        R$ {{ number_format($balance, 2, ',', '.') }}
                                    </span>
                                </div>
                                @if($hasBalance)
                                    <div class="flex items-center justify-between text-slate-500">
                                        <span>Saldo após a compra:</span>
                                        <span class="font-mono tabular-nums font-medium text-ink">R$ {{ number_format($balance - $total, 2, ',', '.') }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between text-danger-600 pt-1">
                                        <span class="tabular-nums">Faltam R$ {{ number_format($total - $balance, 2, ',', '.') }}</span>
                                        <a href="{{ route('card.show') }}" class="font-medium underline">Recarregar PIX →</a>
                                    </div>
                                @endif
                            </div>

                            <form action="{{ route('orders.checkout') }}" method="POST"
                                  x-data="{
                                      submitting: false,
                                      d1: '', d2: '', d3: '', d4: '',
                                      get fullPin() { return '' + this.d1 + this.d2 + this.d3 + this.d4; },
                                      stepPin(e, nextRef) {
                                          if (e.target.value.length === 1 && nextRef) {
                                              $refs[nextRef].focus();
                                          }
                                      }
                                  }"
                                  @submit="submitting = true"
                                  class="space-y-3.5 pt-3 border-t border-line">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ $checkoutToken ?? session('checkout_token') }}">

                                <div>
                                    <label for="checkout_cep" class="mb-1 block text-xs font-medium text-slate-700">CEP de entrega</label>
                                    <input id="checkout_cep" type="text" name="shipping_cep" value="{{ old('shipping_cep', $savedCep) }}"
                                           placeholder="00000-000"
                                           class="block w-full rounded-md border-line bg-paper text-xs font-mono tabular-nums py-2 focus:border-ink focus:ring-ink">
                                </div>

                                <div>
                                    <label for="checkout_address" class="mb-1 block text-xs font-medium text-slate-700">Endereço de entrega</label>
                                    <input id="checkout_address" type="text" name="shipping_address" value="{{ old('shipping_address', $savedAddress) }}" required
                                           placeholder="Rua, número, complemento, bairro, cidade - UF"
                                           class="block w-full rounded-md border-line bg-paper text-xs py-2 focus:border-ink focus:ring-ink">
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-700">PIN do cartão (4 dígitos)</label>
                                    <input type="hidden" name="card_pin" :value="fullPin">
                                    <div class="grid grid-cols-4 gap-2">
                                        <input x-ref="p1" x-model="d1" @input="stepPin($event, 'p2')" type="password" inputmode="numeric" maxlength="1" aria-label="Dígito 1 do PIN"
                                               class="w-full text-center rounded-md border-line bg-paper text-sm font-mono tabular-nums py-2 focus:border-ink focus:ring-ink">
                                        <input x-ref="p2" x-model="d2" @input="stepPin($event, 'p3')" type="password" inputmode="numeric" maxlength="1" aria-label="Dígito 2 do PIN"
                                               class="w-full text-center rounded-md border-line bg-paper text-sm font-mono tabular-nums py-2 focus:border-ink focus:ring-ink">
                                        <input x-ref="p3" x-model="d3" @input="stepPin($event, 'p4')" type="password" inputmode="numeric" maxlength="1" aria-label="Dígito 3 do PIN"
                                               class="w-full text-center rounded-md border-line bg-paper text-sm font-mono tabular-nums py-2 focus:border-ink focus:ring-ink">
                                        <input x-ref="p4" x-model="d4" type="password" inputmode="numeric" maxlength="1" aria-label="Dígito 4 do PIN"
                                               class="w-full text-center rounded-md border-line bg-paper text-sm font-mono tabular-nums py-2 focus:border-ink focus:ring-ink">
                                    </div>
                                </div>

                                <button type="submit"
                                        :disabled="submitting || {{ (! $hasBalance || ! ($card?->is_active)) ? 'true' : 'false' }}"
                                        class="w-full rounded-md bg-ink hover:bg-slate-800 disabled:bg-slate-200 disabled:text-slate-500 disabled:cursor-not-allowed px-4 py-3 text-xs font-medium text-paper transition flex items-center justify-center gap-2">
                                    <span x-text="submitting ? 'Processando…' : 'Finalizar compra'">Finalizar compra</span>
                                </button>
                            </form>
                        @else
                            <div class="pt-3 border-t border-line text-center space-y-2.5">
                                <p class="text-xs text-slate-600">Entre na sua conta para pagar com o saldo do cartão virtual.</p>
                                <a href="{{ route('login') }}" class="block w-full rounded-md bg-ink hover:bg-slate-800 py-2.5 text-xs font-medium text-paper transition">
                                    Entrar para finalizar
                                </a>
                            </div>
                        @endauth

                        <p class="text-center text-xs text-slate-500 pt-1">
                            Compra simulada. O pedido entra como “Solicitado”.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
