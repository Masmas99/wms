<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Kartu stok</h2>
                <p class="mt-1 text-sm text-slate-500">Mutasi kronologis per SKU dengan saldo berjalan.</p>
            </div>

            @if ($product && Auth::user()->isAdmin())
                <x-wms.button :href="wms_route('reports.ledger.export', ['product' => $product->id, 'from' => $from, 'to' => $to])" variant="secondary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Excel
                </x-wms.button>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ wms_route('reports.ledger') }}" class="rounded-2xl border border-blue-100 bg-white p-4 shadow-sm shadow-blue-900/5">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                    <x-wms.field label="Produk" for="product">
                        <x-wms.select id="product" name="product">
                            @foreach ($products as $option)
                                <option value="{{ $option->id }}" @selected($product?->id === $option->id)>{{ $option->sku }} - {{ $option->name }}</option>
                            @endforeach
                        </x-wms.select>
                    </x-wms.field>

                    <x-wms.field label="Dari" for="from">
                        <x-wms.input id="from" name="from" type="date" value="{{ $from }}" />
                    </x-wms.field>

                    <x-wms.field label="Sampai" for="to">
                        <x-wms.input id="to" name="to" type="date" value="{{ $to }}" />
                    </x-wms.field>

                    <x-wms.button type="submit">Terapkan</x-wms.button>
                </div>
            </form>

            @if ($product)
                <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    <x-wms.stat label="Saldo awal" :value="number_format($initial, 2, ',', '.')" :hint="'Per '.date('d/m/Y', strtotime($from))" tone="slate" />
                    <x-wms.stat label="Total masuk" :value="number_format(collect($rows)->sum('in'), 2, ',', '.')" hint="Seluruh mutasi positif" tone="emerald" />
                    <x-wms.stat label="Total keluar" :value="number_format(collect($rows)->sum('out'), 2, ',', '.')" hint="Seluruh mutasi negatif" tone="rose" />
                    <x-wms.stat label="Saldo akhir" :value="number_format($final, 2, ',', '.')" :hint="'Per '.date('d/m/Y', strtotime($to))" tone="blue" />
                </div>

                <x-wms.card :padded="false">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $product->name }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $product->sku }} | satuan {{ $product->uom }}</p>
                        </div>
                        <x-wms.status :status="'approved'" label="Buku besar" />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                <tr>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">Tipe</th>
                                    <th class="px-6 py-4">Referensi</th>
                                    <th class="px-6 py-4">Lokasi</th>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4 text-right">Masuk</th>
                                    <th class="px-6 py-4 text-right">Keluar</th>
                                    <th class="px-6 py-4 text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($rows as $row)
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-500">{{ $row['movement']->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-4 text-xs font-medium text-slate-700">{{ $row['movement']->typeLabel() }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $row['movement']->reference_no ?: '-' }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $row['movement']->location?->full_code ?: '-' }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-500">{{ $row['movement']->user?->name ?: '-' }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-emerald-600">{{ $row['in'] > 0 ? number_format($row['in'], 2, ',', '.') : '-' }}</td>
                                        <td class="px-6 py-4 text-right font-medium text-rose-600">{{ $row['out'] > 0 ? number_format($row['out'], 2, ',', '.') : '-' }}</td>
                                        <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ number_format($row['balance'], 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12">
                                            <x-wms.empty title="Tidak ada mutasi" description="Belum ada pergerakan stok pada rentang tanggal ini." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-wms.card>
            @else
                <x-wms.empty title="Belum ada produk" description="Tambahkan produk terlebih dahulu untuk melihat kartu stok." />
            @endif
        </div>
    </div>
</x-app-layout>
