@php
    $pageTitle = $selectedCategory ? $selectedCategory->name : (request('q') ? 'Busca: ' . request('q') : 'Loja universitária com cartão virtual');
    $pageDesc = $selectedCategory
        ? 'Produtos na categoria ' . $selectedCategory->name . ' com pagamento via cartão virtual e cashback na entrega.'
        : 'Loja universitária com cartão virtual: recarregue por PIX, compre com o saldo e ganhe cashback quando o pedido for entregue.';
    $userCard = auth()->check() ? auth()->user()->virtualCard : null;
@endphp

<x-app-layout :metaTitle="$pageTitle" :metaDescription="$pageDesc">
    <div x-data="{ mobileFiltersOpen: false }" class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">

        @if($isHomeStorefront)
            <section class="bg-surface rounded-2xl border border-line p-6 sm:p-8 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <x-eyebrow>Loja universitária com cartão virtual</x-eyebrow>
                        <h1 class="font-serif text-3xl sm:text-5xl text-ink leading-[1.06]">
                            Compre com o saldo do cartão. Receba parte de volta.
                        </h1>
                        <p class="text-sm sm:text-base text-slate-600 max-w-xl leading-relaxed">
                            Recarregue por PIX, compre com o saldo e ganhe cashback quando o pedido for entregue. Tudo simulado, para estudo.
                        </p>
                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <a href="#catalogo"
                               class="inline-flex items-center gap-2 bg-ink hover:bg-slate-800 active:scale-[0.98] text-paper text-sm font-medium px-5 py-2.5 rounded-lg transition-all duration-200 shadow-sm">
                                <span>Ver produtos</span>
                                <x-icon name="arrow-right" class="size-4" />
                            </a>
                            <a href="#como-funciona"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-line bg-paper hover:border-ink text-sm font-medium text-ink transition-colors duration-200">
                                <span>Como funciona</span>
                            </a>
                            @auth
                                <a href="{{ route('card.show') }}" class="text-xs font-medium text-money-600 hover:underline ml-1">
                                    Acessar carteira e PIX →
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="text-xs font-medium text-ink hover:underline ml-1">
                                    Criar conta de demonstração →
                                </a>
                            @endauth
                        </div>
                    </div>

                    <div class="lg:col-span-5 max-w-md mx-auto lg:max-w-none w-full">
                        <x-virtual-card :card="$userCard" :holderName="auth()->user()?->name" />
                    </div>
                </div>

                <div id="como-funciona" class="mt-8 pt-6 border-t border-line grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-paper border border-line hover:border-slate-300 transition-colors">
                        <div class="h-9 w-9 rounded-lg bg-ink text-paper flex items-center justify-center font-serif text-lg shrink-0">
                            1
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="font-serif text-lg text-ink leading-snug">1. Recarregue por PIX</h2>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Gere o QR Code ou código copia e cola na carteira e confirme a recarga simulada.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-paper border border-line hover:border-slate-300 transition-colors">
                        <div class="h-9 w-9 rounded-lg bg-ink text-paper flex items-center justify-center font-serif text-lg shrink-0">
                            2
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="font-serif text-lg text-ink leading-snug">2. Compre com o saldo</h2>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Adicione itens ao carrinho, calcule o frete por CEP e feche o pedido com seu PIN.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-paper border border-line hover:border-slate-300 transition-colors">
                        <div class="h-9 w-9 rounded-lg bg-money-600 text-paper flex items-center justify-center font-serif text-lg shrink-0">
                            3
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="font-serif text-lg text-ink leading-snug">3. Receba cashback</h2>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Quando o pedido passa para “Produto entregue”, o valor volta direto para o saldo.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            @if($featuredDeals->isNotEmpty())
                <section class="space-y-4">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <x-eyebrow>Oferta</x-eyebrow>
                            <h2 class="font-serif text-2xl sm:text-3xl text-ink mt-0.5">Destaques em oferta</h2>
                        </div>
                        <a href="{{ route('catalog.index', ['sort' => 'maior_desconto']) }}"
                           class="inline-flex items-center gap-1 text-xs font-medium text-ink hover:text-accent-600 transition-colors">
                            <span>Ver todas as ofertas</span>
                            <x-icon name="arrow-right" class="size-3.5" />
                        </a>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($featuredDeals as $dealProduct)
                            <x-product-card :product="$dealProduct" :wishlistIds="$wishlistIds" :eager="$loop->index < 4" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        <section id="catalogo" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <aside class="hidden lg:block lg:col-span-3 xl:col-span-3 lg:sticky lg:top-28 space-y-5 bg-surface rounded-xl border border-line p-5 shadow-sm">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-serif text-xl text-ink">Departamentos</h2>
                        @if($selectedCategory)
                            <a href="{{ route('catalog.index') }}" class="text-xs text-accent-600 hover:underline">Ver todos</a>
                        @endif
                    </div>

                    <ul class="space-y-1.5 text-xs">
                        <li>
                            <a href="{{ route('catalog.index') }}"
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg transition-colors {{ ! $selectedCategory ? 'bg-ink text-paper font-medium' : 'text-slate-700 hover:bg-paper hover:text-ink' }}">
                                <span>Todos os departamentos</span>
                            </a>
                        </li>
                        @foreach($categories as $cat)
                            <li class="pt-1 border-t border-line">
                                <a href="{{ route('catalog.category', $cat->slug) }}"
                                   class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition-colors {{ $selectedCategory?->id === $cat->id ? 'bg-accent-50 text-accent-600 font-semibold' : 'font-medium text-ink hover:bg-paper' }}">
                                    <x-icon :name="$cat->icon ?: 'package'" class="size-3.5 text-slate-500 shrink-0" />
                                    <span class="truncate">{{ $cat->name }}</span>
                                </a>
                                @if($cat->children->isNotEmpty())
                                    <ul class="pl-6 pr-1 pb-1 space-y-0.5">
                                        @foreach($cat->children as $child)
                                            <li>
                                                <a href="{{ route('catalog.category', $child->slug) }}"
                                                   class="block py-1 px-2 rounded-md transition-colors {{ $selectedCategory?->id === $child->id ? 'font-semibold text-accent-600 bg-accent-50' : 'text-slate-600 hover:text-ink hover:bg-paper' }}">
                                                    {{ $child->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                <form action="{{ $selectedCategory ? route('catalog.category', $selectedCategory->slug) : route('catalog.index') }}" method="GET" class="space-y-3.5 pt-4 border-t border-line text-xs">
                    @if(request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif

                    <div class="space-y-1.5">
                        <span class="font-medium text-ink block">Faixa de preço (R$)</span>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Mín." min="0" step="1"
                                   class="w-full rounded-lg border-line bg-paper text-xs py-1.5 px-2.5 tabular-nums focus:border-ink focus:ring-ink">
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Máx." min="0" step="1"
                                   class="w-full rounded-lg border-line bg-paper text-xs py-1.5 px-2.5 tabular-nums focus:border-ink focus:ring-ink">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer text-slate-700 py-0.5">
                        <input type="checkbox" name="cashback" value="1" {{ request()->boolean('cashback') ? 'checked' : '' }}
                               class="rounded border-line text-ink focus:ring-ink">
                        <span>Com cashback na entrega</span>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-ink hover:bg-slate-800 active:scale-[0.98] text-paper font-medium py-2 px-3 rounded-lg transition-all">
                            Aplicar filtros
                        </button>
                        @if(request()->hasAny(['q', 'min_price', 'max_price', 'sort', 'cashback', 'category', 'min_rating']) || $selectedCategory)
                            <a href="{{ route('catalog.index') }}" class="px-3 py-2 rounded-lg border border-line bg-surface text-slate-700 hover:border-ink transition-colors">
                                Limpar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="pt-4 border-t border-line space-y-2 text-xs text-slate-600">
                    <div class="flex items-center gap-2 text-ink font-medium">
                        <x-icon name="truck" class="size-4 text-money-600 shrink-0" />
                        <span>Frete grátis acima de R$ 299</span>
                    </div>
                    <p class="leading-relaxed text-slate-500">
                        Pagamento instantâneo com saldo DigitalCard e estorno integral em caso de cancelamento.
                    </p>
                </div>
            </aside>

            <div class="lg:col-span-9 xl:col-span-9 space-y-4">
                <div class="bg-surface rounded-xl border border-line p-4 flex flex-col gap-3 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-baseline gap-3">
                            <h2 class="font-serif text-2xl sm:text-3xl text-ink">
                                {{ $selectedCategory ? $selectedCategory->name : (request('q') ? 'Resultados para “' . request('q') . '”' : 'Catálogo') }}
                            </h2>
                            <span class="text-xs text-slate-500 tabular-nums">
                                ({{ $products->total() }} {{ $products->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="mobileFiltersOpen = true" type="button"
                                    class="lg:hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-line bg-paper text-xs font-medium text-ink">
                                <x-icon name="sliders-horizontal" class="size-3.5" />
                                <span>Filtrar</span>
                            </button>

                            <form action="{{ $selectedCategory ? route('catalog.category', $selectedCategory->slug) : route('catalog.index') }}" method="GET" class="flex items-center gap-2">
                                @foreach(request()->except(['sort', 'page']) as $k => $v)
                                    @if(is_scalar($v))
                                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                    @endif
                                @endforeach
                                <label for="sort-select" class="text-xs text-slate-500 hidden sm:inline">Ordenar:</label>
                                <select id="sort-select" name="sort" onchange="this.form.submit()"
                                        class="rounded-lg border-line bg-paper text-xs text-ink py-1.5 pl-2.5 pr-7 focus:border-ink focus:ring-ink">
                                    <option value="" {{ ! request('sort') ? 'selected' : '' }}>Relevância</option>
                                    <option value="menor_preco" {{ request('sort') === 'menor_preco' ? 'selected' : '' }}>Menor preço</option>
                                    <option value="maior_preco" {{ request('sort') === 'maior_preco' ? 'selected' : '' }}>Maior preço</option>
                                    <option value="maior_desconto" {{ request('sort') === 'maior_desconto' ? 'selected' : '' }}>Maior desconto</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pt-2 border-t border-line text-xs">
                        <a href="{{ route('catalog.index') }}"
                           class="px-3 py-1 rounded-full border transition-colors shrink-0 {{ ! $selectedCategory && ! request('sort') && ! request('cashback') ? 'bg-ink text-paper border-ink font-medium' : 'bg-paper text-slate-700 border-line hover:border-ink' }}">
                            Todos
                        </a>
                        <a href="{{ route('catalog.index', ['sort' => 'maior_desconto']) }}"
                           class="px-3 py-1 rounded-full border transition-colors shrink-0 {{ request('sort') === 'maior_desconto' ? 'bg-accent-600 text-paper border-accent-600 font-medium' : 'bg-paper text-slate-700 border-line hover:border-ink' }}">
                            Maiores descontos
                        </a>
                        <a href="{{ route('catalog.index', ['cashback' => 1]) }}"
                           class="px-3 py-1 rounded-full border transition-colors shrink-0 {{ request()->boolean('cashback') ? 'bg-money-600 text-paper border-money-600 font-medium' : 'bg-paper text-money-700 border-money-200 hover:border-money-600' }}">
                            Com cashback
                        </a>
                        @foreach($categories as $quickCat)
                            <a href="{{ route('catalog.category', $quickCat->slug) }}"
                               class="px-3 py-1 rounded-full border transition-colors shrink-0 {{ $selectedCategory?->id === $quickCat->id ? 'bg-ink text-paper border-ink font-medium' : 'bg-paper text-slate-700 border-line hover:border-ink' }}">
                                {{ $quickCat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($products->isEmpty())
                    <div class="bg-surface rounded-xl border border-line p-12 text-center space-y-3">
                        <h3 class="font-serif text-xl text-ink">Nenhum produto encontrado</h3>
                        <p class="text-xs text-slate-500">Tente remover alguns filtros ou buscar outro termo.</p>
                        <a href="{{ route('catalog.index') }}" class="inline-block bg-ink text-paper text-xs font-medium px-4 py-2 rounded-lg">
                            Limpar filtros
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($products as $product)
                            <x-product-card :product="$product" :wishlistIds="$wishlistIds" :eager="! $isHomeStorefront && $loop->index < 4" />
                        @endforeach
                    </div>

                    <div class="pt-3">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </section>

        <div x-show="mobileFiltersOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div @click="mobileFiltersOpen = false" class="fixed inset-0 bg-ink/60"></div>
            <div class="fixed inset-x-0 bottom-0 max-h-[82vh] overflow-y-auto rounded-t-xl bg-surface border-t border-line p-5 shadow-float space-y-5 z-10">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <h3 class="font-serif text-xl text-ink">Filtrar produtos</h3>
                    <button @click="mobileFiltersOpen = false" type="button" class="text-slate-500 hover:text-ink">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <form action="{{ $selectedCategory ? route('catalog.category', $selectedCategory->slug) : route('catalog.index') }}" method="GET" class="space-y-4 text-xs">
                    @if(request('q'))
                        <input type="hidden" name="q" value="{{ request('q') }}">
                    @endif
                    <div class="space-y-1.5">
                        <label class="font-medium text-ink block">Departamento</label>
                        <select name="category" class="w-full rounded-md border-line bg-paper text-xs py-2 px-3">
                            <option value="">Todos</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->slug }}" {{ ($selectedCategory?->slug ?? request('category')) === $cat->slug ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @foreach($cat->children as $child)
                                    <option value="{{ $child->slug }}" {{ ($selectedCategory?->slug ?? request('category')) === $child->slug ? 'selected' : '' }}>
                                        — {{ $child->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="font-medium text-ink block mb-1">Preço mín. (R$)</label>
                            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0"
                                   class="w-full rounded-md border-line bg-paper text-xs py-2 px-2.5 tabular-nums">
                        </div>
                        <div>
                            <label class="font-medium text-ink block mb-1">Preço máx. (R$)</label>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0"
                                   class="w-full rounded-md border-line bg-paper text-xs py-2 px-2.5 tabular-nums">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" name="cashback" value="1" {{ request()->boolean('cashback') ? 'checked' : '' }}
                               class="rounded border-line text-ink focus:ring-ink">
                        <span>Apenas com cashback</span>
                    </label>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 bg-ink text-paper font-medium py-2.5 rounded-md">
                            Ver resultados
                        </button>
                        <a href="{{ route('catalog.index') }}" class="px-4 py-2.5 rounded-md border border-line bg-surface text-ink font-medium">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
