@props(['order'])

@php
    $steps = [
        ['submitted', __('Sent'), $order->submitted_at],
        ['processing', __('Processing'), null],
        ['completed', __('Invoiced'), $order->completed_at],
    ];
    $reached = ['submitted' => 1, 'processing' => 2, 'completed' => 3][$order->status] ?? 0;
@endphp

@if ($order->status === 'cancelled')
    <div class="flex items-center gap-3 rounded-2xl bg-rose-50 p-4 text-sm font-medium text-rose-700">
        <x-icon name="x" class="size-5" /> {{ __('This order was cancelled.') }}
    </div>
@else
    <ol class="grid grid-cols-3 gap-2">
        @foreach ($steps as $index => [$key, $label, $date])
            @php($done = $index < $reached)
            <li class="flex flex-col gap-2">
                <span @class(['h-1.5 rounded-full', 'bg-brand-600' => $done, 'bg-slate-200' => ! $done])></span>
                <span @class(['text-sm font-semibold', 'text-slate-900' => $done, 'text-slate-400' => ! $done])>{{ $label }}</span>
                @if ($date)
                    <span class="text-xs text-slate-500">{{ $date->translatedFormat('d M Y, h:i A') }}</span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
