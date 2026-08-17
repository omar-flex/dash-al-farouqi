<?php

namespace App\Http\Controllers\WarehouseManagement;

use App\DataTables\WarehouseManagement\WarehousesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManagement\WarehouseRequest;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseItems;
use App\Services\Inventory\InventoryBalanceCalculator;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->formId = 'formPartner';
        $this->resource = 'warehouses';
    }

    public function index(WarehousesDataTable $dataTable)
    {
        if (! auth()->user()->can('list_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
            'title' => 'Warehouses',
            'sub_title' => 'Warehouse',
            'tableId' => 'warehouses-table',
            'formId' => $this->formId,
            'resource' => $this->resource,
        ];

        return $dataTable->render('pages.apps.warehouse-management.warehouse.list', compact('payload'));
    }

    public function report()
    {
        if (! auth()->user()->can('warehouses_report')) {
            abort(403);
        }

        $customers = Customer::with(['Inbounds' => function ($query) {
            $query->whereIntegerInRaw('status_id', [EnterRequestStatus::VALIDATION, EnterRequestStatus::AUTHORIZATION, EnterRequestStatus::APPROVED]);
        }])->get(['id', 'name']);

        $officialTotals = app(InventoryBalanceCalculator::class)->officialTotalsQuery();
        $availableItems = WarehouseItems::query()
            ->leftJoinSub($officialTotals, 'official_totals', function ($join) {
                $join->on('official_totals.warehouse_item_id', '=', 'warehouse_items.id');
            })
            ->whereRaw('COALESCE(warehouse_items.quantity, 0) - COALESCE(official_totals.quantity, 0) > 0')
            ->get(['warehouse_items.product_id', 'warehouse_items.enter_request_id']);

        $products = Product::whereIntegerInRaw('id', $availableItems->pluck('product_id')->filter()->unique()->all())
            ->get(['id', 'name']);

        $enterRequests = EnterRequest::whereIntegerInRaw('id', $availableItems->pluck('enter_request_id')->filter()->unique()->all())
            ->get(['id', 'bound_number']);

        $warehouses = Warehouse::get(['id', 'code']);

        return view('pages.reports.list', compact('customers', 'warehouses', 'products', 'enterRequests'));
    }

    public function reportDisclosure()
    {
        if (! auth()->user()->can('warehouses_report')) {
            abort(403);
        }

        $fromDate = request('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = request('to_date', now()->format('Y-m-d'));

        $outboundTotals = DB::table('outbound_warehouse_items')
            ->join('outbounds', 'outbounds.id', '=', 'outbound_warehouse_items.outbound_id')
            ->whereIntegerInRaw('outbounds.status_id', InventoryBalanceCalculator::OFFICIAL_STATUSES)
            ->where('outbounds.date', '<=', $toDate)
            ->groupBy('outbound_warehouse_items.warehouse_item_id')
            ->select('outbound_warehouse_items.warehouse_item_id')
            ->selectRaw('SUM(COALESCE(outbound_warehouse_items.quantity, 0)) as outbound_quantity')
            ->selectRaw('SUM(COALESCE(outbound_warehouse_items.custom_value, 0)) as outbound_custom_value')
            ->selectRaw('SUM(COALESCE(outbound_warehouse_items.gross_weight, 0)) as outbound_gross_weight')
            ->selectRaw('MAX(outbounds.date) as last_outbound_date');

        $items = WarehouseItems::query()
            ->join('enter_requests', 'enter_requests.id', '=', 'warehouse_items.enter_request_id')
            ->join('customers', 'customers.id', '=', 'enter_requests.customer_id')
            ->join('products', 'products.id', '=', 'warehouse_items.product_id')
            ->leftJoinSub($outboundTotals, 'outbound_totals', function ($join) {
                $join->on('outbound_totals.warehouse_item_id', '=', 'warehouse_items.id');
            })
            ->whereIntegerInRaw('enter_requests.status_id', [
                EnterRequestStatus::VALIDATION,
                EnterRequestStatus::AUTHORIZATION,
                EnterRequestStatus::APPROVED,
            ])
            ->whereBetween('enter_requests.date', [$fromDate, $toDate])
            ->when(request('customer_id'), function ($query, $customerId) {
                $query->where('enter_requests.customer_id', $customerId);
            })
            ->when(request('product_id'), function ($query, $productId) {
                $query->where('warehouse_items.product_id', $productId);
            })
            ->when(request('enter_request_id'), function ($query, $enterRequestId) {
                $query->where('warehouse_items.enter_request_id', $enterRequestId);
            })
            ->when(request('warehouse_id'), function ($query, $warehouseId) {
                $query->whereExists(function ($locationQuery) use ($warehouseId) {
                    $locationQuery->selectRaw('1')
                        ->from('location_lines')
                        ->join('warehouse_locations', 'warehouse_locations.id', '=', 'location_lines.location_id')
                        ->whereColumn('location_lines.id', 'warehouse_items.location_line_id')
                        ->where('warehouse_locations.warehouse_id', $warehouseId);
                });
            })
            ->whereRaw('(COALESCE(warehouse_items.quantity, 0) - COALESCE(outbound_totals.outbound_quantity, 0)) <> 0')
            ->orderBy('enter_requests.date')
            ->orderBy('enter_requests.id')
            ->orderBy('warehouse_items.id')
            ->get([
                'warehouse_items.id',
                'warehouse_items.quantity',
                'warehouse_items.custom_value',
                'warehouse_items.gross_weight',
                'warehouse_items.batch_number',
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.tax_number as customer_tax_number',
                'products.name as product_name',
                'products.barcode as product_barcode',
                'enter_requests.id as enter_request_id',
                'enter_requests.bound_number',
                'enter_requests.date as inbound_date',
                'enter_requests.manifest_bound_number',
                'enter_requests.manifest_type_number',
                'enter_requests.manifest_year',
                'enter_requests.customs_entry_center',
                DB::raw('COALESCE(warehouse_items.quantity, 0) - COALESCE(outbound_totals.outbound_quantity, 0) as remaining_quantity'),
                DB::raw('COALESCE(warehouse_items.custom_value, 0) - COALESCE(outbound_totals.outbound_custom_value, 0) as remaining_custom_value'),
                DB::raw('COALESCE(warehouse_items.gross_weight, 0) - COALESCE(outbound_totals.outbound_gross_weight, 0) as remaining_gross_weight'),
                DB::raw('outbound_totals.last_outbound_date'),
            ]);

        $customerGroups = $items
            ->groupBy('customer_id')
            ->map(function ($customerItems) {
                $customer = $customerItems->first();

                return (object) [
                    'id' => $customer->customer_id,
                    'name' => $customer->customer_name,
                    'tax_number' => $customer->customer_tax_number,
                    'items' => $customerItems,
                    'total_quantity' => $customerItems->sum('quantity'),
                    'total_remaining' => $customerItems->sum('remaining_quantity'),
                    'total_custom_value' => $customerItems->sum('remaining_custom_value'),
                    'total_gross_weight' => $customerItems->sum('remaining_gross_weight'),
                ];
            })
            ->values();

        $warehouse = null;
        if (request('warehouse_id')) {
            $warehouse = Warehouse::firstWhere('id', request('warehouse_id'));
        }

        return view('pages.reports.warehouse-disclosure', compact('customerGroups', 'fromDate', 'toDate', 'warehouse'));
    }

    public function create()
    {
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) ['formId' => $this->formId];

        return view('pages.apps.warehouse-management.warehouse.create', compact('payload'));
    }

    public function store(WarehouseRequest $request)
    {
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $data['name'] = $request->warehouse_name;
        $data['code'] = $request->code;
        $data['is_active'] = $request->is_active ? 1 : 0;

        Warehouse::create($data);

        return response()->json(['message' => 'Added Successfully', 'status' => 200]);
    }

    public function edit(Warehouse $warehouse)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) ['formId' => $this->formId];

        return view('pages.apps.warehouse-management.warehouse.create', compact('payload', 'warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $data['name'] = $request->warehouse_name;
        $data['code'] = $request->code;
        $data['is_active'] = $request->is_active ? 1 : 0;

        $warehouse->update($data);

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Warehouse $warehouse)
    {
        if (! auth()->user()->can('delete_'.$this->resource)) {
            abort(403);
        }

        $warehouse->delete();
    }
}
