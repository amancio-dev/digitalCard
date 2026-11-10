@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-line pt-4">
        <p class="text-xs text-slate-600 tabular-nums">
            Mostrando <span class="font-medium text-ink">{{ $paginator->firstItem() }}</span> a <span class="font-medium text-ink">{{ $paginator->lastItem() }}</span> de <span class="font-medium text-ink">{{ $paginator->total() }}</span> {{ $paginator->total() === 1 ? 'resultado' : 'resultados' }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="px-3 py-1.5 rounded-md border border-line bg-paper text-xs text-slate-400 cursor-default">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1.5 rounded-md border border-line bg-surface hover:border-ink text-xs font-medium text-ink transition">
                    Anterior
                </a>
            @endif

            <div class="hidden sm:flex items-center gap-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="px-2.5 py-1.5 text-xs text-slate-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="px-3 py-1.5 rounded-md bg-ink text-paper text-xs font-semibold tabular-nums">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 rounded-md border border-line bg-surface hover:border-ink text-xs font-medium text-ink tabular-nums transition" aria-label="Ir para página {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1.5 rounded-md border border-line bg-surface hover:border-ink text-xs font-medium text-ink transition">
                    Próxima
                </a>
            @else
                <span aria-disabled="true" class="px-3 py-1.5 rounded-md border border-line bg-paper text-xs text-slate-400 cursor-default">
                    Próxima
                </span>
            @endif
        </div>
    </nav>
@endif
