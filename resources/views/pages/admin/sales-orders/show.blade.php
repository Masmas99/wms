<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $order->reference_no }}</h2>
                    <x-wms.status :status="$order->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $order->customer_name }}{{ $order->customer_phone ? ' | '.$order->customer_phone : '' }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wms.button :href="wms_route('labels.packing-list', $order)" variant="secondary">Packing list</x-wms.button>

                @if ($order->shipment)
                    <x-wms.button :href="wms_route('labels.shipping', $order)" variant="secondary">Label kirim</x-wms.button>
                @endif

                @if ($order->status === 'draft' && Auth::user()->isAdmin())
                    <form method="POST" action="{{ wms_route('sales-orders.reserve', $order) }}">
                        @csrf
                        <x-wms.button type="submit">Alokasikan stok</x-wms.button>
                    </form>
                @elseif (in_array($order->status, ['reserved', 'picking'], true))
                    <x-wms.button :href="wms_route('sales-orders.picking', $order)">Mulai picking</x-wms.button>
                @elseif ($order->status === 'packed')
                    <x-wms.button :href="wms_route('sales-orders.packing', $order)">Verifikasi packing</x-wms.button>
                @endif

                @if ($order->status === 'reserved' && Auth::user()->isAdmin())
                    <button
                        type="button"
                        @click="$dispatch('wms-confirm', { action: @js(wms_route('sales-orders.release', $order)), name: @js($order->reference_no) })"
                        class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-50"
                    >
                        Lepas alokasi
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $ordered = (float) $order->items->sum('qty_ordered');
                $allocated = (float) $order->items->sum('qty_allocated');
                $picked = (float) $order->items->sum('qty_picked');
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat label="Dipesan" :value="number_format($ordered, 2, ',', '.')" :hint="$order->items->count().' baris pesanan'" tone="blue" />
                <x-wms.stat label="Dialokasikan" :value="number_format($allocated, 2, ',', '.')" hint="Terikat pada lot stok" tone="amber" />
                <x-wms.stat label="Di-pick" :value="number_format($picked, 2, ',', '.')" hint="Sudah diambil dari bin" tone="emerald" />
                <x-wms.stat label="Sisa" :value="number_format(max(0, $ordered - $picked), 2, ',', '.')" hint="Belum di-pick" tone="slate" />
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <x-wms.card class="xl:col-span-2" :padded="false">
                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-slate-900">Baris pesanan</h3>
                        <p class="mt-1 text-xs text-slate-500">Progres alokasi dan pengambilan per produk.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-5 py-3 sm:px-6">Produk</th>
                                    <th class="px-5 py-3 text-right">Dipesan</th>
                                    <th class="px-5 py-3 text-right">Dialokasikan</th>
                                    <th class="px-5 py-3 text-right">Di-pick</th>
                                    <th class="px-5 py-3 sm:px-6">Progres</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($order->items as $item)
                                    @php
                                        $percent = $item->qty_ordered > 0 ? min(100, round($item->qty_picked / $item->qty_ordered * 100)) : 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-4 sm:px-6">
                                            <a href="{{ wms_route('products.show', $item->product) }}" class="font-medium text-slate-800 hover:text-blue-700">{{ $item->product->name }}</a>
                                            <p class="text-xs text-slate-400">{{ $item->product->sku }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-right font-medium text-slate-700">{{ number_format($item->qty_ordered, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right font-medium {{ $item->qty_allocated < $item->qty_ordered ? 'text-amber-600' : 'text-slate-700' }}">{{ number_format($item->qty_allocated, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right font-medium text-emerald-600">{{ number_format($item->qty_picked, 2, ',', '.') }}</td>
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
                            <dt class="text-slate-500">Dialokasikan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->reserved_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Packing selesai</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->packed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Dikirim</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $order->shipped_at?->format('d/m/Y H:i') ?? '-' }}</dd>
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
                        <h3 class="text-sm font-semibold text-slate-900">Alokasi stok</h3>
                        <p class="mt-1 text-xs text-slate-500">Lot yang dipesan untuk pesanan ini beserta urutan pengambilan.</p>
                    </div>
                    @if ($order->status === 'picking')
                        <x-wms.status status="picking" label="Sedang picking" />
                    @elseif ($order->status === 'packed')
                        <x-wms.status status="packed" label="Siap dikirim" />
                    @elseif ($order->status === 'shipped')
                        <x-wms.status status="shipped" label="Sudah dikirim" />
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Urutan</th>
                                <th class="px-5 py-3">Produk</th>
                                <th class="px-5 py-3">Bin</th>
                                <th class="px-5 py-3">Batch / ED</th>
                                <th class="px-5 py-3 text-right">Qty</th>
                                <th class="px-5 py-3 sm:px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $hasAllocation = false; @endphp
                            @foreach ($order->items as $item)
                                @foreach ($item->allocations->sortBy('pick_sequence') as $allocation)
                                    @php $hasAllocation = true; @endphp
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-5 py-4 font-semibold text-slate-400 sm:px-6">{{ $allocation->pick_sequence }}</td>
                                        <td class="px-5 py-4">
                                            <p class="font-medium text-slate-800">{{ $item->product->name }}</p>
                                            <p class="text-xs text-slate-400">{{ $item->product->sku }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500">{{ $allocation->inventoryStock?->location?->full_code }}</td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            {{ $allocation->inventoryStock?->batch_number }}
                                            @if ($allocation->inventoryStock?->expired_date)
                                                <br>ED {{ $allocation->inventoryStock->expired_date->format('d/m/Y') }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right font-medium text-slate-700">{{ number_format($allocation->quantity, 2, ',', '.') }}</td>
                                        <td class="px-5 py-4 sm:px-6">
                                            @if ($allocation->packed_at)
                                                <x-wms.status status="packed" label="Terverifikasi" />
                                            @elseif ($allocation->picked_at)
                                                <x-wms.status status="picked" label="Sudah di-pick" />
                                            @else
                                                <x-wms.status status="pending" label="Menunggu" />
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach

                            @if (! $hasAllocation)
                                <tr>
                                    <td colspan="6" class="px-6 py-10">
                                        <x-wms.empty title="Belum ada alokasi" description="Alokasikan stok untuk menentukan lot yang akan diambil." />
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-wms.card>

            @if ($order->shipment)
                <x-wms.card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Pengiriman</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $order->shipment->courier?->name ?? 'Tanpa ekspedisi' }}{{ $order->shipment->tracking_number ? ' | resi '.$order->shipment->tracking_number : '' }}</p>
                        </div>
                        <p class="text-xs text-slate-500">{{ $order->shipment->shipped_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </x-wms.card>
            @elseif ($order->status === 'packed')
                <x-wms.card>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Serahkan ke ekspedisi</h3>
                        <p class="mt-1 text-xs text-slate-500">Pilih ekspedisi dan masukkan nomor resi bila tersedia.</p>
                    </div>

                    <form method="POST" action="{{ wms_route('sales-orders.ship', $order) }}" class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                        @csrf

                        <x-wms.field label="Ekspedisi" for="courier_id">
                            <x-wms.select id="courier_id" name="courier_id">
                                <option value="">Tanpa ekspedisi</option>
                                @foreach ($couriers as $courier)
                                    <option value="{{ $courier->id }}" @selected(old('courier_id') === $courier->id)>{{ $courier->name }}</option>
                                @endforeach
                            </x-wms.select>
                        </x-wms.field>

                        <x-wms.field label="Nomor resi" for="tracking_number">
                            <x-wms.input id="tracking_number" name="tracking_number" maxlength="120" value="{{ old('tracking_number') }}" placeholder="CGK1234567890" />
                        </x-wms.field>

                        <x-wms.button type="submit">Tandai dikirim</x-wms.button>
                    </form>
                </x-wms.card>
            @endif
        </div>
    </div>

    @if ($order->status === 'reserved')
        <x-wms.confirm-popup
            title="Lepas alokasi stok?"
            message="Seluruh alokasi pesanan berikut akan dilepas dan pesanan kembali ke draft:"
            confirm-label="Lepas alokasi"
        />
    @endif
</x-app-layout>
