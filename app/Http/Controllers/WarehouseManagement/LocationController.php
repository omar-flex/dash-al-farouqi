<?php

namespace App\Http\Controllers\WarehouseManagement;

use AllowDynamicProperties;
use App\DataTables\WarehouseManagement\LocationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManagement\LineRequest;
use App\Http\Requests\WarehouseManagement\LocationRequest;
use App\Http\Requests\WarehouseManagement\WarehouseRequest;
use App\Models\LocationLine;
use App\Models\StorageCategory;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;


#[AllowDynamicProperties] class LocationController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formPartner';
        $this->resource = 'locations';
        $this->warehouses = Warehouse::all();
    }

    public function index(LocationsDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Locations',
            'sub_title' => 'Location',
            'tableId' => 'locations-table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'warehouses' => $this->warehouses,
        ];

        return $dataTable->render('pages.apps.warehouse-management.locations.list', compact('payload'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId, 'warehouses' => $this->warehouses,];

        return view('pages.apps.warehouse-management.locations.create', compact('payload'));
    }

    public function store(LocationRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data['name'] = $request->location_name;
        $data['code'] = $request->code;
        $data['warehouse_id'] = $request->warehouse_id;

        WarehouseLocation::create($data);

        return response()->json(['message' => 'Added Successfully', 'status' => 200]);
    }

    public function edit(WarehouseLocation $location)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId, 'warehouses' => $this->warehouses];

        return view('pages.apps.warehouse-management.locations.create', compact('payload', 'location'));
    }

    public function update(LocationRequest $request, WarehouseLocation $location)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data['name'] = $request->location_name;
        $data['code'] = $request->code;
        $data['is_active'] = $request->is_active ? 1 : 0;

        $location->update($data);

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Warehouse $location)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $location->delete();
    }

    public function locationsLine($location_id)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $categories = StorageCategory::all();
        $lines = LocationLine::where('location_id', $location_id)->get();
        $location = WarehouseLocation::where('id', $location_id)->first();
        $payload = (object)['formId' => $this->formId, 'categories' => $categories];

        return view('pages.apps.warehouse-management.locations.lines', compact('payload', 'location_id', 'lines', 'location'));
    }

    public function locationsLineUpdate($location_id, LineRequest $request)
    {
        $delete_line_ids = [];
        $line_ids = LocationLine::where('location_id', $location_id)->pluck('id')->toArray();
        if (count($line_ids) > 0)
            $delete_line_ids = array_diff($line_ids, $request->lines_id);

        foreach (Arr::get($request->validated(), 'name_lines') as $index => $nameLine) {
            $location = [
                'name' => $nameLine,
                'code' => Arr::get($request->validated(), 'codes.' . $index),
                'category_id' => Arr::get($request->validated(), 'categories.' . $index),
                'capacity' => Arr::get($request->validated(), 'capacities.' . $index),
                'location_id' => $location_id,
            ];
            $line_id = Arr::get($request->all(), 'lines_id.' . $index);
            if ($line_id) {
                LocationLine::where('id', $line_id)->update($location);
            } else {
                LocationLine::create($location);
            }

        }

        if (count($delete_line_ids) > 0) {
            LocationLine::whereIn('id', $delete_line_ids)->delete();
        }

        return response()->json(['message' => 'Location Lines Update Successfully', 'status' => 200]);
    }

}
