<x-app-layout :metaTitle="'Meus Pedidos e Entregas'" :metaDescription="'Histórico de compras demonstrativas e acompanhamento de pedidos na DigitalCard.'">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{ statusFilter: 'todos' }">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-line gap-4">
            <div>
                <x-eyebrow>Minhas compras</x-eyebrow>
                <h1 class="font-serif text-3xl text-ink mt-1">Meus Pedidos e Entregas</h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Acompanhe o andamento desde "Produto solicitado" até "Produto entregue".</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg bg-surface border border-line p-1 text-xs font-medium">
                    <button @click="statusFilter = 'todos'" type="button"
                            :class="statusFilter === 'todos' ? 'bg-ink text-paper' : 'text-slate-600 hover:text-ink'"
                            class="px-3 py-1.5 rounded-md transition">
                        Todos ({{ $orders->count() }})
                    </button>
                    <button @click="statusFilter = 'solicitado'" type="button"
                            :class="statusFilter === 'solicitado' ? 'bg-accent-500 text-paper' : 'text-slate-600 hover:text-ink'"
                            class="px-3 py-1.5 rounded-md transition">
                        Produto solicitado
                    </button>
                    <button @click="statusFilter = 'entregue'" type="button"
                            :class="statusFilter === 'entregue' ? 'bg-money-600 text-paper' : 'text-slate-600 hover:text-ink'"
                            class="px-3 py-1.5 rounded-md transition">
                        Produto entregue
                    </button>
                </div>

                <a href="{{ route('catalog.index') }}"
                   class="inline-flex items-center gap-2 bg-ink hover:bg-slate-800 text-paper text-xs font-semibold px-4 py-2.5 rounded-lg transition">
                    <span>Nova compra</span>
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($orders as $order)
                @php
                    $isRequested = $order->status === \App\Models\Order::STATUS_REQUESTED;
                    $isDelivered = $order->status === \App\Models\Order::STATUS_DELIVERED;
                @endphp
                <div x-show="statusFilter === 'todos' || (statusFilter === 'solicitado' && {{ $isRequested ? 'true' : 'false' }}) || (statusFilter === 'entregue' && {{ $isDelivered ? 'true' : 'false' }})"
                     class="bg-surface rounded-xl border border-line hover:border-slate-300 transition overflow-hidden">

                    <div class="bg-paper px-6 py-3.5 border-b border-line flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div class="flex flex-wrap items-center gap-6">
                            <div>
                                <span class="text-xs text-slate-500 block">Data do pedido</span>
                                <span class="font-medium text-ink">{{ $order->created_at->format('d/m/Y \à\s H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Total pago</span>
                                <span class="font-mono tabular-nums font-semibold text-ink">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Cashback DigitalCard</span>
                                <span class="font-mono tabular-nums font-semibold text-money-700">+ R$ {{ number_format((float) $order->cashback_total, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="font-mono tabular-nums text-xs font-medium text-slate-500">
                                #PED-{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                            <a href="{{ route('orders.show', $order) }}"
                               class="bg-ink hover:bg-slate-800 text-paper text-xs font-semibold px-3.5 py-2 rounded-lg transition">
                                Ver detalhes e rastreio
                            </a>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center gap-2.5">
                                @if($isRequested)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-accent-50 text-accent-900 border border-accent-200">
                                        <span class="size-2 rounded-full bg-accent-500"></span>
                                        Produto solicitado
                                    </span>
                                    <span class="text-xs text-slate-500">Pagamento aprovado • Preparando entrega</span>
                                @elseif($isDelivered)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-money-50 text-money-900 border border-money-200">
                                        <span class="size-2 rounded-full bg-money-500"></span>
                                        Produto entregue
                                    </span>
                                    <span class="text-xs text-money-700 font-medium">Entregue em {{ $order->delivered_at?->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium bg-danger-50 text-danger-900 border border-danger-200">
                                        {{ $order->status }}
                                    </span>
                                @endif
                            </div>

                            <div class="divide-y divide-line pt-1">
                                @foreach($order->items as $item)
                                    <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4 text-xs">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if($item->product)
                                                <a href="{{ route('catalog.product', $item->product->slug) }}" class="w-12 shrink-0">
                                                    <x-product-image :product="$item->product" size="thumb" />
                                                </a>
                                            @endif
                                            <div class="min-w-0">
                                                <a href="{{ $item->product ? route('catalog.product', $item->product->slug) : '#' }}"
                                                   class="font-medium text-ink hover:text-accent-600 block text-sm truncate">
                                                    {{ $item->product->name ?? 'Produto' }}
                                                </a>
                                                <span class="text-slate-500 text-xs">
                                                    Qtd: {{ $item->quantity }} un. × R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            @if($item->product)
                                                <a href="{{ route('catalog.product', $item->product->slug) }}"
                                                   class="hidden sm:inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-ink underline">
                                                    Comprar novamente
                                                </a>
                                            @endif
                                            <span class="font-mono tabular-nums font-semibold text-ink whitespace-nowrap">
                                                R$ {{ number_format($item->quantity * (float) $item->unit_price, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="lg:w-72 p-4 rounded-lg bg-paper border border-line text-xs space-y-1.5 shrink-0">
                            <span class="text-xs font-medium text-slate-500 block">Endereço de entrega</span>
                            <p class="text-ink font-medium leading-relaxed">{{ $order->shipping_address }}</p>
                            @if($order->tracking_code)
                                <p class="pt-1 text-xs font-mono text-money-700 font-medium">Rastreio: {{ $order->tracking_code }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-surface rounded-xl border border-line p-12 text-center space-y-3">
                    <div class="mx-auto size-12 rounded-full bg-paper-dim text-slate-500 flex items-center justify-center">
                        <x-icon name="package" class="size-6" />
                    </div>
                    <h3 class="text-base font-semibold text-ink">Você ainda não realizou nenhum pedido</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        Utilize o saldo do seu Cartão Virtual para comprar no catálogo e acumular cashback.
                    </p>
                    <a href="{{ route('catalog.index') }}"
                       class="inline-block bg-ink hover:bg-slate-800 text-paper font-semibold text-xs px-5 py-2.5 rounded-lg transition">
                        Explorar catálogo de produtos
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
