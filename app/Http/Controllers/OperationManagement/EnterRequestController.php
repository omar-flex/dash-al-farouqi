<?php

namespace App\Http\Controllers\OperationManagement;


use App\DataTables\OperationManagement\EnterRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\CarsRequest;
use App\Http\Requests\OperationManagement\EnterCreateRequest;
use App\Http\Requests\OperationManagement\ProductsRequest;
use App\Models\Category;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestCar;
use App\Models\EnterRequestStatus;
use App\Models\LocationLine;
use App\Models\ManifestFile;
use App\Models\ManifestType;
use App\Models\Product;
use App\Models\UnitMeasure;
use App\Models\Warehouse;
use App\Models\WarehouseItems;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


class EnterRequestController extends Controller
{

    public function __construct()
    {
        $this->formId = 'enterRequest';
        $this->resource = 'enter_requests';
    }

    public function cpmCalculate($grossWeight)
    {
        return $grossWeight / 333;
    }

    public function index(EnterRequestsDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Enter Requests',
            'sub_title' => 'Enter Request',
            'tableId' => 'enter_requests_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
        ];

        return $dataTable->render('pages.apps.operation-management.enter-requests.list', compact('payload'));
    }

    public function show(EnterRequest $enterRequest)
    {
        $locations = [];

        $locationLines = LocationLine::with('location', 'location.warehouse')->get();
        foreach ($locationLines as $key => $locationLine) {
            $locations [$key]['id'] = $locationLine->id;
            $locations [$key]['code'] = $locationLine?->location?->warehouse?->code . '-' . $locationLine?->location?->code . '-' . $locationLine?->code;
        }

        $payload = (object)[
            'title' => 'Enter Request',
            'resource' => $this->resource,
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => $locations,
            //'categories' => Category::where('type', 'service')->get(['id', 'name_en as name']),
        ];

        return view('pages.apps.operation-management.enter-requests.view', compact('enterRequest', 'payload'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Enter Request Create',
            'formId' => $this->formId,
            'tableId' => 'enter_requests_table',
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
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

        $data['cpm_result'] = $request->cpm;

        $cpm_calculated = $this->cpmCalculate($request->gross_weight);
        $data['cpm_calculated'] = $cpm_calculated;
        if ($cpm_calculated > $request->cpm) {
            $data['cpm_result'] = $cpm_calculated;
        }

        $enterRequest = EnterRequest::create(Arr::except($data, 'files'));

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
            'title' => 'Enter Request Edit',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => 'enter_requests_table',
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code'])
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

        $data['cpm_result'] = $request->cpm;
        $data['country_id'] = $request->country_id;

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

        if ($request->hasFile('files')) {
            $this->filesCreate($enterRequest);
        }

        return response()->json(['message' => 'Update Successfully', 'enter_request_id' => $enterRequest->id, 'status' => 200]);
    }

    public function destroy(EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $files = ManifestFile::where('manifest_id', $enterRequest->id)->where('type', 4)->get();
        foreach ($files as $file) {
            Storage::delete($file->path);
            $file->delete();
        }
        $enterRequest->delete();
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

        foreach ($request->products as $index => $product) {
            $product = Product::firstOrCreate([
                'name' => trim($product),
                'barcode' => trim(Arr::get($request->barcodes, $index)),
                'unit_measure_id' => trim(Arr::get($request->unit_measures, $index)),
            ]);

            WarehouseItems::create([
                'quantity' => trim(Arr::get($request->quantities, $index)),
                'location_line_id' => trim(Arr::get($request->locations, $index)),
                'level' => trim(Arr::get($request->levels, $index)),
                'pallet' => trim(Arr::get($request->pallets, $index)),
                'product_id' => $product->id,
                'enter_request_id' => $enterRequest->id,
                'batch_number' => trim(Arr::get($request->batch_numbers, $index)),
            ]);
        }

        if ($request->button_clicked == 'btn-submit') {
            $enterRequest->update(['status_id' => EnterRequestStatus::AUTHORIZATION]);
        }

        $warehouseItems = $enterRequest->WarehouseItems();

        $packages_list = view('pages.apps.operation-management.enter-requests.sections.packages', compact('warehouseItems', 'enterRequest'))->render();

        return response()->json(['message' => 'Added Cars Successfully', 'html' => $packages_list, 'status' => 200]);
    }

    public function filesCreate($enterRequest)
    {
        foreach (request('files') as $file) {
            $extension = $file->getClientOriginalExtension();
            $fileNameToStore = uniqid('') . '.' . $extension;
            $path = Storage::putFileAs(Str::replace('/', '-', $enterRequest->bound_number), $file, $fileNameToStore);
            ManifestFile::create([
                'filename' => $fileNameToStore,
                'path' => $path,
                'extension' => $extension,
                'manifest_id' => $enterRequest->id,
                'user_id' => Auth::id(),
                'type' => ManifestType::INBOUND,
            ]);
        }
    }

    public function fileDelete($file_id)
    {
        $file = ManifestFile::where('id', $file_id)->where('type', ManifestType::INBOUND)->first();
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

}
