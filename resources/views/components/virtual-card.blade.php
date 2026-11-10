@props([
    'card' => null,
    'holderName' => null,
    'interactive' => false,
])
@php
    $isDemo = $card === null;
    $maskedNumber = $card ? $card->masked_number : '9900 •••• •••• 4021';
    $balanceFormatted = $card ? number_format((float) $card->balance, 2, ',', '.') : '1.250,00';
    $cashbackFormatted = $card ? number_format((float) $card->cashback_earned, 2, ',', '.') : '48,90';
    $expiration = $card ? $card->expiration_date : '12/29';
    $holder = $holderName ?: (auth()->user()?->name ?? 'Estudante DigitalCard');
    $isActive = $card ? (bool) $card->is_active : true;
@endphp

@if($interactive && $card)
    <div class="cursor-pointer select-none" style="perspective: 1200px;" @click="flipped = ! flipped">
        <div class="relative aspect-[1.586/1] w-full transition-transform duration-500"
             :style="flipped ? 'transform: rotateY(180deg); transform-style: preserve-3d;' : 'transform-style: preserve-3d;'">

            <div class="absolute inset-0 overflow-hidden rounded-xl border border-slate-800 bg-ink p-5 sm:p-6 text-paper"
                 style="backface-visibility: hidden;">
                <div class="relative flex h-full flex-col justify-between">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-md bg-paper text-ink flex items-center justify-center font-serif text-base">
                                DC
                            </div>
                            <div>
                                <span class="block font-serif text-lg text-paper leading-none">DigitalCard</span>
                                <span class="block text-xs text-slate-300 mt-0.5">Cartão fictício</span>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $isActive ? 'text-money-400' : 'text-danger-400' }}">
                            <span class="size-2 rounded-full {{ $isActive ? 'bg-money-500' : 'bg-danger-500' }}"></span>
                            <span>{{ $isActive ? 'Ativo' : 'Bloqueado' }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1">
                        <div class="h-8 w-11 rounded-sm border border-slate-600 bg-slate-800 grid grid-cols-3 grid-rows-2 gap-0.5 p-0.5">
                            <div class="border-b border-r border-slate-600"></div>
                            <div class="border-b border-slate-600"></div>
                            <div class="border-b border-l border-slate-600"></div>
                            <div class="border-r border-slate-600"></div>
                            <div></div>
                            <div class="border-l border-slate-600"></div>
                        </div>

                        <div class="text-right">
                            <span class="block text-xs text-slate-300">Saldo disponível</span>
                            <span class="font-mono text-lg font-semibold tabular-nums text-money-400"
                                  x-text="showBalance ? 'R$ ' + cardBalanceFormatted : 'R$ ••••••'">R$ {{ $balanceFormatted }}</span>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 font-mono text-base sm:text-xl font-semibold tabular-nums text-paper"
                           x-text="cardNumber">{{ $maskedNumber }}</p>

                        <div class="flex items-end justify-between text-xs border-t border-slate-800 pt-2.5 gap-2">
                            <div class="min-w-0">
                                <span class="block text-xs text-slate-400">Titular</span>
                                <span class="text-xs font-medium text-paper truncate block">{{ $holder }}</span>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="block text-xs text-slate-400">Validade</span>
                                <span class="font-mono text-xs tabular-nums text-paper">{{ $expiration }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute inset-0 overflow-hidden rounded-xl border border-slate-800 bg-ink p-5 sm:p-6 text-paper"
                 style="backface-visibility: hidden; transform: rotateY(180deg);">
                <div class="flex h-full flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="font-serif text-base text-paper">DigitalCard</span>
                        <span class="text-xs text-slate-300">Protegido por PIN</span>
                    </div>

                    <div class="my-2 h-8 bg-slate-950 -mx-6"></div>

                    <div class="grid grid-cols-3 gap-3 border-t border-slate-800 pt-3 text-xs">
                        <div>
                            <span class="block text-xs text-slate-400">Código CVV</span>
                            <span class="font-mono text-base font-semibold tabular-nums text-paper" x-text="cvvCode">•••</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400">Validade</span>
                            <span class="font-mono text-base font-semibold tabular-nums text-paper">{{ $expiration }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-slate-400">Cashback</span>
                            <span class="font-mono text-sm font-semibold tabular-nums text-money-400">R$ {{ $cashbackFormatted }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-center text-slate-400">Clique no cartão para virar</p>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="space-y-2 animate-subtle-float">
        <div class="aspect-[1.586/1] w-full overflow-hidden rounded-xl border border-slate-800 bg-ink p-5 sm:p-6 text-paper flex flex-col justify-between shadow-float transition-transform duration-300 hover:scale-[1.02]">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-md bg-paper text-ink flex items-center justify-center font-serif text-base">
                        DC
                    </div>
                    <div>
                        <span class="block font-serif text-lg text-paper leading-none">DigitalCard</span>
                        <span class="block text-xs text-slate-300 mt-0.5">Cartão fictício</span>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-money-400">
                    <span class="size-2 rounded-full bg-money-500"></span>
                    <span>Ativo</span>
                </span>
            </div>

            <div class="flex items-center justify-between py-1">
                <div class="h-8 w-11 rounded-sm border border-slate-600 bg-slate-800 grid grid-cols-3 grid-rows-2 gap-0.5 p-0.5">
                    <div class="border-b border-r border-slate-600"></div>
                    <div class="border-b border-slate-600"></div>
                    <div class="border-b border-l border-slate-600"></div>
                    <div class="border-r border-slate-600"></div>
                    <div></div>
                    <div class="border-l border-slate-600"></div>
                </div>

                <div class="text-right">
                    <span class="block text-xs text-slate-300">Saldo disponível</span>
                    <span class="font-mono text-lg font-semibold tabular-nums text-money-400">R$ {{ $balanceFormatted }}</span>
                </div>
            </div>

            <div>
                <p class="mb-3 font-mono text-base sm:text-lg font-semibold tabular-nums text-paper">{{ $maskedNumber }}</p>

                <div class="flex items-end justify-between text-xs border-t border-slate-800 pt-2.5 gap-2">
                    <div class="min-w-0">
                        <span class="block text-xs text-slate-400">Titular</span>
                        <span class="text-xs font-medium text-paper truncate block">{{ $holder }}</span>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="block text-xs text-slate-400">Validade</span>
                        <span class="font-mono text-xs tabular-nums text-paper">{{ $expiration }}</span>
                    </div>
                </div>
            </div>
        </div>
        @if($isDemo)
            <p class="text-xs text-slate-500 text-right">Cartão fictício</p>
        @endif
    </div>
@endif
