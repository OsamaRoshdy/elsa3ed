{{-- Pop-up messages from $store.toast (e.g. "added to your order"). --}}
<div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:bottom-6" aria-live="polite">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div
            x-transition.opacity
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium shadow-lg"
            :class="{
                'bg-slate-900 text-white': toast.type === 'success',
                'bg-amber-500 text-white': toast.type === 'warning',
                'bg-rose-600 text-white': toast.type === 'error',
            }"
        >
            <span class="mt-0.5" x-show="toast.type === 'success'"><x-icon name="check" class="size-5 text-emerald-400" /></span>
            <span class="mt-0.5" x-show="toast.type !== 'success'"><x-icon name="warning" class="size-5" /></span>
            <p class="flex-1" x-text="toast.message"></p>
            <button type="button" @click="$store.toast.dismiss(toast.id)" class="opacity-70 hover:opacity-100"><x-icon name="x" class="size-4" /></button>
        </div>
    </template>
</div>
