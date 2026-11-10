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

        <title>{{ config('app.name', 'DigitalCard') }} — Acesso à Conta</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased min-h-screen flex flex-col justify-between bg-paper">
        <x-academic-banner />

        <header class="bg-surface border-b border-line py-4 px-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="{{ route('catalog.index') }}" class="focus:outline-none">
                    <x-application-logo />
                </a>
                <div class="flex items-center gap-3">
                    <button type="button"
                            onclick="window.toggleColorScheme && window.toggleColorScheme()"
                            class="p-2 rounded-lg border border-line bg-paper text-ink hover:border-ink transition flex items-center justify-center">
                        <span class="theme-toggle-moon items-center gap-1.5">
                            <x-icon name="moon" class="size-4" />
                            <span class="sr-only">Ativar modo escuro</span>
                        </span>
                        <span class="theme-toggle-sun items-center gap-1.5">
                            <x-icon name="sun" class="size-4" />
                            <span class="sr-only">Ativar modo claro</span>
                        </span>
                    </button>

                    <a href="{{ route('catalog.index') }}" class="text-xs font-medium text-slate-600 hover:text-ink transition flex items-center gap-1.5">
                        <x-icon name="arrow-left" class="size-3.5" />
                        <span>Voltar para a Loja</span>
                    </a>
                </div>
            </div>
        </header>

        <main id="main-content" class="flex-1 flex flex-col sm:justify-center items-center py-10 px-4">
            <div class="w-full sm:max-w-md bg-surface border border-line rounded-xl p-6 sm:p-8">
                {{ $slot }}
            </div>
        </main>

        <footer class="py-5 text-center text-xs text-slate-500 border-t border-line bg-surface">
            © {{ date('Y') }} DigitalCard — Projeto universitário de demonstração. Nenhum pagamento, cartão ou entrega é real.
        </footer>
    </body>
</html>
