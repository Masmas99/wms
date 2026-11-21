@props(['announcement'])

<div
    x-data="notificationItem"
    x-show="unread"
    data-id="{{ $announcement->id }}"
    data-unread="1"
    data-action="{{ route('notifications.read', $announcement) }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="announcement-banner-title-{{ $announcement->id }}"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
>
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" aria-hidden="true"></div>

    <form
        method="POST"
        action="{{ route('notifications.read', $announcement) }}"
        x-ref="form"
        @submit.prevent="markRead"
        class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-950/30 ring-1 ring-white/20"
    >
        @csrf

        <button
            type="submit"
            :disabled="saving"
            aria-label="Tutup banner dan tandai sudah dibaca"
            class="absolute right-3 top-3 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-950/60 text-white shadow-lg ring-1 ring-white/30 backdrop-blur transition hover:bg-slate-950/80 focus:outline-none focus:ring-2 focus:ring-white disabled:cursor-wait disabled:opacity-60"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="overflow-y-auto bg-slate-100">
            <img
                src="{{ $announcement->imageUrl() }}"
                alt="{{ $announcement->title }}"
                class="max-h-[70vh] w-full object-contain"
                onerror="this.parentElement.remove()"
            >
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-500">Pemberitahuan banner</p>
                <h2 id="announcement-banner-title-{{ $announcement->id }}" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">
                    {{ $announcement->title }}
                </h2>

                @if (filled($announcement->body))
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        {{ \Illuminate\Support\Str::limit($announcement->body, 220) }}
                    </p>
                @endif
            </div>

            <x-wms.button type="submit" class="shrink-0" x-bind:disabled="saving">
                <span x-text="saving ? 'Menyimpan...' : 'Tutup & tandai dibaca'">Tutup & tandai dibaca</span>
            </x-wms.button>
        </div>
    </form>
</div>
