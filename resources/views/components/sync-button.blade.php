<form method="POST" action="{{ route('admin.sync.store') }}" x-data="{ syncing: false }" @submit="syncing = true" {{ $attributes }}>
    @csrf
    <button type="submit" class="btn-primary" :disabled="syncing">
        <x-icon name="arrows" class="size-4" ::class="syncing && 'animate-spin'" />
        <span x-show="!syncing">{{ __('Sync with Daftra') }}</span>
        <span x-cloak x-show="syncing">{{ __('Sync started…') }}</span>
    </button>
</form>
