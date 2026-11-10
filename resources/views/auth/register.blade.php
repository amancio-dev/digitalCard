<x-guest-layout>
    <div class="pb-5 border-b border-line">
        <x-eyebrow>Nova conta</x-eyebrow>
        <h2 class="font-serif text-2xl text-ink mt-1">Crie sua conta DigitalCard</h2>
        <p class="text-xs text-slate-600 mt-1">Seu cartão virtual é emitido automaticamente ao concluir o cadastro.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4 pt-4">
        @csrf

        <div>
            <label for="name" class="block text-xs font-medium text-slate-700 mb-1">Nome completo (impresso no cartão virtual)</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                   placeholder="Ex: André Amâncio"
                   class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div>
            <label for="email" class="block text-xs font-medium text-slate-700 mb-1">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   placeholder="seuemail@exemplo.com"
                   class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-medium text-slate-700 mb-1">Senha</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       placeholder="Mín. 8 caracteres"
                       class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-medium text-slate-700 mb-1">Confirmar senha</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       placeholder="Repita a senha"
                       class="block w-full rounded-lg border-line text-sm py-2.5 focus:border-ink focus:ring-ink">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        <button type="submit"
                class="w-full bg-ink hover:bg-slate-800 text-paper font-semibold text-xs py-3 px-4 rounded-lg transition flex items-center justify-center gap-2">
            <span>Criar conta e emitir cartão virtual</span>
            <x-icon name="arrow-right" class="size-4" />
        </button>

        <div class="text-center pt-2 border-t border-line">
            <a class="text-xs font-medium text-slate-600 hover:text-ink underline" href="{{ route('login') }}">
                Já possui uma conta? Faça login aqui
            </a>
        </div>
    </form>
</x-guest-layout>
