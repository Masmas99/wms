<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Support\BarcodeGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController extends Controller
{
    /**
     * Printable product barcode labels.
     */
    public function product(Request $request, Product $product): View
    {
        $copies = min(50, max(1, $request->integer('copies', 1)));
        $code = $product->barcode ?: $product->sku;

        return view(wms_view('labels.product'), [
            'product' => $product,
            'copies' => $copies,
            'code' => $code,
            'barcode' => BarcodeGenerator::svg($code, 50, 1.4, false),
        ]);
    }

    /**
     * Printable label for a single stock lot.
     */
    public function stock(Request $request, InventoryStock $stock): View
    {
        $copies = min(50, max(1, $request->integer('copies', 1)));

        $stock->load(['product', 'location.warehouse']);

        $code = $stock->product->barcode ?: $stock->product->sku;

        return view(wms_view('labels.stock'), [
            'stock' => $stock,
            'copies' => $copies,
            'code' => $code,
            'barcode' => BarcodeGenerator::svg($code, 50, 1.4, false),
        ]);
    }

    /**
     * Printable bin label.
     */
    public function location(Location $location): View
    {
        return view(wms_view('labels.location'), [
            'location' => $location->load('warehouse'),
            'barcode' => BarcodeGenerator::svg($location->full_code, 50, 1.2, false),
        ]);
    }

    /**
     * Packing list / delivery note for an order.
     */
    public function packingList(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['items.product', 'items.allocations.inventoryStock.location', 'shipment.courier', 'creator']);

        return view(wms_view('labels.packing-list'), [
            'order' => $salesOrder,
            'barcode' => BarcodeGenerator::svg($salesOrder->reference_no, 50, 1.3, false),
        ]);
    }

    /**
     * Printable purchase order document.
     */
    public function purchaseOrder(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['items.product', 'supplier', 'warehouse', 'creator']);

        return view(wms_view('labels.purchase-order'), [
            'order' => $purchaseOrder,
            'barcode' => BarcodeGenerator::svg($purchaseOrder->reference_no, 50, 1.3, false),
        ]);
    }

    /**
     * Shipping label with tracking barcode.
     */
    public function shipping(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['shipment.courier', 'items.product']);

        $code = $salesOrder->shipment?->tracking_number ?: $salesOrder->reference_no;

        return view(wms_view('labels.shipping'), [
            'order' => $salesOrder,
            'code' => $code,
            'barcode' => BarcodeGenerator::svg($code, 60, 1.3, false),
        ]);
    }
}
