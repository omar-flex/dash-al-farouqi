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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class WarehouseController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formPartner';
        $this->resource = 'warehouses';
    }

    public function index(WarehousesDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
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
        if (!auth()->user()->can('warehouses_report'))
            abort(403);

        $customers = Customer::with(['Inbounds' => function ($query) {
            $query->whereIntegerInRaw('status_id', [EnterRequestStatus::VALIDATION, EnterRequestStatus::AUTHORIZATION, EnterRequestStatus::APPROVED]);
        }])->get(['id', 'name']);

        $product = WarehouseItems::where('is_status', 1);
        $products = Product::whereIntegerInRaw('id', $product->pluck('product_id')->unique())->get(['id', 'name']);

        $enterRequests = EnterRequest::whereIntegerInRaw('id', $product->pluck('enter_request_id')->unique())->get(['id', 'bound_number']);

        $warehouses = Warehouse::get(['id', 'code']);

        return view('pages.reports.list', compact('customers', 'warehouses', 'products', 'enterRequests'));
    }

    public function reportDisclosure()
    {
        if (!auth()->user()->can('warehouses_report'))
            abort(403);

        $fromDate = request('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = request('to_date', now()->format('Y-m-d'));
        $customer_ids = EnterRequest::whereIntegerInRaw('status_id', [EnterRequestStatus::VALIDATION, EnterRequestStatus::AUTHORIZATION, EnterRequestStatus::APPROVED])
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date')
            ->when(request('customer_id'), function ($query) {
                return $query->where('customer_id', request('customer_id'));
            })
            ->when(request('product_id'), function ($query) {
                $enter_request_ids = WarehouseItems::where('is_status', 1)
                    ->where('product_id', request('product_id'))
                    ->pluck('enter_request_id')->unique();
                return $query->whereIntegerInRaw('id', $enter_request_ids);
            })->when(request('enter_request_id'), function ($query) {
                return $query->where('id', request('enter_request_id'));
            })
            ->pluck('customer_id')
            ->unique();

        $customers = Customer::whereIntegerInRaw('id', $customer_ids)
            ->when(count($customer_ids) > 1, function ($query) use ($customer_ids) {
                return $query->orderByRaw('FIELD(id, ' . $customer_ids->implode(',') . ')');
            })->get();

        $warehouse = null;
        if (request('warehouse_id'))
            $warehouse = Warehouse::firstWhere('id', request('warehouse_id'));


        return view('pages.reports.warehouse-disclosure', compact('customers', 'fromDate', 'toDate', 'warehouse'));
    }


    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId];

        return view('pages.apps.warehouse-management.warehouse.create', compact('payload'));
    }

    public function store(WarehouseRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data['name'] = $request->warehouse_name;
        $data['code'] = $request->code;
        $data['is_active'] = $request->is_active ? 1 : 0;

        Warehouse::create($data);

        return response()->json(['message' => 'Added Successfully', 'status' => 200]);
    }

    public function edit(Warehouse $warehouse)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId];

        return view('pages.apps.warehouse-management.warehouse.create', compact('payload', 'warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data['name'] = $request->warehouse_name;
        $data['code'] = $request->code;
        $data['is_active'] = $request->is_active ? 1 : 0;

        $warehouse->update($data);

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Warehouse $warehouse)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $warehouse->delete();
    }

}
