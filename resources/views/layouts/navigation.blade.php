@php
    $savedCep = session('shipping_cep');
    $savedAddress = session('shipping_address');
@endphp

<header x-data="{
            mobileMenuOpen: false,
            cartDrawerOpen: false,
            deptMenuOpen: false,
            cepModalOpen: false,
            showNavBalance: true
        }"
        @keydown.escape.window="mobileMenuOpen = false; cartDrawerOpen = false; deptMenuOpen = false; cepModalOpen = false"
        class="sticky top-0 z-40 border-b border-slate-800 max-w-full">

    <div class="bg-ink text-paper border-b border-slate-800">
        <div class="max-w-[1440px] mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-2 lg:gap-4 min-w-0">

                <div class="flex items-center gap-2 lg:gap-3 shrink-0 min-w-0">
                    <a href="{{ route('catalog.index') }}" class="flex items-center gap-2.5 group focus:outline-none shrink-0">
                        <div class="h-9 w-9 rounded-md bg-paper text-ink flex items-center justify-center font-serif text-lg shrink-0">
                            DC
                        </div>
                        <div class="flex flex-col">
                            <span class="font-serif text-paper text-xl leading-none">
                                DigitalCard
                            </span>
                            <span class="hidden 2xl:block text-xs text-slate-400 mt-0.5">Loja universitária com cartão virtual</span>
                        </div>
                    </a>

                    <button @click="cepModalOpen = true" type="button"
                            class="hidden xl:flex items-center gap-1.5 px-2.5 py-1.5 rounded-md hover:bg-slate-800 border border-transparent hover:border-slate-700 transition text-left max-w-[160px] min-w-0 shrink">
                        <x-icon name="truck" class="size-4 text-slate-300" />
                        <div class="truncate min-w-0">
                            <span class="block text-xs text-slate-400 leading-none truncate">Enviar para</span>
                            <span class="block text-xs font-medium text-paper truncate mt-0.5 tabular-nums">{{ $savedCep ? 'CEP '.$savedCep : 'Informe seu CEP' }}</span>
                        </div>
                    </button>
                </div>

                <div class="flex-1 min-w-0 max-w-xl hidden md:block">
                    <form action="{{ route('catalog.index') }}" method="GET"
                          class="flex items-stretch w-full min-w-0 rounded-md overflow-hidden bg-paper text-ink border border-slate-700 focus-within:ring-2 focus-within:ring-accent-500">
                        <select name="category" aria-label="Departamento"
                                class="hidden xl:block max-w-[130px] truncate bg-paper hover:bg-slate-200 border-0 border-r border-line text-xs font-medium text-ink py-2.5 pl-2.5 pr-6 focus:ring-0 cursor-pointer transition shrink-0">
                            <option value="">Todos</option>
                            @foreach($navCategories as $navCat)
                                <option value="{{ $navCat['slug'] }}" {{ request('category') === $navCat['slug'] ? 'selected' : '' }}>
                                    {{ $navCat['name'] }}
                                </option>
                            @endforeach
                        </select>

                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="Buscar produtos..."
                               class="flex-1 min-w-0 w-full bg-paper border-0 text-sm text-ink placeholder-slate-500 py-2 px-3 focus:ring-0">

                        <button type="submit" aria-label="Buscar produtos"
                                class="bg-paper hover:bg-slate-200 text-ink border-l border-line px-3.5 flex items-center justify-center transition shrink-0">
                            <x-icon name="search" class="size-4" />
                        </button>
                    </form>
                </div>

                <div class="hidden md:flex items-center gap-1.5 lg:gap-2 shrink-0">
                    @auth
                        @if(auth()->user()->virtualCard)
                            <div class="relative shrink-0" x-data="{ walletOpen: false }" @click.outside="walletOpen = false">
                                <div class="flex items-center rounded-md bg-slate-900 border border-slate-700 hover:border-slate-500 transition">
                                    <button @click="showNavBalance = !showNavBalance" type="button"
                                            aria-label="Mostrar ou ocultar saldo"
                                            class="pl-2.5 pr-1 py-2 text-slate-300 hover:text-paper transition">
                                        <span x-show="showNavBalance"><x-icon name="eye" class="size-4" /></span>
                                        <span x-show="!showNavBalance" x-cloak><x-icon name="eye-off" class="size-4 text-slate-400" /></span>
                                    </button>

                                    <button @click="walletOpen = !walletOpen" type="button"
                                            class="flex items-center gap-1.5 pr-2.5 py-1.5 text-left whitespace-nowrap">
                                        <div>
                                            <span class="hidden 2xl:block text-xs text-slate-400 leading-none">Saldo</span>
                                            <span class="font-mono tabular-nums font-semibold text-xs text-money-400 block">
                                                <span x-show="showNavBalance">R$ {{ number_format((float) auth()->user()->virtualCard->balance, 2, ',', '.') }}</span>
                                                <span x-show="!showNavBalance" x-cloak>R$ •••••</span>
                                            </span>
                                        </div>
                                        <x-icon name="chevron-down" class="size-3.5 text-slate-400" />
                                    </button>
                                </div>

                                <div x-show="walletOpen" x-cloak
                                     x-transition
                                     class="absolute right-0 mt-2 w-72 rounded-lg bg-surface text-ink p-4 shadow-float border border-line z-50">
                                    <div class="flex justify-between items-center pb-2.5 mb-3 border-b border-line">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ auth()->user()->virtualCard->is_active ? 'bg-money-600' : 'bg-danger-600' }}"></span>
                                            <span class="text-xs font-medium text-slate-700">Cartão virtual</span>
                                        </div>
                                        <span class="text-xs font-mono tabular-nums text-slate-500">{{ auth()->user()->virtualCard->masked_number }}</span>
                                    </div>

                                    <div class="pb-3 mb-3 border-b border-line">
                                        <div class="flex justify-between items-center text-xs text-slate-500">
                                            <span>Saldo disponível</span>
                                            <span class="text-money-600 tabular-nums">Cashback: R$ {{ number_format((float) auth()->user()->virtualCard->cashback_earned, 2, ',', '.') }}</span>
                                        </div>
                                        <p class="font-mono text-xl font-semibold tabular-nums text-money-600 mt-1">
                                            <span x-show="showNavBalance">R$ {{ number_format((float) auth()->user()->virtualCard->balance, 2, ',', '.') }}</span>
                                            <span x-show="!showNavBalance" x-cloak>R$ ••••••</span>
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <a href="{{ route('card.show') }}"
                                           class="text-center bg-ink hover:bg-slate-800 text-paper font-medium py-2 px-3 rounded-md transition">
                                            Recarregar PIX
                                        </a>
                                        <a href="{{ route('card.show') }}"
                                           class="text-center border border-line bg-surface hover:border-ink text-ink font-medium py-2 px-3 rounded-md transition">
                                            Ver extrato
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <button type="button"
                            onclick="window.toggleColorScheme && window.toggleColorScheme()"
                            title="Alternar tema claro ou escuro"
                            class="relative p-2 rounded-md text-slate-300 hover:text-paper hover:bg-slate-800 transition shrink-0">
                        <span class="theme-toggle-moon items-center justify-center">
                            <x-icon name="moon" class="size-5" />
                            <span class="sr-only">Ativar modo escuro</span>
                        </span>
                        <span class="theme-toggle-sun items-center justify-center">
                            <x-icon name="sun" class="size-5" />
                            <span class="sr-only">Ativar modo claro</span>
                        </span>
                    </button>

                    <a href="{{ route('wishlist.index') }}"
                       title="Lista de desejos"
                       aria-label="Lista de desejos"
                       class="relative p-2 rounded-md text-slate-300 hover:text-paper hover:bg-slate-800 transition shrink-0">
                        <x-icon name="heart" class="size-5" />
                        @if($wishlistCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-paper text-ink text-xs font-semibold tabular-nums flex items-center justify-center">
                                {{ $wishlistCount }}
                            </span>
                        @endif
                    </a>

                    <button @click="cartDrawerOpen = true" type="button"
                            aria-label="Abrir carrinho de compras"
                            class="flex items-center gap-2 px-2.5 py-2 rounded-md bg-paper text-ink hover:bg-slate-200 transition shrink-0">
                        <div class="relative">
                            <x-icon name="shopping-cart" class="size-4 text-ink" />
                            @if($cartItemCount > 0)
                                <span class="absolute -top-2 -right-2 min-w-[16px] h-4 px-1 rounded-full bg-ink text-paper text-xs font-semibold tabular-nums flex items-center justify-center">
                                    {{ $cartItemCount }}
                                </span>
                            @endif
                        </div>
                        <div class="hidden 2xl:block text-left whitespace-nowrap">
                            <span class="font-mono tabular-nums text-xs font-semibold text-ink leading-tight">
                                R$ {{ number_format($drawerTotal, 2, ',', '.') }}
                            </span>
                        </div>
                    </button>

                    @auth
                        <div class="relative shrink-0" x-data="{ userMenu: false }" @click.outside="userMenu = false">
                            <button @click="userMenu = !userMenu" type="button"
                                    aria-label="Menu da conta de {{ auth()->user()->name }}"
                                    class="flex items-center gap-1.5 p-1.5 rounded-md hover:bg-slate-800 transition">
                                <span class="h-8 w-8 rounded-md font-semibold text-xs flex items-center justify-center bg-slate-800 text-paper border border-slate-700">
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                </span>
                                <x-icon name="chevron-down" class="size-3.5 text-slate-400" />
                            </button>

                            <div x-show="userMenu" x-cloak
                                 x-transition
                                 class="absolute right-0 mt-2 w-60 rounded-lg bg-surface text-ink shadow-float border border-line py-2 z-50">
                                <div class="px-4 py-2.5 border-b border-line">
                                    <p class="text-xs font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                </div>

                                @can('admin')
                                    <div class="py-1 border-b border-line">
                                        <span class="px-4 py-1 block text-xs font-medium text-slate-500">Administração</span>
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-4 py-2 text-xs font-medium text-ink hover:bg-slate-100">
                                            <span>Visão geral</span>
                                        </a>
                                        <a href="{{ route('admin.recharges.index') }}" class="flex items-center justify-between px-4 py-2 text-xs font-medium text-ink hover:bg-slate-100">
                                            <span>Recargas PIX</span>
                                            @if($adminPendingRechargesCount > 0)
                                                <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums bg-danger-600 text-white font-semibold">{{ $adminPendingRechargesCount }}</span>
                                            @endif
                                        </a>
                                        <a href="{{ route('admin.orders.index') }}" class="flex items-center justify-between px-4 py-2 text-xs font-medium text-ink hover:bg-slate-100">
                                            <span>Pedidos</span>
                                            @if($adminPendingOrdersCount > 0)
                                                <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums bg-ink text-paper font-semibold">{{ $adminPendingOrdersCount }}</span>
                                            @endif
                                        </a>
                                    </div>
                                @endcan

                                <div class="py-1">
                                    <a href="{{ route('card.show') }}" class="flex items-center justify-between px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        <span>Meu cartão virtual</span>
                                        <span class="font-mono tabular-nums text-xs font-semibold text-money-600">R$ {{ number_format((float) (auth()->user()->virtualCard->balance ?? 0), 2, ',', '.') }}</span>
                                    </a>
                                    <a href="{{ route('orders.index') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        Meus pedidos
                                    </a>
                                    <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        <span>Meu carrinho</span>
                                        @if($cartItemCount > 0)
                                            <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums bg-ink text-paper font-semibold">{{ $cartItemCount }}</span>
                                        @endif
                                    </a>
                                    <a href="{{ route('wishlist.index') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        Favoritos ({{ $wishlistCount }})
                                    </a>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        Dados da conta
                                    </a>
                                </div>

                                <div class="border-t border-line pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-danger-600 hover:bg-danger-50">
                                            Sair da conta
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ route('login') }}" class="text-xs font-medium text-slate-200 hover:text-paper px-2.5 py-2 rounded-md transition whitespace-nowrap">
                                Entrar
                            </a>
                            <a href="{{ route('register') }}"
                               class="bg-paper hover:bg-slate-200 text-ink text-xs font-medium px-3 py-2 rounded-md transition whitespace-nowrap">
                                Criar conta
                            </a>
                        </div>
                    @endauth
                </div>

                <div class="flex md:hidden items-center gap-1.5 shrink-0">
                    <button type="button"
                            onclick="window.toggleColorScheme && window.toggleColorScheme()"
                            class="p-2 rounded-md text-slate-300 hover:text-paper hover:bg-slate-800 transition">
                        <span class="theme-toggle-moon items-center justify-center">
                            <x-icon name="moon" class="size-5" />
                            <span class="sr-only">Ativar modo escuro</span>
                        </span>
                        <span class="theme-toggle-sun items-center justify-center">
                            <x-icon name="sun" class="size-5" />
                            <span class="sr-only">Ativar modo claro</span>
                        </span>
                    </button>

                    <a href="{{ route('cart.index') }}" aria-label="Carrinho" class="relative p-2 text-paper">
                        <x-icon name="shopping-cart" class="size-5" />
                        @if($cartItemCount > 0)
                            <span class="absolute top-0.5 right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-paper text-ink text-xs font-semibold tabular-nums flex items-center justify-center">
                                {{ $cartItemCount }}
                            </span>
                        @endif
                    </a>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                            aria-label="Abrir menu de navegação"
                            class="p-2 rounded-md text-slate-300 hover:text-paper hover:bg-slate-800 focus:outline-none">
                        <span x-show="!mobileMenuOpen"><x-icon name="menu" class="size-5" /></span>
                        <span x-show="mobileMenuOpen" x-cloak><x-icon name="x" class="size-5" /></span>
                    </button>
                </div>
            </div>

            <div class="md:hidden pb-3">
                <form action="{{ route('catalog.index') }}" method="GET" class="flex rounded-md overflow-hidden bg-paper text-ink min-w-0">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Buscar produtos..."
                           class="flex-1 min-w-0 bg-paper border-0 text-xs text-ink py-2.5 px-3 focus:ring-0">
                    <button type="submit" aria-label="Buscar" class="bg-paper text-ink border-l border-line px-3.5 flex items-center justify-center shrink-0">
                        <x-icon name="search" class="size-4" />
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-ink text-slate-200 border-b border-slate-800 text-xs">
        <div class="max-w-[1440px] mx-auto px-3 sm:px-6 lg:px-8 flex items-center justify-between h-10 gap-3 overflow-x-auto no-scrollbar">
            <div class="flex items-center gap-1 sm:gap-2 whitespace-nowrap min-w-0">
                <div class="relative shrink-0" @click.outside="deptMenuOpen = false">
                    <button @click="deptMenuOpen = !deptMenuOpen" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-paper font-medium transition">
                        <x-icon name="menu" class="size-3.5" />
                        <span>Departamentos</span>
                        <x-icon name="chevron-down" class="size-3 text-slate-400" />
                    </button>

                    <div x-show="deptMenuOpen" x-cloak
                         x-transition
                         class="fixed left-3 right-3 sm:right-auto sm:left-6 mt-2 sm:w-[520px] max-w-[calc(100vw-1.5rem)] rounded-lg bg-surface text-ink shadow-float border border-line p-5 z-50 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($navCategories as $parentCat)
                            <div class="space-y-1.5">
                                <a href="{{ route('catalog.category', $parentCat['slug']) }}"
                                   class="font-semibold text-xs text-ink hover:underline flex items-center justify-between border-b border-line pb-1">
                                    <span>{{ $parentCat['name'] }}</span>
                                    <x-icon name="chevron-right" class="size-3.5 text-slate-400" />
                                </a>
                                @if(! empty($parentCat['children']))
                                    <ul class="space-y-1 pl-1">
                                        @foreach($parentCat['children'] as $subCat)
                                            <li>
                                                <a href="{{ route('catalog.category', $subCat['slug']) }}"
                                                   class="text-xs text-slate-600 hover:text-ink block py-0.5">
                                                    {{ $subCat['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('catalog.index', ['sort' => 'maior_desconto']) }}"
                   class="px-2.5 py-1 rounded-md font-medium hover:text-paper hover:bg-slate-800 transition shrink-0 {{ request('sort') === 'maior_desconto' ? 'text-accent-400 font-semibold' : 'text-slate-300' }}">
                    Em oferta
                </a>

                <a href="{{ route('catalog.index', ['cashback' => 1]) }}"
                   class="px-2.5 py-1 rounded-md font-medium text-money-400 hover:bg-slate-800 transition shrink-0">
                    Cashback
                </a>

                @foreach($navCategories as $topCat)
                    <a href="{{ route('catalog.category', $topCat['slug']) }}"
                       class="px-2.5 py-1 rounded-md font-medium hover:text-paper hover:bg-slate-800 transition shrink-0 {{ request()->is('categoria/'.$topCat['slug']) ? 'text-accent-400 font-semibold' : 'text-slate-300' }}">
                        {{ $topCat['name'] }}
                    </a>
                @endforeach
            </div>

            <div class="hidden xl:flex items-center gap-3 whitespace-nowrap shrink-0">
                @auth
                    @can('admin')
                        <a href="{{ route('admin.recharges.index') }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-800 text-slate-200 font-medium hover:bg-slate-700 transition">
                            <span>Recargas</span>
                            @if($adminPendingRechargesCount > 0)
                                <span class="px-1.5 py-0.5 rounded-full bg-danger-600 text-white text-xs font-semibold tabular-nums">{{ $adminPendingRechargesCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('admin.orders.index') }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-800 text-slate-200 font-medium hover:bg-slate-700 transition">
                            <span>Pedidos</span>
                            @if($adminPendingOrdersCount > 0)
                                <span class="px-1.5 py-0.5 rounded-full bg-slate-600 text-white text-xs font-semibold tabular-nums">{{ $adminPendingOrdersCount }}</span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('card.show') }}" class="text-slate-300 hover:text-paper font-medium transition">
                            Meu cartão
                        </a>
                        <span class="text-slate-700">·</span>
                        <a href="{{ route('orders.index') }}" class="text-slate-300 hover:text-paper font-medium transition">
                            Meus pedidos
                        </a>
                    @endcan
                @endauth
            </div>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-ink border-b border-slate-800 px-4 pt-3 pb-6 space-y-3 text-slate-200">
        @auth
            @if(auth()->user()->virtualCard)
                <div class="p-3.5 rounded-md bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">Saldo disponível</span>
                        <span class="font-mono tabular-nums text-lg font-semibold text-money-400">
                            R$ {{ number_format((float) auth()->user()->virtualCard->balance, 2, ',', '.') }}
                        </span>
                    </div>
                    <a href="{{ route('card.show') }}" class="bg-paper text-ink font-medium text-xs px-3 py-2 rounded-md">
                        Recarregar
                    </a>
                </div>
            @endif
        @endauth

        <div class="space-y-1">
            <a href="{{ route('catalog.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-paper hover:bg-slate-800">Catálogo</a>
            <a href="{{ route('wishlist.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-300 hover:bg-slate-800">Favoritos ({{ $wishlistCount }})</a>
            <a href="{{ route('cart.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-300 hover:bg-slate-800">Carrinho ({{ $cartItemCount }})</a>
            @auth
                <a href="{{ route('card.show') }}" class="block px-3 py-2 rounded-md text-sm text-slate-300 hover:bg-slate-800">Meu cartão virtual</a>
                <a href="{{ route('orders.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-300 hover:bg-slate-800">Meus pedidos</a>
                @can('admin')
                    <div class="pt-2 mt-2 border-t border-slate-800 space-y-1">
                        <span class="px-3 text-xs text-slate-400">Administração</span>
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-paper bg-slate-800">Painel Admin</a>
                        <a href="{{ route('admin.recharges.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-200 bg-slate-800">Recargas ({{ $adminPendingRechargesCount }})</a>
                        <a href="{{ route('admin.orders.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-200 bg-slate-800">Pedidos</a>
                    </div>
                @endcan
                <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-slate-800">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 text-sm font-medium text-danger-400">Sair da conta</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-2 pt-2">
                    <a href="{{ route('login') }}" class="text-center py-2.5 rounded-md border border-slate-700 font-medium text-xs text-paper">Entrar</a>
                    <a href="{{ route('register') }}" class="text-center py-2.5 rounded-md bg-paper font-medium text-xs text-ink">Criar conta</a>
                </div>
            @endauth
        </div>
    </div>

    <div x-show="cepModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4">
            <div @click="cepModalOpen = false" class="fixed inset-0 bg-ink/70"></div>
            <div class="relative bg-surface rounded-lg max-w-md w-full p-6 shadow-float border border-line z-10">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <div>
                        <x-eyebrow>Entrega</x-eyebrow>
                        <h3 class="font-serif text-xl text-ink">Informe seu CEP</h3>
                    </div>
                    <button @click="cepModalOpen = false" type="button" class="text-slate-500 hover:text-ink">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <form action="{{ route('address.save') }}" method="POST" class="space-y-4 mt-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">CEP de entrega</label>
                        <input type="text" name="cep" value="{{ $savedCep }}" required placeholder="00000-000"
                               class="w-full rounded-md border-line bg-surface text-sm font-mono tabular-nums py-2.5 focus:border-ink focus:ring-ink">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Endereço completo (opcional)</label>
                        <input type="text" name="shipping_address" value="{{ $savedAddress }}"
                               placeholder="Rua, número, bairro, cidade - UF"
                               class="w-full rounded-md border-line bg-surface text-xs py-2.5 focus:border-ink focus:ring-ink">
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 bg-ink hover:bg-slate-800 text-paper font-medium text-xs py-2.5 rounded-md transition">
                            Salvar localização
                        </button>
                        <button @click="cepModalOpen = false" type="button" class="px-4 py-2.5 rounded-md border border-line bg-surface text-ink font-medium text-xs">
                            Fechar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="cartDrawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <div @click="cartDrawerOpen = false"
                 x-show="cartDrawerOpen"
                 x-transition.opacity
                 class="fixed inset-0 bg-ink/65"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div x-show="cartDrawerOpen"
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-md bg-surface shadow-float border-l border-line flex flex-col h-screen">

                    <div class="flex items-center justify-between px-6 py-4 bg-ink text-paper">
                        <div>
                            <h2 class="font-serif text-xl">Carrinho ({{ $cartItemCount }})</h2>
                        </div>
                        <button @click="cartDrawerOpen = false" type="button" aria-label="Fechar carrinho" class="rounded-md p-1.5 text-slate-300 hover:text-paper">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>

                    @php
                        $navFreeMin = (float) config('digitalcard.free_shipping_min_total', 299.00);
                        $navMissingFree = max(0.0, round($navFreeMin - ($drawerTotal ?? 0), 2));
                        $navFreePct = min(100, (int) round((($drawerTotal ?? 0) / max(1.0, $navFreeMin)) * 100));
                    @endphp
                    <div class="border-b border-line px-6 py-3 space-y-1.5 text-xs text-ink bg-paper">
                        <div class="flex items-center justify-between font-medium">
                            @if($navMissingFree > 0)
                                <span>Faltam <strong class="tabular-nums">R$ {{ number_format($navMissingFree, 2, ',', '.') }}</strong> para frete grátis</span>
                            @else
                                <span class="text-money-600 inline-flex items-center gap-1">
                                    <x-icon name="check" class="size-3.5" />
                                    <span>Frete grátis liberado</span>
                                </span>
                            @endif
                            <span class="font-mono tabular-nums">{{ $navFreePct }}%</span>
                        </div>
                        <div class="w-full h-1 bg-line rounded-full overflow-hidden">
                            <div class="h-full bg-money-600 transition-all" style="width: {{ $navFreePct }}%"></div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 divide-y divide-line">
                        @forelse($drawerItems as $dItem)
                            @php($dProd = $dItem['product'])
                            <div class="py-4 first:pt-0 last:pb-0 flex gap-3.5 items-center">
                                <div class="w-14 shrink-0 rounded-md overflow-hidden border border-line">
                                    <x-product-image :product="$dProd" size="thumb" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('catalog.product', $dProd->slug) }}" class="text-xs font-medium text-ink hover:underline line-clamp-1">
                                        {{ $dProd->name }}
                                    </a>
                                    <p class="text-xs text-slate-500 mt-0.5 tabular-nums">
                                        {{ $dItem['quantity'] }}x <span class="font-mono font-semibold text-ink">R$ {{ number_format((float) $dProd->price, 2, ',', '.') }}</span>
                                    </p>
                                    <span class="text-xs font-medium text-money-600 tabular-nums">+ R$ {{ number_format($dItem['cashback'], 2, ',', '.') }} de cashback</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-mono tabular-nums text-xs font-semibold text-ink block">
                                        R$ {{ number_format($dItem['subtotal'], 2, ',', '.') }}
                                    </span>
                                    <form action="{{ route('cart.remove', $dProd) }}" method="POST" class="mt-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-danger-600 hover:underline">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="py-16 text-center space-y-2">
                                <p class="text-sm font-medium text-ink">Seu carrinho está vazio</p>
                                <p class="text-xs text-slate-500">Navegue pelo catálogo e adicione produtos.</p>
                            </div>
                        @endforelse
                    </div>

                    @if(! empty($drawerItems))
                        <div class="border-t border-line bg-paper p-6 space-y-4">
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal ({{ $cartItemCount }} {{ $cartItemCount === 1 ? 'item' : 'itens' }})</span>
                                    <span class="font-mono tabular-nums font-semibold text-ink text-base">R$ {{ number_format($drawerTotal, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-money-600 font-medium">
                                    <span>Cashback estimado</span>
                                    <span class="font-mono tabular-nums">+ R$ {{ number_format($drawerCashback, 2, ',', '.') }}</span>
                                </div>
                            </div>

                            <a href="{{ route('cart.index') }}"
                               class="flex w-full items-center justify-center gap-2 rounded-md bg-ink hover:bg-slate-800 px-4 py-3 text-xs font-medium text-paper transition">
                                <span>Ir para o carrinho</span>
                                <x-icon name="arrow-right" class="size-3.5" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
