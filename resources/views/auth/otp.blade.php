<x-layouts.auth :title="__('Verification code')">
    <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-icon name="check" class="size-6" /></span>
    <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900">{{ __('Enter your code') }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ __('To finish signing in, enter the 6-digit code we sent you.') }}</p>
    <p class="mt-1 text-sm font-medium text-slate-700"><span dir="ltr">{{ $email }}</span></p>

    <form method="POST" action="{{ route('login.otp') }}" class="mt-8 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="code" class="label">{{ __('Verification code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"
                class="input py-3 text-center font-mono text-2xl tracking-[0.5em]" dir="ltr" placeholder="••••••"
                x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 6); if ($el.value.length === 6) { submitting = true; $el.form.requestSubmit() }">
            @error('code')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full py-3" :disabled="submitting">
            <span x-show="!submitting">{{ __('Verify and sign in') }}</span>
            <span x-cloak x-show="submitting">{{ __('Signing in...') }}</span>
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-4 text-sm">
        <form method="POST" action="{{ route('login.otp.resend') }}">
            @csrf
            <button type="submit" class="font-medium text-brand-600 hover:text-brand-700">{{ __('Resend code') }}</button>
        </form>
        <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-900">{{ __('Use another account') }}</a>
    </div>
</x-layouts.auth>
