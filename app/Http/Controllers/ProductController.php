<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formProduct';
        $this->resource = 'products';
    }

    public function search(Request $request)
    {
        $q = $request->input('q');

        $results = Product::where('name', 'like', '%' . $q . '%')
            ->limit(10)
            ->get();

        return response()->json($results);
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

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId];

        return view('pages.apps.products.create', compact('payload'));
    }

    public function store(ProductRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->only('description');

        $data['name'] = $request->product_name;

        $product = Product::create($data);

        return Product::get(['id', 'name']);
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
