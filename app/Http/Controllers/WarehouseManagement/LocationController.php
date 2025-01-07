<?php

namespace App\Http\Controllers\WarehouseManagement;


use App\DataTables\WarehouseManagement\WarehousesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManagement\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class LocationController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formPartner';
        $this->resource = 'locations';
    }

    public function index(WarehousesDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Warehouses',
            'sub_title' => 'Warehouse',
            'tableId' => 'locations-table',
            'formId' => $this->formId,
            'resource' => $this->resource,
        ];

        return $dataTable->render('pages.apps.warehouse-management.warehouse.list', compact('payload'));
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
