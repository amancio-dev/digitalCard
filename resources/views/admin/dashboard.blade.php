<x-admin-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8"
         x-data="{
            previewModalOpen: false,
            previewUrl: '',
            previewType: '',
            rejectModalOpen: false,
            rejectAction: '',
            rejectClient: '',
            rejectAmount: '',
            createProductModalOpen: false,
            createCategoryModalOpen: false,
            editProductModalOpen: false,
            editProductAction: '',
            editProductName: '',
            editProductCategoryId: '',
            editProductPrice: '',
            editProductComparePrice: '',
            editProductCashback: '',
            editProductStock: '',
            editProductImage: '',
            openReceipt(url, type = 'image') {
                this.previewUrl = url;
                this.previewType = type;
                this.previewModalOpen = true;
            },
            openReject(action, client, amount) {
                this.rejectAction = action;
                this.rejectClient = client;
                this.rejectAmount = amount;
                this.rejectModalOpen = true;
            },
            openEditProduct(action, name, categoryId, price, comparePrice, cashback, stock, image) {
                this.editProductAction = action;
                this.editProductName = name;
                this.editProductCategoryId = categoryId;
                this.editProductPrice = price;
                this.editProductComparePrice = comparePrice;
                this.editProductCashback = cashback;
                this.editProductStock = stock;
                this.editProductImage = image;
                this.editProductModalOpen = true;
            }
         }">

        <div class="flex flex-col justify-between gap-4 border-b border-line pb-6 sm:flex-row sm:items-center">
            <div>
                <x-eyebrow>Backoffice • Visão geral operacional</x-eyebrow>
                <h1 class="mt-1 font-serif text-3xl text-ink">Dashboard Administrativo</h1>
                <p class="mt-1 text-sm text-slate-600">Acompanhe saldo em circulação, vendas, recargas pendentes e entregas.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.recharges.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface px-4 py-2.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                    <x-icon name="wallet" class="size-4 text-slate-500" />
                    <span>Fila de recargas</span>
                </a>
                <a href="{{ route('admin.orders.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface px-4 py-2.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                    <x-icon name="package" class="size-4 text-slate-500" />
                    <span>Todos os pedidos</span>
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-money-200 bg-money-50 p-4 text-money-900">
                <x-icon name="circle-check" class="size-5 text-money-700 shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-money-950">Ação concluída</p>
                    <p class="text-xs text-money-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 text-danger-900">
                <ul class="list-inside list-disc space-y-0.5 text-xs text-danger-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 divide-y sm:grid-cols-2 sm:divide-y-0 xl:grid-cols-4 sm:divide-x divide-line rounded-xl border border-line bg-surface">
            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Total em saldo nos cartões</span>
                    <x-icon name="credit-card" class="size-4" />
                </div>
                <p class="mt-2 font-mono tabular-nums text-2xl font-semibold text-ink whitespace-nowrap">R$ {{ number_format($totalBalance, 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Cashback acumulado: R$ {{ number_format($totalCashback, 2, ',', '.') }}</p>
            </div>

            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Recargas pendentes</span>
                    <x-icon name="clock" class="size-4 text-accent-600" />
                </div>
                <p class="mt-2 font-mono tabular-nums text-2xl font-semibold text-ink">{{ $pendingRechargesCount }}</p>
                <p class="mt-1 text-xs text-accent-700">R$ {{ number_format($pendingRechargesAmount, 2, ',', '.') }} aguardando análise</p>
            </div>

            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Total de vendas</span>
                    <x-icon name="receipt" class="size-4 text-money-600" />
                </div>
                <p class="mt-2 font-mono tabular-nums text-2xl font-semibold text-money-700 whitespace-nowrap">R$ {{ number_format($totalSales, 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $deliveredOrdersCount }} pedido(s) entregue(s)</p>
            </div>

            <div class="p-5">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium">Aguardando entrega</span>
                    <x-icon name="truck" class="size-4" />
                </div>
                <p class="mt-2 font-mono tabular-nums text-2xl font-semibold text-ink">{{ $pendingOrdersCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Status "Produto solicitado"</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="flex flex-col justify-between gap-2 border-b border-line p-6 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-base font-semibold text-ink">Fila de aprovação de comprovantes</h2>
                    <p class="text-xs text-slate-500">Inspecione a imagem ou PDF e aprove ou recuse com justificativa.</p>
                </div>
                <a href="{{ route('admin.recharges.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-ink hover:text-accent-600">
                    <span>Ver fila completa</span>
                    <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-line bg-paper text-xs font-medium text-slate-500">
                            <th class="px-6 py-3.5">Solicitação</th>
                            <th class="px-6 py-3.5">Cliente</th>
                            <th class="px-6 py-3.5">Valor</th>
                            <th class="px-6 py-3.5">Comprovante</th>
                            <th class="px-6 py-3.5 text-right">Decisão</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($pendingRecharges as $recharge)
                            @php
                                $card = $recharge->virtualCard;
                                $client = $card?->user;
                            @endphp
                            <tr class="hover:bg-paper/60">
                                <td class="px-6 py-4">
                                    <span class="block font-mono tabular-nums font-semibold text-ink">#REC-{{ str_pad((string) $recharge->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="text-xs text-slate-500">{{ $recharge->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block font-medium text-ink">{{ $client->name ?? 'Cliente' }}</span>
                                    <span class="text-xs text-slate-500">{{ $client->email ?? '' }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono tabular-nums text-sm font-semibold text-money-700 whitespace-nowrap">
                                    R$ {{ number_format((float) $recharge->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="openReceipt('{{ route('recharges.receipt', $recharge) }}', '{{ str_ends_with(strtolower($recharge->receipt_path), '.pdf') ? 'pdf' : 'image' }}')" type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                                        <x-icon name="eye" class="size-3.5 text-slate-500" />
                                        <span>Inspecionar</span>
                                    </button>
                                </td>
                                <td class="px-6 py-4">
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
                                                       class="w-24 rounded-lg border-line px-2 py-1.5 text-xs font-mono tabular-nums font-semibold text-ink">
                                                <button type="submit" class="rounded-lg bg-money-600 px-3 py-2 text-xs font-semibold text-paper transition hover:bg-money-700">
                                                    Aprovar
                                                </button>
                                            </form>

                                            <button type="button"
                                                    @click="openReject('{{ route('admin.recharges.reject', $recharge) }}', '{{ addslashes($client->name ?? 'Cliente') }}', '{{ number_format((float) $recharge->amount, 2, ',', '.') }}')"
                                                    class="rounded-lg border border-danger-200 bg-surface px-3 py-2 text-xs font-semibold text-danger-700 transition hover:bg-danger-50">
                                                Recusar
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                    Nenhuma recarga pendente. Todos os comprovantes foram analisados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="flex flex-col justify-between gap-2 border-b border-line p-6 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-base font-semibold text-ink">Entregas aguardando confirmação</h2>
                    <p class="text-xs text-slate-500">Pedidos em "Produto solicitado". Confirme a entrega para liberar o cashback.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-ink hover:text-accent-600">
                    <span>Gestão de pedidos</span>
                    <x-icon name="arrow-right" class="size-3.5" />
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-line bg-paper text-xs font-medium text-slate-500">
                            <th class="px-6 py-3.5">Pedido</th>
                            <th class="px-6 py-3.5">Cliente</th>
                            <th class="px-6 py-3.5">Total</th>
                            <th class="px-6 py-3.5">Cashback</th>
                            <th class="px-6 py-3.5 text-right">Confirmar entrega</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($pendingOrders as $order)
                            <tr class="hover:bg-paper/60">
                                <td class="px-6 py-4">
                                    <a href="{{ route('orders.show', $order) }}" class="font-mono tabular-nums font-semibold text-ink hover:text-accent-600 underline">
                                        #PED-{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <span class="block text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block font-medium text-ink">{{ $order->user->name ?? 'Cliente' }}</span>
                                    <span class="block max-w-xs truncate text-xs text-slate-500" title="{{ $order->shipping_address }}">{{ $order->shipping_address }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono tabular-nums text-sm font-semibold text-ink whitespace-nowrap">
                                    R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 font-mono tabular-nums font-semibold text-money-700 whitespace-nowrap">
                                    + R$ {{ number_format((float) $order->cashback_total, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.orders.deliver', $order) }}" method="POST" class="flex items-center justify-end gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="tracking_code" maxlength="100" placeholder="Rastreio (opcional)"
                                               class="w-36 rounded-lg border-line px-2.5 py-2 text-xs">
                                        <button type="submit" class="whitespace-nowrap rounded-lg bg-money-600 px-3 py-2 text-xs font-semibold text-paper transition hover:bg-money-700">
                                            Entregar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">Nenhum pedido aguardando entrega.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-line p-6">
                <div>
                    <h2 id="admin-products-caption" class="text-base font-semibold text-ink">Gestão de catálogo (Produtos e Categorias)</h2>
                    <p class="text-xs text-slate-500">Cadastre novos itens, edite preços, estoque e cashback ou alterne a visibilidade de produtos.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button @click="createCategoryModalOpen = true" type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3.5 py-2 text-xs font-medium text-ink transition hover:bg-paper-dim">
                        <x-icon name="plus" class="size-3.5" />
                        <span>Nova categoria</span>
                    </button>
                    <button @click="createProductModalOpen = true" type="button"
                            class="inline-flex items-center gap-1 rounded-lg bg-ink px-4 py-2 text-xs font-semibold text-paper transition hover:bg-slate-800">
                        <x-icon name="plus" class="size-3.5" />
                        <span>Novo produto</span>
                    </button>
                </div>
            </div>

            <div tabindex="0" role="region" aria-labelledby="admin-products-caption" class="table-scroll-region">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-line bg-paper text-xs font-medium text-slate-500">
                            <th class="px-6 py-3.5">Produto</th>
                            <th class="px-6 py-3.5">Categoria</th>
                            <th class="px-6 py-3.5">Preço</th>
                            <th class="px-6 py-3.5">Cashback</th>
                            <th class="px-6 py-3.5">Estoque</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($products as $product)
                            <tr class="hover:bg-paper/60">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 shrink-0">
                                            <x-product-image :product="$product" size="thumb" />
                                        </div>
                                        <span class="max-w-xs truncate font-medium text-ink" title="{{ $product->name }}">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $product->category->name ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono tabular-nums font-semibold text-ink whitespace-nowrap">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 font-mono tabular-nums font-semibold text-money-700 whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $product->cashback_percent, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="px-6 py-4 font-mono tabular-nums text-slate-600">{{ $product->stock }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded border px-2 py-0.5 text-xs font-medium {{ $product->is_active ? 'border-money-200 bg-money-50 text-money-700' : 'border-line bg-paper text-slate-500' }}">
                                        {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openEditProduct(
                                                    '{{ route('admin.products.update', $product) }}',
                                                    '{{ addslashes($product->name) }}',
                                                    '{{ $product->category_id }}',
                                                    '{{ (float) $product->price }}',
                                                    '{{ (float) ($product->compare_at_price ?? $product->original_price) }}',
                                                    '{{ (float) $product->cashback_percent }}',
                                                    '{{ (int) $product->stock }}',
                                                    '{{ addslashes((string) $product->image_path) }}'
                                                )"
                                                class="rounded-lg border border-line bg-paper px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-paper-dim">
                                            Editar
                                        </button>

                                        <form action="{{ route('admin.products.toggle', $product) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-paper-dim">
                                                {{ $product->is_active ? 'Desativar' : 'Reativar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="previewModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div @click="previewModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-line bg-surface p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:align-middle">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-4">
                        <h4 class="text-base font-semibold text-ink">Conferência do comprovante</h4>
                        <button @click="previewModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="flex min-h-96 max-h-[70vh] items-center justify-center overflow-hidden rounded-lg border border-line bg-paper p-2">
                        <template x-if="previewType === 'image'">
                            <img loading="lazy" :src="previewUrl" alt="Comprovante" class="max-h-[65vh] w-auto rounded-lg object-contain">
                        </template>
                        <template x-if="previewType === 'pdf'">
                            <iframe :src="previewUrl" class="h-[65vh] w-full rounded-lg border-0 bg-surface"></iframe>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                        <a :href="previewUrl" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-ink underline">Abrir arquivo original</a>
                        <button @click="previewModalOpen = false" type="button" class="rounded-lg bg-ink px-5 py-2.5 text-xs font-semibold text-paper transition hover:bg-slate-800">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div @click="rejectModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-line bg-surface p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:align-middle">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-3">
                        <div>
                            <span class="block text-xs font-medium text-danger-600">Ação administrativa</span>
                            <h4 class="text-base font-semibold text-ink">Recusar solicitação</h4>
                        </div>
                        <button @click="rejectModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="mb-4 rounded-lg border border-line bg-paper p-3 text-xs">
                        <p class="text-slate-600">Cliente: <strong class="text-ink" x-text="rejectClient"></strong></p>
                        <p class="text-slate-600">Valor: <strong class="font-mono tabular-nums text-danger-700" x-text="'R$ ' + rejectAmount"></strong></p>
                    </div>

                    <form :action="rejectAction" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Motivo da recusa (obrigatório)</label>
                            <textarea name="admin_notes" rows="3" required placeholder="Ex: Comprovante ilegível ou valor divergente."
                                      class="block w-full rounded-lg border-line text-xs focus:border-danger-600 focus:ring-danger-600"></textarea>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-lg bg-danger-600 py-2.5 text-xs font-semibold text-paper transition hover:bg-danger-700">Confirmar recusa</button>
                            <button @click="rejectModalOpen = false" type="button" class="rounded-lg bg-paper-dim px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-line">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="createProductModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div @click="createProductModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-line bg-surface p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-3">
                        <div>
                            <span class="block text-xs font-medium text-slate-500">Catálogo DigitalCard</span>
                            <h4 class="text-base font-semibold text-ink">Cadastrar novo produto</h4>
                        </div>
                        <button @click="createProductModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Nome do produto</label>
                            <input type="text" name="name" required placeholder="Ex: Smartphone Galaxy S25 Ultra 512GB"
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Categoria</label>
                            <select name="category_id" required class="w-full rounded-lg border-line text-xs py-2">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Preço (R$)</label>
                                <input type="number" step="0.01" min="0.01" name="price" required placeholder="1999.90"
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Preço "De:" (R$)</label>
                                <input type="number" step="0.01" min="0.01" name="compare_at_price" placeholder="2399.90"
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Cashback (%)</label>
                                <input type="number" step="0.5" min="0" max="50" name="cashback_percent" value="3.0" required
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Estoque inicial</label>
                                <input type="number" min="0" name="stock" value="15" required
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Caminho ou URL da imagem (opcional)</label>
                            <input type="text" name="image_path" placeholder="/images/products/exemplo.webp"
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Descrição técnica</label>
                            <textarea name="description" rows="3" required placeholder="Descreva os principais recursos e especificações..."
                                      class="w-full rounded-lg border-line text-xs"></textarea>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-lg bg-ink py-2.5 text-xs font-semibold text-paper transition hover:bg-slate-800">Salvar produto</button>
                            <button @click="createProductModalOpen = false" type="button" class="rounded-lg bg-paper-dim px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-line">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="editProductModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div @click="editProductModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-line bg-surface p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-3">
                        <div>
                            <span class="block text-xs font-medium text-slate-500">Edição rápida</span>
                            <h4 class="text-base font-semibold text-ink">Editar produto</h4>
                        </div>
                        <button @click="editProductModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <form :action="editProductAction" method="POST" class="space-y-3 text-xs">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Nome do produto</label>
                            <input type="text" name="name" x-model="editProductName" required
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Categoria</label>
                            <select name="category_id" x-model="editProductCategoryId" required class="w-full rounded-lg border-line text-xs py-2">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Preço (R$)</label>
                                <input type="number" step="0.01" min="0.01" name="price" x-model="editProductPrice" required
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Preço "De:" (R$)</label>
                                <input type="number" step="0.01" min="0.01" name="compare_at_price" x-model="editProductComparePrice"
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Cashback (%)</label>
                                <input type="number" step="0.5" min="0" max="50" name="cashback_percent" x-model="editProductCashback" required
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                            <div>
                                <label class="mb-1 block font-medium text-slate-700">Estoque</label>
                                <input type="number" min="0" name="stock" x-model="editProductStock" required
                                       class="w-full rounded-lg border-line text-xs py-2 font-mono tabular-nums">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Caminho ou URL da imagem</label>
                            <input type="text" name="image_path" x-model="editProductImage"
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-lg bg-ink py-2.5 text-xs font-semibold text-paper transition hover:bg-slate-800">Salvar alterações</button>
                            <button @click="editProductModalOpen = false" type="button" class="rounded-lg bg-paper-dim px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-line">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="createCategoryModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div @click="createCategoryModalOpen = false" class="fixed inset-0 bg-ink/70 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-xl border border-line bg-surface p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:align-middle">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-3">
                        <div>
                            <span class="block text-xs font-medium text-slate-500">Departamentos</span>
                            <h4 class="text-base font-semibold text-ink">Criar nova categoria</h4>
                        </div>
                        <button @click="createCategoryModalOpen = false" class="text-slate-500 hover:text-ink">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Nome da categoria</label>
                            <input type="text" name="name" required placeholder="Ex: Áudio Profissional"
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Departamento pai (opcional)</label>
                            <select name="parent_id" class="w-full rounded-lg border-line text-xs py-2">
                                <option value="">Nenhum (Departamento principal)</option>
                                @foreach($categories->whereNull('parent_id') as $parentCat)
                                    <option value="{{ $parentCat->id }}">{{ $parentCat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block font-medium text-slate-700">Ícone Lucide</label>
                            <input type="text" name="icon" value="package" maxlength="40"
                                   class="w-full rounded-lg border-line text-xs py-2">
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-lg bg-ink py-2.5 text-xs font-semibold text-paper transition hover:bg-slate-800">Criar categoria</button>
                            <button @click="createCategoryModalOpen = false" type="button" class="rounded-lg bg-paper-dim px-4 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-line">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
