<x-guest-layout>
    <div class="space-y-6">
        <div class="border-b border-line pb-4">
            <x-eyebrow>Acesso restrito — Backoffice</x-eyebrow>
            <h1 class="mt-1 font-serif text-2xl text-ink">Login Administrativo DigitalCard</h1>
            <p class="mt-1 text-xs text-slate-600">
                Exclusivo para administradores do ambiente acadêmico DigitalCard.
            </p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="admin_email" value="E-mail Administrativo" />
                <x-text-input id="admin_email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="admin_password" value="Senha" />
                <x-text-input id="admin_password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('login') }}" class="text-xs font-medium text-slate-600 hover:text-ink underline">
                    Voltar para login de clientes
                </a>

                <x-primary-button>
                    Entrar no Painel Admin
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
