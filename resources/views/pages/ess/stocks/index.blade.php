<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Stok</h2>
                <p class="mt-1 text-sm text-slate-500">Seluruh lot stok beserta alokasi dan status kedaluwarsa.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('transfers.create')" variant="secondary">Transfer stok</x-wms.button>
                <x-wms.button :href="wms_route('adjustments.create')">Ajukan penyesuaian</x-wms.button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3">
                <x-wms.stat label="Total fisik" :value="number_format($totalOnHand, 2, ',', '.')" hint="Seluruh lot di semua gudang" tone="blue" />
                <x-wms.stat label="Dialokasikan" :value="number_format($totalReserved, 2, ',', '.')" hint="Terikat pada pesanan" tone="amber" />
                <x-wms.stat class="col-span-2 sm:col-span-1" label="Tersedia" :value="number_format($totalOnHand - $totalReserved, 2, ',', '.')" hint="Siap dipakai" tone="emerald" />
            </div>

            <form method="GET" action="{{ wms_route('stocks.index') }}" class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm shadow-blue-900/5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </span>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Cari SKU, nama, atau barcode..." class="w-full rounded-xl border-blue-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                    </div>

                    <select name="warehouse" class="rounded-xl border-blue-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Semua gudang</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected($warehouseId === $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>

                    <select name="filter" class="rounded-xl border-blue-200 bg-white py-2.5 pl-3 pr-8 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                        <option value="">Semua status</option>
                        <option value="available" @selected($filter === 'available')>Tersedia</option>
                        <option value="empty" @selected($filter === 'empty')>Kosong</option>
                        <option value="expiring" @selected($filter === 'expiring')>Segera kedaluwarsa</option>
                        <option value="expired" @selected($filter === 'expired')>Kedaluwarsa</option>
                    </select>

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Cari</button>

                    @if ($search !== '' || $warehouseId !== '' || $filter !== '')
                        <a href="{{ wms_route('stocks.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 hover:text-slate-900">Reset</a>
                    @endif
                </div>
            </form>

            <x-wms.card :padded="false">
                <div class="flex items-center justify-between gap-4 border-b border-blue-100 px-6 py-4">
                    <p class="text-sm text-slate-500"><span class="font-semibold text-slate-900">{{ $stocks->total() }}</span> lot stok</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Buku besar stok</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-blue-100 text-left text-sm">
                        <thead class="bg-blue-50/70 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Produk</th>
                                <th class="px-6 py-4">Bin</th>
                                <th class="px-6 py-4">Batch</th>
                                <th class="px-6 py-4">Kedaluwarsa</th>
                                <th class="px-6 py-4 text-right">Fisik</th>
                                <th class="px-6 py-4 text-right">Tersedia</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50">
                            @forelse ($stocks as $stock)
                                @php
                                    $available = (float) $stock->qty_on_hand - (float) $stock->qty_reserved;
                                @endphp
                                <tr class="transition hover:bg-blue-50/40">
                                    <td class="px-6 py-4">
                                        <a href="{{ wms_route('products.show', $stock->product) }}" class="font-medium text-slate-900 hover:text-blue-700">{{ $stock->product->name }}</a>
                                        <p class="text-xs text-slate-500">{{ $stock->product->sku }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ wms_route('locations.show', $stock->location) }}" class="text-sm font-medium text-slate-700 hover:text-blue-700">{{ $stock->location->full_code }}</a>
                                        <p class="text-xs text-slate-400">{{ $stock->location->warehouse->name }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">{{ $stock->batch_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        @if ($stock->expired_date)
                                            <span class="{{ $stock->isExpired() ? 'font-semibold text-rose-600' : ($stock->isExpiringSoon() ? 'font-semibold text-amber-600' : 'text-slate-500') }}">{{ $stock->expired_date->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-slate-400">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700">{{ number_format($stock->qty_on_hand, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-semibold {{ $available <= 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($available, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4">
                                        @if ($stock->isExpired())
                                            <x-wms.status status="rejected" label="Kedaluwarsa" />
                                        @elseif ($stock->isExpiringSoon())
                                            <x-wms.status status="pending" label="Segera ED" />
                                        @elseif ($available <= 0)
                                            <x-wms.status status="draft" label="Habis" />
                                        @else
                                            <x-wms.status status="approved" label="Tersedia" />
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end">
                                            <x-wms.icon-button :href="wms_route('labels.stocks', $stock)" icon="printer" label="Cetak label {{ $stock->product->name }} di {{ $stock->location->full_code }}" target="_blank" rel="noopener" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12">
                                        <x-wms.empty title="Tidak ada lot stok" description="Ubah filter atau lakukan receiving untuk menambah stok." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($stocks->hasPages())
                    <div class="border-t border-blue-100 px-6 py-4">{{ $stocks->links() }}</div>
                @endif
            </x-wms.card>
        </div>
    </div>
</x-app-layout>
