<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $order->reference_no }}</h2>
                    <x-wms.status :status="$order->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $order->supplier?->name }} | {{ $order->warehouse?->name }} | kedatangan {{ $order->expected_date?->format('d/m/Y') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('labels.purchase-order', $order)" variant="secondary">Cetak label</x-wms.button>

                @if ($stagedItems->isNotEmpty())
                    <x-wms.button :href="wms_route('purchase-orders.put-away', $order)">Put-away</x-wms.button>
                @elseif (in_array($order->status, ['scheduled', 'receiving'], true))
                    <x-wms.button :href="wms_route('purchase-orders.receive', $order)">Receive barang</x-wms.button>
                @endif

                @if (Auth::user()->isAdmin() && ! in_array($order->status, ['completed', 'cancelled'], true))
                    <button
                        type="button"
                        @click="$dispatch('wms-confirm', { action: @js(wms_route('purchase-orders.cancel', $order)), name: @js($order->reference_no) })"
                        class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-50"
                    >
                        Batalkan
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $expected = (float) $order->items->sum('qty_expected');
                $received = (float) $order->items->sum('qty_received');
                $rejected = (float) $order->items->sum('qty_rejected');
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat label="Dijadwalkan" :value="number_format($expected, 2, ',', '.')" :hint="$order->items->count().' baris produk'" tone="blue" />
                <x-wms.stat label="Diterima baik" :value="number_format($received, 2, ',', '.')" hint="Masuk ke staging" tone="emerald" />
                <x-wms.stat label="Ditolak" :value="number_format($rejected, 2, ',', '.')" hint="Tidak masuk stok" tone="rose" />
                <x-wms.stat label="Sisa" :value="number_format(max(0, $expected - $received - $rejected), 2, ',', '.')" hint="Belum diterima" tone="amber" />
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <x-wms.card class="xl:col-span-2" :padded="false">
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-slate-900">Baris purchase order</h3>
                        <p class="mt-1 text-xs text-slate-500">Progres penerimaan per produk.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-5 py-3 sm:px-6">Produk</th>
                                    <th class="px-5 py-3 text-right">Diharapkan</th>
                                    <th class="px-5 py-3 text-right">Diterima</th>
                                    <th class="px-5 py-3 text-right">Ditolak</th>
                                    <th class="px-5 py-3 sm:px-6">Progres</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($order->items as $item)
                                    @php
                                        $done = (float) $item->qty_received + (float) $item->qty_rejected;
                                        $percent = $item->qty_expected > 0 ? min(100, round($done / $item->qty_expected * 100)) : 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-4 sm:px-6">
                                            <a href="{{ wms_route('products.show', $item->product) }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $item->product->name }}</a>
                                            <p class="text-xs text-slate-400">{{ $item->product->sku }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-right font-medium text-slate-700">{{ number_format($item->qty_expected, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right font-medium text-emerald-600">{{ number_format($item->qty_received, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right font-medium text-rose-600">{{ number_format($item->qty_rejected, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100">
                                                    <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ $percent }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-slate-500">{{ $percent }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-wms.card>

                <x-wms.card>
                    <h3 class="text-sm font-semibold text-slate-900">Informasi</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Dibuat oleh</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->creator?->name ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Dibuat pada</dt>
                            <dd class="font-medium text-slate-800">{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Receipt</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->receipt?->reference_no ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Diterima pada</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->receipt?->received_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        </div>
                        @if ($order->notes)
                            <div>
                                <dt class="text-slate-500">Catatan</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $order->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-wms.card>
            </div>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Penerimaan fisik</h3>
                        <p class="mt-1 text-xs text-slate-500">Batch yang dicatat saat receiving beserta posisi akhirnya.</p>
                    </div>
                    <x-wms.status :status="$order->receipt ? 'staged' : 'draft'" :label="$order->receipt ? 'Receipt terbit' : 'Belum receiving'" />
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Produk</th>
                                <th class="px-5 py-3">Batch</th>
                                <th class="px-5 py-3">Kedaluwarsa</th>
                                <th class="px-5 py-3">Staging</th>
                                <th class="px-5 py-3">Bin akhir</th>
                                <th class="px-5 py-3 text-right">Baik</th>
                                <th class="px-5 py-3 text-right">Ditolak</th>
                                <th class="px-5 py-3 sm:px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($order->receipt?->items ?? [] as $item)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4 sm:px-6">
                                        <p class="font-medium text-slate-800">{{ $item->product?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $item->product?->sku }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500">{{ $item->batch_number }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $item->expired_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-5 py-4 text-xs text-slate-500">{{ $item->location?->full_code }}</td>
                                    <td class="px-5 py-4 text-xs text-slate-500">{{ $item->putAwayLocation?->full_code ?? '-' }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-emerald-600">{{ number_format($item->qty_good, 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-rose-600">{{ number_format($item->qty_rejected, 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 sm:px-6"><x-wms.status :status="$item->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10">
                                        <x-wms.empty title="Belum ada penerimaan" description="Mulai receiving untuk mencatat barang datang ke staging." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-wms.card>
        </div>
    </div>

    @if (Auth::user()->isAdmin() && ! in_array($order->status, ['completed', 'cancelled'], true))
        <x-wms.confirm-popup
            title="Batalkan purchase order?"
            message="Purchase order berikut akan ditandai batal dan tidak dapat dilanjutkan:"
            confirm-label="Batalkan order"
        />
    @endif
</x-app-layout>
