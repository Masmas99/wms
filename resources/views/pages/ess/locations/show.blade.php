<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $location->full_code }}</h2>
                    @if ($location->is_staging)
                        <x-wms.status status="staged" label="Area staging" />
                    @else
                        <x-wms.status :status="$location->is_active ? 'approved' : 'draft'" :label="$location->is_active ? 'Aktif' : 'Nonaktif'" />
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $location->warehouse->name }} | Zona {{ $location->zone }} / Aisle {{ $location->aisle }} / Rak {{ $location->rack }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('labels.locations', $location)" variant="secondary">Cetak label bin</x-wms.button>
                @if (Auth::user()->isAdmin())
                    <x-wms.button :href="wms_route('locations.edit', $location)">Edit bin</x-wms.button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $onHand = (float) $location->stocks->sum('qty_on_hand');
                $reserved = (float) $location->stocks->sum('qty_reserved');
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat label="Stok fisik" :value="number_format($onHand, 2, ',', '.')" :hint="$location->stocks->count().' lot'" tone="blue" />
                <x-wms.stat label="Dialokasikan" :value="number_format($reserved, 2, ',', '.')" hint="Untuk pesanan berjalan" tone="amber" />
                <x-wms.stat label="Tersedia" :value="number_format($onHand - $reserved, 2, ',', '.')" hint="Siap dipindahkan" tone="emerald" />
                <x-wms.stat label="Kapasitas" :value="number_format($location->capacity_m3, 2, ',', '.').' m3'" :hint="number_format($location->capacity_kg, 2, ',', '.').' kg'" tone="slate" />
            </div>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Isi bin</h3>
                        <p class="mt-1 text-xs text-slate-500">Lot stok yang saat ini tersimpan di lokasi ini.</p>
                    </div>
                    <x-wms.button :href="wms_route('stocks.index', ['search' => ''])" variant="secondary">Lihat semua stok</x-wms.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Produk</th>
                                <th class="px-5 py-3">Batch</th>
                                <th class="px-5 py-3">Kedaluwarsa</th>
                                <th class="px-5 py-3">Masuk</th>
                                <th class="px-5 py-3 text-right">Fisik</th>
                                <th class="px-5 py-3 text-right sm:px-6">Tersedia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($location->stocks as $stock)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3 sm:px-6">
                                        <a href="{{ wms_route('products.show', $stock->product) }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $stock->product->name }}</a>
                                        <p class="text-xs text-slate-400">{{ $stock->product->sku }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500">{{ $stock->batch_number }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs">
                                        @if ($stock->expired_date)
                                            <span class="{{ $stock->isExpired() ? 'font-semibold text-rose-600' : ($stock->isExpiringSoon() ? 'font-semibold text-amber-600' : 'text-slate-500') }}">{{ $stock->expired_date->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-slate-400">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $stock->received_at?->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-700">{{ number_format($stock->qty_on_hand, 2, ',', '.') }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-emerald-600 sm:px-6">{{ number_format($stock->availableQuantity(), 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10">
                                        <x-wms.empty title="Bin ini kosong" description="Belum ada lot stok yang tersimpan di lokasi ini." />
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
