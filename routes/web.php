<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Wms\AuditLogController;
use App\Http\Controllers\Wms\CourierController;
use App\Http\Controllers\Wms\DashboardController as WmsDashboardController;
use App\Http\Controllers\Wms\LabelController;
use App\Http\Controllers\Wms\LocationController;
use App\Http\Controllers\Wms\ProductController;
use App\Http\Controllers\Wms\PurchaseOrderController;
use App\Http\Controllers\Wms\ReportController;
use App\Http\Controllers\Wms\ReturnOrderController;
use App\Http\Controllers\Wms\SalesOrderController;
use App\Http\Controllers\Wms\ScanController;
use App\Http\Controllers\Wms\StockAdjustmentController;
use App\Http\Controllers\Wms\StockController;
use App\Http\Controllers\Wms\StockOpnameController;
use App\Http\Controllers\Wms\StockTransferController;
use App\Http\Controllers\Wms\SupplierController;
use App\Http\Controllers\Wms\WarehouseController;
use App\Http\Middleware\EnsureUserIsNotAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.landing.index');
})->name('home');

Route::view('/design-system', 'design-system.index')->name('design-system');

Route::redirect('/admin/login', '/login');

Route::get('/ess', WmsDashboardController::class)
    ->middleware(['auth', 'verified', EnsureUserIsNotAdmin::class])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('announcements', AnnouncementController::class)->except('show');
    });

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{announcement}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

$wmsRoutes = function (): void {
    /*
     * Administrative routes are registered first so that literal paths such
     * as "products/create" are never swallowed by the show route model binding.
     */
    Route::middleware('admin')->group(function (): void {
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

        Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::post('sales-orders/{salesOrder}/reserve', [SalesOrderController::class, 'reserve'])->name('sales-orders.reserve');
        Route::post('sales-orders/{salesOrder}/release', [SalesOrderController::class, 'release'])->name('sales-orders.release');

        Route::get('opnames/create', [StockOpnameController::class, 'create'])->name('opnames.create');
        Route::post('opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
        Route::post('opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])->name('opnames.complete');

        Route::post('adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');

        Route::post('returns/{returnOrder}/items/{item}/disposition', [ReturnOrderController::class, 'disposition'])->name('returns.disposition');

        Route::get('reports/aging', [ReportController::class, 'aging'])->name('reports.aging');
        Route::get('reports/aging/export', [ReportController::class, 'agingExport'])->name('reports.aging.export');
        Route::get('reports/capacity', [ReportController::class, 'capacity'])->name('reports.capacity');
        Route::get('reports/capacity/export', [ReportController::class, 'capacityExport'])->name('reports.capacity.export');
        Route::get('reports/ledger/export', [ReportController::class, 'ledgerExport'])->name('reports.ledger.export');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::resource('warehouses', WarehouseController::class)->except(['index', 'show']);
        Route::resource('locations', LocationController::class)->except(['index', 'show']);
        Route::resource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::resource('couriers', CourierController::class)->except(['index', 'show']);

        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('couriers', [CourierController::class, 'index'])->name('couriers.index');
    });

    Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('scan', [ScanController::class, 'lookup'])->name('scan.lookup');

    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');

    Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');

    Route::get('opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
    Route::get('opnames/{stockOpname}', [StockOpnameController::class, 'show'])->name('opnames.show');
    Route::post('opnames/{stockOpname}/count', [StockOpnameController::class, 'count'])->name('opnames.count');

    Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveStore'])->name('purchase-orders.receive.store');
    Route::get('purchase-orders/{purchaseOrder}/put-away', [PurchaseOrderController::class, 'putAway'])->name('purchase-orders.put-away');
    Route::post('purchase-orders/{purchaseOrder}/put-away', [PurchaseOrderController::class, 'putAwayStore'])->name('purchase-orders.put-away.store');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/{salesOrder}/picking', [SalesOrderController::class, 'picking'])->name('sales-orders.picking');
    Route::post('sales-orders/{salesOrder}/pick', [SalesOrderController::class, 'pick'])->name('sales-orders.pick');
    Route::get('sales-orders/{salesOrder}/packing', [SalesOrderController::class, 'packing'])->name('sales-orders.packing');
    Route::post('sales-orders/{salesOrder}/pack', [SalesOrderController::class, 'pack'])->name('sales-orders.pack');
    Route::post('sales-orders/{salesOrder}/ship', [SalesOrderController::class, 'ship'])->name('sales-orders.ship');
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');

    Route::get('returns', [ReturnOrderController::class, 'index'])->name('returns.index');
    Route::get('returns/create', [ReturnOrderController::class, 'create'])->name('returns.create');
    Route::post('returns', [ReturnOrderController::class, 'store'])->name('returns.store');
    Route::get('returns/{returnOrder}', [ReturnOrderController::class, 'show'])->name('returns.show');

    Route::get('reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');

    Route::get('labels/products/{product}', [LabelController::class, 'product'])->name('labels.products');
    Route::get('labels/locations/{location}', [LabelController::class, 'location'])->name('labels.locations');
    Route::get('labels/sales-orders/{salesOrder}/packing-list', [LabelController::class, 'packingList'])->name('labels.packing-list');
    Route::get('labels/sales-orders/{salesOrder}/shipping', [LabelController::class, 'shipping'])->name('labels.shipping');
    Route::get('labels/purchase-orders/{purchaseOrder}', [LabelController::class, 'purchaseOrder'])->name('labels.purchase-order');

    Route::resource('products', ProductController::class)->only(['index', 'show']);
    Route::resource('warehouses', WarehouseController::class)->only(['index', 'show']);
    Route::resource('locations', LocationController::class)->only(['index', 'show']);
};

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.wms.')
    ->group($wmsRoutes);

Route::middleware(['auth', 'verified', EnsureUserIsNotAdmin::class])
    ->prefix('ess')
    ->name('ess.wms.')
    ->group($wmsRoutes);

Route::middleware(['auth', 'verified', 'admin'])
    ->get('/admin/wms/{path?}', function (Request $request, ?string $path = null) {
        $target = '/admin';

        if ($path !== null && $path !== '') {
            $target .= '/'.$path;
        }

        $query = $request->getQueryString();

        return redirect($target.($query ? '?'.$query : ''));
    })->where('path', '.*');

Route::middleware(['auth', 'verified', EnsureUserIsNotAdmin::class])
    ->get('/ess/wms/{path?}', function (Request $request, ?string $path = null) {
        $target = '/ess';

        if ($path !== null && $path !== '') {
            $target .= '/'.$path;
        }

        $query = $request->getQueryString();

        return redirect($target.($query ? '?'.$query : ''));
    })->where('path', '.*');

Route::middleware(['auth', 'verified'])->get('/wms/{path?}', function (Request $request, ?string $path = null) {
    $portal = $request->user()->isAdmin() ? 'admin' : 'ess';
    $target = '/'.$portal;

    if ($path !== null && $path !== '') {
        $target .= '/'.$path;
    }

    return redirect($target);
})->where('path', '.*')->name('wms.legacy');

Route::redirect('/dashboard', '/ess');
Route::redirect('/pages/ess', '/ess');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/api.php';
