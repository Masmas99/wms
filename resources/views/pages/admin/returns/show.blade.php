<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $return->reference_no }}</h2>
                    <x-wms.status :status="$return->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $return->customer_name }}{{ $return->salesOrder ? ' | '.$return->salesOrder->reference_no : '' }} | diterima {{ $return->created_at?->format('d/m/Y H:i') }}</p>
            </div>

            <x-wms.button :href="wms_route('returns.index')" variant="secondary">Daftar retur</x-wms.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $isAdmin = Auth::user()->isAdmin();
                $totalQty = (float) $return->items->sum('quantity');
                $pendingCount = $return->items->where('disposition', 'pending')->count();
                $decidedCount = $return->items->count() - $pendingCount;
            @endphp

            <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3">
                <x-wms.stat label="Total unit retur" :value="number_format($totalQty, 2, ',', '.')" :hint="$return->items->count().' baris'" tone="blue" />
                <x-wms.stat label="Menunggu disposisi" :value="(string) $pendingCount" hint="Perlu keputusan admin" tone="amber" />
                <x-wms.stat class="col-span-2 sm:col-span-1" label="Sudah diputuskan" :value="(string) $decidedCount" hint="Restock, karantina, atau scrap" tone="emerald" />
            </div>

            @if (! $isAdmin && $pendingCount > 0)
                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
                    Disposisi retur hanya dapat ditetapkan oleh admin. Barang tetap tercatat sebagai retur diterima sampai ada keputusan.
                </div>
            @endif

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Baris retur</h3>
                        <p class="mt-1 text-xs text-slate-500">Kondisi fisik dan keputusan akhir tiap barang.</p>
                    </div>
                    <x-wms.status :status="$return->status" />
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($return->items as $item)
                        <div class="p-5 sm:p-6">
                            <div class="grid gap-4 sm:grid-cols-12 sm:items-start">
                                <div class="sm:col-span-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item->product?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->product?->sku }} | batch {{ $item->batch_number }}</p>
                                    @if ($item->notes)
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->notes }}</p>
                                    @endif
                                </div>

                                <div class="sm:col-span-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Kuantitas</p>
                                    <p class="text-sm font-bold text-slate-900">{{ number_format($item->quantity, 2, ',', '.') }} {{ $item->product?->uom }}</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Kondisi</p>
                                    <x-wms.status :status="$item->condition" />
                                </div>

                                <div class="sm:col-span-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Disposisi</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <x-wms.status :status="$item->disposition" :label="$item->dispositionLabel()" />
                                        @if ($item->location)
                                            <span class="text-xs text-slate-500">ke {{ $item->location->full_code }}</span>
                                        @endif
                                    </div>
                                    @if ($item->decider)
                                        <p class="mt-1 text-xs text-slate-400">{{ $item->decider->name }} | {{ $item->decided_at?->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($isAdmin && $item->disposition === 'pending')
                                <form
                                    method="POST"
                                    action="{{ wms_route('returns.disposition', [$return, $item]) }}"
                                    class="mt-4 grid gap-3 rounded-xl border border-blue-100 bg-blue-50/40 p-4 sm:grid-cols-12 sm:items-end"
                                    x-data="{ disposition: 'restock' }"
                                >
                                    @csrf

                                    <div class="sm:col-span-3">
                                        <label for="disposition_{{ $item->id }}" class="block text-xs font-semibold text-slate-600">Keputusan</label>
                                        <select
                                            id="disposition_{{ $item->id }}"
                                            name="disposition"
                                            x-model="disposition"
                                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                        >
                                            <option value="restock">Restock ke bin</option>
                                            <option value="quarantine">Karantina</option>
                                            <option value="scrap">Scrap</option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-4" x-show="disposition === 'restock'" x-cloak>
                                        <label for="location_{{ $item->id }}" class="block text-xs font-semibold text-slate-600">Bin tujuan</label>
                                        <select
                                            id="location_{{ $item->id }}"
                                            name="location_id"
                                            x-bind:disabled="disposition !== 'restock'"
                                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                        >
                                            <option value="">Pilih bin</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->full_code }} | {{ $location->warehouse?->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="sm:col-span-4" x-bind:class="disposition === 'restock' ? 'sm:col-span-3' : 'sm:col-span-7'">
                                        <label for="notes_{{ $item->id }}" class="block text-xs font-semibold text-slate-600">Catatan</label>
                                        <input
                                            id="notes_{{ $item->id }}"
                                            name="notes"
                                            maxlength="500"
                                            class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                        >
                                    </div>

                                    <div class="sm:col-span-2 sm:flex sm:justify-end">
                                        <x-wms.button type="submit">Simpan</x-wms.button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-wms.card>

            @if ($return->notes)
                <x-wms.card>
                    <h3 class="text-sm font-semibold text-slate-900">Catatan retur</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $return->notes }}</p>
                </x-wms.card>
            @endif
        </div>
    </div>
</x-app-layout>
