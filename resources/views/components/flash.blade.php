@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
        <x-icon name="check" class="mt-0.5 text-emerald-600" />
        <p class="flex-1">{{ session('success') }}</p>
        <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800"><x-icon name="x" class="size-4" /></button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
        <x-icon name="warning" class="mt-0.5 text-rose-600" />
        <p class="flex-1">{{ session('error') }}</p>
        <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-800"><x-icon name="x" class="size-4" /></button>
    </div>
@endif
