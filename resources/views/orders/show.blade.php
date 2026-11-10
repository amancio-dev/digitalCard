<x-app-layout :metaTitle="'Pedido #PED-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)" :metaDescription="'Detalhes e rastreamento do pedido demonstrativo #PED-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)">
    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8"
         x-data="{ copiedTracking: false }">
        <div class="flex items-center justify-between">
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-ink transition">
                <x-icon name="arrow-left" class="size-4" />
                <span>Voltar para Meus Pedidos</span>
            </a>

            <button onclick="window.print()" type="button"
                    class="inline-flex items-center gap-1.5 bg-surface hover:bg-paper-dim border border-line text-slate-700 text-xs font-medium px-3.5 py-2 rounded-lg transition">
                <x-icon name="printer" class="size-4 text-slate-500" />
                <span>Imprimir comprovante demonstrativo</span>
            </button>
        </div>

        <div class="bg-surface rounded-xl border border-line overflow-hidden p-6 sm:p-8 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-line gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-mono tabular-nums text-xl font-semibold text-ink">
                            #PED-{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium border {{ $order->status === \App\Models\Order::STATUS_DELIVERED ? 'bg-money-50 text-money-900 border-money-200' : ($order->status === \App\Models\Order::STATUS_CANCELLED || $order->status === \App\Models\Order::STATUS_RETURNED ? 'bg-danger-50 text-danger-900 border-danger-200' : 'bg-accent-50 text-accent-900 border-accent-200') }}">
                            {{ $order->status }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Realizado em {{ $order->created_at->format('d/m/Y \à\s H:i') }}</p>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-xs text-slate-500 block">Total debitado no cartão virtual</span>
                    <span class="font-mono tabular-nums text-2xl font-semibold text-ink">
                        R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="space-y-5">
                <h2 class="text-xs font-medium text-slate-500">Linha do tempo e histórico de status</h2>

                @php
                    $steps = [
                        \App\Models\Order::STATUS_REQUESTED,
                        \App\Models\Order::STATUS_SEPARATING,
                        \App\Models\Order::STATUS_SHIPPED,
                        \App\Models\Order::STATUS_DELIVERED,
                    ];
                    $isCancelled = in_array($order->status, [\App\Models\Order::STATUS_CANCELLED, \App\Models\Order::STATUS_RETURNED], true);
                    $currentIndex = array_search($order->status, $steps, true);
                    $historyByStatus = $order->statusHistories ? $order->statusHistories->keyBy('to_status') : collect();
                @endphp

                @if($isCancelled)
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 flex items-center gap-3 text-xs text-danger-900">
                        <x-icon name="circle-alert" class="size-5 text-danger-600 shrink-0" />
                        <div>
                            <strong class="font-semibold block">Pedido em estado: {{ $order->status }}</strong>
                            <span class="text-danger-700">O valor foi estornado ao cartão virtual e o estoque recomposto.</span>
                        </div>
                    </div>
                @endif

                <div class="relative">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                        @foreach($steps as $idx => $stepLabel)
                            @php
                                $done = ! $isCancelled && ($order->status === \App\Models\Order::STATUS_DELIVERED || ($currentIndex !== false && $idx <= $currentIndex));
                                $stepHistory = $historyByStatus->get($stepLabel);
                                $stepDate = $stepHistory?->created_at ?? ($idx === 0 ? $order->created_at : ($stepLabel === \App\Models\Order::STATUS_DELIVERED ? $order->delivered_at : null));
                            @endphp
                            <div class="relative flex sm:flex-col items-start gap-3 rounded-lg border p-3.5 {{ $done ? 'border-money-200 bg-money-50/60 text-money-950' : 'border-line bg-paper text-slate-500' }}">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex size-6 items-center justify-center rounded-full text-xs font-mono font-semibold {{ $done ? 'bg-money-600 text-paper' : 'bg-paper-dim text-slate-600' }}">
                                        @if($done)
                                            <x-icon name="check" class="size-3.5" />
                                        @else
                                            {{ $idx + 1 }}
                                        @endif
                                    </span>
                                    <span class="font-semibold text-ink">{{ $stepLabel }}</span>
                                </div>
                                @if($stepDate)
                                    <span class="text-xs text-slate-500 font-mono tabular-nums">{{ $stepDate->format('d/m H:i') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($order->statusHistories && $order->statusHistories->isNotEmpty())
                    <ul class="space-y-2 border-l border-line pl-4 text-xs">
                        @foreach($order->statusHistories as $hist)
                            <li class="text-slate-700">
                                <strong class="text-ink font-medium">{{ $hist->to_status }}</strong>
                                <span class="text-slate-500 font-mono tabular-nums">({{ $hist->created_at?->format('d/m/Y H:i') }})</span>
                                @if($hist->notes)
                                    — <span>{{ $hist->notes }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-line bg-paper p-3.5 text-xs flex items-center justify-between gap-2">
                        <div>
                            <span class="block text-xs text-slate-500">Código de rastreio simulado</span>
                            <span class="font-mono tabular-nums font-semibold text-ink">{{ $order->tracking_code ?: 'Ainda não informado' }}</span>
                        </div>
                        @if($order->tracking_code)
                            <button type="button"
                                    @click="navigator.clipboard && navigator.clipboard.writeText('{{ $order->tracking_code }}'); copiedTracking = true; setTimeout(() => copiedTracking = false, 2000)"
                                    class="inline-flex items-center gap-1 rounded border border-line bg-surface px-2.5 py-1 text-xs font-medium text-ink hover:bg-paper-dim">
                                <x-icon name="copy" class="size-3.5" />
                                <span x-text="copiedTracking ? 'Copiado' : 'Copiar'">Copiar</span>
                            </button>
                        @endif
                    </div>
                    <div class="rounded-lg border border-money-200 bg-money-50 p-3.5 text-xs">
                        <span class="block text-xs text-money-700">Cashback do pedido (sobre valor pago)</span>
                        <span class="font-mono tabular-nums font-semibold text-money-800">R$ {{ number_format((float) $order->cashback_total, 2, ',', '.') }}</span>
                        <span class="ml-1 text-xs text-money-700">{{ $order->cashback_credited_at ? '(creditado na entrega)' : '(será creditado na entrega)' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-paper p-4 rounded-lg border border-line text-xs">
                <div>
                    <span class="text-xs text-slate-500 block mb-1">Destinatário e conta</span>
                    <p class="font-semibold text-ink">{{ $order->user->name ?? 'Cliente' }}</p>
                    <p class="text-slate-600">{{ $order->user->email ?? '' }}</p>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block mb-1">Endereço de entrega</span>
                    <p class="text-ink font-medium leading-relaxed">{{ $order->shipping_address }}</p>
                    @if($order->shipping_cep)
                        <p class="text-slate-500 mt-0.5 font-mono">CEP: {{ $order->shipping_cep }}</p>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-medium text-slate-500">Produtos adquiridos</h3>
                <div class="divide-y divide-line">
                    @foreach($order->items as $item)
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($item->product)
                                    <a href="{{ route('catalog.product', $item->product->slug) }}" class="w-12 shrink-0">
                                        <x-product-image :product="$item->product" size="thumb" />
                                    </a>
                                @endif
                                <div class="min-w-0">
                                    <h4 class="text-xs font-semibold text-ink leading-snug">
                                        @if($item->product)
                                            <a href="{{ route('catalog.product', $item->product->slug) }}" class="hover:text-accent-600">
                                                {{ $item->product->name }}
                                            </a>
                                        @else
                                            Produto do Sistema
                                        @endif
                                    </h4>
                                    <p class="text-xs text-slate-600 mt-0.5">
                                        Quantidade: {{ $item->quantity }} unidade(s) • Preço unitário: R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @if($item->product)
                                    <a href="{{ route('catalog.product', $item->product->slug) }}"
                                       class="hidden sm:inline-flex text-xs font-medium text-slate-600 hover:text-ink underline">
                                        Comprar novamente
                                    </a>
                                @endif
                                <span class="font-mono tabular-nums font-semibold text-sm text-ink whitespace-nowrap">
                                    R$ {{ number_format($item->quantity * (float) $item->unit_price, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-line space-y-2 text-xs">
                @if((float) $order->subtotal_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal dos produtos</span>
                        <span class="font-mono tabular-nums">R$ {{ number_format((float) $order->subtotal_amount, 2, ',', '.') }}</span>
                    </div>
                @endif
                @if((float) $order->discount_amount > 0)
                    <div class="flex justify-between text-money-700 font-medium">
                        <span>Desconto do cupom {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span>
                        <span class="font-mono tabular-nums">- R$ {{ number_format((float) $order->discount_amount, 2, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-slate-600">
                    <span>Frete simulado</span>
                    @if((float) $order->shipping_cost > 0)
                        <span class="font-mono tabular-nums font-medium">R$ {{ number_format((float) $order->shipping_cost, 2, ',', '.') }}</span>
                    @else
                        <span class="text-money-700 font-medium">Grátis</span>
                    @endif
                </div>
                <div class="flex justify-between items-baseline pt-3 border-t border-line text-sm font-semibold text-ink">
                    <span>Total pago com Cartão Virtual</span>
                    <span class="font-mono tabular-nums text-xl text-money-700">
                        R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            @if (auth()->id() === $order->user_id && $order->status === \App\Models\Order::STATUS_REQUESTED)
                <div class="flex flex-col items-start justify-between gap-3 rounded-lg border border-danger-200 bg-danger-50 p-4 sm:flex-row sm:items-center">
                    <div>
                        <span class="block text-xs font-semibold text-danger-900">Deseja cancelar este pedido?</span>
                        <span class="text-xs text-danger-700">O saldo será estornado no cartão virtual e o estoque recomposto.</span>
                    </div>

                    <form action="{{ route('orders.cancel', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="whitespace-nowrap rounded-lg border border-danger-200 bg-surface px-4 py-2 text-xs font-semibold text-danger-700 transition hover:bg-danger-100">
                            Cancelar Pedido
                        </button>
                    </form>
                </div>
            @elseif (auth()->id() === $order->user_id && $order->canRequestRegretReturn())
                <div class="flex flex-col items-start justify-between gap-3 rounded-lg border border-accent-200 bg-accent-50 p-4 sm:flex-row sm:items-center">
                    <div>
                        <span class="block text-xs font-semibold text-accent-950">Direito de arrependimento (até 7 dias após a entrega)</span>
                        <span class="text-xs text-accent-800">Solicite a devolução com estorno do valor pago (deduzindo eventual cashback creditado).</span>
                    </div>

                    <form action="{{ route('orders.return', $order) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="whitespace-nowrap rounded-lg bg-accent-600 hover:bg-accent-700 px-4 py-2 text-xs font-semibold text-paper transition">
                            Solicitar Devolução (Arrependimento 7 dias)
                        </button>
                    </form>
                </div>
            @endif

            @can('admin')
                @if ($order->statusEnum()->isOpen())
                    <div class="mt-8 space-y-4 rounded-xl border border-line bg-paper p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <x-eyebrow>Gestão logística (Admin)</x-eyebrow>
                                <h4 class="mt-1 text-sm font-semibold text-ink">Atualizar etapa ou confirmar entrega</h4>
                            </div>

                            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg border border-danger-200 bg-danger-50 px-3 py-1.5 text-xs font-semibold text-danger-700 hover:bg-danger-100 transition">
                                    Cancelar Pedido (Admin)
                                </button>
                            </form>
                        </div>

                        <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="status_select" class="block text-xs font-medium text-slate-700 mb-1">Próximo status</label>
                                <select id="status_select" name="status" class="w-full rounded-lg border-line bg-surface px-3 py-2 text-xs text-ink">
                                    <option value="{{ \App\Models\Order::STATUS_SEPARATING }}">Em separação</option>
                                    <option value="{{ \App\Models\Order::STATUS_SHIPPED }}">Enviado</option>
                                    <option value="{{ \App\Models\Order::STATUS_DELIVERED }}">Produto entregue</option>
                                </select>
                            </div>
                            <div>
                                <label for="tracking_code_input" class="block text-xs font-medium text-slate-700 mb-1">Código de rastreio</label>
                                <input id="tracking_code_input" type="text" name="tracking_code" value="{{ $order->tracking_code }}" maxlength="100"
                                       placeholder="Ex: BR123456789"
                                       class="w-full rounded-lg border-line bg-surface px-3 py-2 text-xs text-ink">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full rounded-lg bg-money-600 hover:bg-money-700 px-4 py-2 text-xs font-semibold text-paper transition">
                                    Salvar status
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
