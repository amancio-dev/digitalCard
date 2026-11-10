<x-app-layout :metaTitle="$product->name" :metaDescription="Str::limit($product->description, 155)" :metaImage="$product->image_url">

    @push('head')
        @php
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'description' => $product->description,
                'sku' => 'PROD-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT),
                'image' => [$product->image_url],
                'offers' => [
                    '@type' => 'Offer',
                    'url' => url()->current(),
                    'priceCurrency' => 'BRL',
                    'price' => number_format((float) $product->price, 2, '.', ''),
                    'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                ],
            ];
            if ((int) $product->reviews_count > 0) {
                $jsonLd['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => number_format((float) $product->rating, 1, '.', ''),
                    'reviewCount' => (int) $product->reviews_count,
                ];
            }
            $breadcrumbLd = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Início',
                        'item' => route('catalog.index'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $product->category?->name ?? 'Catálogo',
                        'item' => $product->category ? route('catalog.category', $product->category->slug) : route('catalog.index'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $product->name,
                        'item' => url()->current(),
                    ],
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    @php
        $userCard = auth()->check() ? auth()->user()->virtualCard : null;
        $balance = $userCard ? (float) $userCard->balance : 0.00;
        $productPrice = (float) $product->price;
        $hasBalance = $balance >= $productPrice;
        $remainingBalance = round($balance - $productPrice, 2);
        $maxCb = (float) config('digitalcard.max_cashback_percent', 15.0);
        $effectiveCbPercent = min((float) $product->cashback_percent, $maxCb);
        $cashbackValue = round($productPrice * ($effectiveCbPercent / 100), 2);
        $isWishlisted = in_array($product->id, $wishlistIds ?? [], true);
        $reviewsCount = (int) $product->reviews_count;
        $ratingValue = (float) $product->rating;
        $freeShippingMin = (float) config('digitalcard.free_shipping_min_total', 299.00);
    @endphp

    <div x-data="{ qty: 1, expressCheckoutOpen: false }" class="max-w-[1440px] mx-auto py-6 pb-24 lg:pb-12 px-4 sm:px-6 lg:px-8 space-y-10">
        <nav aria-label="Breadcrumb" class="flex items-center flex-wrap gap-1.5 text-xs text-slate-500">
            <a href="{{ route('catalog.index') }}" class="hover:text-ink">Início</a>
            @if($product->category?->parent)
                <span>/</span>
                <a href="{{ route('catalog.category', $product->category->parent->slug) }}" class="hover:text-ink">
                    {{ $product->category->parent->name }}
                </a>
            @endif
            @if($product->category)
                <span>/</span>
                <a href="{{ route('catalog.category', $product->category->slug) }}" class="hover:text-ink">
                    {{ $product->category->name }}
                </a>
            @endif
            <span>/</span>
            <span class="text-ink font-medium truncate max-w-xs">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            <div class="lg:col-span-6 space-y-3">
                <div class="relative rounded-lg border border-line bg-surface overflow-hidden">
                    <x-product-image :product="$product" size="full" :priority="true" />

                    <form action="{{ route('wishlist.toggle', $product) }}" method="POST" class="absolute top-3 right-3">
                        @csrf
                        <button type="submit"
                                aria-label="{{ $isWishlisted ? 'Remover dos favoritos' : 'Salvar nos favoritos' }}"
                                class="h-9 w-9 rounded-full bg-surface border border-line hover:border-ink flex items-center justify-center transition {{ $isWishlisted ? 'text-danger-600' : 'text-slate-600 hover:text-ink' }}">
                            <x-icon name="heart" class="size-4" />
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-6 space-y-6">
                <div class="space-y-3 border-b border-line pb-5">
                    @if($product->category)
                        <x-eyebrow>{{ $product->category->name }}</x-eyebrow>
                    @endif

                    <h1 class="font-serif text-4xl text-ink leading-tight">
                        {{ $product->name }}
                    </h1>

                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        @if($reviewsCount > 0)
                            <a href="#avaliacoes" class="inline-flex items-center gap-1 text-ink font-medium hover:underline">
                                <x-icon name="star" class="size-3.5" />
                                <span class="tabular-nums">{{ number_format($ratingValue, 1, ',', '.') }}</span>
                                <span class="text-slate-500 tabular-nums">({{ $reviewsCount }} {{ $reviewsCount === 1 ? 'avaliação' : 'avaliações' }})</span>
                            </a>
                        @else
                            <a href="#avaliacoes" class="text-slate-500 hover:text-ink hover:underline">
                                Seja o primeiro a avaliar
                            </a>
                        @endif

                        @if(($soldUnitsCount ?? 0) > 0)
                            <span>·</span>
                            <span class="tabular-nums">{{ $soldUnitsCount }} {{ $soldUnitsCount === 1 ? 'vendido' : 'vendidos' }}</span>
                        @endif
                    </div>
                </div>

                <div class="space-y-2 border-b border-line pb-5">
                    <div class="flex items-baseline flex-wrap gap-x-3 gap-y-1">
                        <span class="text-[32px] leading-none font-semibold text-ink tabular-nums">
                            R$ {{ number_format($productPrice, 2, ',', '.') }}
                        </span>

                        @if($product->discount_percent > 0)
                            <span class="text-sm text-slate-500 line-through tabular-nums">
                                R$ {{ number_format((float) $product->compare_at_price, 2, ',', '.') }}
                            </span>
                            <span class="text-sm font-medium text-accent-600 tabular-nums">
                                −{{ $product->discount_percent }}%
                            </span>
                        @endif
                    </div>

                    @if($effectiveCbPercent > 0)
                        <p class="text-xs font-medium text-money-600 tabular-nums">
                            + R$ {{ number_format($cashbackValue, 2, ',', '.') }} ({{ rtrim(rtrim(number_format($effectiveCbPercent, 2, ',', '.'), '0'), ',') }}%) de cashback ao receber o pedido
                        </p>
                    @endif
                </div>

                <p class="text-sm text-slate-700 leading-relaxed">
                    {{ $product->description }}
                </p>

                <div class="sticky top-24 bg-surface rounded-lg border border-line p-5 space-y-4">
                    <div class="flex items-center justify-between text-xs pb-3 border-b border-line">
                        <span class="font-medium {{ $product->stock > 0 ? 'text-money-600' : 'text-danger-600' }}">
                            {{ $product->stock > 0 ? 'Em estoque' : 'Indisponível' }}
                        </span>
                        <span class="text-slate-500 tabular-nums">{{ (int) $product->stock }} {{ (int) $product->stock === 1 ? 'unidade disponível' : 'unidades disponíveis' }}</span>
                    </div>

                    <div class="space-y-2 pb-3 border-b border-line text-xs">
                        <form action="{{ route('address.save') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="text" name="cep" value="{{ $savedCep }}" required placeholder="Calcular frete (CEP)"
                                   aria-label="Calcular frete por CEP"
                                   class="flex-1 rounded-md border-line bg-paper text-xs font-mono tabular-nums py-2 px-3 focus:border-ink focus:ring-ink">
                            <button type="submit" class="px-3 py-2 rounded-md border border-line bg-surface hover:border-ink text-xs font-medium text-ink">
                                Calcular
                            </button>
                        </form>

                        @if(! empty($shippingEstimate))
                            <p class="text-xs {{ $shippingEstimate['is_free'] ? 'text-money-600 font-medium' : 'text-slate-700' }} tabular-nums">
                                {{ $shippingEstimate['is_free'] ? 'Frete grátis' : 'Frete: R$ '.number_format($shippingEstimate['cost'], 2, ',', '.') }}
                                · {{ $shippingEstimate['estimated_days'] }} dias úteis ({{ $shippingEstimate['uf'] }})
                            </p>
                        @elseif($productPrice >= $freeShippingMin)
                            <p class="text-xs text-money-600 font-medium tabular-nums">
                                Frete grátis (compras acima de R$ {{ number_format($freeShippingMin, 0, ',', '.') }})
                            </p>
                        @endif
                    </div>

                    @auth
                        <div class="flex items-center justify-between text-xs pb-3 border-b border-line">
                            <span class="text-slate-600">Saldo no cartão:</span>
                            <span class="font-mono font-semibold tabular-nums {{ $hasBalance ? 'text-money-600' : 'text-danger-600' }}">
                                R$ {{ number_format($balance, 2, ',', '.') }}
                            </span>
                        </div>
                    @endauth

                    <div class="flex items-center gap-3">
                        <label for="buy_qty_select" class="text-xs font-medium text-slate-700">Quantidade</label>
                        <select id="buy_qty_select" x-model.number="qty" class="rounded-md border-line bg-paper text-xs font-medium text-ink py-2 pl-3 pr-8 tabular-nums">
                            @for($q = 1; $q <= min(10, max(1, $product->stock)); $q++)
                                <option value="{{ $q }}">{{ $q }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" :value="qty">
                            <button type="submit"
                                    class="w-full bg-ink hover:bg-slate-800 text-paper font-medium text-xs py-3 px-4 rounded-md transition flex items-center justify-center gap-2">
                                <x-icon name="shopping-cart" class="size-4" />
                                <span>Adicionar ao carrinho</span>
                            </button>
                        </form>

                        @auth
                            <button @click="expressCheckoutOpen = !expressCheckoutOpen" type="button"
                                    class="w-full border border-line bg-surface hover:border-ink text-ink font-medium text-xs py-3 px-4 rounded-md transition">
                                Comprar agora
                            </button>
                        @else
                            <a href="{{ route('login') }}"
                               class="w-full border border-line bg-surface hover:border-ink text-ink font-medium text-xs py-3 px-4 rounded-md transition text-center">
                                Entrar para comprar
                            </a>
                        @endauth
                    </div>

                    @auth
                        <div x-show="expressCheckoutOpen" x-cloak x-transition class="pt-3 border-t border-line space-y-3">
                            <form action="{{ route('orders.checkout') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ $checkoutToken ?? session('checkout_token') }}">
                                <input type="hidden" name="items[0][product_id]" value="{{ $product->id }}">
                                <input type="hidden" name="items[0][quantity]" :value="qty">

                                <div>
                                    <label for="express_shipping_address" class="block text-xs font-medium text-slate-700 mb-1">Endereço para entrega</label>
                                    <input id="express_shipping_address" type="text" name="shipping_address" value="{{ $savedAddress ?? '' }}" required
                                           placeholder="Rua, número, bairro, cidade - UF, CEP"
                                           class="w-full rounded-md border-line bg-paper text-xs py-2">
                                </div>

                                <div>
                                    <label for="express_card_pin" class="block text-xs font-medium text-slate-700 mb-1">PIN do cartão (4 dígitos)</label>
                                    <input id="express_card_pin" type="password" name="card_pin" inputmode="numeric" maxlength="4" required placeholder="••••"
                                           class="w-full rounded-md border-line bg-paper text-xs py-2 font-mono tabular-nums">
                                </div>

                                <button type="submit"
                                        {{ ! $hasBalance ? 'disabled' : '' }}
                                        class="w-full bg-ink hover:bg-slate-800 disabled:bg-slate-200 disabled:text-slate-500 text-paper font-medium text-xs py-2.5 px-4 rounded-md transition tabular-nums">
                                    Confirmar compra
                                </button>
                                <p class="text-xs text-slate-500">Compra simulada. O pedido entra como “Solicitado”.</p>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
            <section class="space-y-4 pt-6 border-t border-line">
                <div class="flex items-end justify-between">
                    <div>
                        <x-eyebrow>Catálogo</x-eyebrow>
                        <h2 class="font-serif text-2xl sm:text-3xl text-ink mt-1">Produtos relacionados</h2>
                    </div>
                    @if($product->category)
                        <a href="{{ route('catalog.category', $product->category->slug) }}" class="text-xs font-medium text-ink hover:underline">
                            Ver todos →
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($relatedProducts as $rel)
                        <x-product-card :product="$rel" :wishlistIds="$wishlistIds" />
                    @endforeach
                </div>
            </section>
        @endif

        <section id="avaliacoes" class="pt-6 border-t border-line space-y-6">
            <div>
                <x-eyebrow>Avaliações</x-eyebrow>
                <h2 class="font-serif text-2xl sm:text-3xl text-ink mt-1">Avaliações de clientes</h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-4 bg-surface rounded-lg p-5 border border-line space-y-4">
                    @if($reviewsCount > 0)
                        <div class="flex items-center gap-4">
                            <span class="font-serif text-4xl text-ink tabular-nums">{{ number_format($ratingValue, 1, ',', '.') }}</span>
                            <div>
                                <div class="flex items-center gap-0.5 text-ink" aria-hidden="true">
                                    @for($i = 1; $i <= 5; $i++)
                                        <x-icon name="star" class="size-4 {{ $i <= (int) round($ratingValue) ? 'text-ink' : 'text-slate-300' }}" />
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-600 block mt-0.5 tabular-nums">
                                    {{ $reviewsCount }} {{ $reviewsCount === 1 ? 'avaliação verificada' : 'avaliações verificadas' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs pt-2 border-t border-line">
                            @foreach([5, 4, 3, 2, 1] as $stars)
                                @php
                                    $dist = $ratingDistribution[$stars] ?? ['count' => 0, 'percent' => 0];
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="w-10 font-medium text-slate-700 tabular-nums">{{ $stars }}</span>
                                    <div class="flex-1 h-1.5 bg-line rounded-full overflow-hidden">
                                        <div class="h-full bg-ink rounded-full" style="width: {{ $dist['percent'] }}%"></div>
                                    </div>
                                    <span class="w-16 text-right font-mono tabular-nums text-slate-600">{{ $dist['percent'] }}% ({{ $dist['count'] }})</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-1.5 py-1">
                            <p class="text-sm font-medium text-ink">Ainda sem avaliações</p>
                            <p class="text-xs text-slate-600">Seja o primeiro a avaliar este produto após comprar e receber seu pedido.</p>
                        </div>
                    @endif

                    @auth
                        @if($canReview ?? false)
                            <form action="{{ route('catalog.review', $product->slug) }}" method="POST"
                                  x-data="{ selectedRating: 5 }"
                                  class="pt-4 border-t border-line space-y-3">
                                @csrf
                                <h3 class="text-xs font-semibold text-ink">Avaliar este produto</h3>

                                <div>
                                    <label class="block text-xs text-slate-700 mb-1">Nota (1 a 5):</label>
                                    <select name="rating" x-model.number="selectedRating" class="w-full rounded-md border-line bg-paper text-xs py-2 tabular-nums">
                                        @for($star = 5; $star >= 1; $star--)
                                            <option value="{{ $star }}">{{ $star }} {{ $star === 1 ? 'estrela' : 'estrelas' }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div>
                                    <label for="review_comment" class="sr-only">Comentário da avaliação</label>
                                    <textarea id="review_comment" name="comment" rows="3" maxlength="1000" required
                                              placeholder="Conte sua experiência com o produto..."
                                              class="w-full rounded-md border-line bg-paper text-xs p-3"></textarea>
                                </div>

                                <button type="submit" class="w-full bg-ink hover:bg-slate-800 text-paper font-medium text-xs py-2.5 px-4 rounded-md transition">
                                    Publicar avaliação
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>

                <div class="lg:col-span-8 space-y-3">
                    @forelse(($reviews ?? $product->reviews) as $review)
                        <div class="p-4 rounded-lg bg-surface border border-line space-y-2">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div>
                                    <span class="text-xs font-semibold text-ink block">{{ $review->user->name ?? 'Cliente' }}</span>
                                    @if($review->is_verified_purchase)
                                        <span class="text-xs text-money-600 inline-flex items-center gap-1">
                                            <x-icon name="check" class="size-3" />
                                            <span>Compra verificada</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-mono tabular-nums text-ink font-medium">Nota {{ (int) $review->rating }}/5</span>
                                    <span class="block text-xs text-slate-500 tabular-nums">{{ $review->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="text-xs text-slate-700 leading-relaxed pt-1">{{ $review->comment }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 rounded-lg bg-surface border border-line text-center space-y-1">
                            <p class="text-sm font-medium text-ink">Nenhuma avaliação registrada.</p>
                            <p class="text-xs text-slate-500">As avaliações aparecem aqui após a entrega do pedido.</p>
                        </div>
                    @endforelse

                    @if(isset($reviews) && method_exists($reviews, 'links'))
                        <div class="pt-2">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 lg:hidden bg-surface border-t border-line px-4 py-3 flex items-center justify-between gap-3">
        <div>
            <span class="block text-xs text-slate-500">Preço</span>
            <span class="text-base font-semibold text-ink tabular-nums">R$ {{ number_format($productPrice, 2, ',', '.') }}</span>
        </div>
        <form action="{{ route('cart.add', $product) }}" method="POST" class="flex-1 max-w-[220px]">
            @csrf
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="w-full bg-ink text-paper text-xs font-medium py-2.5 px-4 rounded-md flex items-center justify-center gap-1.5">
                <x-icon name="shopping-cart" class="size-3.5" />
                <span>Adicionar ao carrinho</span>
            </button>
        </form>
    </div>
</x-app-layout>
