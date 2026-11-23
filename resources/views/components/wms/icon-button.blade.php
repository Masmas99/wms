@props(['icon' => 'eye', 'label', 'href' => null, 'variant' => 'default', 'type' => 'button'])

@php
    $icons = [
        'eye' => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'pencil' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
        'trash' => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'arrow-right' => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
        'download' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3',
        'receive' => 'M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.31a2.25 2.25 0 0 0-.1.664v3.776A2.25 2.25 0 0 0 4.5 20h15a2.25 2.25 0 0 0 2.25-2.25v-3.776a2.25 2.25 0 0 0-.1-.664l-2.412-7.972A2.25 2.25 0 0 0 17.088 3.75H15M12 3v9m0 0 3-3m-3 3L9 9',
        'check' => 'm4.5 12.75 6 6 9-13.5',
        'check-circle' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'cube' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'x-mark' => 'M6 18 18 6M6 6l12 12',
        'printer' => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z',
    ];

    $variants = [
        'default' => 'text-slate-500 hover:bg-blue-50 hover:text-blue-700',
        'primary' => 'text-blue-600 hover:bg-blue-50 hover:text-blue-700',
        'success' => 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700',
        'danger' => 'text-rose-600 hover:bg-rose-50 hover:text-rose-700',
    ];

    $classes = 'inline-flex h-9 w-9 items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 '.($variants[$variant] ?? $variants['default']);
@endphp

@if (filled($href))
    <a
        href="{{ $href }}"
        title="{{ $label }}"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['eye'] }}" />
        </svg>
    </a>
@else
    <button
        type="{{ $type }}"
        title="{{ $label }}"
        aria-label="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['eye'] }}" />
        </svg>
    </button>
@endif
