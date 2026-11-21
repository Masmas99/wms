<div
    x-data="{ open: false, unread: {{ $unreadCount }} }"
    x-init="$store.notifications.setUnread(unread)"
    x-effect="unread = $store.notifications.unread"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open"
        aria-haspopup="true"
        :aria-label="'Notifikasi, ' + unread + ' belum dibaca'"
        title="Notifikasi"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-500 ring-1 ring-inset ring-blue-100 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        <span
            x-show="unread > 0"
            x-cloak
            x-text="unread > 99 ? '99+' : unread"
            class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white ring-2 ring-white"
        >
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute right-0 z-40 mt-2 w-80 max-w-[calc(100vw_-_2rem)] overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-xl shadow-blue-900/10 sm:w-96"
    >
        <div class="flex items-center justify-between gap-3 border-b border-blue-50 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
                <p
                    class="mt-0.5 text-xs text-slate-500"
                    x-text="unread > 0 ? unread + ' belum dibaca' : 'Semua sudah dibaca'"
                >
                    {{ $unreadCount > 0 ? $unreadCount.' belum dibaca' : 'Semua sudah dibaca' }}
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('notifications.read-all') }}"
                x-show="unread > 0"
                @submit.prevent="$store.notifications.markAllRead($event.target)"
            >
                @csrf

                <button
                    type="submit"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Tandai semua
                </button>
            </form>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($announcements as $announcement)
                @php
                    $isUnread = ! $announcement->isReadBy(Auth::user());
                    $typeTheme = match ($announcement->type) {
                        \App\Models\Announcement::TYPE_SUCCESS => [
                            'container' => 'bg-emerald-100 text-emerald-600',
                            'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                        ],
                        \App\Models\Announcement::TYPE_WARNING => [
                            'container' => 'bg-amber-100 text-amber-600',
                            'icon' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM12 3.75 1.5 21h21L12 3.75Z',
                        ],
                        \App\Models\Announcement::TYPE_DANGER => [
                            'container' => 'bg-rose-100 text-rose-600',
                            'icon' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM12 3.75 1.5 21h21L12 3.75Z',
                        ],
                        \App\Models\Announcement::TYPE_BANNER => [
                            'container' => 'bg-indigo-100 text-indigo-600',
                            'icon' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z',
                        ],
                        default => [
                            'container' => 'bg-blue-100 text-blue-600',
                            'icon' => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
                        ],
                    };
                @endphp

                <form
                    method="POST"
                    action="{{ route('notifications.read', $announcement) }}"
                    x-data="notificationItem"
                    x-ref="form"
                    data-id="{{ $announcement->id }}"
                    data-unread="{{ $isUnread ? '1' : '0' }}"
                    data-action="{{ route('notifications.read', $announcement) }}"
                    @submit.prevent="markRead"
                >
                    @csrf

                    <button
                        type="submit"
                        :disabled="saving"
                        :class="unread ? 'bg-blue-50/40' : 'bg-white'"
                        class="flex w-full items-start gap-3 border-b border-blue-50 px-4 py-3 text-left transition hover:bg-blue-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 disabled:cursor-wait"
                    >
                        @if ($announcement->imageUrl())
                            <img src="{{ $announcement->imageUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-xl object-cover ring-1 ring-inset ring-indigo-100">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $typeTheme['container'] }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeTheme['icon'] }}" />
                                </svg>
                            </span>
                        @endif

                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold text-slate-900">{{ $announcement->title }}</span>

                                <span
                                    x-show="unread"
                                    class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-blue-700"
                                >
                                    Baru
                                </span>
                            </span>

                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">
                                {{ \Illuminate\Support\Str::limit($announcement->body, 90) }}
                            </span>

                            <span class="mt-1.5 block text-[11px] font-medium text-slate-400">
                                {{ $announcement->created_at?->format('d M Y H:i') }}
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-slate-900">Belum ada notifikasi</p>
                    <p class="mt-1 text-xs text-slate-500">Pemberitahuan baru akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-blue-50 p-2">
            <a
                href="{{ route('notifications.index') }}"
                class="flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Lihat semua notifikasi
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </div>
    </div>
</div>
