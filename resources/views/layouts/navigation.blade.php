@php
    $isDashboard = request()->routeIs('dashboard');
    $isAdminArea = request()->routeIs('admin.dashboard');
    $isAdminUsers = request()->routeIs('admin.users.*');
    $isAdminAnnouncements = request()->routeIs('admin.announcements.*');
    $wmsPrefix = Auth::user()->isAdmin() ? 'admin.wms.' : 'ess.wms.';
    $userInitial = strtoupper(mb_substr(Auth::user()->name, 0, 1));
    $userAvatar = Auth::user()->avatarUrl();

    $warehouseLinks = [
        ['label' => 'Stok', 'route' => 'stocks.index', 'active' => 'stocks.*', 'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
        ['label' => 'Barang masuk', 'route' => 'purchase-orders.index', 'active' => 'purchase-orders.*', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3'],
        ['label' => 'Barang keluar', 'route' => 'sales-orders.index', 'active' => 'sales-orders.*', 'icon' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5'],
        ['label' => 'Transfer', 'route' => 'transfers.index', 'active' => 'transfers.*', 'icon' => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
        ['label' => 'Stock opname', 'route' => 'opnames.index', 'active' => 'opnames.*', 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'],
        ['label' => 'Penyesuaian', 'route' => 'adjustments.index', 'active' => 'adjustments.*', 'icon' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75'],
        ['label' => 'Retur', 'route' => 'returns.index', 'active' => 'returns.*', 'icon' => 'M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3'],
        ['label' => 'Mode scan', 'route' => 'scan.index', 'active' => 'scan.*', 'icon' => 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-3.75A1.125 1.125 0 0 1 3.75 8.625v-3.75ZM3.75 15.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-3.75a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM15.375 3.75c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-3.75A1.125 1.125 0 0 1 10.5 8.625v-3.75c0-.621.504-1.125 1.125-1.125h3.75ZM14.25 14.25h3.375m-3.375 3h3.375m-3.375-6h3.375'],
        ['label' => 'Laporan', 'route' => 'reports.ledger', 'active' => 'reports.*', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
    ];

    $masterDataLinks = [
        ['label' => 'Master barang', 'route' => 'products.index', 'active' => 'products.*', 'icon' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.832.699 2.531 0l4.318-4.318a1.79 1.79 0 0 0 0-2.531l-9.581-9.581A2.25 2.25 0 0 0 9.568 3Z'],
        ['label' => 'Gudang & bin', 'route' => 'warehouses.index', 'active' => 'warehouses.*', 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z'],
        ['label' => 'Pemasok', 'route' => 'suppliers.index', 'active' => 'suppliers.*', 'icon' => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z'],
        ['label' => 'Ekspedisi', 'route' => 'couriers.index', 'active' => 'couriers.*', 'icon' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
        ['label' => 'Audit aktivitas', 'route' => 'audit.index', 'active' => 'audit.*', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
    ];
@endphp

<!-- Mobile overlay -->
<div
    x-show="sidebarOpen"
    x-transition.opacity
    @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-blue-950/30 backdrop-blur-sm lg:hidden"
    x-cloak
></div>

<!-- Sidebar -->
<aside
    id="app-sidebar"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': ! sidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:px-3': sidebarCollapsed,
    }"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col overflow-hidden border-r border-blue-100 bg-white px-4 py-5 shadow-2xl shadow-blue-900/10 transition-all duration-300 ease-in-out lg:translate-x-0 lg:shadow-none"
    aria-label="Navigasi utama"
>
    <div class="pointer-events-none absolute -left-16 top-20 h-56 w-56 rounded-full bg-blue-200/40 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-sky-200/40 blur-3xl" aria-hidden="true"></div>

    <!-- Brand -->
    <div
        class="relative flex items-center justify-between gap-3"
        :class="sidebarCollapsed ? 'lg:flex-col' : ''"
    >
        <a href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="group flex min-w-0 items-center gap-3" aria-label="Rumitech Dashboard">
            <x-application-logo class="h-10 w-10 shrink-0 rounded-2xl object-cover shadow-lg shadow-blue-600/20 ring-1 ring-blue-100 transition duration-300 group-hover:scale-105" />
            <span class="min-w-0" x-show="! sidebarCollapsed || ! isDesktop">
                <span class="block truncate text-sm font-semibold tracking-tight text-slate-900">Rumitech</span>
                <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-500">Content manager</span>
            </span>
        </a>

        <button
            type="button"
            @click="sidebarCollapsed = ! sidebarCollapsed"
            class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:inline-flex"
            aria-controls="app-sidebar"
            :aria-expanded="sidebarCollapsed ? 'false' : 'true'"
            :aria-label="sidebarCollapsed ? 'Perbesar navigasi' : 'Kecilkan navigasi'"
            :title="sidebarCollapsed ? 'Perbesar navigasi' : 'Kecilkan navigasi'"
        >
            <svg :class="sidebarCollapsed ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>

        <button
            type="button"
            @click="sidebarOpen = false"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden"
        >
            <span class="sr-only">Tutup menu</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="relative mt-8 flex-1 overflow-y-auto" :class="sidebarCollapsed ? 'lg:mt-4' : ''">
        <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400" x-show="! sidebarCollapsed || ! isDesktop">
            Menu utama
        </p>

        <div class="mt-3 space-y-1">
            @unless (Auth::user()->isAdmin())
                <a
                    href="{{ route('dashboard') }}"
                    @class([
                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => $isDashboard,
                        'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! $isDashboard,
                    ])
                    :class="{
                        'lg:justify-center': sidebarCollapsed,
                        'lg:gap-0': sidebarCollapsed,
                        'lg:px-2': sidebarCollapsed,
                    }"
                    :title="sidebarCollapsed ? 'Dashboard' : null"
                    aria-label="Dashboard"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span x-show="! sidebarCollapsed || ! isDesktop">Dashboard</span>
                </a>
            @endunless

            @if (Auth::user()->isAdmin())
                <a
                    href="{{ route('admin.dashboard') }}"
                    @class([
                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => $isAdminArea,
                        'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! $isAdminArea,
                    ])
                    :class="{
                        'lg:justify-center': sidebarCollapsed,
                        'lg:gap-0': sidebarCollapsed,
                        'lg:px-2': sidebarCollapsed,
                    }"
                    :title="sidebarCollapsed ? 'Dashboard' : null"
                    aria-label="Dashboard"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <span x-show="! sidebarCollapsed || ! isDesktop">Dashboard</span>
                </a>
            @endif

        </div>

        <div class="mt-6 border-t border-blue-100 pt-5">
            <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400" x-show="! sidebarCollapsed || ! isDesktop">
                Operasional gudang
            </p>

            <div class="mt-3 space-y-1">
                @foreach ($warehouseLinks as $link)
                    <a
                        href="{{ wms_route($link['route']) }}"
                        @class([
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => request()->routeIs($wmsPrefix.$link['active']),
                            'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! request()->routeIs($wmsPrefix.$link['active']),
                        ])
                        :class="{
                            'lg:justify-center': sidebarCollapsed,
                            'lg:gap-0': sidebarCollapsed,
                            'lg:px-2': sidebarCollapsed,
                        }"
                        :title="sidebarCollapsed ? '{{ $link['label'] }}' : null"
                        aria-label="{{ $link['label'] }}"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                        </svg>
                        <span x-show="! sidebarCollapsed || ! isDesktop">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if (Auth::user()->isAdmin())
            <div class="mt-6 border-t border-blue-100 pt-5">
                <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400" x-show="! sidebarCollapsed || ! isDesktop">
                    Master data
                </p>

                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('admin.users.index') }}"
                        @class([
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => $isAdminUsers,
                            'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! $isAdminUsers,
                        ])
                        :class="{
                            'lg:justify-center': sidebarCollapsed,
                            'lg:gap-0': sidebarCollapsed,
                            'lg:px-2': sidebarCollapsed,
                        }"
                        :title="sidebarCollapsed ? 'Manajemen user' : null"
                        aria-label="Manajemen user"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span x-show="! sidebarCollapsed || ! isDesktop">Manajemen user</span>
                    </a>

                    <a
                        href="{{ route('admin.announcements.index') }}"
                        @class([
                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => $isAdminAnnouncements,
                            'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! $isAdminAnnouncements,
                        ])
                        :class="{
                            'lg:justify-center': sidebarCollapsed,
                            'lg:gap-0': sidebarCollapsed,
                            'lg:px-2': sidebarCollapsed,
                        }"
                        :title="sidebarCollapsed ? 'Pemberitahuan' : null"
                        aria-label="Pemberitahuan"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                        </svg>
                        <span x-show="! sidebarCollapsed || ! isDesktop">Pemberitahuan</span>
                    </a>

                    @foreach ($masterDataLinks as $link)
                        <a
                            href="{{ wms_route($link['route']) }}"
                            @class([
                                'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-inset ring-blue-100' => request()->routeIs($wmsPrefix.$link['active']),
                                'text-slate-500 hover:bg-blue-50/70 hover:text-blue-700' => ! request()->routeIs($wmsPrefix.$link['active']),
                            ])
                            :class="{
                                'lg:justify-center': sidebarCollapsed,
                                'lg:gap-0': sidebarCollapsed,
                                'lg:px-2': sidebarCollapsed,
                            }"
                            :title="sidebarCollapsed ? '{{ $link['label'] }}' : null"
                            aria-label="{{ $link['label'] }}"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                            </svg>
                            <span x-show="! sidebarCollapsed || ! isDesktop">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </nav>

    <!-- User -->
    <div class="relative mt-4 border-t border-blue-100 pt-4">
        <div
            class="flex items-center gap-3 rounded-2xl bg-blue-50/70 p-3 ring-1 ring-inset ring-blue-100"
            :class="{
                'lg:justify-center': sidebarCollapsed,
                'lg:p-2': sidebarCollapsed,
            }"
        >
            @if ($userAvatar)
                <img
                    src="{{ $userAvatar }}"
                    alt="{{ Auth::user()->name }}"
                    class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white"
                >
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-sky-500 text-sm font-semibold text-white">
                    {{ $userInitial }}
                </span>
            @endif
            <div class="min-w-0 flex-1" x-show="! sidebarCollapsed || ! isDesktop">
                <p class="truncate text-sm font-medium text-slate-900">{{ Auth::user()->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-2" :class="{ 'lg:grid-cols-1': sidebarCollapsed }">
            <a
                href="{{ route('profile.edit') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-blue-100 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :title="sidebarCollapsed ? 'Profil' : null"
                aria-label="Profil"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span x-show="! sidebarCollapsed || ! isDesktop">Profil</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-blue-100 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :title="sidebarCollapsed ? 'Keluar' : null"
                    aria-label="Keluar"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span x-show="! sidebarCollapsed || ! isDesktop">Keluar</span>
                </button>
            </form>
        </div>
    </div>
</aside>
