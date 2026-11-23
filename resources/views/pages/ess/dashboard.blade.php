<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Operasional gudang</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Ringkasan stok, arus barang, dan pekerjaan operasional {{ $isAdmin ? 'seluruh gudang' : 'hari ini' }}.
                </p>
            </div>

            <div class="inline-flex items-center gap-1 rounded-xl border border-blue-100 bg-white p-1 shadow-sm shadow-blue-900/5">
                @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $value => $label)
                    <a
                        href="{{ wms_route('dashboard', ['days' => $value]) }}"
                        @class([
                            'rounded-lg px-3 py-2 text-xs font-semibold transition',
                            'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $days === $value,
                            'text-slate-500 hover:bg-blue-50 hover:text-blue-700' => $days !== $value,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-slot>

    @php
        $format = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $compact = function (float $value): string {
            if ($value >= 1_000_000) {
                return number_format($value / 1_000_000, 1, ',', '.').' jt';
            }

            if ($value >= 1_000) {
                return number_format($value / 1_000, 1, ',', '.').' rb';
            }

            return number_format($value, 0, ',', '.');
        };
        $pointCount = count($trend['labels']);
        $barMaxWidth = $pointCount <= 7 ? 'max-w-[2rem]' : ($pointCount <= 30 ? 'max-w-[0.75rem]' : 'max-w-[0.25rem]');
        $barGap = $pointCount <= 7 ? 'gap-1.5' : ($pointCount <= 30 ? 'gap-0.5' : 'gap-px');
        $columnGap = $pointCount <= 7 ? 'gap-2' : 'gap-px';
        $chartMinWidth = $pointCount > 30 ? 'min-w-[48rem]' : ($pointCount > 7 ? 'min-w-[36rem]' : '');
        $step = max(1, (int) ceil($pointCount / 7));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <x-wms.stat
                    label="Stok fisik"
                    :value="$compact($totalOnHand)"
                    :hint="$activeSkus.' SKU aktif'"
                    tone="blue"
                    icon="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"
                />
                <x-wms.stat
                    label="Dialokasikan"
                    :value="$compact($totalReserved)"
                    hint="Stok untuk pesanan berjalan"
                    tone="amber"
                    icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
                <x-wms.stat
                    label="Masuk hari ini"
                    :value="$compact($inboundToday)"
                    :hint="$openInbound.' purchase order terbuka'"
                    tone="emerald"
                    icon="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"
                />
                <x-wms.stat
                    label="Keluar hari ini"
                    :value="$compact($outboundToday)"
                    :hint="$openOutbound.' pesanan berjalan'"
                    tone="rose"
                    icon="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"
                />
            </div>

            @if ($isAdmin)
                <div class="grid gap-3 sm:gap-4 lg:grid-cols-3">
                    <x-wms.stat
                        class="lg:col-span-2"
                        label="Nilai aset stok"
                        value="Rp {{ number_format((float) $assetValue, 0, ',', '.') }}"
                        hint="Hanya terlihat oleh admin"
                        tone="slate"
                        icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                    <x-wms.stat
                        label="Menunggu approval"
                        :value="$pendingAdjustments"
                        hint="Penyesuaian stok pending"
                        tone="amber"
                        icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                    />
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-3">
                <x-wms.card class="animate-fade-in-up xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Arus barang</h3>
                            <p class="mt-1 text-xs text-slate-500">Volume masuk dan keluar per hari.</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Masuk
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span> Keluar
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <div class="{{ $chartMinWidth }} {{ $chartMinWidth !== '' ? 'lg:min-w-0' : '' }} pb-5">
                            <div class="relative h-64">
                                <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                                    @for ($gridLine = 0; $gridLine < 4; $gridLine++)
                                        <div class="border-t border-dashed border-slate-100"></div>
                                    @endfor
                                    <div class="border-t border-slate-200"></div>
                                </div>

                                <div class="relative flex h-full items-end {{ $columnGap }}">
                                    @foreach ($trend['labels'] as $index => $label)
                                        <div class="group relative flex h-full min-w-0 flex-1 items-end justify-center {{ $barGap }}">
                                            <div
                                                class="chart-bar flex-1 {{ $barMaxWidth }} rounded-t-sm bg-emerald-500/90 transition group-hover:bg-emerald-500"
                                                style="height: {{ max(2, round(($trend['inbound'][$index] / $trend['max']) * 100)) }}%; animation-delay: {{ min(360, $index * 20) }}ms"
                                                title="{{ $label }} masuk: {{ $format($trend['inbound'][$index]) }}"
                                            ></div>
                                            <div
                                                class="chart-bar flex-1 {{ $barMaxWidth }} rounded-t-sm bg-blue-500/90 transition group-hover:bg-blue-500"
                                                style="height: {{ max(2, round(($trend['outbound'][$index] / $trend['max']) * 100)) }}%; animation-delay: {{ min(360, $index * 20) }}ms"
                                                title="{{ $label }} keluar: {{ $format($trend['outbound'][$index]) }}"
                                            ></div>

                                            @if ($index % $step === 0 || $index === $pointCount - 1)
                                                <span class="absolute -bottom-5 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-slate-400">
                                                    {{ $label }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </x-wms.card>

                <x-wms.card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Okupansi gudang</h3>
                            <p class="mt-1 text-xs text-slate-500">Bin terisi dibanding total bin.</p>
                        </div>
                        <a href="{{ wms_route('warehouses.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-500">Lihat semua</a>
                    </div>

                    <div class="mt-5 max-h-72 space-y-4 overflow-y-auto pr-2">
                        @forelse ($warehouses as $warehouse)
                            @php
                                $percent = $warehouse->total_bins > 0 ? round($warehouse->occupied_bins / $warehouse->total_bins * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <a href="{{ wms_route('warehouses.show', $warehouse) }}" class="truncate font-semibold text-slate-700 hover:text-blue-700">
                                        {{ $warehouse->name }}
                                    </a>
                                    <span class="shrink-0 font-semibold text-slate-500">{{ $percent }}%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-blue-500" style="width: {{ $percent }}%"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $warehouse->occupied_bins }} dari {{ $warehouse->total_bins }} bin terisi</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada gudang terdaftar.</p>
                        @endforelse
                    </div>
                </x-wms.card>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <x-wms.card>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Di bawah minimum</h3>
                        <a href="{{ wms_route('products.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Lihat semua</a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($belowMinimum as $product)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <a href="{{ wms_route('products.show', $product) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-blue-700">{{ $product->name }}</a>
                                    <p class="text-xs text-slate-400">{{ $product->sku }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold text-amber-600">{{ $format((float) $product->on_hand - (float) $product->reserved_qty) }}</span>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">Semua stok di atas ambang minimum.</p>
                        @endforelse
                    </div>
                </x-wms.card>

                <x-wms.card>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Stok kosong</h3>
                        <a href="{{ wms_route('stocks.index', ['filter' => 'empty']) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Lihat semua</a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($outOfStock as $product)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <a href="{{ wms_route('products.show', $product) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-blue-700">{{ $product->name }}</a>
                                    <p class="text-xs text-slate-400">{{ $product->sku }}</p>
                                </div>
                                <x-wms.status status="rejected" label="Kosong" />
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">Tidak ada produk tanpa stok.</p>
                        @endforelse
                    </div>
                </x-wms.card>

                <x-wms.card>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Segera kedaluwarsa</h3>
                        <a href="{{ wms_route('stocks.index', ['filter' => 'expiring']) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500">Lihat semua</a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100">
                        @forelse ($expiring as $stock)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <a href="{{ wms_route('locations.show', $stock->location) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-blue-700">{{ $stock->product->name }}</a>
                                    <p class="text-xs text-slate-400">{{ $stock->location->full_code }} | {{ $stock->batch_number }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold text-amber-600">{{ $stock->expired_date?->format('d/m/Y') }}</p>
                                    <p class="text-xs text-slate-400">{{ $format($stock->qty_on_hand) }} {{ $stock->product->uom }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">Tidak ada stok yang mendekati kedaluwarsa.</p>
                        @endforelse
                    </div>
                </x-wms.card>
            </div>

            <x-wms.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Pergerakan terbaru</h3>
                        <p class="mt-1 text-xs text-slate-500">Aktivitas stok terakhir di seluruh bin.</p>
                    </div>
                    <x-wms.button :href="wms_route('reports.ledger')" variant="secondary">Buka kartu stok</x-wms.button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-5 py-3 sm:px-6">Waktu</th>
                                <th class="px-5 py-3">Produk</th>
                                <th class="px-5 py-3">Tipe</th>
                                <th class="px-5 py-3">Lokasi</th>
                                <th class="px-5 py-3 text-right sm:px-6">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentMovements as $movement)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500 sm:px-6">{{ $movement->created_at?->format('d/m H:i') }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-800">{{ $movement->product?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $movement->product?->sku }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <x-wms.status :status="$movement->type" :label="$movement->typeLabel()" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $movement->location?->full_code }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold {{ $movement->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }} sm:px-6">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $format($movement->quantity) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10">
                                        <x-wms.empty title="Belum ada pergerakan stok" description="Pergerakan akan muncul setelah receiving, transfer, atau pengiriman pertama." />
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
