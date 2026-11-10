@props([
    'product',
    'size' => 'card',
    'priority' => false,
])
@php
    $hasCustom = $product->image_path && (str_starts_with($product->image_path, 'products/') || str_starts_with($product->image_path, 'uploads/')) && is_file(storage_path('app/public/'.ltrim($product->image_path, '/')));
    $hasFull = $product->slug && is_file(public_path('images/products/'.$product->slug.'.webp'));
    $hasThumb = $product->slug && is_file(public_path('images/products/'.$product->slug.'-400.webp'));
    $exists = $hasCustom || $hasFull || $hasThumb;

    $w = $size === 'full' ? 800 : ($size === 'thumb' ? 80 : 400);
    $h = $size === 'full' ? 1000 : ($size === 'thumb' ? 100 : 500);

    $catName = $product->category?->name ?? 'Catálogo';
    $catIcon = $product->category?->icon ?: 'package';
    $slugMap = [
        'smartphones' => 'smartphone',
        'audio-fones' => 'headphones',
        'smartwatches' => 'watch',
        'tablets-ereaders' => 'tablet',
        'notebooks' => 'laptop',
        'perifericos' => 'mouse',
        'monitores' => 'monitor',
        'armazenamento' => 'hard-drive',
        'consoles' => 'gamepad-2',
        'acessorios-gamer' => 'gamepad',
        'eletrodomesticos' => 'cooking-pot',
        'assistentes-hubs' => 'speaker',
        'seguranca-conectividade' => 'lightbulb',
        'cameras-acao' => 'camera',
        'drones-estabilizadores' => 'plane',
    ];
    if ($product->category?->slug && isset($slugMap[$product->category->slug])) {
        $catIcon = $slugMap[$product->category->slug];
    }
@endphp

@if($exists)
    @php
        $mainSrc = $hasCustom
            ? asset('storage/'.ltrim($product->image_path, '/'))
            : ($size === 'full' || ! $hasThumb
                ? asset('images/products/'.$product->slug.'.webp')
                : asset('images/products/'.$product->slug.'-400.webp'));
        $srcsetParts = [];
        if ($hasThumb) {
            $srcsetParts[] = asset('images/products/'.$product->slug.'-400.webp').' 400w';
        }
        if ($hasFull) {
            $srcsetParts[] = asset('images/products/'.$product->slug.'.webp').' 800w';
        }
        $srcset = implode(', ', $srcsetParts);
        $aspectClass = $size === 'full' ? 'aspect-[4/5]' : 'aspect-square';
        $imgFitClass = $size === 'full' ? 'h-full w-full object-contain p-4' : ($size === 'thumb' ? 'h-full w-full object-contain p-1' : 'h-full w-full object-contain p-3 transition-transform duration-500 ease-out group-hover:scale-105');
    @endphp
    <div {{ $attributes->merge(['class' => $aspectClass.' product-photo-box w-full overflow-hidden bg-surface flex items-center justify-center']) }}>
        <img src="{{ $mainSrc }}"
             @if($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $size === 'full' ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 25vw, 50vw' }}" @endif
             width="{{ $w }}"
             height="{{ $h }}"
             alt="{{ $product->name }}"
             loading="{{ $priority ? 'eager' : 'lazy' }}"
             @if($priority) fetchpriority="high" @endif
             decoding="async"
             class="{{ $imgFitClass }}">
    </div>
@else
    @php($fallbackAspect = $size === 'full' ? 'aspect-[4/5]' : 'aspect-square')
    <div {{ $attributes->merge(['class' => $fallbackAspect.' w-full overflow-hidden bg-slate-100 flex flex-col items-center justify-center gap-2 p-4 text-center select-none']) }}>
        <x-icon :name="$catIcon" class="{{ $size === 'thumb' ? 'size-5' : 'size-12' }} text-slate-400" />
        @if($size !== 'thumb')
            <span class="text-xs text-slate-500 line-clamp-1">{{ $catName }}</span>
        @endif
    </div>
@endif
