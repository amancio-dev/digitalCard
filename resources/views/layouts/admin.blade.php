@props(['title' => 'Painel Administrativo'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} — Backoffice DigitalCard</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
            .table-scroll-region {
                overflow: auto;
                max-inline-size: 100%;
            }
        </style>
    </head>
    <body class="min-h-full flex flex-col font-sans antialiased text-slate-900 bg-slate-100"
          x-data="{ sidebarOpen: false }">

        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar Desktop e Drawer Mobile (U4) -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
                   class="fixed inset-y-0 left-0 z-50 w-64 bg-ink text-white flex flex-col justify-between transition-transform duration-300 md:static md:translate-x-0 shrink-0">
                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-md bg-paper text-ink flex items-center justify-center font-serif text-lg">
                            DC
                        </div>
                        <div>
                            <span class="font-serif text-white text-xl leading-none block">
                                Painel Admin
                            </span>
                            <span class="text-xs text-slate-300">
                                DigitalCard
                            </span>
                        </div>
                    </a>
                    <button @click="sidebarOpen = false" type="button" aria-label="Fechar menu lateral" class="md:hidden text-slate-300 hover:text-white">
                        <x-icon name="x" class="size-4" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-sm font-medium">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-accent-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span>Visão geral</span>
                    </a>

                    <a href="{{ route('admin.recharges.index') }}"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md transition {{ request()->routeIs('admin.recharges.*') ? 'bg-slate-800 text-accent-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span>Recargas</span>
                        @php($pRech = \App\Models\Recharge::where('status', \App\Models\Recharge::STATUS_PENDING)->count())
                        @if($pRech > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs tabular-nums bg-danger-600 text-white font-semibold">{{ $pRech }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.orders.index') }}"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md transition {{ request()->routeIs('admin.orders.*') ? 'bg-slate-800 text-accent-400 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span>Pedidos</span>
                        @php($pOrd = \App\Models\Order::whereIn('status', [\App\Models\Order::STATUS_REQUESTED, \App\Models\Order::STATUS_SEPARATING, \App\Models\Order::STATUS_SHIPPED])->count())
                        @if($pOrd > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs tabular-nums bg-slate-700 text-white font-semibold">{{ $pOrd }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.dashboard') }}#produtos"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        <span>Produtos</span>
                    </a>

                    <a href="{{ route('admin.dashboard') }}#cupons"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        <span>Cupons</span>
                    </a>

                    <a href="{{ route('admin.reports.sales.csv') }}"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        <span>Exportar vendas (CSV)</span>
                    </a>
                </nav>

                <div class="p-4 border-t border-slate-800 space-y-2">
                    <a href="{{ route('catalog.index') }}"
                       class="block text-center py-2 px-3 rounded-md border border-slate-700 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-medium transition">
                        Voltar à Loja
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-xs font-medium text-danger-300 hover:text-danger-200">
                            Sair
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Área Central do Admin -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                <header class="bg-surface border-b border-line px-4 sm:px-6 py-3 flex items-center justify-between md:hidden">
                    <button @click="sidebarOpen = true" type="button" aria-label="Abrir menu administrativo"
                            class="p-2 rounded-md text-slate-700 hover:bg-slate-100">
                        <x-icon name="menu" class="size-5" />
                    </button>
                    <span class="font-serif text-lg text-ink">Painel Admin</span>
                    <a href="{{ route('catalog.index') }}" class="text-xs font-medium text-ink underline">Voltar à Loja</a>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
