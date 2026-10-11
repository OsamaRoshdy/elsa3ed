<x-layouts.app :title="__('Users')">
    <x-page-header :title="__('Portal users')" :subtitle="__('Admins and client logins for this portal.')">
        <a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New user') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by name or email...')" />
    </div>

    <div class="card overflow-hidden">
        @if ($users->isEmpty())
            <x-empty-state icon="user" :title="__('No users found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Linked Daftra client') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $user->name }}</td>
                                <td dir="ltr" class="text-start">{{ $user->email }}</td>
                                <td>
                                    <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-brand-50 text-brand-700' => $user->isAdmin(), 'bg-slate-100 text-slate-600' => ! $user->isAdmin()])>
                                        {{ $user->isAdmin() ? __('Administrator') : __('Client') }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->client)
                                        <a href="{{ route('admin.clients.show', $user->client) }}" class="hover:text-brand-600">{{ $user->client->name }}</a>
                                    @elseif ($user->daftra_client_id)
                                        <span class="text-amber-600">{{ __('Not synced (ID :id)', ['id' => $user->daftra_client_id]) }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        @unless ($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" x-data @submit="if (! window.confirm(@js(__('Delete this user?')))) $event.preventDefault()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="{{ __('Delete') }}"><x-icon name="trash" class="size-4" /></button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
