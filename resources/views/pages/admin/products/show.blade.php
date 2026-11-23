<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $product->name }}</h2>
                    <x-wms.status :status="$product->is_active ? 'approved' : 'draft'" :label="$product->is_active ? 'Aktif' : 'Nonaktif'" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $product->sku }} | {{ $product->category ?: 'Tanpa kategori' }} | {{ $product->uom }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('labels.products', $product)" variant="secondary">Cetak label</x-wms.button>
                @if (Auth::user()->isAdmin())
                    <x-wms.button :href="wms_route('products.edit', $product)">Edit produk</x-wms.button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $onHand = (float) $product->stocks->sum('qty_on_hand');
                $reserved = (float) $product->stocks->sum('qty_reserved');
                $available = $onHand - $reserved;
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat label="Stok fisik" :value="number_format($onHand, 2, ',', '.')" :hint="'Satuan '.$product->uom" tone="blue" />
                <x-wms.stat label="Dialokasikan" :value="number_format($reserved, 2, ',', '.')" hint="Untuk pesanan berjalan" tone="amber" />
                <x-wms.stat label="Tersedia" :value="number_format($available, 2, ',', '.')" hint="Siap dialokasikan" tone="emerald" />
                @if (Auth::user()->isAdmin())
                    <x-wms.stat
                        label="Nilai aset"
                        value="Rp {{ number_format($onHand * (float) $product->unit_cost, 0, ',', '.') }}"
                        :hint="'HPP Rp '.number_format((float) $product->unit_cost, 0, ',', '.').' / unit'"
                        tone="slate"
                    />
                @else
                    <x-wms.stat label="Rotasi" :value="$product->rotation" :hint="'Min '.number_format($product->min_stock, 2, ',', '.').' / maks '.number_format($product->max_stock, 2, ',', '.')" tone="slate" />
                @endif
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <x-wms.card class="xl:col-span-2" :padded="false">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Sebaran stok per lot</h3>
                            <p class="mt-1 text-xs text-slate-500">Hanya lot dengan kuantitas fisik di atas nol.</p>
                        </div>
                        <x-wms.status :status="$product->rotation === 'FEFO' ? 'pending' : 'draft'" :label="'Rotasi '.$product->rotation" />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-5 py-3 sm:px-6">Bin</th>
                                    <th class="px-5 py-3">Batch</th>
                                    <th class="px-5 py-3">Kedaluwarsa</th>
                                    <th class="px-5 py-3 text-right">Fisik</th>
                                    <th class="px-5 py-3 text-right sm:px-6">Tersedia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($product->stocks as $stock)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-3 sm:px-6">
                                            <a href="{{ wms_route('locations.show', $stock->location) }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $stock->location->full_code }}</a>
                                            <p class="text-xs text-slate-400">{{ $stock->location->warehouse->name }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-500">{{ $stock->batch_number }}</td>
                                        <td class="whitespace-nowrap px-5 py-3 text-xs">
                                            @if ($stock->expired_date)
                                                <span class="{{ $stock->isExpired() ? 'font-semibold text-rose-600' : ($stock->isExpiringSoon() ? 'font-semibold text-amber-600' : 'text-slate-500') }}">
                                                    {{ $stock->expired_date->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right font-medium text-slate-700">{{ number_format($stock->qty_on_hand, 2, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-emerald-600 sm:px-6">{{ number_format($stock->availableQuantity(), 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10">
                                            <x-wms.empty title="Belum ada stok tersimpan" description="Lakukan receiving dan put-away untuk mengisi bin." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-wms.card>

                <x-wms.card>
                    <h3 class="text-sm font-semibold text-slate-900">Informasi produk</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Barcode</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $product->barcode ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Satuan</dt>
                            <dd class="font-medium text-slate-800">{{ $product->uom }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Rotasi</dt>
                            <dd class="font-medium text-slate-800">{{ $product->rotation }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Stok minimum</dt>
                            <dd class="font-medium text-slate-800">{{ number_format($product->min_stock, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Stok maksimum</dt>
                            <dd class="font-medium text-slate-800">{{ number_format($product->max_stock, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Berat</dt>
                            <dd class="font-medium text-slate-800">{{ $product->weight_kg !== null ? number_format($product->weight_kg, 2, ',', '.').' kg' : '-' }}</dd>
                        </div>
                    </dl>
                </x-wms.card>
            </div>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Riwayat pergerakan</h3>
                        <p class="mt-1 text-xs text-slate-500">15 transaksi terakhir untuk SKU ini.</p>
                    </div>
                    <x-wms.button :href="wms_route('reports.ledger', ['product' => $product->id])" variant="secondary">Kartu stok lengkap</x-wms.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Waktu</th>
                                <th class="px-5 py-3">Tipe</th>
                                <th class="px-5 py-3">Referensi</th>
                                <th class="px-5 py-3">Bin</th>
                                <th class="px-5 py-3">User</th>
                                <th class="px-5 py-3 text-right sm:px-6">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($movements as $movement)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500 sm:px-6">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-3"><x-wms.status :status="$movement->type" :label="$movement->typeLabel()" /></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $movement->reference_no }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $movement->location?->full_code }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $movement->user?->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold {{ $movement->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }} sm:px-6">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10">
                                        <x-wms.empty title="Belum ada pergerakan" description="Riwayat akan tercatat setelah stok masuk atau keluar." />
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
