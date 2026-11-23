<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Aging stok</h2>
                <p class="mt-1 text-sm text-slate-500">Umur simpan lot dan nilai persediaan yang menua.</p>
            </div>

            <x-wms.button :href="wms_route('reports.aging.export')" variant="secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Unduh CSV
            </x-wms.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
                <x-wms.stat class="col-span-2 xl:col-span-1" label="Nilai persediaan" :value="'Rp '.number_format($totalValue, 0, ',', '.')" hint="Seluruh lot tersimpan" tone="blue" />
                <x-wms.stat label="0-30 hari" :value="number_format($summary['0-30'], 2, ',', '.')" hint="Lot baru" tone="emerald" />
                <x-wms.stat label="31-60 hari" :value="number_format($summary['31-60'], 2, ',', '.')" hint="Pantau perputaran" tone="amber" />
                <x-wms.stat label="61-90 hari" :value="number_format($summary['61-90'], 2, ',', '.')" hint="Perlu percepatan" tone="amber" />
                <x-wms.stat label="Lebih dari 90 hari" :value="number_format($summary['>90'], 2, ',', '.')" hint="Risiko dead stock" tone="rose" />
            </div>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Rincian umur lot</h3>
                        <p class="mt-1 text-xs text-slate-500">Diurutkan dari lot paling lama.</p>
                    </div>
                    <x-wms.status status="approved" label="Analitik" />
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                            <tr>
                                <th class="px-6 py-4">Produk</th>
                                <th class="px-6 py-4">Gudang / Bin</th>
                                <th class="px-6 py-4">Batch</th>
                                <th class="px-6 py-4">Kedaluwarsa</th>
                                <th class="px-6 py-4">Masuk</th>
                                <th class="px-6 py-4 text-right">Umur</th>
                                <th class="px-6 py-4">Bucket</th>
                                <th class="px-6 py-4 text-right">Qty</th>
                                <th class="px-6 py-4 text-right">Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-slate-800">{{ $row['stock']->product?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['stock']->product?->sku }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-medium text-slate-600">{{ $row['stock']->location?->warehouse?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['stock']->location?->full_code }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">{{ $row['stock']->batch_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-500">{{ $row['stock']->expired_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-500">{{ $row['stock']->received_at?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right text-xs font-semibold text-slate-700">{{ $row['age'] }} hari</td>
                                    <td class="px-6 py-4">
                                        <x-wms.status
                                            :status="$row['bucket'] === '>90' ? 'scrap' : ($row['bucket'] === '0-30' ? 'approved' : 'pending')"
                                            :label="$row['bucket'].' hari'"
                                        />
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-700">{{ number_format($row['stock']->qty_on_hand, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-900">Rp {{ number_format($row['value'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12">
                                        <x-wms.empty title="Belum ada stok" description="Aging muncul setelah ada lot tersimpan di bin." />
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
