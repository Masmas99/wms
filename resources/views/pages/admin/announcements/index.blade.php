<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Pemberitahuan</h2>
                <p class="mt-1 text-sm text-slate-500">Kirim pengumuman ke semua user atau pemberitahuan pribadi.</p>
            </div>

            <x-wms.button :href="route('admin.announcements.create')">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat pemberitahuan
            </x-wms.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm shadow-blue-900/5">
                <div class="flex items-center justify-between gap-4 border-b border-blue-100 px-6 py-4">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-900">{{ $announcements->total() }}</span> pemberitahuan
                    </p>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Riwayat pengiriman</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-blue-100 text-left text-sm">
                        <thead class="bg-blue-50/70 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th scope="col" class="px-6 py-4">Pemberitahuan</th>
                                <th scope="col" class="px-6 py-4">Penerima</th>
                                <th scope="col" class="px-6 py-4">Dibaca</th>
                                <th scope="col" class="px-6 py-4">Dibuat</th>
                                <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50">
                            @forelse ($announcements as $announcement)
                                @php
                                    $typeTone = match ($announcement->type) {
                                        \App\Models\Announcement::TYPE_SUCCESS => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        \App\Models\Announcement::TYPE_WARNING => 'bg-amber-50 text-amber-700 ring-amber-200',
                                        \App\Models\Announcement::TYPE_DANGER => 'bg-rose-50 text-rose-700 ring-rose-200',
                                        \App\Models\Announcement::TYPE_BANNER => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                        default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    };

                                    $recipientCount = $announcement->audience === \App\Models\Announcement::AUDIENCE_ALL
                                        ? $userCount
                                        : $announcement->recipients_count;
                                @endphp

                                <tr class="transition hover:bg-blue-50/40">
                                    <td class="max-w-md px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($announcement->imageUrl())
                                                <img src="{{ $announcement->imageUrl() }}" alt="" class="h-8 w-8 rounded-lg object-cover ring-1 ring-inset ring-blue-100" onerror="this.remove()">
                                            @endif

                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider ring-1 ring-inset {{ $typeTone }}">
                                                {{ $announcement->typeLabel() }}
                                            </span>
                                            <p class="min-w-0 truncate font-medium text-slate-900">{{ $announcement->title }}</p>
                                        </div>
                                        <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-slate-500">
                                            {{ \Illuminate\Support\Str::limit($announcement->body, 140) }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            {{ $announcement->audienceLabel() }}
                                        </span>
                                        <p class="mt-1.5 text-xs text-slate-500">{{ $recipientCount }} penerima</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                        {{ $announcement->reads_count }} / {{ $recipientCount }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <p class="text-sm font-medium text-slate-900">{{ $announcement->creator?->name ?? 'Sistem' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $announcement->created_at?->format('d M Y H:i') ?? '-' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-wms.icon-button
                                                :href="route('admin.announcements.edit', $announcement)"
                                                icon="pencil"
                                                label="Edit {{ $announcement->title }}"
                                                variant="primary"
                                            />

                                            <x-wms.icon-button
                                                icon="trash"
                                                label="Hapus {{ $announcement->title }}"
                                                variant="danger"
                                                x-on:click="$dispatch('wms-delete', { action: {{ Js::from(route('admin.announcements.destroy', $announcement)) }}, name: {{ Js::from($announcement->title) }} })"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="text-sm font-medium text-slate-900">Belum ada pemberitahuan</p>
                                        <p class="mt-1 text-sm text-slate-500">Buat pemberitahuan pertama untuk mengabari pengguna Rumitech.</p>
                                        <x-wms.button :href="route('admin.announcements.create')" class="mt-4">
                                            Buat pemberitahuan
                                        </x-wms.button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($announcements->hasPages())
                    <div class="border-t border-blue-100 px-6 py-4">
                        {{ $announcements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-wms.delete-popup
        title="Hapus pemberitahuan?"
        message="Pemberitahuan berikut akan dihapus permanen:"
        confirm-label="Hapus pemberitahuan"
    />
</x-app-layout>
