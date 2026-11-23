@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'blue'])

@php
    $tones = [
        'blue' => ['accent' => 'bg-blue-500', 'icon' => 'bg-blue-50 text-blue-600 ring-blue-100'],
        'emerald' => ['accent' => 'bg-emerald-500', 'icon' => 'bg-emerald-50 text-emerald-600 ring-emerald-100'],
        'amber' => ['accent' => 'bg-amber-400', 'icon' => 'bg-amber-50 text-amber-600 ring-amber-100'],
        'rose' => ['accent' => 'bg-rose-500', 'icon' => 'bg-rose-50 text-rose-600 ring-rose-100'],
        'slate' => ['accent' => 'bg-slate-800', 'icon' => 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    $theme = $tones[$tone] ?? $tones['blue'];
    $hasIcon = filled($icon);
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm shadow-blue-900/5']) }}>
    <div class="h-1 {{ $theme['accent'] }}"></div>

    <div class="relative p-3 sm:p-5">
        @if ($hasIcon)
            <span class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-lg ring-1 ring-inset sm:right-5 sm:top-5 sm:h-9 sm:w-9 sm:rounded-xl {{ $theme['icon'] }}">
                <svg class="h-4 w-4 sm:h-[18px] sm:w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </span>
        @endif

        <p @class([
            'break-words text-xs font-medium text-slate-500 sm:text-sm',
            'pr-9 sm:pr-12' => $hasIcon,
        ])>{{ $label }}</p>

        <p @class([
            'truncate text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl',
            'mt-3 sm:mt-4' => $hasIcon,
            'mt-2' => ! $hasIcon,
        ])>{{ $value }}</p>

        @if (filled($hint))
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</div>
