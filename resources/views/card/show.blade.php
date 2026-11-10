<x-app-layout :metaTitle="'Meu Cartão Virtual'" :metaDescription="'Consulte saldo, recarregue via PIX simulado e acompanhe o extrato do seu cartão virtual universitário.'">
    @php
        $initialRevealedPan = session('revealed_pan');
        $initialRevealedCvv = session('revealed_cvv');
        $pixKey = (string) config('digitalcard.pix.key', 'simulacao-pix@digitalcard.edu.br');
    @endphp

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8"
         x-data="{
            showBalance: true,
            flipped: false,
            copied: false,
            toastMessage: 'Copiado para a área de transferência!',
            dragging: false,
            rechargeAmount: 250,
            receiptName: '',
            receiptSize: '',
            receiptPreviewUrl: '',
            isPdf: false,
            txFilter: 'todas',
            cardBalanceFormatted: '{{ number_format((float) $card->balance, 2, ',', '.') }}',
            cardNumber: '{{ $initialRevealedPan ?: $card->masked_number }}',
            cvvCode: '{{ $initialRevealedCvv ?: '•••' }}',
            isRevealed: {{ $initialRevealedPan ? 'true' : 'false' }},
            revealPin: '',
            revealError: '',
            revealLoading: false,
            pixRemainingSeconds: {{ $activePixCharge && $activePixCharge->expires_at ? max(0, (int) now()->diffInSeconds($activePixCharge->expires_at, false)) : 0 }},
            pixStatus: '{{ $activePixCharge->status ?? '' }}',
            init() {
                @if($activePixCharge)
                    setInterval(() => {
                        if (this.pixRemainingSeconds > 0 && this.pixStatus === 'pendente') {
                            this.pixRemainingSeconds--;
                        }
                    }, 1000);

                    const pollTimer = setInterval(async () => {
                        if (this.pixStatus !== 'pendente') {
                            clearInterval(pollTimer);
                            return;
                        }
                        try {
                            const res = await fetch('{{ route('card.pix.status', $activePixCharge) }}', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            this.pixRemainingSeconds = data.remaining_seconds ?? this.pixRemainingSeconds;
                            if (data.status === 'aprovado') {
                                this.pixStatus = 'aprovado';
                                this.cardBalanceFormatted = data.card_balance_formatted;
                                this.showToast('Pagamento PIX confirmado! Saldo atualizado para R$ ' + data.card_balance_formatted);
                                clearInterval(pollTimer);
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } catch (e) {}
                    }, 5000);
                @endif
            },
            formatCountdown(sec) {
                const m = String(Math.floor(sec / 60)).padStart(2, '0');
                const s = String(sec % 60).padStart(2, '0');
                return m + 'm : ' + s + 's';
            },
            showToast(msg) {
                this.toastMessage = msg;
                this.copied = true;
                setTimeout(() => this.copied = false, 3500);
            },
            copyText(text, label = 'Copiado para a área de transferência!') {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
                this.showToast(label);
            },
            async revealCardData() {
                this.revealError = '';
                this.revealLoading = true;
                try {
                    const res = await fetch('{{ route('card.reveal-cvv') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ pin: this.revealPin })
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        this.revealError = data.errors?.pin?.[0] || data.message || 'PIN inválido.';
                    } else {
                        this.cardNumber = data.card_number;
                        this.cvvCode = data.cvv;
                        this.isRevealed = true;
                        this.revealPin = '';
                        this.showToast('Número completo e CVV revelados temporariamente!');
                    }
                } catch (e) {
                    this.revealError = 'Erro ao validar o PIN.';
                } finally {
                    this.revealLoading = false;
                }
            },
            setQuickAmount(val) {
                this.rechargeAmount = val;
            },
            handleFile(file) {
                if (! file) {
                    this.receiptName = '';
                    this.receiptSize = '';
                    this.receiptPreviewUrl = '';
                    this.isPdf = false;
                    return;
                }
                this.receiptName = file.name;
                this.receiptSize = file.size >= 1048576
                    ? (file.size / 1048576).toFixed(2) + ' MB'
                    : (file.size / 1024).toFixed(1) + ' KB';
                this.isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                this.receiptPreviewUrl = (! this.isPdf && file.type.startsWith('image/'))
                    ? URL.createObjectURL(file)
                    : '';
            },
            handleDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer.files[0];
                if (! file) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                this.$refs.receipt.files = transfer.files;
                this.handleFile(file);
            }
         }">

        <div x-show="copied" x-cloak
             x-transition
             role="status"
             aria-live="polite"
             class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-lg border border-slate-800 bg-ink px-4 py-3 text-xs font-medium text-paper shadow-sm">
            <x-icon name="check" class="size-4 text-money-400" />
            <span x-text="toastMessage"></span>
        </div>

        <div class="flex flex-col justify-between gap-4 border-b border-line pb-5 md:flex-row md:items-end">
            <div>
                <x-eyebrow>Loja universitária com cartão virtual</x-eyebrow>
                <h1 class="mt-1 font-serif text-3xl text-ink">Meu Cartão Virtual</h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium {{ $card->is_active ? 'border-money-200 bg-money-50 text-money-800' : 'border-danger-200 bg-danger-50 text-danger-800' }}">
                    <span class="size-2 rounded-full {{ $card->is_active ? 'bg-money-500' : 'bg-danger-500' }}"></span>
                    {{ $card->is_active ? 'Cartão ativo para compras' : 'Cartão bloqueado temporariamente' }}
                </span>
                <a href="{{ route('catalog.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-ink hover:bg-slate-800 px-4 py-2.5 text-xs font-semibold text-paper transition">
                    <span>Ir às compras</span>
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            <div class="space-y-5 lg:col-span-5">
                <x-virtual-card :card="$card" :interactive="true" />

                <div class="grid grid-cols-3 gap-2.5">
                    <button @click="isRevealed ? copyText(cardNumber, 'Número copiado!') : showToast('Confirme seu PIN abaixo para revelar e copiar o número completo.')"
                            type="button"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-2.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                        <x-icon name="copy" class="size-3.5 text-slate-500" />
                        <span>Copiar Nº</span>
                    </button>

                    <button @click="showBalance = ! showBalance" type="button"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-2.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                        <x-icon name="eye" class="size-3.5 text-slate-500" />
                        <span x-text="showBalance ? 'Ocultar saldo' : 'Mostrar saldo'"></span>
                    </button>

                    <button @click="flipped = ! flipped" type="button"
                            class="flex items-center justify-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-2.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                        <x-icon name="rotate-ccw" class="size-3.5 text-slate-500" />
                        <span>Virar cartão</span>
                    </button>
                </div>

                <div class="rounded-xl border border-line bg-surface p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Revelar número completo e CVV</h2>
                            <p class="text-xs text-slate-600 mt-0.5">Digite seu PIN de 4 dígitos para ver o número completo e o CVV.</p>
                        </div>
                    </div>

                    <form action="{{ route('card.reveal-cvv') }}" method="POST" @submit.prevent="revealCardData()" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <label for="reveal_pin_field" class="sr-only">PIN de 4 dígitos</label>
                        <input id="reveal_pin_field" type="password" name="pin" x-model="revealPin" inputmode="numeric" maxlength="4" required
                               placeholder="PIN de 4 dígitos"
                               class="flex-1 rounded-lg border-line text-xs font-mono py-2.5 px-3">
                        <button type="submit" :disabled="revealLoading"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-ink hover:bg-slate-800 text-paper font-semibold text-xs px-4 py-2.5 transition">
                            <x-icon name="lock" class="size-3.5" />
                            <span x-text="revealLoading ? 'Verificando...' : 'Revelar com PIN'">Revelar com PIN</span>
                        </button>
                    </form>

                    <p x-show="revealError" x-cloak class="text-xs font-medium text-danger-600" x-text="revealError"></p>

                    <div x-show="isRevealed" x-cloak class="rounded-lg border border-money-200 bg-money-50 p-3 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">Nº do cartão: <strong class="font-mono tabular-nums" x-text="cardNumber"></strong></span>
                            <button @click="copyText(cardNumber, 'Número completo copiado!')" type="button" class="font-medium text-money-800 underline">Copiar</button>
                        </div>
                        <p class="font-medium text-ink">CVV: <strong class="font-mono tabular-nums text-money-900" x-text="cvvCode"></strong></p>
                    </div>
                </div>

                <div class="space-y-4 rounded-xl border border-line bg-surface p-5">
                    <div class="border-b border-line pb-3">
                        <h2 class="text-sm font-semibold text-ink">Segurança e controle do cartão</h2>
                        <p class="text-xs text-slate-600 mt-0.5">Bloqueio temporário e configuração da senha PIN de 4 dígitos.</p>
                    </div>

                    <form action="{{ route('card.toggle-block') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="flex w-full items-center justify-between rounded-lg border px-4 py-2.5 text-left text-xs font-medium transition {{ $card->is_active ? 'border-danger-200 bg-danger-50 text-danger-700 hover:bg-danger-100' : 'border-money-200 bg-money-50 text-money-700 hover:bg-money-100' }}">
                            <span>{{ $card->is_active ? 'Bloquear cartão temporariamente' : 'Desbloquear cartão agora' }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-surface">{{ $card->is_active ? 'Bloquear' : 'Ativar' }}</span>
                        </button>
                    </form>

                    <form action="{{ route('card.update-pin') }}" method="POST" class="space-y-3 border-t border-line pt-4">
                        @csrf
                        @method('PATCH')

                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-700">PIN de 4 dígitos</span>
                            <span class="rounded px-2 py-0.5 text-xs font-medium {{ $card->security_pin ? 'bg-money-50 text-money-800 border border-money-200' : 'bg-accent-50 text-accent-900 border border-accent-200' }}">
                                {{ $card->security_pin ? 'PIN configurado' : 'Pendente (obrigatório para comprar)' }}
                            </span>
                        </div>

                        @if ($card->security_pin)
                            <div>
                                <label for="current_pin_input" class="sr-only">PIN atual</label>
                                <input id="current_pin_input" type="password" name="current_pin" inputmode="numeric" maxlength="4" placeholder="PIN atual (4 dígitos)"
                                       class="block w-full rounded-lg border-line text-xs py-2">
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="new_pin_input" class="sr-only">Novo PIN</label>
                                <input id="new_pin_input" type="password" name="pin" inputmode="numeric" maxlength="4" required placeholder="Novo PIN (4 dígitos)"
                                       class="block w-full rounded-lg border-line text-xs py-2">
                            </div>
                            <div>
                                <label for="confirm_pin_input" class="sr-only">Confirmar novo PIN</label>
                                <input id="confirm_pin_input" type="password" name="pin_confirmation" inputmode="numeric" maxlength="4" required placeholder="Confirmar PIN"
                                       class="block w-full rounded-lg border-line text-xs py-2">
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full rounded-lg bg-ink px-3 py-2.5 text-xs font-semibold text-paper transition hover:bg-slate-800">
                            {{ $card->security_pin ? 'Atualizar PIN de segurança' : 'Cadastrar PIN de segurança' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-7">
                <div class="rounded-xl border border-line bg-surface p-6 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-line pb-4">
                        <div>
                            <x-eyebrow>PIX Simulado</x-eyebrow>
                            <h2 class="mt-1 text-lg font-semibold text-ink">Recarregar saldo via PIX simulado</h2>
                            <p class="text-xs text-slate-600 mt-0.5">Escolha o valor, gere o PIX e pague com o QR Code ou o código copia e cola.</p>
                        </div>
                        <span class="text-xs font-mono text-slate-600 bg-paper-dim px-2.5 py-1 rounded-md border border-line">
                            Chave: {{ $pixKey }}
                        </span>
                    </div>

                    <form action="{{ route('card.pix.generate') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="pix_recharge_amount" class="mb-2 block text-xs font-medium text-slate-700">
                                1. Escolha ou digite o valor da recarga PIX (R$)
                            </label>
                            <div class="grid grid-cols-5 gap-2 mb-2.5">
                                @foreach([100, 250, 500, 1000, 2500] as $preset)
                                    <button type="button" @click="setQuickAmount({{ $preset }})"
                                            :class="rechargeAmount == {{ $preset }} ? 'bg-ink text-paper border-ink' : 'bg-paper text-slate-700 border-line hover:bg-paper-dim'"
                                            class="py-2 px-2 rounded-lg border font-mono tabular-nums text-xs font-medium transition">
                                        +R$ {{ number_format($preset, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2.5">
                                <div class="relative flex-1">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                        <span class="text-sm font-medium text-slate-500">R$</span>
                                    </div>
                                    <input id="pix_recharge_amount" type="number" step="0.01" min="1.00" max="999999.99" name="amount" x-model="rechargeAmount" required
                                           class="block w-full rounded-lg border-line py-2.5 pl-11 font-mono tabular-nums text-base font-semibold text-ink focus:border-ink focus:ring-ink">
                                </div>
                                <button type="submit"
                                        class="rounded-lg bg-ink hover:bg-slate-800 px-5 py-3 text-xs font-semibold text-paper transition whitespace-nowrap">
                                    Gerar PIX agora
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($activePixCharge)
                        <div class="rounded-xl border border-money-200 bg-money-50/60 p-5 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-money-200 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex size-2 rounded-full bg-money-500"></span>
                                    <span class="text-xs font-medium text-money-950">
                                        Cobrança ativa: <code class="font-mono">{{ $activePixCharge->txid }}</code>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-slate-700">Expira em:</span>
                                    <span class="rounded bg-ink px-2 py-0.5 font-mono tabular-nums font-medium text-paper"
                                          x-text="formatCountdown(pixRemainingSeconds)"></span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-5">
                                <div class="shrink-0 rounded-xl border border-line bg-surface p-3">
                                    {!! $activePixQrSvg !!}
                                </div>

                                <div class="flex-1 space-y-3 text-xs w-full min-w-0">
                                    <div class="flex items-baseline justify-between">
                                        <span class="font-medium text-slate-700">Valor da cobrança:</span>
                                        <span class="font-mono tabular-nums text-xl font-semibold text-money-800">
                                            R$ {{ number_format((float) $activePixCharge->amount, 2, ',', '.') }}
                                        </span>
                                    </div>

                                    <div>
                                        <label for="pix_brcode_copy" class="block text-xs font-medium text-slate-700 mb-1">
                                            PIX copia e cola
                                        </label>
                                        <div class="flex gap-2">
                                            <input id="pix_brcode_copy" type="text" readonly value="{{ $activePixCharge->pix_payload }}"
                                                   class="flex-1 min-w-0 rounded-lg border-line bg-surface font-mono text-xs py-2 px-3 text-slate-800">
                                            <button @click="copyText('{{ $activePixCharge->pix_payload }}', 'Código PIX copia e cola copiado!')"
                                                    type="button"
                                                    class="rounded-lg bg-ink hover:bg-slate-800 px-3.5 py-2 text-xs font-semibold text-paper transition shrink-0">
                                                Copiar
                                            </button>
                                        </div>
                                    </div>

                                    @if(! app()->isProduction())
                                        <form action="{{ route('webhooks.pix.simulate') }}" method="POST" class="pt-1">
                                            @csrf
                                            <input type="hidden" name="txid" value="{{ $activePixCharge->txid }}">
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-money-600 hover:bg-money-700 px-4 py-3 text-xs font-semibold text-paper transition flex items-center justify-center gap-2">
                                                <x-icon name="qr-code" class="size-4" />
                                                <span>Simular pagamento (Simulação acadêmica)</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <details class="group rounded-lg border border-line bg-paper p-4 text-xs">
                        <summary class="cursor-pointer font-medium text-ink flex items-center justify-between list-none">
                            <span>Alternativa secundária: enviar comprovante manual (sujeito à aprovação do administrador)</span>
                            <x-icon name="chevron-down" class="size-4 text-slate-500 group-open:rotate-180 transition-transform" />
                        </summary>

                        <form action="{{ route('card.recharge') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4 pt-4 border-t border-line">
                            @csrf
                            <div>
                                <label for="manual_recharge_amount" class="mb-1 block text-xs font-medium text-slate-700">Valor do comprovante (R$)</label>
                                <input id="manual_recharge_amount" type="number" step="0.01" min="1.00" name="amount" x-model="rechargeAmount" required
                                       class="block w-full rounded-lg border-line py-2 px-3 font-mono tabular-nums text-sm font-semibold text-ink">
                            </div>

                            <div>
                                <label for="receipt-upload" class="mb-1 block text-xs font-medium text-slate-700">Arquivo do comprovante (JPG, PNG ou PDF até 5MB)</label>
                                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required id="receipt-upload"
                                       x-ref="receipt"
                                       @change="handleFile($event.target.files[0])"
                                       class="block w-full text-xs text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-2 file:text-xs file:font-semibold file:text-paper">
                            </div>

                            <button type="submit"
                                    class="w-full rounded-lg bg-ink hover:bg-slate-800 px-4 py-2.5 text-xs font-semibold text-paper transition">
                                Enviar comprovante para análise
                            </button>
                        </form>
                    </details>
                </div>

                <div class="overflow-hidden rounded-xl border border-line bg-surface">
                    <div class="flex flex-col justify-between gap-3 border-b border-line p-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 id="ledger-table-caption" class="text-sm font-semibold text-ink">Extrato de movimentações</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Histórico de recargas, compras, cashback e estornos.</p>
                        </div>

                        <div class="inline-flex items-center rounded-lg bg-paper-dim p-1 text-xs font-medium">
                            <button @click="txFilter = 'todas'" type="button"
                                    :class="txFilter === 'todas' ? 'bg-surface text-ink shadow-xs' : 'text-slate-600 hover:text-ink'"
                                    class="rounded-md px-3 py-1.5 transition">
                                Todas ({{ $card->transactions->count() }})
                            </button>
                            <button @click="txFilter = 'entradas'" type="button"
                                    :class="txFilter === 'entradas' ? 'bg-money-600 text-paper shadow-xs' : 'text-slate-600 hover:text-ink'"
                                    class="rounded-md px-3 py-1.5 transition">
                                Entradas
                            </button>
                            <button @click="txFilter = 'saidas'" type="button"
                                    :class="txFilter === 'saidas' ? 'bg-danger-600 text-paper shadow-xs' : 'text-slate-600 hover:text-ink'"
                                    class="rounded-md px-3 py-1.5 transition">
                                Saídas
                            </button>
                        </div>
                    </div>

                    <div class="md:hidden divide-y divide-line">
                        @forelse($card->transactions as $transaction)
                            @php
                                $isCredit = (float) $transaction->amount >= 0;
                                $typeLabels = [
                                    'recarga' => ['Recarga PIX', 'bg-money-50 text-money-700 border-money-200'],
                                    'compra' => ['Compra Loja', 'bg-danger-50 text-danger-700 border-danger-200'],
                                    'cashback' => ['Cashback', 'bg-accent-50 text-accent-800 border-accent-200'],
                                    'estorno' => ['Estorno', 'bg-paper text-slate-700 border-line'],
                                ];
                                [$typeLabel, $typeClasses] = $typeLabels[$transaction->type] ?? [$transaction->type, 'bg-paper text-slate-600 border-line'];
                            @endphp
                            <div x-show="txFilter === 'todas' || (txFilter === 'entradas' && {{ $isCredit ? 'true' : 'false' }}) || (txFilter === 'saidas' && {{ ! $isCredit ? 'true' : 'false' }})"
                                 class="p-4 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-ink">{{ $transaction->description }}</span>
                                    <span class="font-mono tabular-nums font-semibold whitespace-nowrap {{ $isCredit ? 'text-money-700' : 'text-danger-700' }}">
                                        {{ $isCredit ? '+' : '-' }} R$ {{ number_format(abs((float) $transaction->amount), 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500">
                                    <span>{{ $transaction->created_at->format('d/m/Y H:i') }} • {{ $typeLabel }}</span>
                                    <span class="font-mono tabular-nums">Saldo: R$ {{ number_format((float) $transaction->balance_after, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="p-6 text-center text-xs text-slate-500">Nenhuma movimentação registrada ainda.</p>
                        @endforelse
                    </div>

                    <div tabindex="0" role="region" aria-labelledby="ledger-table-caption" class="hidden md:block table-scroll-region">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="border-b border-line bg-paper text-xs font-medium text-slate-500">
                                    <th class="px-5 py-3">Data</th>
                                    <th class="px-5 py-3">Lançamento</th>
                                    <th class="px-5 py-3">Natureza</th>
                                    <th class="px-5 py-3 text-right">Valor</th>
                                    <th class="px-5 py-3 text-right">Saldo final</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line text-xs">
                                @forelse($card->transactions as $transaction)
                                    @php
                                        $isCredit = (float) $transaction->amount >= 0;
                                        $typeLabels = [
                                            'recarga' => ['Recarga PIX', 'bg-money-50 text-money-700 border-money-200'],
                                            'compra' => ['Compra Loja', 'bg-danger-50 text-danger-700 border-danger-200'],
                                            'cashback' => ['Cashback', 'bg-accent-50 text-accent-800 border-accent-200'],
                                            'estorno' => ['Estorno', 'bg-paper text-slate-700 border-line'],
                                        ];
                                        [$typeLabel, $typeClasses] = $typeLabels[$transaction->type] ?? [$transaction->type, 'bg-paper text-slate-600 border-line'];
                                    @endphp
                                    <tr x-show="txFilter === 'todas' || (txFilter === 'entradas' && {{ $isCredit ? 'true' : 'false' }}) || (txFilter === 'saidas' && {{ ! $isCredit ? 'true' : 'false' }})"
                                        class="transition hover:bg-paper/60">
                                        <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-5 py-3.5 font-medium text-ink">{{ $transaction->description }}</td>
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex rounded border px-2 py-0.5 text-xs font-medium {{ $typeClasses }}">{{ $typeLabel }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono tabular-nums font-semibold whitespace-nowrap {{ $isCredit ? 'text-money-700' : 'text-danger-700' }}">
                                            {{ $isCredit ? '+' : '-' }} R$ {{ number_format(abs((float) $transaction->amount), 2, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono tabular-nums text-slate-700 whitespace-nowrap">
                                            R$ {{ number_format((float) $transaction->balance_after, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                            Nenhuma movimentação registrada ainda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-line bg-surface">
                    <div class="flex items-center justify-between border-b border-line p-5">
                        <div>
                            <h3 class="text-sm font-semibold text-ink">Histórico de recargas</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Acompanhe suas cobranças PIX simuladas e comprovantes enviados.</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-md bg-paper-dim text-xs font-medium text-slate-700">
                            {{ $card->recharges->count() }} {{ $card->recharges->count() === 1 ? 'registro' : 'registros' }}
                        </span>
                    </div>

                    <div class="md:hidden divide-y divide-line">
                        @forelse($card->recharges as $recharge)
                            <div class="p-4 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono tabular-nums font-semibold text-ink">#REC-{{ str_pad((string) $recharge->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="font-mono tabular-nums font-semibold text-ink">
                                        R$ {{ number_format((float) ($recharge->approved_amount ?? $recharge->amount), 2, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500">
                                    <span>{{ $recharge->created_at->format('d/m/Y H:i') }}</span>
                                    <span>{{ $recharge->status === 'aprovado' ? 'Aprovado' : ($recharge->status === 'recusado' ? 'Recusado' : 'Pendente') }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="p-6 text-center text-xs text-slate-500">Nenhuma recarga registrada até o momento.</p>
                        @endforelse
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="border-b border-line bg-paper text-xs font-medium text-slate-500">
                                    <th class="px-5 py-3">Protocolo</th>
                                    <th class="px-5 py-3">Data</th>
                                    <th class="px-5 py-3">Valor</th>
                                    <th class="px-5 py-3">Origem / Arquivo</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line text-xs">
                                @forelse($card->recharges as $recharge)
                                    <tr class="transition hover:bg-paper/50">
                                        <td class="px-5 py-3.5 font-mono tabular-nums font-medium text-slate-700">
                                            #REC-{{ str_pad((string) $recharge->id, 5, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-600">{{ $recharge->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-5 py-3.5 font-mono tabular-nums font-semibold text-ink whitespace-nowrap">
                                            R$ {{ number_format((float) ($recharge->approved_amount ?? $recharge->amount), 2, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if($recharge->receipt_path)
                                                <a href="{{ route('recharges.receipt', $recharge) }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 rounded-md border border-line bg-paper px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-paper-dim">
                                                    Ver comprovante
                                                </a>
                                            @else
                                                <span class="font-mono text-xs text-slate-600">{{ $recharge->txid ?? 'PIX Simulado' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if ($recharge->status === 'aprovado')
                                                <span class="inline-flex items-center gap-1.5 rounded border border-money-200 bg-money-50 px-2 py-0.5 text-xs font-medium text-money-800">
                                                    <span class="size-1.5 rounded-full bg-money-500"></span> Aprovado
                                                </span>
                                            @elseif ($recharge->status === 'recusado')
                                                <span class="inline-flex items-center gap-1.5 rounded border border-danger-200 bg-danger-50 px-2 py-0.5 text-xs font-medium text-danger-800">
                                                    <span class="size-1.5 rounded-full bg-danger-500"></span> Recusado
                                                </span>
                                                @if ($recharge->admin_notes)
                                                    <span class="mt-1 block text-xs text-danger-600">Motivo: {{ $recharge->admin_notes }}</span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded border border-accent-200 bg-accent-50 px-2 py-0.5 text-xs font-medium text-accent-900">
                                                    <span class="size-1.5 rounded-full bg-accent-500"></span> Pendente
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                            Nenhuma recarga registrada até o momento.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
