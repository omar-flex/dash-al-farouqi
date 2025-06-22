<?php

namespace App\Http\Controllers;

use App\DataTables\ProductsDataTable;
use App\Http\Requests\ProductRequest;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\Product;
use App\Models\UnitMeasure;
use App\Models\WarehouseItems;
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
            ->when(request('enter_request_id'), function ($query) {
                $enterRequest = EnterRequest::firstWhere(['id' => request('enter_request_id')]);
                $enter_request_ids = EnterRequest::where('customer_id', $enterRequest->Customer->id)->pluck('id');
                $product_ids = WarehouseItems::whereIntegerInRaw('enter_request_id', $enter_request_ids)
                    ->pluck('product_id');
                $query->whereIntegerInRaw('id', $product_ids);
            })
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function index(ProductsDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Products',
            'sub_title' => 'Product',
            'tableId' => 'products-table',
            'formId' => $this->formId,
            'resource' => $this->resource,
        ];

        return $dataTable->render('pages.apps.products.list', compact('payload'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'formId' => $this->formId,
            'unit_measures' => UnitMeasure::get(['id', 'name'])
        ];

        return view('pages.apps.products.create', compact('payload'));
    }

    public function store(ProductRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->only('description', 'unit_measure_id', 'barcode');

        $data['name'] = $request->product_name;

        Product::create($data);

        return response()->json(['message' => 'Created Successfully', 'status' => 200]);
    }

    public function edit(Product $product)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)[
            'formId' => $this->formId,
            'unit_measures' => UnitMeasure::get(['id', 'name'])
        ];

        return view('pages.apps.products.create', compact('payload', 'product'));

    }

    public function update(ProductRequest $request, Product $product)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data = $request->only('description', 'unit_measure_id', 'barcode');

        $data['name'] = $request->product_name;

        $product->update($data);

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Product $product)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        if ($product->Items->count() > 0)
            return response()->json([
                'exception' => 'Cannot Delete This Product'
            ], 500);
        else
            $product->delete();
    }

}
