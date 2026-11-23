<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    /**
     * Display every stock lot with its reservation state.
     */
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $stocks = $this->filteredQuery($filters)
            ->orderBy('expired_date')
            ->orderBy('product_id')
            ->paginate(20)
            ->withQueryString();

        return view(wms_view('stocks.index'), [
            'stocks' => $stocks,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'search' => $filters['search'],
            'warehouseId' => $filters['warehouse'],
            'filter' => $filters['filter'],
            'totalOnHand' => (float) InventoryStock::query()->sum('qty_on_hand'),
            'totalReserved' => (float) InventoryStock::query()->sum('qty_reserved'),
        ]);
    }

    /**
     * Read the stock list filters from the request.
     *
     * @return array{search: string, warehouse: string, filter: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'warehouse' => $request->string('warehouse')->trim()->toString(),
            'filter' => $request->string('filter')->trim()->toString(),
        ];
    }

    /**
     * Build the stock query for the given filters.
     *
     * @param  array{search: string, warehouse: string, filter: string}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $search = $filters['search'];

        return InventoryStock::query()
            ->with(['product', 'location.warehouse'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('product', fn (Builder $product) => $product
                    ->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%"));
            })
            ->when($filters['warehouse'] !== '', fn (Builder $query) => $query->whereHas('location', fn (Builder $location) => $location->where('warehouse_id', $filters['warehouse'])))
            ->when($filters['filter'] === 'available', fn (Builder $query) => $query->whereRaw('qty_on_hand - qty_reserved > 0'))
            ->when($filters['filter'] === 'empty', fn (Builder $query) => $query->where('qty_on_hand', '<=', 0))
            ->when($filters['filter'] === 'expiring', fn (Builder $query) => $query
                ->whereNotNull('expired_date')
                ->whereDate('expired_date', '>=', now()->toDateString())
                ->whereDate('expired_date', '<=', now()->addDays(30)->toDateString()))
            ->when($filters['filter'] === 'expired', fn (Builder $query) => $query
                ->whereNotNull('expired_date')
                ->whereDate('expired_date', '<', now()->toDateString()));
    }
}
