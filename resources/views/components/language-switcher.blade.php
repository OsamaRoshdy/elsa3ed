<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-xl bg-slate-100 p-1 text-xs font-semibold']) }}>
    <a href="{{ route('locale', 'ar') }}" @class(['rounded-lg px-2.5 py-1.5 transition', 'bg-white text-slate-900 shadow-sm' => app()->getLocale() === 'ar', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'ar'])>العربية</a>
    <a href="{{ route('locale', 'en') }}" @class(['rounded-lg px-2.5 py-1.5 transition', 'bg-white text-slate-900 shadow-sm' => app()->getLocale() === 'en', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'en'])>English</a>
</div>
