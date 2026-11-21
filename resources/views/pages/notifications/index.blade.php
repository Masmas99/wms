<x-app-layout>
    <x-slot name="header">
        <div
            x-data="{ unread: {{ $unreadCount }} }"
            x-init="$store.notifications.setUnread({{ $unreadCount }})"
            x-effect="unread = $store.notifications.unread"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">Notifikasi</h2>

                    @if ($unreadCount > 0)
                        <span
                            x-show="unread > 0"
                            x-text="unread + ' baru'"
                            class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-blue-700"
                        >
                            {{ $unreadCount }} baru
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-slate-500">Pemberitahuan dari admin untuk Anda.</p>
            </div>

            @if ($unreadCount > 0)
                <form
                    method="POST"
                    action="{{ route('notifications.read-all') }}"
                    x-show="unread > 0"
                    @submit.prevent="$store.notifications.markAllRead($event.target)"
                >
                    @csrf

                    <x-wms.button type="submit" variant="secondary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Tandai semua dibaca
                    </x-wms.button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div
                x-data="{ unread: {{ $unreadCount }} }"
                x-init="$store.notifications.setUnread({{ $unreadCount }})"
                x-effect="unread = $store.notifications.unread"
                class="flex flex-wrap items-center gap-2"
            >
                <a
                    href="{{ route('notifications.index') }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500',
                        'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $filter !== 'unread',
                        'bg-white text-slate-600 ring-1 ring-inset ring-blue-100 hover:bg-blue-50 hover:text-blue-700' => $filter === 'unread',
                    ])
                >
                    Semua
                </a>

                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500',
                        'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $filter === 'unread',
                        'bg-white text-slate-600 ring-1 ring-inset ring-blue-100 hover:bg-blue-50 hover:text-blue-700' => $filter !== 'unread',
                    ])
                >
                    Belum dibaca

                    @if ($unreadCount > 0)
                        <span
                            x-show="unread > 0"
                            x-text="unread > 99 ? '99+' : unread"
                            @class([
                                'inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-none',
                                'bg-white/20 text-white' => $filter === 'unread',
                                'bg-blue-100 text-blue-700' => $filter !== 'unread',
                            ])
                        >
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    @endif
                </a>
            </div>

            @if ($announcements->isEmpty())
                <div class="rounded-2xl border border-blue-100 bg-white px-6 py-12 text-center shadow-sm shadow-blue-900/5">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </span>

                    <p class="mt-4 text-sm font-semibold text-slate-900">
                        {{ $filter === 'unread' ? 'Semua notifikasi sudah dibaca' : 'Belum ada notifikasi' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $filter === 'unread' ? 'Tidak ada pemberitahuan yang menunggu untuk dibaca.' : 'Pemberitahuan dari admin akan muncul di sini.' }}
                    </p>

                    @if ($filter === 'unread')
                        <a
                            href="{{ route('notifications.index') }}"
                            class="mt-4 inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-blue-600 ring-1 ring-inset ring-blue-200 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Lihat semua notifikasi
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm shadow-blue-900/5">
                    <div class="divide-y divide-blue-50">
                        @foreach ($announcements as $announcement)
                            @php
                                $isUnread = ! $announcement->isReadBy(Auth::user());

                                $typeTone = match ($announcement->type) {
                                    \App\Models\Announcement::TYPE_SUCCESS => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    \App\Models\Announcement::TYPE_WARNING => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    \App\Models\Announcement::TYPE_DANGER => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    \App\Models\Announcement::TYPE_BANNER => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                    default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                };

                                $iconTone = match ($announcement->type) {
                                    \App\Models\Announcement::TYPE_SUCCESS => 'bg-emerald-100 text-emerald-600',
                                    \App\Models\Announcement::TYPE_WARNING => 'bg-amber-100 text-amber-600',
                                    \App\Models\Announcement::TYPE_DANGER => 'bg-rose-100 text-rose-600',
                                    \App\Models\Announcement::TYPE_BANNER => 'bg-indigo-100 text-indigo-600',
                                    default => 'bg-blue-100 text-blue-600',
                                };

                                $iconPath = match ($announcement->type) {
                                    \App\Models\Announcement::TYPE_SUCCESS => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                                    \App\Models\Announcement::TYPE_WARNING, \App\Models\Announcement::TYPE_DANGER => 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM12 3.75 1.5 21h21L12 3.75Z',
                                    \App\Models\Announcement::TYPE_BANNER => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z',
                                    default => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
                                };
                            @endphp

                            <article
                                x-data="notificationItem"
                                data-id="{{ $announcement->id }}"
                                data-unread="{{ $isUnread ? '1' : '0' }}"
                                data-action="{{ route('notifications.read', $announcement) }}"
                                @if ($filter === 'unread')
                                    x-show="unread"
                                @endif
                                :class="unread ? 'bg-blue-50/40' : 'bg-white'"
                                class="p-5 transition-colors sm:p-6"
                            >
                                <div class="flex items-start gap-4">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconTone }}">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                                        </svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider ring-1 ring-inset {{ $typeTone }}">
                                                {{ $announcement->typeLabel() }}
                                            </span>

                                            @if ($isUnread)
                                                <span
                                                    x-show="unread"
                                                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-blue-700"
                                                >
                                                    Belum dibaca
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="mt-2 text-base font-semibold text-slate-900">{{ $announcement->title }}</h3>

                                        @if (filled($announcement->body))
                                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $announcement->body }}</p>
                                        @endif

                                        @if ($announcement->imageUrl())
                                            <img
                                                src="{{ $announcement->imageUrl() }}"
                                                alt="Gambar pemberitahuan: {{ $announcement->title }}"
                                                class="mt-4 max-h-96 w-full rounded-xl object-cover ring-1 ring-inset ring-blue-100"
                                                onerror="this.remove()"
                                            >
                                        @endif

                                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                            <span>Dari {{ $announcement->creator?->name ?? 'Sistem' }}</span>
                                            <time datetime="{{ $announcement->created_at?->toIso8601String() }}" title="{{ $announcement->created_at?->format('d M Y H:i') }}">
                                                {{ $announcement->created_at?->format('d M Y H:i') ?? '-' }}
                                            </time>
                                        </div>

                                        @if ($isUnread)
                                            <form
                                                method="POST"
                                                action="{{ route('notifications.read', $announcement) }}"
                                                x-ref="form"
                                                x-show="unread"
                                                class="mt-4"
                                                @submit.prevent="markRead"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    :disabled="saving"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 ring-1 ring-inset ring-blue-200 transition hover:bg-blue-50 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-wait disabled:opacity-60"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 9 17.25 19.5 6.75" />
                                                    </svg>
                                                    <span x-text="saving ? 'Menyimpan...' : 'Tandai dibaca'">Tandai dibaca</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>

                @if ($announcements->hasPages())
                    {{ $announcements->links() }}
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
