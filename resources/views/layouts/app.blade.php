@props([
    'metaTitle' => null,
    'metaDescription' => 'DigitalCard — Projeto universitário de e-commerce integrado com carteira e cartão virtual fictício para demonstração acadêmica.',
    'metaImage' => null,
    'noindex' => false,
])
@php
    $shouldNoIndex = $noindex || request()->routeIs('cart.*', 'card.*', 'orders.*', 'profile.*', 'admin.*', 'login', 'register', 'password.*', 'verification.*');
    $resolvedTitle = $metaTitle
        ? $metaTitle.' — '.config('app.name', 'DigitalCard')
        : config('app.name', 'DigitalCard').' — E-commerce Universitário com Cartão Virtual';
    $resolvedImage = $metaImage ?: asset('images/hero/hero_smartphones.webp');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-paper">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <script>
            try {
                document.querySelector('meta[name="color-scheme"]').content = localStorage.getItem("color-scheme") ?? "light dark";
            } catch {}
        </script>

        <title>{{ $resolvedTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        @if($shouldNoIndex)
            <meta name="robots" content="noindex, nofollow">
        @else
            <meta name="robots" content="index, follow">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="DigitalCard">
        <meta property="og:title" content="{{ $resolvedTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $resolvedImage }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $resolvedTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $resolvedImage }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('head')

        <style>
            [x-cloak] { display: none !important; }
            .table-scroll-region {
                overflow: auto;
                max-inline-size: 100%;
            }
            .table-scroll-region table {
                border-collapse: separate;
                border-spacing: 0;
            }
            .table-scroll-region thead {
                position: sticky;
                inset-block-start: 0;
                z-index: 1;
            }
            @media (prefers-reduced-motion: reduce) {
                *, ::before, ::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                    scroll-behavior: auto !important;
                }
            }
        </style>
    </head>
    <body class="min-h-full flex flex-col font-sans antialiased text-slate-900 bg-paper selection:bg-accent-400 selection:text-slate-950">
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-xl focus:bg-slate-950 focus:px-4 focus:py-2.5 focus:text-xs focus:font-bold focus:text-accent-400 focus:shadow-lg">
            Pular para o conteúdo principal
        </a>

        <x-academic-banner />

        @if (session('success') || session('error') || session('warning') || $errors->any())
            <div x-data="{ visible: true }"
                 x-init="setTimeout(() => visible = false, 6000)"
                 x-show="visible"
                 x-cloak
                 role="status"
                 aria-live="polite"
                 class="fixed bottom-5 right-5 z-50 max-w-md w-full pointer-events-auto px-4 sm:px-0">
                @if (session('success'))
                    <div class="flex items-start gap-3.5 rounded-lg border border-line bg-ink p-4 text-paper shadow-float">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-money-600 text-paper" aria-hidden="true">
                            <x-icon name="check" class="size-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-money-300">Operação confirmada</p>
                            <p class="text-xs text-slate-200 mt-0.5 leading-relaxed">{{ session('success') }}</p>
                        </div>
                        <button @click="visible = false" type="button" aria-label="Fechar notificação" class="text-slate-300 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                @elseif (session('warning'))
                    <div class="flex items-start gap-3.5 rounded-lg border border-line bg-ink p-4 text-paper shadow-float">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-700 text-paper" aria-hidden="true">
                            <x-icon name="circle-alert" class="size-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-200">Atenção</p>
                            <p class="text-xs text-slate-200 mt-0.5 leading-relaxed">{{ session('warning') }}</p>
                        </div>
                        <button @click="visible = false" type="button" aria-label="Fechar notificação" class="text-slate-300 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                @else
                    <div class="flex items-start gap-3.5 rounded-lg border border-line bg-ink p-4 text-paper shadow-float">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-danger-600 text-white" aria-hidden="true">
                            <x-icon name="x" class="size-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-danger-300">Verifique os dados</p>
                            <p class="text-xs text-slate-200 mt-0.5 leading-relaxed">{{ session('error') ?? $errors->first() }}</p>
                        </div>
                        <button @click="visible = false" type="button" aria-label="Fechar notificação" class="text-slate-300 hover:text-white p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white border-b border-slate-200">
                <div class="max-w-[1440px] mx-auto py-5 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main id="main-content" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-ink text-slate-300 mt-12 border-t border-slate-800">
            <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" type="button"
                    class="w-full bg-ink hover:bg-slate-900 text-slate-200 text-xs font-semibold py-3.5 text-center transition border-b border-slate-800/80 min-h-[44px]">
                Voltar ao início da página ↑
            </button>

            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 pb-10 border-b border-slate-800">
                    <div class="lg:col-span-2 space-y-4 pr-4">
                        <x-application-logo :dark="true" />
                        <p class="text-xs text-slate-300 leading-relaxed max-w-sm">
                            Projeto universitário — ambiente de demonstração. Nenhum pagamento, cartão ou entrega é real. Sistema acadêmico desenvolvido em Laravel para estudo de arquitetura web, segurança e UX.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-white mb-3.5">Departamentos</h4>
                        <ul class="space-y-2 text-xs text-slate-300">
                            <li><a href="{{ route('catalog.category', 'eletronicos') }}" class="hover:text-accent-400 transition">Eletrônicos & Smartphones</a></li>
                            <li><a href="{{ route('catalog.category', 'informatica') }}" class="hover:text-accent-400 transition">Informática & Hardware</a></li>
                            <li><a href="{{ route('catalog.category', 'games') }}" class="hover:text-accent-400 transition">Consoles & Universo Gamer</a></li>
                            <li><a href="{{ route('catalog.category', 'casa-cozinha') }}" class="hover:text-accent-400 transition">Casa & Cozinha</a></li>
                            <li><a href="{{ route('catalog.category', 'cameras-drones') }}" class="hover:text-accent-400 transition">Câmeras & Drones</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-white mb-3.5">Cartão & Conta</h4>
                        <ul class="space-y-2 text-xs text-slate-300">
                            @auth
                                <li><a href="{{ route('card.show') }}" class="hover:text-accent-400 transition">Meu Cartão Virtual Fictício</a></li>
                                <li><a href="{{ route('orders.index') }}" class="hover:text-accent-400 transition">Meus Pedidos</a></li>
                                <li><a href="{{ route('wishlist.index') }}" class="hover:text-accent-400 transition">Lista de Desejos</a></li>
                                <li><a href="{{ route('cart.index') }}" class="hover:text-accent-400 transition">Meu Carrinho</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="hover:text-accent-400 transition">Entrar na Minha Conta</a></li>
                                <li><a href="{{ route('register') }}" class="hover:text-accent-400 transition">Criar Conta Demonstrativa</a></li>
                                <li><a href="{{ route('wishlist.index') }}" class="hover:text-accent-400 transition">Lista de Favoritos</a></li>
                            @endauth
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-white mb-3.5">Fluxo Acadêmico do Pedido</h4>
                        @php
                            $footerOrderSteps = [
                                1 => \App\Enums\OrderStatus::REQUESTED,
                                2 => \App\Enums\OrderStatus::SEPARATING,
                                3 => \App\Enums\OrderStatus::SHIPPED,
                                4 => \App\Enums\OrderStatus::DELIVERED,
                            ];
                        @endphp
                        <ul class="space-y-1.5 text-xs text-slate-300">
                            @foreach($footerOrderSteps as $stepNum => $statusCase)
                                <li class="p-2 rounded-md bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <span class="font-medium {{ $statusCase === \App\Enums\OrderStatus::DELIVERED ? 'text-money-400' : 'text-slate-200' }}">
                                        {{ $stepNum }}. {{ $statusCase->label() }}
                                    </span>
                                </li>
                            @endforeach
                            <li class="p-2 rounded-md bg-slate-900 border border-danger-900/50 flex items-center justify-between">
                                <span class="font-medium text-danger-400 inline-flex items-center gap-1">
                                    <x-icon name="x" class="size-3.5" />
                                    <span>{{ \App\Enums\OrderStatus::CANCELLED->label() }} (Estorno)</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-300 gap-4">
                    <p>© {{ date('Y') }} DigitalCard — Projeto universitário (ambiente de demonstração). Nenhum pagamento, cartão ou entrega é real.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
