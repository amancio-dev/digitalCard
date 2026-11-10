<x-admin-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8"
         x-data="{
            previewModalOpen: false,
            previewUrl: '',
            previewType: '',
            rejectModalOpen: false,
            rejectAction: '',
            rechargeAmount: '',
            clientName: '',
            openReceipt(url, type = 'image') {
                this.previewUrl = url;
                this.previewType = type;
                this.previewModalOpen = true;
            },
            openReject(action, name, amount) {
                this.rejectAction = action;
                this.clientName = name;
                this.rechargeAmount = amount;
                this.rejectModalOpen = true;
            }
         }">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-line gap-4">
            <div>
                <x-eyebrow>Backoffice • Liberação de saldo</x-eyebrow>
                <h1 class="font-serif text-3xl text-ink mt-1">
                    Aprovação de Recargas no Cartão Virtual
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Analise e aprove os comprovantes de pagamento enviados pelos clientes para liberar saldo.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.orders.index') }}"
                   class="bg-surface hover:bg-paper-dim border border-line text-ink text-xs font-medium px-4 py-2.5 rounded-lg transition inline-flex items-center gap-1.5">
                    <x-icon name="package" class="size-4 text-slate-500" />
                    <span>Gestão de Pedidos</span>
                    <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-money-50 border border-money-200 text-money-900 rounded-xl flex items-center gap-3">
                <x-icon name="circle-check" class="size-5 text-money-700 shrink-0" />
                <div>
                    <p class="font-semibold text-sm text-money-950">Ação concluída com sucesso</p>
                    <p class="text-xs text-money-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-danger-50 border border-danger-200 text-danger-900 rounded-xl">
                <p class="font-semibold text-xs mb-1">Atenção ao processar a solicitação:</p>
                <ul class="text-xs list-disc list-inside space-y-0.5 text-danger-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $pendingTotal = \App\Models\Recharge::where('status', 'pendente')->sum('amount');
            $approvedTotal = \App\Models\Recharge::where('status', 'aprovado')->sum('amount');
        @endphp

        <div class="grid grid-cols-1 divide-y sm:grid-cols-3 sm:divide-y-0 sm:divide-x divide-line rounded-xl border border-line bg-surface">
            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Recargas aguardando aprovação</span>
                    <x-icon name="clock" class="size-4 text-accent-600" />
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="font-mono tabular-nums text-2xl font-semibold text-ink">{{ $counts['pendente'] }}</span>
                    <span class="font-mono tabular-nums text-xs font-medium text-accent-800 bg-accent-50 px-2.5 py-0.5 rounded border border-accent-200 whitespace-nowrap">
                        Total: R$ {{ number_format($pendingTotal, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Total aprovado em saldo</span>
                    <x-icon name="circle-check" class="size-4 text-money-600" />
                </div>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="font-mono tabular-nums text-2xl font-semibold text-money-700 whitespace-nowrap">
                        R$ {{ number_format($approvedTotal, 2, ',', '.') }}
                    </span>
                    <span class="text-xs font-medium text-money-700 bg-money-50 px-2 py-0.5 rounded border border-money-200">
                        {{ $counts['aprovado'] }} liberadas
                    </span>
                </div>
            </div>

            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Regra de segurança</span>
                    <x-icon name="shield-check" class="size-4" />
                </div>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    A liberação de saldo credita imediatamente o valor confirmado na carteira do titular, impedindo aprovações em duplicidade.
                </p>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-line overflow-hidden">
            <div class="flex items-center border-b border-line px-6 pt-4 bg-paper gap-2 overflow-x-auto">
                <a href="{{ route('admin.recharges.index', ['tab' => 'pendente']) }}"
                   class="pb-3.5 px-3.5 text-xs font-medium transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $currentTab === 'pendente' ? 'border-accent-600 text-ink font-semibold' : 'border-transparent text-slate-500 hover:text-ink' }}">
                    <span class="size-2 rounded-full bg-accent-500"></span>
                    <span>Aguardando aprovação</span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono {{ $currentTab === 'pendente' ? 'bg-accent-100 text-accent-900' : 'bg-paper-dim text-slate-700' }}">
                        {{ $counts['pendente'] }}
                    </span>
                </a>

                <a href="{{ route('admin.recharges.index', ['tab' => 'aprovado']) }}"
                   class="pb-3.5 px-3.5 text-xs font-medium transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $currentTab === 'aprovado' ? 'border-money-600 text-ink font-semibold' : 'border-transparent text-slate-500 hover:text-ink' }}">
                    <span class="size-2 rounded-full bg-money-500"></span>
                    <span>Aprovadas recentemente</span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono bg-paper-dim text-slate-700">
                        {{ $counts['aprovado'] }}
                    </span>
                </a>

                <a href="{{ route('admin.recharges.index', ['tab' => 'recusado']) }}"
                   class="pb-3.5 px-3.5 text-xs font-medium transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $currentTab === 'recusado' ? 'border-danger-600 text-ink font-semibold' : 'border-transparent text-slate-500 hover:text-ink' }}">
                    <span class="size-2 rounded-full bg-danger-500"></span>
                    <span>Recusadas</span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono bg-paper-dim text-slate-700">
                        {{ $counts['recusado'] }}
                    </span>
                </a>

                <a href="{{ route('admin.recharges.index', ['tab' => 'todos']) }}"
                   class="pb-3.5 px-3.5 text-xs font-medium transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $currentTab === 'todos' ? 'border-ink text-ink font-semibold' : 'border-transparent text-slate-500 hover:text-ink' }}">
                    <span>Todas as recargas</span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono bg-paper-dim text-slate-700">
                        {{ $counts['todos'] }}
                    </span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line text-xs font-medium text-slate-500 bg-paper">
                            <th class="py-3.5 px-6">ID / Data</th>
                            <th class="py-3.5 px-6">Titular e Cartão Virtual</th>
                            <th class="py-3.5 px-6">Saldo atual</th>
                            <th class="py-3.5 px-6">Valor solicitado</th>
                            <th class="py-3.5 px-6">Comprovante de pagamento</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Ação de aprovação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line text-xs">
                        @forelse($recharges as $recharge)
                            @php
                                $card = $recharge->virtualCard;
                                $user = $card?->user;
                                $currentBalance = (float) ($card?->balance ?? 0);
                                $requestedAmount = (float) $recharge->amount;
                                $projectedBalance = $currentBalance + $requestedAmount;
                            @endphp

                            <tr class="hover:bg-paper/60 transition {{ $recharge->status === 'pendente' ? 'bg-accent-50/10' : '' }}">
                                <td class="py-4 px-6">
                                    <span class="font-mono tabular-nums font-semibold text-ink block">
                                        #REC-{{ str_pad((string) $recharge->id, 5, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        {{ $recharge->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </td>

                                <td class="py-4 px-6">
                                    <p class="font-semibold text-ink text-sm">{{ $user->name ?? 'Cliente Desconhecido' }}</p>
                                    <p class="text-xs text-slate-500 font-mono tabular-nums">{{ $card->masked_number ?? 'N/A' }}</p>
                                    <span class="text-xs text-slate-500">{{ $user->email ?? '' }}</span>
                                </td>

                                <td class="py-4 px-6 font-mono tabular-nums text-slate-600 font-medium whitespace-nowrap">
                                    R$ {{ number_format($currentBalance, 2, ',', '.') }}
                                </td>

                                <td class="py-4 px-6">
                                    <span class="font-mono tabular-nums font-semibold text-sm text-money-700 block whitespace-nowrap">
                                        R$ {{ number_format($requestedAmount, 2, ',', '.') }}
                                    </span>
                                    @if($recharge->status === 'pendente')
                                        <span class="text-xs text-slate-500 whitespace-nowrap">
                                            Novo saldo: R$ {{ number_format($projectedBalance, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-6">
                                    <button @click="openReceipt('{{ route('recharges.receipt', $recharge) }}', '{{ str_ends_with(strtolower($recharge->receipt_path), '.pdf') ? 'pdf' : 'image' }}')"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-ink bg-surface hover:bg-paper-dim px-3.5 py-1.5 rounded-lg transition border border-line">
                                        <x-icon name="eye" class="size-4 text-slate-500" />
                                        <span>Inspecionar comprovante</span>
                                    </button>
                                </td>

                                <td class="py-4 px-6">
                                    @if ($recharge->status === 'aprovado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-medium bg-money-50 text-money-800 border border-money-200">
                                            <span class="size-1.5 rounded-full bg-money-500"></span>
                                            Saldo aprovado
                                        </span>
                                        @if($recharge->reviewed_at)
                                            <span class="text-xs text-slate-500 block mt-0.5">{{ $recharge->reviewed_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    @elseif ($recharge->status === 'recusado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-medium bg-danger-50 text-danger-800 border border-danger-200">
                                            <span class="size-1.5 rounded-full bg-danger-500"></span>
                                            Recusado
                                        </span>
                                        @if($recharge->admin_notes)
                                            <span class="text-xs text-danger-700 block mt-0.5 truncate max-w-xs" title="{{ $recharge->admin_notes }}">
                                                Obs: {{ $recharge->admin_notes }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-medium bg-accent-50 text-accent-900 border border-accent-200">
                                            <span class="size-1.5 rounded-full bg-accent-500"></span>
                                            Aguardando aprovação
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-6 text-right">
                                    @if ($recharge->status === 'pendente')
                                        @if((int) ($card?->user_id) === (int) auth()->id())
                                            <span class="inline-flex items-center rounded bg-accent-50 border border-accent-200 px-2.5 py-1 text-xs font-medium text-accent-900">
                                                Auto-aprovação bloqueada
                                            </span>
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('admin.recharges.approve', $recharge) }}" method="POST" class="flex items-center gap-1.5">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number" name="approved_amount" step="0.01" min="1.00" max="999999.99"
                                                           value="{{ number_format((float) $recharge->amount, 2, '.', '') }}"
                                                           aria-label="Valor confirmado da recarga"
                                                           class="w-28 rounded-lg border-line px-2.5 py-1.5 text-xs font-mono tabular-nums font-semibold text-ink">
                                                    <button type="submit"
                                                            class="bg-money-600 hover:bg-money-700 text-paper font-semibold text-xs px-4 py-2 rounded-lg transition inline-flex items-center gap-1.5">
                                                        <x-icon name="check" class="size-3.5" />
                                                        <span>Aprovar e Creditar</span>
                                                    </button>
                                                </form>

                                                <button @click="openReject('{{ route('admin.recharges.reject', $recharge) }}', '{{ addslashes($user->name ?? 'Cliente') }}', '{{ number_format($requestedAmount, 2, ',', '.') }}')"
                                                        type="button"
                                                        class="bg-surface hover:bg-danger-50 text-danger-700 font-semibold text-xs px-3 py-2 rounded-lg transition border border-danger-200">
                                                    Recusar
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-500 whitespace-nowrap">
                                            Processado
                                            @if($recharge->approved_amount)
                                                (R$ {{ number_format((float) $recharge->approved_amount, 2, ',', '.') }})
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-slate-500">
                                    @if($currentTab === 'pendente')
                                        <h4 class="text-sm font-semibold text-ink">Nenhuma recarga pendente no momento</h4>
                                        <p class="text-xs text-slate-500 mt-1">Todos os comprovantes enviados pelos clientes foram conferidos e aprovados.</p>
                                    @else
                                        <h4 class="text-sm font-semibold text-ink">Nenhum registro encontrado nesta aba.</h4>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-line">
                {{ $recharges->links() }}
            </div>
        </div>

        <div x-show="previewModalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="previewModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-surface rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full p-6 border border-line">
                    <div class="flex justify-between items-center pb-4 mb-4 border-b border-line">
                        <div class="flex items-center gap-2">
                            <x-icon name="receipt" class="size-4 text-slate-500" />
                            <h4 class="text-base font-semibold text-ink">Conferência do Comprovante de Pagamento</h4>
                        </div>
                        <button @click="previewModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="bg-paper rounded-lg overflow-hidden flex items-center justify-center min-h-96 max-h-[70vh] p-2 border border-line">
                        <template x-if="previewType === 'image'">
                            <img loading="lazy" :src="previewUrl" alt="Comprovante" class="max-h-[65vh] w-auto object-contain rounded-lg">
                        </template>

                        <template x-if="previewType === 'pdf'">
                            <iframe :src="previewUrl" class="w-full h-[65vh] rounded-lg border-0 bg-surface"></iframe>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <a :href="previewUrl" target="_blank" rel="noopener noreferrer"
                           class="text-xs font-medium text-ink underline inline-flex items-center gap-1">
                            <span>Abrir arquivo original em nova aba</span>
                            <x-icon name="arrow-right" class="size-3.5" />
                        </a>

                        <button @click="previewModalOpen = false" type="button"
                                class="bg-ink hover:bg-slate-800 text-paper font-semibold text-xs px-5 py-2.5 rounded-lg transition">
                            Fechar visualização
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="rejectModalOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="rejectModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-surface rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6 border border-line">
                    <div class="flex justify-between items-center pb-3 mb-4 border-b border-line">
                        <div>
                            <span class="text-xs font-medium text-danger-600 block">Ação administrativa</span>
                            <h4 class="text-base font-semibold text-ink">Recusar Solicitação de Recarga</h4>
                        </div>
                        <button @click="rejectModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="mb-4 p-3 bg-paper rounded-lg border border-line text-xs">
                        <p class="text-slate-600">Cliente: <strong class="text-ink" x-text="clientName"></strong></p>
                        <p class="text-slate-600">Valor: <strong class="font-mono tabular-nums text-danger-700" x-text="'R$ ' + rechargeAmount"></strong></p>
                    </div>

                    <form :action="rejectAction" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                Motivo da recusa (para o cliente)
                            </label>
                            <textarea name="admin_notes" rows="3" required
                                      placeholder="Ex: Comprovante ilegível, valor divergente do PIX recebido ou comprovante de agendamento não efetivado."
                                      class="block w-full rounded-lg border-line text-xs py-2.5 focus:border-danger-600 focus:ring-danger-600"></textarea>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit"
                                    class="flex-1 bg-danger-600 hover:bg-danger-700 text-paper font-semibold text-xs py-2.5 rounded-lg transition">
                                Confirmar Recusa
                            </button>
                            <button @click="rejectModalOpen = false" type="button"
                                    class="bg-paper-dim hover:bg-line text-slate-700 font-medium text-xs px-4 py-2.5 rounded-lg transition">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
