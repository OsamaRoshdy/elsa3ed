@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-slate-500">
            {!! __('Showing :from–:to of :total', [
                'from' => '<span class="font-semibold text-slate-900 tabular-nums">'.number_format($paginator->firstItem()).'</span>',
                'to' => '<span class="font-semibold text-slate-900 tabular-nums">'.number_format($paginator->lastItem()).'</span>',
                'total' => '<span class="font-semibold text-slate-900 tabular-nums">'.number_format($paginator->total()).'</span>',
            ]) !!}
        </p>

        @php
            $base = 'inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-sm font-medium transition';
            $idle = 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900';
            $disabled = 'cursor-not-allowed bg-slate-50 text-slate-300 ring-1 ring-slate-100';
        @endphp

        <div class="flex items-center gap-1.5">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $base }} {{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} {{ $idle }}" aria-label="{{ __('pagination.previous') }}">
                    <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" />
                </a>
            @endif

            {{-- Page numbers (hidden on small screens) --}}
            <div class="hidden items-center gap-1.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $base }} text-slate-400" aria-disabled="true">…</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $base }} bg-brand-600 font-semibold text-white shadow-sm shadow-brand-600/30 tabular-nums">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $base }} {{ $idle }} tabular-nums" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Current page on small screens --}}
            <span class="px-2 text-sm text-slate-500 sm:hidden">
                {{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} {{ $idle }}" aria-label="{{ __('pagination.next') }}">
                    <x-icon name="chevron" class="size-4 rtl:-scale-x-100" />
                </a>
            @else
                <span class="{{ $base }} {{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <x-icon name="chevron" class="size-4 rtl:-scale-x-100" />
                </span>
            @endif
        </div>
    </nav>
@endif
