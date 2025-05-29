<?php

namespace App\Http\Controllers\OperationManagement;


use AllowDynamicProperties;
use App\DataTables\OperationManagement\OutboundsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\CarsRequest;
use App\Http\Requests\OperationManagement\OutboundProductsRequest;
use App\Http\Requests\OperationManagement\OutboundRequest;
use App\Http\Requests\OperationManagement\OutboundValidationsRequest;
use App\Models\ClearanceCompany;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\LocationLine;
use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundFile;
use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\Product;
use App\Models\UnitMeasure;
use App\Models\Warehouse;
use App\Models\WarehouseItems;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


#[AllowDynamicProperties] class OutboundsController extends Controller
{

    public function __construct()
    {
        $this->formId = 'outboundForm';
        $this->resource = 'outbounds';
        $this->tableId = 'outbounds_table';
    }

    public function index(OutboundsDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Outbounds',
            'sub_title' => 'Outbound',
            'tableId' => $this->tableId,
            'formId' => $this->formId,
            'resource' => $this->resource,
            'statuses' => OutboundStatus::get(['id', 'name']),
            'customers' => Customer::get(['id', 'name']),
            'companies' => ClearanceCompany::orderBy('name')->get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.operation-management.outbounds.list', compact('payload'));
    }

    public function show(Outbound $outbound)
    {
        $locations = [];

        $locationLines = LocationLine::with('location', 'location.warehouse')->get();
        foreach ($locationLines as $key => $locationLine) {
            $locations [$key]['id'] = $locationLine->id;
            $locations [$key]['code'] = $locationLine?->location?->warehouse?->code . ' - ' . $locationLine?->location?->code . ' - ' . $locationLine?->code;
        }

        $payload = (object)[
            'title' => 'Outbound',
            'resource' => $this->resource,
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => $locations,
        ];

        $product_ids = WarehouseItems::where('enter_request_id', $outbound->enter_request_id)
            ->when($outbound->status_id == OutboundStatus::WH_RELEASE_PRODUCT, function ($q) {
                return $q->where('is_status', 1);
            })->pluck('product_id')->unique();


        $products = Product::whereIntegerInRaw('products.id', $product_ids)
            ->leftJoin('unit_measures', 'products.unit_measure_id', '=', 'unit_measures.id')
            ->leftJoin('warehouse_items', 'products.id', '=', 'warehouse_items.product_id')
            ->select('products.id', 'products.name', 'products.barcode', 'unit_measures.name as unit_measure_name')
            ->distinct()
            ->get();

        $cars = OutboundCar::where('outbound_id', $outbound->id)->get();

        $warehouseItems = $outbound->OutboundWarehouseItems;

        return view('pages.apps.operation-management.outbounds.view', compact('outbound', 'payload', 'warehouseItems', 'products', 'cars'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'formId' => $this->formId,
            'resource' => $this->resource,
            'bound_numbers' => EnterRequest::get(['id', 'bound_number as name']),
            'countries' => Country::all(['id', 'name']),
            'tableId' => $this->tableId,
        ];

        return view('pages.apps.operation-management.outbounds.create', compact('payload'));
    }

    public function store(OutboundRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['outbound_number'] = $request->manifest_year . '/' . $request->customs_entry_center . '/' . $request->manifest_type_number . '/' . $request->manifest_outbound_number;

        if ($request->button_clicked == 'btn-draft') {
            $data['status_id'] = EnterRequestStatus::DRAFT;
            $message = 'Added Draft Successfully';
        } elseif ($request->button_clicked == 'btn-submit') {
            $data['status_id'] = EnterRequestStatus::CAR_CHECK;
            $message = 'Added Successfully';
        }

        $outbound = Outbound::create(Arr::except($data, 'files'));

        $cpm_result = ceil(($outbound->EnterRequest->cpm / $outbound->EnterRequest->gross_weight) * $outbound->gross_weight);

        $outbound->update(['cpm_result' => $cpm_result]);

        if ($request->has('files')) {
            $this->filesCreate($outbound);
        }

        return response()->json(['message' => $message, 'status' => 200]);
    }

    public function edit(Outbound $outbound)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)[
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => $this->tableId,
            'bound_numbers' => EnterRequest::get(['id', 'bound_number as name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code']),
        ];

        return view('pages.apps.operation-management.outbounds.create', compact('payload', 'outbound'));
    }

    public function update(OutboundRequest $request, Outbound $outbound)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['outbound_number'] = $request->manifest_year . '/' . $request->customs_entry_center . '/' . $request->manifest_type_number . '/' . $request->manifest_outbound_number;

        if ($outbound->status_id == EnterRequestStatus::DRAFT) {
            if ($request->button_clicked == 'btn-draft') {
                $data['status_id'] = EnterRequestStatus::DRAFT;
            } elseif ($request->button_clicked == 'btn-submit') {
                $data['status_id'] = EnterRequestStatus::CAR_CHECK;
            }
        }

        $data = Arr::except($data, 'files');

        $data['clearance_company_representative'] = request('clearance_company_representative', 0);
        $data['scanning_archiving'] = request('scanning_archiving', 0);
        $data['customs_department_representative'] = request('customs_department_representative', 0);

        $outbound->update($data);

        $cpm_result = ceil(($outbound->EnterRequest->cpm / $outbound->EnterRequest->gross_weight) * $outbound->gross_weight);

        $outbound->update(['cpm_result' => $cpm_result]);

        if ($request->has('files')) {
            $this->filesCreate($outbound);
        }

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Outbound $outbound)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $files = OutboundFile::where('outbound_id', $outbound->id)->get();
        foreach ($files as $file) {
            if (Storage::path($file->path)) {
                Storage::delete($file->path);
            }
            $file->delete();
        }
        $outbound->delete();
    }

    public function filesCreate($outbound)
    {
        if (request('files')) {
            foreach (request('files') as $file) {
                $extension = $file->getClientOriginalExtension();
                $cleanName = preg_replace('/[^A-Za-z0-9\.\-_]/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $uniqueName = $cleanName . '-' . uniqid() . '.' . $extension;
                $path = Storage::putFileAs('Outbounds', $file, $uniqueName);
                OutboundFile::create([
                    'filename' => Str::replace('/', '-', $outbound->outbound_number),
                    'path' => $path,
                    'extension' => $extension,
                    'outbound_id' => $outbound->id,
                    'user_id' => Auth::id(),
                ]);
            }
        }
    }

    public function fileDelete($file_id)
    {
        $file = OutboundFile::where('id', $file_id)->first();
        if (Storage::path($file->path)) {
            Storage::delete($file->path);
        }
        $file->delete();
    }

    public function pdf($id)
    {
        $outbound = Outbound::firstWhere('id', $id);
        return view('pages.pdf_model.outbounds', compact('outbound'));
    }

    public function outputProducts($id)
    {
        $outbound = Outbound::firstWhere('id', $id);
        return view('pages.apps.operation-management.outbounds.output-products', compact('outbound'));
    }

    public function pdfOutputProducts($id, $car_id)
    {
        $outbound = Outbound::firstWhere('id', $id);
        $items = OutboundWarehouseItems::where([
            'outbound_id' => $outbound->id,
            'outbound_car_id' => $car_id,
        ])->get();
        return view('pages.pdf_model.output_products', compact('outbound', 'items', 'car_id'));
    }

    public function pdfForm($id)
    {
        $outbound = Outbound::firstWhere('id', $id);
        return view('pages.pdf_model.outbounds_receipt_delivery_commitment_form', compact('outbound'));
    }

    public function cars($outbound_id, CarsRequest $request)
    {

        $outbound = Outbound::firstWhere('id', $outbound_id);


        foreach ($request->numbers as $index => $number) {
            $car_id = Arr::get($request->car_ids, $index);
            if ($car_id) {
                OutboundCar::where('id', $car_id)->update([
                    'number' => $number,
                    'seal_number' => Arr::get($request->seal_numbers, $index),
                ]);
            } else {
                OutboundCar::create([
                    'number' => $number,
                    'seal_number' => Arr::get($request->seal_numbers, $index),
                    'outbound_id' => $outbound_id,
                ]);
            }
        }

        $outbound->update(['status_id' => OutboundStatus::WH_RELEASE_PRODUCT]);

        $cares_list = view('pages.apps.operation-management.outbounds.sections._cares-list', compact('outbound'))->render();

        return response()->json(['message' => 'Added Cars Successfully', 'html' => $cares_list, 'status' => 200]);
    }


    public function product($outbound_id, $product_id)
    {
        $outbound = Outbound::firstWhere('id', $outbound_id);
        if ($outbound) {
            $item = WarehouseItems::where('enter_request_id', $outbound->enter_request_id)
                ->with('product', 'product.UnitMeasure')
                ->where('product_id', $product_id)
                ->first(['id',
                    'batch_number',
                    'product_id',
                    'location_line_id',
                    'remaining_quantity AS quantity',
                    'level', 'pallet']);
            $item->location = $item->locationLine->location->warehouse->code . '-' . $item->locationLine->location->code . '-' . $item->locationLine->code;
            if ($item->level)
                $item->location = $item->location . '-' . $item->level;
            if ($item->pallet)
                $item->location = $item->location . '-' . $item->pallet;
            return $item;
        }
        return null;
    }

    public function products($outbound_id, OutboundProductsRequest $request)
    {
        $outbound = Outbound::firstWhere('id', $outbound_id);
        $check_quantity = false;

        $delete_items_ids = [];
        $items_ids = $outbound->OutboundWarehouseItems->pluck('id')->toArray();
        if (count($items_ids) > 0)
            $delete_items_ids = array_diff($items_ids, $request->items_id);

        if ($request->button_clicked == 'btn-submit') {
            $outbound->update(['status_id' => OutboundStatus::VALIDATION]);
            $check_quantity = true;
        }

        foreach ($request->products_id as $index => $product) {
            $item = WarehouseItems::where('product_id', $product)
                ->where('enter_request_id', $outbound->enter_request_id)
                ->first();

            if ($item) {
                $item_custom_value_rate = $item->custom_value / $item->quantity;
                $item_gross_weight_rate = $item->gross_weight / $item->quantity;
                $item_net_weight_rate = $item->net_weight / $item->quantity;
                $item_cpm_rate = $item->cpm / $item->quantity;
                $item_cpm_capacity_rate = $item->cpm_capacity / $item->quantity;
                $quantity = Arr::get($request->quantities, $index);
                $warehouse_item_id = trim(Arr::get($request->warehouse_item_ids, $index));
                $warehouse_item = WarehouseItems::where('id', $warehouse_item_id)->first();
                if ($check_quantity) {
                    if ($warehouse_item->quantity == $quantity)
                        $warehouse_item->update(['is_status' => 0]);
                }
                $warehouse_item->update([
                    'remaining_quantity' => $warehouse_item->quantity - $quantity
                ]);
                $item = [
                    'quantity' => $quantity,
                    'location' => trim(Arr::get($request->locations, $index)),
                    'warehouse_item_id' => $warehouse_item_id,
                    'custom_value' => $item_custom_value_rate * $quantity,
                    'gross_weight' => $item_gross_weight_rate * $quantity,
                    'net_weight' => $item_net_weight_rate * $quantity,
                    'cpm' => $item_cpm_rate * $quantity,
                    'cpm_capacity' => $item_cpm_capacity_rate * $quantity,
                    'is_status' => $check_quantity,
                    'outbound_id' => $outbound->id,
                    'outbound_car_id' => trim(Arr::get($request->cars_id, $index))
                ];
            }
            $items_id = Arr::get($request->all(), 'items_id.' . $index);

            if ($items_id) {
                OutboundWarehouseItems::where('id', $items_id)->update($item);
            } else {
                OutboundWarehouseItems::create($item);
            }
            if (count($delete_items_ids) > 0) {
                OutboundWarehouseItems::whereIn('id', $delete_items_ids)->delete();
            }
        }

        return response()->json(['message' => 'Added or Update Release Product item Successfully', 'status' => 200]);
    }

    public function validations($outbound_id, OutboundValidationsRequest $request)
    {
        $outbound = Outbound::firstWhere('id', $outbound_id);

        foreach ($request->custom_values as $index => $custom_value) {
            $gross_weight = Arr::get($request->gross_weights, $index);
            $item = [
                'custom_value' => $custom_value,
                'gross_weight' => $gross_weight,
                'net_weight' => trim(Arr::get($request->net_weights, $index)),
                'cpm' => $gross_weight * $outbound->enterRequest->cpm_weight_ration,
                'cpm_capacity' => $gross_weight * $outbound->enterRequest->cpm_weight_ration_wh
            ];
            $items_id = Arr::get($request->all(), 'items_id.' . $index);
            OutboundWarehouseItems::where('id', $items_id)->update($item);
        }

        if ($request->button_clicked == 'btn-submit') {
            $outbound->update(['status_id' => EnterRequestStatus::AUTHORIZATION]);
        }

        return response()->json(['message' => 'Added or Update Validations item Successfully', 'status' => 200]);
    }


}
