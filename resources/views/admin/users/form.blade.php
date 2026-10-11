<x-layouts.app :title="$user->exists ? __('Edit user') : __('New user')">
    <a href="{{ route('admin.users.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to users') }}
    </a>
    <x-page-header :title="$user->exists ? __('Edit user') : __('New user')" :subtitle="__('Client users can only see the data of the Daftra client they are linked to.')" />

    @php
        $clientOptions = $clients->map(fn ($client) => ['id' => $client->daftra_id, 'name' => $client->name, 'email' => $client->email])->values();
    @endphp

    <form
        method="POST"
        action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
        class="card max-w-2xl space-y-5 p-6"
        x-init="fillFromClient()"
        x-data="{
            role: @js(old('role', $user->role)),
            clientId: @js((string) old('daftra_client_id', $user->daftra_client_id)),
            clients: @js($clientOptions),
            fillFromClient() {
                const client = this.clients.find(c => String(c.id) === this.clientId);
                if (! client) return;
                if (! this.$refs.name.value) this.$refs.name.value = client.name;
                if (! this.$refs.email.value && client.email) this.$refs.email.value = client.email;
            },
        }"
    >
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div>
            <span class="label">{{ __('Role') }}</span>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['client' => [__('Client'), __('Sees only their own account')], 'admin' => [__('Administrator'), __('Full access to the portal')]] as $value => [$label, $hint])
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition" :class="role === '{{ $value }}' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-100' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" class="mt-0.5 text-brand-600">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $label }}</span>
                            <span class="block text-xs text-slate-500">{{ $hint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="role === 'client'" x-cloak>
            <label for="daftra_client_id" class="label">{{ __('Daftra client') }}</label>
            <select id="daftra_client_id" name="daftra_client_id" class="input" data-searchable data-placeholder="{{ __('Search by name or email...') }}" x-model="clientId" @change="fillFromClient()" :disabled="role !== 'client'">
                <option value=""></option>
                @foreach ($clients as $client)
                    <option value="{{ $client->daftra_id }}">{{ $client->name }}{{ $client->email ? ' — '.$client->email : '' }}</option>
                @endforeach
            </select>
            @if ($clients->isEmpty())
                <p class="mt-1.5 text-sm text-amber-600">{{ __('No clients synced yet. Run a sync with Daftra first.') }}</p>
            @endif
            @error('daftra_client_id') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="input" x-ref="name">
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="input" dir="ltr" x-ref="email">
            @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" @required(! $user->exists) autocomplete="new-password" class="input" dir="ltr">
                @if ($user->exists)
                    <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to keep the current password.') }}</p>
                @endif
                @error('password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user->exists) autocomplete="new-password" class="input" dir="ltr">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ $user->exists ? __('Save changes') : __('Create user') }}</button>
        </div>
    </form>
</x-layouts.app>
