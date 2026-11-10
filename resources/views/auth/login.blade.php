<x-guest-layout>
    <div class="pb-5 border-b border-line">
        <x-eyebrow>Acesso DigitalCard</x-eyebrow>
        <h1 class="font-serif text-2xl text-ink mt-1">Entre na sua conta</h1>
        <p class="text-xs text-slate-600 mt-1">Acesse seu cartão virtual e acompanhe seus pedidos demonstrativos.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4 pt-4">
        @csrf

        <div class="space-y-3.5">
            <div>
                <label for="email" class="block text-xs font-medium text-slate-700 mb-1">E-mail da conta</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="cliente@digitalcard.com"
                       class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-medium text-slate-700">Senha</label>
                    @if (Route::has('password.request'))
                        <a class="text-xs font-medium text-slate-600 hover:text-ink underline" href="{{ route('password.request') }}">
                            Esqueceu a senha?
                        </a>
                    @endif
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       placeholder="••••••••"
                       class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between text-xs">
                <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer text-slate-700">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="rounded border-line text-ink focus:ring-ink">
                    <span>Manter conectado</span>
                </label>
                <a href="{{ route('register') }}" class="font-medium text-ink hover:underline">Criar nova conta</a>
            </div>
        </div>

        <button type="submit"
                class="w-full font-semibold text-xs py-3 px-4 rounded-lg transition flex items-center justify-center gap-2 bg-ink hover:bg-slate-800 text-paper">
            <span>Entrar na loja e carteira</span>
            <x-icon name="arrow-right" class="size-4" />
        </button>

        <div class="pt-4 border-t border-line text-center">
            <a href="{{ route('admin.login') }}" class="text-xs font-medium text-slate-600 hover:text-ink underline">
                Acesso ao Painel Administrativo
            </a>
        </div>
    </form>
</x-guest-layout>
