{{-- Full-screen logo loader, shown by resources/js/page-loader.js while navigating between pages. --}}
<div id="page-loader" hidden class="fixed inset-0 z-[100] grid place-items-center bg-white/70 opacity-0 backdrop-blur-sm transition-opacity duration-200" role="status" aria-live="polite">
    <div class="flex flex-col items-center gap-4">
        <x-logo-mark spinning class="size-16 drop-shadow-lg" />
        <span class="text-sm font-semibold text-slate-500">{{ __('Loading...') }}</span>
    </div>
</div>
