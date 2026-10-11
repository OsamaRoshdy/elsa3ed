<x-layouts.auth :title="__('Login')">
    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Welcome back') }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ __('Sign in to view your account, invoices and available products.') }}</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="email" class="label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input" dir="ltr">
            @error('email')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="input" dir="ltr">
            @error('password')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            {{ __('Remember me') }}
        </label>

        <button type="submit" class="btn-primary w-full py-3" :disabled="submitting">
            <span x-show="!submitting">{{ __('Login') }}</span>
            <span x-cloak x-show="submitting">{{ __('Signing in...') }}</span>
        </button>
    </form>

    <a href="{{ route('products.index') }}" class="mt-8 inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
        {{ __('Browse products') }}
        <x-icon name="chevron" class="size-4 rtl:-scale-x-100" />
    </a>
</x-layouts.auth>
