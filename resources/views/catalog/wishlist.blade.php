<x-app-layout :metaTitle="'Favoritos (' . $products->count() . ')'" :metaDescription="'Produtos salvos na sua conta DigitalCard.'">
    <div class="max-w-[1440px] mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-line">
            <div>
                <x-eyebrow>Favoritos</x-eyebrow>
                <h1 class="font-serif text-3xl text-ink mt-1">Lista de desejos ({{ $products->count() }})</h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">Produtos salvos para acompanhar preços e comprar com o saldo do cartão.</p>
            </div>
            <a href="{{ route('catalog.index') }}"
               class="inline-flex items-center gap-2 bg-ink hover:bg-slate-800 text-paper text-xs font-medium px-4 py-2.5 rounded-md transition">
                <span>Ver catálogo</span>
                <x-icon name="arrow-right" class="size-3.5" />
            </a>
        </div>

        @if($products->isEmpty())
            <div class="bg-surface rounded-lg border border-line p-12 text-center space-y-3">
                <div class="mx-auto w-10 h-10 rounded-full bg-paper border border-line text-slate-500 flex items-center justify-center">
                    <x-icon name="heart" class="size-5" />
                </div>
                <h3 class="font-serif text-xl text-ink">Sua lista de desejos está vazia</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Clique no ícone de coração nos produtos do catálogo para salvá-los aqui.</p>
                <a href="{{ route('catalog.index') }}" class="inline-block bg-ink hover:bg-slate-800 text-paper font-medium text-xs px-5 py-2.5 rounded-md transition">
                    Ir para o catálogo
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($products as $product)
                    <x-product-card :product="$product" :wishlistIds="$wishlistIds ?? []" :eager="$loop->index < 4" />
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
