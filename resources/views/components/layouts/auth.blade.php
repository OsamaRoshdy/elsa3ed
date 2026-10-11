@props(['title'])

{{-- Login pages: form on one side, brand panel on the other. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ __(config('app.name')) }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">
<div class="flex min-h-full">
    <div class="flex flex-1 flex-col px-6 py-8 sm:px-12 lg:flex-none lg:basis-1/2 xl:px-24">
        <div class="flex items-center justify-between">
            <a href="{{ route('products.index') }}" class="flex items-center gap-2.5">
                <x-logo />
            </a>
            <x-language-switcher />
        </div>

        <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-12">
            <x-flash />
            {{ $slot }}
        </div>
    </div>

    <div class="relative hidden flex-1 overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 lg:block">
        <div class="absolute -top-24 -end-24 size-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -start-16 size-96 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="relative flex h-full flex-col justify-center px-16 text-white">
            <x-logo inverted class="mb-10" />
            <h2 class="max-w-md text-4xl leading-tight font-bold">{{ __('Your account, always up to date.') }}</h2>
            <p class="mt-4 max-w-md text-lg text-white/70">{{ __('See your balance, invoices, payments and live stock — synchronized directly from Daftra.') }}</p>
            <div class="mt-10 grid max-w-md grid-cols-2 gap-4">
                @foreach ([['wallet', __('Outstanding balance')], ['document', __('Invoices')], ['banknotes', __('Payments')], ['cube', __('Live stock')]] as [$icon, $label])
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                        <x-icon :name="$icon" class="size-6 text-white/80" />
                        <span class="text-sm font-medium">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<x-page-loader />
</body>
</html>
