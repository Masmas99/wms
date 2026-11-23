<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $opname->reference_no }}</h2>
                    <x-wms.status :status="$opname->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $opname->warehouse?->name }}{{ $opname->zone ? ' | zona '.$opname->zone : '' }} | {{ $opname->scheduled_date?->format('d/m/Y') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('opnames.index')" variant="secondary">Daftar opname</x-wms.button>

                @if (Auth::user()->isAdmin() && $opname->status !== 'completed')
                    <form method="POST" action="{{ wms_route('opnames.complete', $opname) }}">
                        @csrf
                        <x-wms.button type="submit" :disabled="! $opname->isFullyCounted()">Tutup opname</x-wms.button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $total = $opname->items->count();
                $variance = $opname->items->filter(fn ($item) => abs((float) $item->difference) > 0.0001)->count();
                $isAdmin = Auth::user()->isAdmin();
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3">
                <x-wms.stat label="Baris hitung" :value="(string) $total" hint="Snapshot bin berisi stok" tone="blue" />
                <x-wms.stat label="Sudah dihitung" :value="$counted.' / '.$total" hint="Baris dengan kuantitas fisik" tone="emerald" />
                <x-wms.stat class="col-span-2 sm:col-span-1" label="Baris selisih" :value="(string) $variance" hint="Menunggu keputusan admin" tone="amber" />
            </div>

            @if ($opname->status === 'completed')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    Opname telah ditutup pada {{ $opname->completed_at?->format('d/m/Y H:i') }}. Lembar hitung tidak dapat diubah.
                </div>
            @else
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
                    @if ($isAdmin)
                        Stok sistem ditampilkan untuk kebutuhan audit. Pastikan petugas menghitung fisik secara mandiri.
                    @else
                        Blind count aktif. Stok sistem disembunyikan sampai admin menutup opname.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ wms_route('opnames.count', $opname) }}">
                @csrf

                <x-wms.card :padded="false">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Lembar hitung fisik</h3>
                            <p class="mt-1 text-xs text-slate-500">Isi kuantitas fisik sesuai hasil hitung di bin.</p>
                        </div>
                        @if ($opname->status !== 'completed')
                            <x-wms.button type="submit">Simpan hasil hitung</x-wms.button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Produk</th>
                                    <th class="px-6 py-4">Bin</th>
                                    <th class="px-6 py-4">Batch</th>
                                    @if ($isAdmin)
                                        <th class="px-6 py-4 text-right">Stok sistem</th>
                                    @endif
                                    <th class="px-6 py-4 text-right">Hitung fisik</th>
                                    @if ($isAdmin)
                                        <th class="px-6 py-4 text-right">Selisih</th>
                                        <th class="px-6 py-4">Penyesuaian</th>
                                    @endif
                                    <th class="px-6 py-4">Petugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($opname->items as $item)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-slate-800">{{ $item->product?->name }}</p>
                                            <p class="text-xs text-slate-400">{{ $item->product?->sku }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $item->location?->full_code }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $item->batch_number }}</td>

                                        @if ($isAdmin)
                                            <td class="px-6 py-4 text-right font-medium text-slate-700">{{ number_format($item->system_qty, 2, ',', '.') }}</td>
                                        @endif

                                        <td class="px-6 py-4 text-right">
                                            @if ($opname->status === 'completed')
                                                <span class="font-semibold text-slate-900">{{ $item->counted_qty !== null ? number_format($item->counted_qty, 2, ',', '.') : '-' }}</span>
                                            @else
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    name="items[{{ $item->id }}]"
                                                    value="{{ old('items.'.$item->id, $item->counted_qty) }}"
                                                    placeholder="0,00"
                                                    class="w-28 rounded-xl border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                                >
                                            @endif
                                        </td>

                                        @if ($isAdmin)
                                            <td class="px-6 py-4 text-right font-semibold {{ (float) $item->difference > 0 ? 'text-emerald-600' : ((float) $item->difference < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                                @if ($item->counted_qty === null)
                                                    -
                                                @else
                                                    {{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 2, ',', '.') }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if ($item->adjustment)
                                                    <x-wms.status :status="$item->adjustment->status" />
                                                    <p class="mt-1 text-xs text-slate-400">{{ $item->adjustment->reference_no }}</p>
                                                @else
                                                    <span class="text-xs text-slate-400">Tidak ada</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            {{ $item->counter?->name ?? '-' }}
                                            @if ($item->counted_at)
                                                <p class="text-xs text-slate-400">{{ $item->counted_at->format('d/m/Y H:i') }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($opname->status !== 'completed')
                        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                            <x-wms.button type="submit">Simpan hasil hitung</x-wms.button>
                        </div>
                    @endif
                </x-wms.card>
            </form>

            @if ($opname->notes)
                <x-wms.card>
                    <h3 class="text-sm font-semibold text-slate-900">Catatan opname</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $opname->notes }}</p>
                </x-wms.card>
            @endif
        </div>
    </div>
</x-app-layout>
