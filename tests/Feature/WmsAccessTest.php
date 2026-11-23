<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\InventoryStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnOrder;
use App\Models\SalesOrder;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class WmsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/ess')->assertRedirect(route('login'));
        $this->get('/admin/stocks')->assertRedirect(route('login'));
        $this->get('/ess/stocks')->assertRedirect(route('login'));
        $this->get('/admin/wms/stocks')->assertRedirect(route('login'));
        $this->get('/ess/wms/stocks')->assertRedirect(route('login'));
    }

    public function test_admin_and_user_wms_portals_are_separated_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)->get('/admin/wms')->assertForbidden();
        $this->actingAs($staff)->get('/admin/stocks')->assertForbidden();

        $this->actingAs($admin)
            ->get('/ess')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get('/ess/wms')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertViewIs('pages.admin.dashboard');

        $this->actingAs($admin)
            ->get('/admin/wms')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($staff)
            ->get('/ess')
            ->assertOk()
            ->assertViewIs('pages.ess.dashboard');

        $this->actingAs($staff)
            ->get('/ess/wms')
            ->assertRedirect(route('dashboard'));
    }

    public function test_legacy_wms_urls_redirect_to_the_users_portal(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)
            ->get('/wms')
            ->assertRedirect('/admin');

        $this->actingAs($admin)
            ->get('/wms/stocks')
            ->assertRedirect('/admin/stocks');

        $this->actingAs($admin)
            ->get('/admin/wms/purchase-orders')
            ->assertRedirect('/admin/purchase-orders');

        $this->actingAs($staff)
            ->get('/wms')
            ->assertRedirect('/ess');

        $this->actingAs($staff)
            ->get('/ess/wms/purchase-orders')
            ->assertRedirect('/ess/purchase-orders');
    }

    public function test_regular_users_only_get_operational_access(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($staff)->get('/ess')->assertOk()->assertViewIs('pages.ess.dashboard');
        $this->actingAs($staff)->get('/ess/wms')->assertRedirect(route('dashboard'));
        $this->actingAs($staff)->get('/ess/stocks')->assertOk();
        $this->actingAs($staff)->get('/ess/scan')->assertOk();
        $this->actingAs($staff)->get('/ess/sales-orders')->assertOk();
        $this->actingAs($staff)->get('/ess/purchase-orders')->assertOk();
        $this->actingAs($staff)->get('/ess/opnames')->assertOk();
        $this->actingAs($staff)->get('/ess/returns')->assertOk();
        $this->actingAs($staff)->get('/ess/transfers')->assertOk();
        $this->actingAs($staff)->get('/ess/reports/ledger')->assertOk();
        $this->actingAs($staff)->get('/ess/adjustments')->assertOk();
        $this->actingAs($staff)->get('/ess/adjustments/create')->assertOk();
        $this->actingAs($staff)->get('/ess/returns/create')->assertOk();

        $this->actingAs($staff)->get(wms_route('products.show', $product))->assertOk();

        $this->actingAs($staff)->get('/ess/products/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/warehouses/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/suppliers')->assertForbidden();
        $this->actingAs($staff)->get('/ess/suppliers/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/couriers')->assertForbidden();
        $this->actingAs($staff)->get('/ess/couriers/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/reports/aging')->assertForbidden();
        $this->actingAs($staff)->get('/ess/reports/capacity')->assertForbidden();
        $this->actingAs($staff)->get('/ess/audit-logs')->assertForbidden();
        $this->actingAs($staff)->get('/ess/purchase-orders/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/sales-orders/create')->assertForbidden();
        $this->actingAs($staff)->get('/ess/opnames/create')->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('purchase-orders.store'), [])
            ->assertForbidden();
    }

    public function test_regular_users_do_not_see_admin_only_actions(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get('/ess/purchase-orders')
            ->assertOk()
            ->assertDontSee('Purchase order baru');

        $this->actingAs($staff)
            ->get('/ess/sales-orders')
            ->assertOk()
            ->assertDontSee('Sales order baru');
    }

    public function test_regular_users_cannot_see_or_trigger_admin_only_actions(): void
    {
        [$admin, $warehouse, $bin, $product, $supplier] = $this->fixture();
        $this->createDocuments($admin, $warehouse, $bin, $product, $supplier);

        $staff = User::factory()->create();

        $this->actingAs($staff)->post(wms_route('adjustments.store'), [
            'product_id' => $product->id,
            'location_id' => $bin->id,
            'quantity_difference' => 1,
            'reason' => 'Koreksi stok',
        ])->assertRedirect();

        $purchaseOrder = PurchaseOrder::query()->firstOrFail();
        $salesOrder = SalesOrder::query()->firstOrFail();
        $opname = StockOpname::query()->firstOrFail();
        $return = ReturnOrder::query()->firstOrFail();
        $returnItem = $return->items()->firstOrFail();
        $adjustment = StockAdjustment::query()->firstOrFail();

        $this->actingAs($staff)
            ->get(wms_route('purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertDontSee(wms_route('purchase-orders.cancel', $purchaseOrder), false);

        $this->actingAs($staff)
            ->get(wms_route('sales-orders.show', $salesOrder))
            ->assertOk()
            ->assertDontSee(wms_route('sales-orders.reserve', $salesOrder), false)
            ->assertDontSee(wms_route('sales-orders.release', $salesOrder), false);

        $this->actingAs($staff)
            ->get(wms_route('opnames.show', $opname))
            ->assertOk()
            ->assertDontSee(wms_route('opnames.complete', $opname), false);

        $this->actingAs($staff)
            ->get(wms_route('returns.show', $return))
            ->assertOk()
            ->assertDontSee(wms_route('returns.disposition', [$return, $returnItem]), false);

        $this->actingAs($staff)
            ->get(wms_route('adjustments.index'))
            ->assertOk()
            ->assertDontSee(wms_route('adjustments.approve', $adjustment), false)
            ->assertDontSee(wms_route('adjustments.reject', $adjustment), false);

        $this->actingAs($staff)
            ->post(wms_route('purchase-orders.cancel', $purchaseOrder))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('sales-orders.reserve', $salesOrder))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('sales-orders.release', $salesOrder))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('opnames.complete', $opname))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('returns.disposition', [$return, $returnItem]), ['disposition' => 'scrap'])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('adjustments.approve', $adjustment))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(wms_route('adjustments.reject', $adjustment), ['decision_note' => 'Tidak sesuai'])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(wms_route('reports.ledger.export', ['product' => $product->id]))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(wms_route('reports.aging.export'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(wms_route('reports.capacity.export'))
            ->assertForbidden();
    }

    public function test_regular_users_do_not_see_asset_values_on_the_dashboard(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get('/ess')
            ->assertOk()
            ->assertViewIs('pages.ess.dashboard')
            ->assertDontSee('Nilai aset stok')
            ->assertDontSee('Menunggu approval');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertViewIs('pages.admin.dashboard')
            ->assertSee('Nilai aset stok')
            ->assertSee('Menunggu approval');
    }

    public function test_admin_can_open_every_wms_screen(): void
    {
        [$admin, $warehouse, $bin, $product, $supplier, $courier] = $this->fixture();

        $this->actingAs($admin);
        $this->createDocuments($admin, $warehouse, $bin, $product, $supplier);

        $this->get('/admin')
            ->assertOk()
            ->assertViewIs('pages.admin.dashboard');

        $this->get('/admin/wms')
            ->assertRedirect(route('admin.dashboard'));

        $routes = [
            '/admin/stocks',
            '/admin/products',
            '/admin/products/create',
            '/admin/warehouses',
            '/admin/warehouses/create',
            '/admin/locations',
            '/admin/locations/create',
            '/admin/suppliers',
            '/admin/couriers',
            '/admin/purchase-orders',
            '/admin/sales-orders',
            '/admin/transfers',
            '/admin/transfers/create',
            '/admin/opnames',
            '/admin/opnames/create',
            '/admin/adjustments',
            '/admin/adjustments/create',
            '/admin/returns',
            '/admin/returns/create',
            '/admin/reports/ledger',
            '/admin/reports/aging',
            '/admin/reports/capacity',
            '/admin/audit-logs',
            '/admin/scan',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }

        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('wms-delete', false);

        $purchaseOrder = PurchaseOrder::query()->firstOrFail();
        $salesOrder = SalesOrder::query()->firstOrFail();
        $opname = StockOpname::query()->firstOrFail();
        $return = ReturnOrder::query()->firstOrFail();

        $this->get(wms_route('products.show', $product))->assertOk();
        $this->get(wms_route('warehouses.show', $warehouse))->assertOk();
        $this->get(wms_route('locations.show', $bin))->assertOk();
        $this->get(wms_route('purchase-orders.show', $purchaseOrder))->assertOk();
        $this->get(wms_route('purchase-orders.receive', $purchaseOrder))->assertOk();
        $this->get(wms_route('sales-orders.show', $salesOrder))->assertOk();
        $this->get(wms_route('opnames.show', $opname))->assertOk();
        $this->get(wms_route('returns.show', $return))->assertOk();
    }

    public function test_picking_and_packing_screens_render_for_reserved_orders(): void
    {
        [$admin, $warehouse, $bin, $product] = $this->fixture();

        $order = SalesOrder::factory()->create([
            'created_by' => $admin->id,
            'status' => SalesOrder::STATUS_DRAFT,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'qty_ordered' => 2,
            'qty_allocated' => 0,
            'qty_picked' => 0,
        ]);

        InventoryStock::factory()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'qty_on_hand' => 10,
            'qty_reserved' => 0,
        ]);

        $this->actingAs($admin)->post(wms_route('sales-orders.reserve', $order));

        $this->actingAs($admin)
            ->get(wms_route('sales-orders.picking', $order))
            ->assertOk()
            ->assertSee('Picking');

        $this->actingAs($admin)
            ->get(wms_route('sales-orders.packing', $order))
            ->assertRedirect();

        $allocation = $order->items()->firstOrFail()->allocations()->firstOrFail();

        $this->actingAs($admin)->post(wms_route('sales-orders.pick', $order), [
            'allocation' => $allocation->id,
        ]);

        $this->actingAs($admin)
            ->get(wms_route('sales-orders.packing', $order))
            ->assertOk()
            ->assertSee('Packing');
    }

    public function test_printable_labels_render_inline_barcodes(): void
    {
        [$admin, $warehouse, $bin, $product, $supplier] = $this->fixture();

        $this->actingAs($admin);
        $this->createDocuments($admin, $warehouse, $bin, $product, $supplier);

        $purchaseOrder = PurchaseOrder::query()->firstOrFail();
        $salesOrder = SalesOrder::query()->firstOrFail();

        $this->get(wms_route('labels.products', ['product' => $product, 'copies' => 2]))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($product->sku);

        $this->get(wms_route('labels.locations', $bin))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($bin->full_code);

        $this->get(wms_route('labels.packing-list', $salesOrder))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($salesOrder->reference_no);

        $this->get(wms_route('labels.shipping', $salesOrder))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($salesOrder->reference_no);

        $this->get(wms_route('labels.purchase-order', $purchaseOrder))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($purchaseOrder->reference_no);
    }

    public function test_stock_lot_label_prints_product_bin_and_batch_details(): void
    {
        [$admin, , $bin, $product] = $this->fixture();

        $stock = InventoryStock::factory()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-TEST',
            'qty_on_hand' => 12,
            'qty_reserved' => 2,
        ]);

        $this->actingAs($admin)
            ->get(wms_route('stocks.index'))
            ->assertOk()
            ->assertSee(wms_route('labels.stocks', $stock), false);

        $this->actingAs($admin)
            ->get(wms_route('labels.stocks', $stock))
            ->assertOk()
            ->assertViewIs('pages.admin.labels.stock')
            ->assertSee('<svg', false)
            ->assertSee($product->sku)
            ->assertSee($bin->full_code)
            ->assertSee('BATCH-TEST');

        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(wms_route('labels.stocks', $stock))
            ->assertOk()
            ->assertViewIs('pages.ess.labels.stock')
            ->assertSee($product->sku)
            ->assertSee($bin->full_code);
    }

    public function test_scan_lookup_resolves_products_bins_and_orders(): void
    {
        [$admin, $warehouse, $bin, $product] = $this->fixture();

        $this->actingAs($admin)
            ->post(wms_route('scan.lookup'), ['code' => $product->sku])
            ->assertRedirect(wms_route('products.show', $product))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(wms_route('scan.index'))
            ->assertOk()
            ->assertSee($product->sku);

        $this->actingAs($admin)
            ->post(wms_route('scan.lookup'), ['code' => $bin->full_code])
            ->assertRedirect(wms_route('locations.show', $bin))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->from(wms_route('scan.index'))
            ->post(wms_route('scan.lookup'), ['code' => 'UNKNOWN-CODE'])
            ->assertRedirect(wms_route('scan.index'))
            ->assertSessionHas('error');
    }

    /**
     * @return array{0: User, 1: Warehouse, 2: Location, 3: Product, 4: Supplier, 5: Courier}
     */
    private function fixture(): array
    {
        $admin = User::factory()->admin()->create();
        $warehouse = Warehouse::factory()->create(['code' => 'WH-ACC']);
        $staging = Location::factory()->staging()->create([
            'warehouse_id' => $warehouse->id,
            'full_code' => 'WH-ACC-STG-STG-STG-IN',
        ]);
        $bin = Location::factory()->create([
            'warehouse_id' => $warehouse->id,
            'full_code' => 'WH-ACC-A-01-01-01',
        ]);
        $product = Product::factory()->create(['sku' => 'ACC-001']);
        $supplier = Supplier::factory()->create();
        $courier = Courier::factory()->create();

        InventoryStock::factory()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'qty_on_hand' => 12,
            'qty_reserved' => 0,
        ]);

        return [$admin, $warehouse, $bin, $product, $supplier, $courier];
    }

    private function createDocuments(
        User $admin,
        Warehouse $warehouse,
        Location $bin,
        Product $product,
        Supplier $supplier,
    ): void {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $admin->id,
            'status' => PurchaseOrder::STATUS_SCHEDULED,
        ]);

        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'qty_expected' => 5,
            'qty_received' => 0,
            'qty_rejected' => 0,
        ]);

        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $admin->id,
            'status' => SalesOrder::STATUS_DRAFT,
        ]);

        $salesOrder->items()->create([
            'product_id' => $product->id,
            'qty_ordered' => 2,
            'qty_allocated' => 0,
            'qty_picked' => 0,
        ]);

        $this->actingAs($admin)->post(wms_route('opnames.store'), [
            'warehouse_id' => $warehouse->id,
            'scheduled_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)->post(wms_route('returns.store'), [
            'customer_name' => 'PT Pelanggan',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'condition' => 'good',
                    'batch_number' => 'ACC-RMA-1',
                ],
            ],
        ]);
    }

    public function test_admins_can_download_the_stock_card_as_an_excel_spreadsheet(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
            'name' => 'Produk Uji',
            'uom' => 'pcs',
        ]);

        $response = $this->actingAs($admin)->get(wms_route('reports.ledger.export', [
            'product' => $product->id,
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertDownload('kartu-stok-SKU-001-20260101-20260131.xls');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Kartu Stok', $content);
        $this->assertStringContainsString('Produk Uji', $content);
        $this->assertStringContainsString('SKU-001', $content);
    }

    public function test_adjustment_actions_use_valid_alpine_handlers_and_show_a_success_popup(): void
    {
        [$admin, , $bin, $product] = $this->fixture();

        $stock = InventoryStock::query()
            ->where('location_id', $bin->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $adjustment = StockAdjustment::create([
            'reference_no' => 'ADJ-TEST-001',
            'product_id' => $product->id,
            'location_id' => $bin->id,
            'batch_number' => $stock->batch_number,
            'quantity_difference' => 1,
            'reason' => 'Koreksi hasil hitung fisik',
            'status' => StockAdjustment::STATUS_PENDING,
            'requested_by' => $admin->id,
        ]);

        $rejectedAdjustment = StockAdjustment::create([
            'reference_no' => 'ADJ-TEST-002',
            'product_id' => $product->id,
            'location_id' => $bin->id,
            'batch_number' => $stock->batch_number,
            'quantity_difference' => -1,
            'reason' => 'Koreksi tidak sesuai',
            'status' => StockAdjustment::STATUS_PENDING,
            'requested_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(wms_route('adjustments.index'))
            ->assertOk()
            ->assertSee('x-on:click', false)
            ->assertSee(Js::from(wms_route('adjustments.approve', $adjustment)), false)
            ->assertSee(Js::from(wms_route('adjustments.reject', $adjustment)), false)
            ->assertDontSee('@js(', false);

        $this->actingAs($admin)
            ->post(wms_route('adjustments.approve', $adjustment))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(wms_route('adjustments.index'))
            ->assertSee('Berhasil')
            ->assertSee('disetujui dan stok diperbarui');

        $this->actingAs($admin)
            ->post(wms_route('adjustments.reject', $rejectedAdjustment), [
                'decision_note' => 'Tidak sesuai hasil hitung',
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(wms_route('adjustments.index'))
            ->assertSee('Berhasil')
            ->assertSee('ditolak tanpa mengubah stok');
    }
}
