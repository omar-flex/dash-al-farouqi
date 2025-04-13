<?php

namespace App\Http\Controllers\OperationManagement;


use AllowDynamicProperties;
use App\DataTables\OperationManagement\EnterRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\CarsRequest;
use App\Http\Requests\OperationManagement\EnterCreateRequest;
use App\Http\Requests\OperationManagement\ProductsRequest;
use App\Http\Requests\OperationManagement\ValidationsRequest;
use App\Models\ClearanceCompany;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestCar;
use App\Models\EnterRequestFile;
use App\Models\EnterRequestStatus;
use App\Models\LocationLine;
use App\Models\Product;
use App\Models\UnitMeasure;
use App\Models\Warehouse;
use App\Models\WarehouseItems;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


#[AllowDynamicProperties] class EnterRequestController extends Controller
{

    public function __construct()
    {
        $this->formId = 'enterRequest';
        $this->resource = 'enter_requests';
    }

    public function cpmCalculate($grossWeight)
    {
        return ceil($grossWeight / 333);
    }

    public function index(EnterRequestsDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Inbounds',
            'sub_title' => 'Inbound',
            'tableId' => 'enter_requests_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'statuses' => EnterRequestStatus::get(['id', 'name']),
            'customers' => Customer::get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.operation-management.enter-requests.list', compact('payload'));
    }

    public function show(EnterRequest $enterRequest)
    {
        $payload = (object)[
            'title' => 'Inbound',
            'resource' => $this->resource,
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => (object)$this->getLocations(),
        ];

        $warehouseItems = $enterRequest->WarehouseItems;

        return view('pages.apps.operation-management.enter-requests.view', compact('enterRequest', 'warehouseItems', 'payload'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Inbound Create',
            'formId' => $this->formId,
            'tableId' => 'enter_requests_table',
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
            'companies' => ClearanceCompany::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code'])
        ];

        return view('pages.apps.operation-management.enter-requests.create', compact('payload'));
    }

    public function store(EnterCreateRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year . '/' . $request->customs_entry_center . '/' . $request->manifest_type_number . '/' . $request->manifest_bound_number;

        if ($request->button_clicked == 'btn-draft') {
            $data['status_id'] = EnterRequestStatus::DRAFT;
            $message = 'Added Draft Successfully';
        } elseif ($request->button_clicked == 'btn-submit') {
            $data['status_id'] = EnterRequestStatus::CAR_CHECK;
            $message = 'Added Successfully';
        }

        $data['cpm_result'] = ceil($request->cpm);
        $cpm_calculated = $this->cpmCalculate($request->gross_weight);
        $data['cpm_calculated'] = $cpm_calculated;
        if ($cpm_calculated > $request->cpm) {
            $data['cpm_result'] = $cpm_calculated;
        }

        $data = Arr::except($data, 'files');
        $enterRequest = EnterRequest::create($data);

        $data['cpm_weight_ration'] = $enterRequest->cpm / $enterRequest->gross_weight;
        $data['cpm_weight_ration_wh'] = $enterRequest->cpm_result / $enterRequest->gross_weight;

        $enterRequest->update($data);

        if ($request->hasFile('files')) {
            $this->filesCreate($enterRequest);
        }

        return response()->json(['message' => $message, 'status' => 200]);
    }

    public function edit(EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Inbound Edit',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => 'enter_requests_table',
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code']),
            'companies' => ClearanceCompany::get(['id', 'name']),
        ];

        return view('pages.apps.operation-management.enter-requests.create', compact('payload', 'enterRequest'));
    }

    public function update(EnterCreateRequest $request, EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year . '/' . $request->customs_entry_center . '/' . $request->manifest_type_number . '/' . $request->manifest_bound_number;

        if ($enterRequest->status_id == EnterRequestStatus::DRAFT) {
            if ($request->button_clicked == 'btn-draft') {
                $data['status_id'] = EnterRequestStatus::DRAFT;
            } elseif ($request->button_clicked == 'btn-submit') {
                $data['status_id'] = EnterRequestStatus::CAR_CHECK;
            }
        }

        $data['cpm_result'] = ceil($request->cpm);

        $cpm_calculated = $this->cpmCalculate($request->gross_weight);
        $data['cpm_calculated'] = $cpm_calculated;
        if ($cpm_calculated > $request->cpm) {
            $data['cpm_result'] = $cpm_calculated;
        }

        if ($enterRequest->cars()->count() > 0) {
            $data['quantity_car'] = $enterRequest->quantity_car;
        }

        $data = Arr::except($data, 'files');
        $enterRequest->update($data);

        $data['cpm_weight_ration'] = $enterRequest->cpm / $enterRequest->gross_weight;
        $data['cpm_weight_ration_wh'] = $enterRequest->cpm_result / $enterRequest->gross_weight;

        $data['clearance_company_representative'] = request('clearance_company_representative', 0);
        $data['scanning_archiving'] = request('scanning_archiving', 0);
        $data['customs_department_representative'] = request('customs_department_representative', 0);

        $enterRequest->update($data);

        if ($request->hasFile('files')) {
            $this->filesCreate($enterRequest);
        }

        return response()->json(['message' => 'Update Successfully', 'enter_request_id' => $enterRequest->id, 'status' => 200]);
    }

    public function destroy(EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);
        $count = $enterRequest->Outbounds->count();
        if ($enterRequest->Outbounds->count() > 0)
            return response()->json([
                'exception' => "Cannot Delete This Inbound Because It Has Outbound ($count)",
            ], 403);
        else {
            $files = EnterRequestFile::where('enter_request_id', $enterRequest->id)->get();
            foreach ($files as $file) {
                if (Storage::path($file->path)) {
                    Storage::delete($file->path);
                }
                $file->delete();
            }
            $enterRequest->delete();
        }

    }

    public function cars($enter_request_id, CarsRequest $request)
    {
        $enterRequest = EnterRequest::firstWhere('id', $enter_request_id);

        foreach ($request->numbers as $index => $number) {
            EnterRequestCar::create([
                'number' => $number,
                'seal_number' => Arr::get($request->seal_numbers, $index),
                'is_status' => Arr::get($request->statuses, $index),
                'is_tracking_device' => Arr::get($request->tracking_devices, $index),
                'enter_request_id' => $enter_request_id,
            ]);
        }

        $enterRequest->update(['status_id' => EnterRequestStatus::WH_ENTER_PRODUCT]);

        $cares_list = view('pages.apps.operation-management.enter-requests.sections._cares-list', compact('enterRequest'))->render();

        return response()->json(['message' => 'Added Cars Successfully', 'html' => $cares_list, 'status' => 200]);
    }

    public function products($enter_request_id, ProductsRequest $request)
    {
        $enterRequest = EnterRequest::firstWhere('id', $enter_request_id);

        $delete_items_ids = [];
        $items_ids = $enterRequest->WarehouseItems->pluck('id')->toArray();
        if (count($items_ids) > 0)
            $delete_items_ids = array_diff($items_ids, $request->items_id);

        foreach ($request->products as $index => $product) {
            $product = Product::firstOrCreate([
                'name' => trim($product),
                'barcode' => trim(Arr::get($request->barcodes, $index)),
                'unit_measure_id' => trim(Arr::get($request->unit_measures, $index)),
            ]);
            $item = [
                'quantity' => trim(Arr::get($request->quantities, $index)),
                'location_line_id' => trim(Arr::get($request->locations, $index)),
                'level' => trim(Arr::get($request->levels, $index)),
                'pallet' => trim(Arr::get($request->pallets, $index)),
                'product_id' => $product->id,
                'enter_request_id' => $enterRequest->id,
                'batch_number' => trim(Arr::get($request->batch_numbers, $index)),
            ];

            $items_id = Arr::get($request->all(), 'items_id.' . $index);

            if ($items_id) {
                WarehouseItems::where('id', $items_id)->update($item);
            } else {
                WarehouseItems::create($item);
            }
            if (count($delete_items_ids) > 0) {
                WarehouseItems::whereIn('id', $delete_items_ids)->delete();
            }
        }

        $payload = (object)[
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => (object)$this->getLocations(),
        ];

        if ($request->button_clicked == 'btn-submit') {
            $enterRequest->update(['status_id' => EnterRequestStatus::VALIDATION]);
        }

        return response()->json(['message' => 'Added or Update Product item Successfully', 'status' => 200]);
    }

    public function validations($enter_request_id, ValidationsRequest $request)
    {
        $enterRequest = EnterRequest::firstWhere('id', $enter_request_id);

        foreach ($request->custom_values as $index => $custom_value) {
            $gross_weight = Arr::get($request->gross_weights, $index);
            $item = [
                'custom_value' => $custom_value,
                'gross_weight' => $gross_weight,
                'net_weight' => trim(Arr::get($request->net_weights, $index)),
                'custom_tariff_code' => trim(Arr::get($request->custom_tariff_codes, $index)),
                'cpm' => $gross_weight * $enterRequest->cpm_weight_ration,
                'cpm_capacity' => $gross_weight * $enterRequest->cpm_weight_ration_wh
            ];
            $items_id = Arr::get($request->all(), 'items_id.' . $index);
            WarehouseItems::where('id', $items_id)->update($item);
        }

        if ($request->button_clicked == 'btn-submit') {
            $enterRequest->update(['status_id' => EnterRequestStatus::AUTHORIZATION]);
        }

        return response()->json(['message' => 'Added or Update Validations item Successfully', 'status' => 200]);
    }

    public function filesCreate($enterRequest)
    {
        foreach (request('files') as $file) {
            $extension = $file->getClientOriginalExtension();
            $cleanName = preg_replace('/[^A-Za-z0-9\.\-_]/', '-', $file->getClientOriginalName());
            $path = Storage::putFileAs('Inbounds', $file, $cleanName);
            EnterRequestFile::create([
                'filename' => Str::replace('/', '-', $enterRequest->bound_number),
                'path' => $path,
                'extension' => $extension,
                'enter_request_id' => $enterRequest->id,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function fileDelete($file_id)
    {
        $file = EnterRequestFile::where('id', $file_id)->first();
        if (Storage::path($file->path)) {
            Storage::delete($file->path);
        }
        $file->delete();
    }

    public function pdf($id)
    {
        $enterRequest = EnterRequest::firstWhere('id', $id);
        return view('pages.pdf_model.receiving_customs_declaration', compact('enterRequest'));
    }

    public function pdfForm($id)
    {
        $enterRequest = EnterRequest::firstWhere('id', $id);
        return view('pages.pdf_model.receipt_delivery_commitment_form', compact('enterRequest'));
    }

    public function getLocations()
    {
        $locations = [];
        $locationLines = LocationLine::with('location', 'location.warehouse')->get();
        foreach ($locationLines as $locationLine) {
            $location = [];
            $location['id'] = $locationLine->id;
            $location['code'] = $locationLine?->location?->warehouse?->code . '-' . $locationLine?->location?->code . '-' . $locationLine?->code;
            $locations[] = (object)$location;
        }

        return $locations;
    }

}
