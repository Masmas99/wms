<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $warehouse->name }}</h2>
                    <x-wms.status :status="$warehouse->is_active ? 'approved' : 'draft'" :label="$warehouse->is_active ? 'Aktif' : 'Nonaktif'" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $warehouse->code }}{{ $warehouse->city ? ' | '.$warehouse->city : '' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (Auth::user()->isAdmin())
                    <x-wms.button :href="wms_route('locations.create', ['warehouse' => $warehouse->id])" variant="secondary">Tambah bin</x-wms.button>
                    <x-wms.button :href="wms_route('warehouses.edit', $warehouse)">Edit gudang</x-wms.button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $storageBins = $warehouse->locations->where('is_staging', false);
                $occupied = $storageBins->filter(fn ($location) => $location->occupied_lots > 0)->count();
                $percent = $storageBins->count() > 0 ? round($occupied / $storageBins->count() * 100) : 0;
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat label="Bin penyimpanan" :value="$storageBins->count()" :hint="'Kode awalan '.$warehouse->code" tone="blue" />
                <x-wms.stat label="Bin terisi" :value="$occupied" :hint="$percent.'% utilisasi'" tone="amber" />
                <x-wms.stat label="Total kuantitas" :value="number_format((float) $totalQuantity, 2, ',', '.')" hint="Seluruh lot di gudang" tone="emerald" />
                <x-wms.stat label="Area staging" :value="$warehouse->locations->where('is_staging', true)->count()" hint="Dipakai saat receiving" tone="slate" />
            </div>

            <x-wms.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Utilisasi bin</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $occupied }} dari {{ $storageBins->count() }} bin menyimpan stok aktif.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-700">{{ $percent }}%</span>
                </div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-blue-500" style="width: {{ $percent }}%"></div>
                </div>
            </x-wms.card>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Daftar bin</h3>
                        <p class="mt-1 text-xs text-slate-500">Termasuk area staging dan bin penyimpanan.</p>
                    </div>
                    <x-wms.button :href="wms_route('locations.index', ['warehouse' => $warehouse->id])" variant="secondary">Kelola lokasi</x-wms.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Kode bin</th>
                                <th class="px-5 py-3">Koordinat</th>
                                <th class="px-5 py-3">Kapasitas</th>
                                <th class="px-5 py-3 text-right">Lot terisi</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right sm:px-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($warehouse->locations as $location)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3 sm:px-6">
                                        <a href="{{ wms_route('locations.show', $location) }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $location->full_code }}</a>
                                        @if ($location->is_staging)
                                            <p class="text-xs font-semibold text-amber-600">Area staging</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $location->zone }} / {{ $location->aisle }} / {{ $location->rack }} / {{ $location->bin_code }}</td>
                                    <td class="px-5 py-3 text-xs text-slate-500">
                                        {{ number_format($location->capacity_m3, 2, ',', '.') }} m3<br>
                                        {{ number_format($location->capacity_kg, 2, ',', '.') }} kg
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-700">{{ $location->occupied_lots }}</td>
                                    <td class="px-5 py-3"><x-wms.status :status="$location->is_active ? 'approved' : 'draft'" :label="$location->is_active ? 'Aktif' : 'Nonaktif'" /></td>
                                    <td class="px-5 py-3 text-right sm:px-6">
                                        <x-wms.icon-button :href="wms_route('locations.show', $location)" icon="eye" label="Detail {{ $location->full_code }}" variant="primary" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10">
                                        <x-wms.empty title="Belum ada bin" description="Tambahkan bin penyimpanan untuk mulai menempatkan stok." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-wms.card>
        </div>
    </div>
</x-app-layout>
