@props(['product', 'wishlistIds' => [], 'eager' => false])

@php
    $isWishlisted = in_array($product->id, $wishlistIds ?? [], true);
    $maxCb = (float) config('digitalcard.max_cashback_percent', 15.0);
    $effectiveCbPercent = min((float) $product->cashback_percent, $maxCb);
    $cashbackAmount = round((float) $product->price * ($effectiveCbPercent / 100), 2);
    $reviewsCount = (int) $product->reviews_count;
    $rating = (float) $product->rating;
@endphp

<article class="scroll-reveal-card bg-surface rounded-xl border border-line hover:border-slate-400 hover:-translate-y-1.5 hover:shadow-float transition-all duration-300 ease-out flex flex-col overflow-hidden group">
    <div class="relative border-b border-line bg-surface overflow-hidden">
        <a href="{{ route('catalog.product', $product->slug) }}" class="block overflow-hidden">
            <x-product-image :product="$product" size="card" :priority="$eager" />
        </a>

        @if($product->discount_percent > 0)
            <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-accent-600 text-paper text-xs font-semibold tabular-nums shadow-sm pointer-events-none">
                −{{ $product->discount_percent }}%
            </span>
        @endif

        <form action="{{ route('wishlist.toggle', $product) }}" method="POST" class="absolute top-2.5 right-2.5">
            @csrf
            <button type="submit"
                    aria-label="{{ $isWishlisted ? 'Remover '.$product->name.' dos favoritos' : 'Adicionar '.$product->name.' aos favoritos' }}"
                    class="h-8 w-8 rounded-full bg-surface border border-line hover:border-ink hover:scale-110 active:scale-95 flex items-center justify-center transition-all duration-200 shadow-sm {{ $isWishlisted ? 'text-danger-600 border-danger-200 bg-danger-50' : 'text-slate-600 hover:text-ink' }}">
                <x-icon name="heart" class="size-4" />
            </button>
        </form>
    </div>

    <div class="p-3.5 sm:p-4 flex-1 flex flex-col justify-between gap-2.5">
        <div class="space-y-1">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs text-slate-500 truncate">
                    {{ $product->category->name ?? 'Catálogo' }}
                </span>

                @if($reviewsCount > 0)
                    <div class="flex items-center gap-1 shrink-0" title="{{ $reviewsCount }} {{ $reviewsCount === 1 ? 'avaliação' : 'avaliações' }}">
                        <x-icon name="star" class="size-3.5 text-accent-500" />
                        <span class="text-xs tabular-nums font-medium text-ink">{{ number_format($rating, 1, ',', '.') }}</span>
                        <span class="text-xs text-slate-400 tabular-nums">({{ $reviewsCount }})</span>
                    </div>
                @endif
            </div>

            <h3 class="text-sm font-medium text-ink group-hover:text-accent-600 transition-colors line-clamp-2 leading-snug min-h-[2.5rem]">
                <a href="{{ route('catalog.product', $product->slug) }}">
                    {{ $product->name }}
                </a>
            </h3>
        </div>

        <div class="pt-2.5 border-t border-line space-y-2.5">
            <div class="space-y-1">
                <div class="flex items-baseline flex-wrap gap-x-2 gap-y-0.5">
                    <span class="text-lg font-semibold text-ink tabular-nums whitespace-nowrap">
                        R$ {{ number_format((float) $product->price, 2, ',', '.') }}
                    </span>

                    @if($product->discount_percent > 0)
                        <span class="text-xs text-slate-400 line-through tabular-nums whitespace-nowrap">
                            R$ {{ number_format((float) $product->compare_at_price, 2, ',', '.') }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-1 flex-wrap">
                    @if($effectiveCbPercent > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-money-50 border border-money-200 text-xs font-medium text-money-700 tabular-nums">
                            <span>+ R$ {{ number_format($cashbackAmount, 2, ',', '.') }} cashback</span>
                        </span>
                    @endif

                    @if((int) $product->stock > 0 && (int) $product->stock <= 3)
                        <span class="text-xs font-medium text-accent-600 tabular-nums">
                            Restam {{ (int) $product->stock }}
                        </span>
                    @endif
                </div>
            </div>

            <form action="{{ route('cart.add', $product) }}" method="POST">
                @csrf
                <input type="hidden" name="quantity" value="1">
                <button type="submit"
                        class="w-full py-2 px-3 rounded-lg border border-line bg-paper text-ink group-hover:bg-ink group-hover:text-paper group-hover:border-ink hover:bg-slate-800 active:scale-[0.98] font-medium text-xs flex items-center justify-center gap-1.5 transition-all duration-200">
                    <x-icon name="shopping-cart" class="size-3.5 transition-transform duration-200 group-hover:scale-110" />
                    <span>Adicionar ao carrinho</span>
                </button>
            </form>
        </div>
    </div>
</article>
