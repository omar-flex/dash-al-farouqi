<?php

namespace App\Http\Controllers\OperationManagement;

use AllowDynamicProperties;
use App\DataTables\OperationManagement\EnterRequestsDataTable;
use App\DataTables\OperationManagement\OutboundsDataTable;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        if (! auth()->user()->can('list_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
            'title' => 'Inbounds',
            'sub_title' => 'Inbound',
            'tableId' => 'enter_requests_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'statuses' => EnterRequestStatus::get(['id', 'name']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'companies' => ClearanceCompany::orderBy('name')->get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.operation-management.enter-requests.list', compact('payload'));
    }

    public function show(EnterRequest $enterRequest, OutboundsDataTable $dataTable)
    {
        if (! auth()->user()->can('list_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
            'title' => 'Inbound',
            'resource' => $this->resource,
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => (object) $this->getLocations(),
        ];

        $warehouseItems = $enterRequest->WarehouseItems;

        return $dataTable->render('pages.apps.operation-management.enter-requests.view', compact('enterRequest', 'warehouseItems', 'payload'));
    }

    public function create()
    {
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
            'title' => 'Inbound Create',
            'formId' => $this->formId,
            'tableId' => 'enter_requests_table',
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
            'companies' => ClearanceCompany::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code']),
        ];

        return view('pages.apps.operation-management.enter-requests.create', compact('payload'));
    }

    public function store(EnterCreateRequest $request)
    {
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year.'/'.$request->customs_entry_center.'/'.$request->manifest_type_number.'/'.$request->manifest_bound_number;

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
            $this->filesCreate($enterRequest, $request);
        }

        return response()->json(['message' => $message, 'status' => 200]);
    }

    public function edit(EnterRequest $enterRequest)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
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
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year.'/'.$request->customs_entry_center.'/'.$request->manifest_type_number.'/'.$request->manifest_bound_number;

        if ($enterRequest->manifest_type_number == 8 && $request->inbound_transfer) {
            $data['bound_number'] = $data['bound_number'].'/7/'.$request->inbound_transfer;
        }

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
            $this->filesCreate($enterRequest, $request);
        }

        return response()->json(['message' => 'Update Successfully', 'enter_request_id' => $enterRequest->id, 'status' => 200]);
    }

    public function destroy(EnterRequest $enterRequest)
    {
        if (! auth()->user()->can('delete_'.$this->resource)) {
            abort(403);
        }

        $paths = DB::transaction(function () use ($enterRequest): array {
            $lockedEnterRequest = EnterRequest::query()
                ->whereKey($enterRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $outboundCount = $lockedEnterRequest->Outbounds()->count();
            if ($outboundCount > 0) {
                throw ValidationException::withMessages([
                    'enter_request' => "Cannot delete this inbound because it has $outboundCount outbound declaration(s).",
                ]);
            }

            if ($lockedEnterRequest->WarehouseItems()->exists()) {
                throw ValidationException::withMessages([
                    'enter_request' => 'Cannot delete this inbound while warehouse stock rows exist.',
                ]);
            }

            $files = EnterRequestFile::query()
                ->where('enter_request_id', $lockedEnterRequest->id)
                ->lockForUpdate()
                ->get();
            $paths = $files->pluck('path')->filter()->values()->all();
            $files->each->delete();
            $lockedEnterRequest->delete();

            return $paths;
        });

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        return response()->json(['message' => 'Deleted successfully', 'status' => 200]);
    }

    public function cars($enter_request_id, CarsRequest $request)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = $request->validated();
        $numbers = array_values($payload['numbers']);
        $sealNumbers = array_values($payload['seal_numbers'] ?? array_fill(0, count($numbers), null));
        $statuses = array_values($payload['statuses']);
        $trackingDevices = array_values($payload['tracking_devices'] ?? array_fill(0, count($numbers), null));

        foreach ([$sealNumbers, $statuses, $trackingDevices] as $values) {
            if (count($values) !== count($numbers)) {
                throw ValidationException::withMessages([
                    'numbers' => 'All inbound car fields must align with every car row.',
                ]);
            }
        }

        $enterRequest = DB::transaction(function () use (
            $enter_request_id,
            $numbers,
            $sealNumbers,
            $statuses,
            $trackingDevices,
        ): EnterRequest {
            $lockedEnterRequest = EnterRequest::query()
                ->whereKey($enter_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedEnterRequest->status_id !== EnterRequestStatus::CAR_CHECK) {
                throw ValidationException::withMessages([
                    'enter_request' => 'Cars can only be added during the car-check step.',
                ]);
            }

            if (is_numeric($lockedEnterRequest->quantity_car)
                && (int) $lockedEnterRequest->quantity_car !== count($numbers)) {
                throw ValidationException::withMessages([
                    'numbers' => 'The number of car rows must equal the inbound car quantity.',
                ]);
            }

            if (EnterRequestCar::query()->where('enter_request_id', $lockedEnterRequest->id)->exists()) {
                throw ValidationException::withMessages([
                    'numbers' => 'Cars have already been recorded for this inbound.',
                ]);
            }

            foreach ($numbers as $index => $number) {
                EnterRequestCar::query()->create([
                    'number' => $number,
                    'seal_number' => $sealNumbers[$index],
                    'is_status' => $statuses[$index],
                    'is_tracking_device' => $trackingDevices[$index],
                    'enter_request_id' => $lockedEnterRequest->id,
                ]);
            }

            $lockedEnterRequest->update(['status_id' => EnterRequestStatus::WH_ENTER_PRODUCT]);

            return $lockedEnterRequest->fresh('Cars');
        });

        $cares_list = view('pages.apps.operation-management.enter-requests.sections._cares-list', compact('enterRequest'))->render();

        return response()->json(['message' => 'Added Cars Successfully', 'html' => $cares_list, 'status' => 200]);
    }

    public function products($enter_request_id, ProductsRequest $request)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        DB::transaction(function () use ($enter_request_id, $request) {
            $enterRequest = EnterRequest::query()->lockForUpdate()->findOrFail($enter_request_id);
            if (! in_array((int) $enterRequest->status_id, [
                EnterRequestStatus::WH_ENTER_PRODUCT,
                EnterRequestStatus::NEED_REVISION,
            ], true)) {
                throw ValidationException::withMessages([
                    'enter_request' => 'Products cannot be edited at the current inbound status.',
                ]);
            }

            if (! in_array($request->button_clicked, ['btn-draft', 'btn-submit'], true)) {
                throw ValidationException::withMessages([
                    'button_clicked' => 'A draft or submit action is required.',
                ]);
            }

            if ($request->button_clicked === 'btn-submit'
                || (int) $enterRequest->status_id === EnterRequestStatus::NEED_REVISION) {
                $submittedQuantity = round((float) collect($request->input('quantities', []))->sum(), 3);
                $packageQuantity = round((float) $enterRequest->quantity_packages, 3);
                if (abs($submittedQuantity - $packageQuantity) > 0.0005) {
                    throw ValidationException::withMessages([
                        'quantities' => "Product quantity ($submittedQuantity) must equal package count ($packageQuantity).",
                    ]);
                }
            }

            $currentItems = WarehouseItems::query()
                ->where('enter_request_id', $enterRequest->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $submittedItemIds = collect($request->input('items_id', []))
                ->filter()
                ->map(fn ($id) => (int) $id);

            $itemsToDelete = $currentItems->except($submittedItemIds->all());
            foreach ($itemsToDelete as $itemToDelete) {
                if ($itemToDelete->OutboundWarehouseItems()->exists()) {
                    throw ValidationException::withMessages([
                        'items_id' => "Warehouse item {$itemToDelete->id} cannot be deleted because it has outbound history.",
                    ]);
                }
            }

            foreach ($request->input('products', []) as $index => $productName) {
                $product = Product::firstOrCreate([
                    'name' => trim($productName),
                    'barcode' => trim((string) Arr::get($request->barcodes, $index)),
                    'unit_measure_id' => (int) Arr::get($request->unit_measures, $index),
                ]);

                $warehouseItemId = Arr::get($request->input('items_id', []), $index);
                $existingItem = $warehouseItemId ? $currentItems->get((int) $warehouseItemId) : null;
                if ($warehouseItemId && ! $existingItem) {
                    throw ValidationException::withMessages([
                        "items_id.$index" => 'The warehouse item does not belong to this inbound manifest.',
                    ]);
                }

                $quantity = (float) Arr::get($request->quantities, $index);
                $otherQuantity = Arr::get($request->other_quantities, $index);
                $otherQuantity = $otherQuantity === null || $otherQuantity === '' ? null : (float) $otherQuantity;
                $batchNumber = trim((string) Arr::get($request->batch_numbers, $index));
                $batchNumber = $batchNumber === '' ? null : $batchNumber;

                $identityAndQuantity = [
                    'quantity' => $quantity,
                    'other_quantity' => $otherQuantity,
                    'product_id' => $product->id,
                    'batch_number' => $batchNumber,
                ];
                $locationData = [
                    'location_line_id' => (int) Arr::get($request->locations, $index),
                    'level' => trim((string) Arr::get($request->levels, $index)) ?: null,
                    'pallet' => trim((string) Arr::get($request->pallets, $index)) ?: null,
                    'enter_request_id' => $enterRequest->id,
                ];

                if (! $existingItem) {
                    WarehouseItems::create($locationData + $identityAndQuantity + [
                        'remaining_quantity' => $quantity,
                        'remaining_other_quantity' => $otherQuantity,
                        'is_status' => $quantity > 0,
                    ]);

                    continue;
                }

                if ($existingItem->OutboundWarehouseItems()->exists()) {
                    $identityChanged = (int) $existingItem->product_id !== $product->id
                        || trim((string) $existingItem->batch_number) !== trim((string) $batchNumber)
                        || abs((float) $existingItem->quantity - $quantity) > 0.0005
                        || abs((float) ($existingItem->other_quantity ?? 0) - (float) ($otherQuantity ?? 0)) > 0.0005;

                    if ($identityChanged) {
                        throw ValidationException::withMessages([
                            "quantities.$index" => 'Product identity and quantities cannot change after an outbound movement exists.',
                        ]);
                    }

                    $existingItem->update($locationData);

                    continue;
                }

                $existingItem->update($locationData + $identityAndQuantity + [
                    'remaining_quantity' => $quantity,
                    'remaining_other_quantity' => $otherQuantity,
                    'is_status' => $quantity > 0,
                ]);
            }

            if ($itemsToDelete->isNotEmpty()) {
                WarehouseItems::whereIntegerInRaw('id', $itemsToDelete->keys()->all())->delete();
            }

            if ($request->button_clicked == 'btn-submit') {
                $enterRequest->update(['status_id' => EnterRequestStatus::VALIDATION]);
            }
        });

        return response()->json(['message' => 'Added or Update Product item Successfully', 'status' => 200]);
    }

    public function validations($enter_request_id, ValidationsRequest $request)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        DB::transaction(function () use ($enter_request_id, $request) {
            $enterRequest = EnterRequest::query()->lockForUpdate()->findOrFail($enter_request_id);
            if (! in_array((int) $enterRequest->status_id, [
                EnterRequestStatus::VALIDATION,
                EnterRequestStatus::NEED_REVISION,
            ], true)) {
                throw ValidationException::withMessages([
                    'enter_request' => 'Validation values cannot be edited at the current inbound status.',
                ]);
            }

            if (! in_array($request->button_clicked, ['btn-draft', 'btn-submit'], true)) {
                throw ValidationException::withMessages([
                    'button_clicked' => 'A draft or submit action is required.',
                ]);
            }

            if ($request->button_clicked === 'btn-submit') {
                $totals = [
                    'custom_values' => [(float) $enterRequest->total_cost, 'custom value'],
                    'gross_weights' => [(float) $enterRequest->gross_weight, 'gross weight'],
                    'net_weights' => [(float) $enterRequest->net_weight, 'net weight'],
                ];
                foreach ($totals as $field => [$expected, $label]) {
                    $actual = round((float) collect($request->input($field, []))->sum(), 3);
                    $expected = round($expected, 3);
                    if (abs($actual - $expected) > 0.0005) {
                        throw ValidationException::withMessages([
                            $field => "Total $label ($actual) must equal the inbound $label ($expected).",
                        ]);
                    }
                }
            }

            $warehouseItems = WarehouseItems::query()
                ->where('enter_request_id', $enterRequest->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($warehouseItems->count() !== count($request->input('items_id', []))) {
                throw ValidationException::withMessages([
                    'items_id' => 'Every inbound warehouse item must be included exactly once.',
                ]);
            }

            foreach ($request->input('custom_values', []) as $index => $customValue) {
                $warehouseItemId = (int) Arr::get($request->input('items_id', []), $index);
                $warehouseItem = $warehouseItems->get($warehouseItemId);
                if (! $warehouseItem) {
                    throw ValidationException::withMessages([
                        "items_id.$index" => 'The warehouse item does not belong to this inbound manifest.',
                    ]);
                }

                $grossWeight = (float) Arr::get($request->gross_weights, $index);
                $attributes = [
                    'custom_value' => (float) $customValue,
                    'gross_weight' => $grossWeight,
                    'net_weight' => (float) Arr::get($request->net_weights, $index),
                    'custom_tariff_code' => trim((string) Arr::get($request->custom_tariff_codes, $index)),
                    'cpm' => $grossWeight * (float) $enterRequest->cpm_weight_ration,
                    'cpm_capacity' => $grossWeight * (float) $enterRequest->cpm_weight_ration_wh,
                ];

                if ($warehouseItem->OutboundWarehouseItems()->exists()) {
                    $changed = collect(['custom_value', 'gross_weight', 'net_weight', 'cpm', 'cpm_capacity'])
                        ->contains(fn ($field) => abs((float) $warehouseItem->{$field} - (float) $attributes[$field]) > 0.0005)
                        || trim((string) $warehouseItem->custom_tariff_code) !== $attributes['custom_tariff_code'];

                    if ($changed) {
                        throw ValidationException::withMessages([
                            "custom_values.$index" => 'Financial and weight values cannot change after an outbound movement exists.',
                        ]);
                    }

                    continue;
                }

                $warehouseItem->update($attributes);
            }

            if ($request->button_clicked == 'btn-submit') {
                $enterRequest->update(['status_id' => EnterRequestStatus::AUTHORIZATION]);
            }
        });

        return response()->json(['message' => 'Added or Update Validations item Successfully', 'status' => 200]);
    }

    public function filesCreate($enterRequest, EnterCreateRequest $request)
    {
        foreach ($request->file('files', []) as $file) {
            $extension = $file->getClientOriginalExtension();
            $cleanName = preg_replace('/[^A-Za-z0-9\.\-_]/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $uniqueName = $cleanName.'-'.uniqid().'.'.$extension;
            $path = Storage::disk('public')->putFileAs('Inbounds', $file, $uniqueName);

            if ($path === false) {
                throw new \RuntimeException('Failed to store inbound file.');
            }

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
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $fileReference = EnterRequestFile::query()->findOrFail($file_id);
        $path = DB::transaction(function () use ($fileReference): ?string {
            $enterRequest = EnterRequest::query()
                ->whereKey($fileReference->enter_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $enterRequest->status_id === EnterRequestStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'enter_request' => 'Approved inbounds cannot be modified.',
                ]);
            }

            $file = EnterRequestFile::query()
                ->whereKey($fileReference->id)
                ->where('enter_request_id', $enterRequest->id)
                ->lockForUpdate()
                ->firstOrFail();
            $path = $file->path;
            $file->delete();

            return $path;
        });

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['message' => 'File deleted successfully', 'status' => 200]);
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
            $location['code'] = $locationLine?->location?->warehouse?->code.'-'.$locationLine?->location?->code.'-'.$locationLine?->code;
            $locations[] = (object) $location;
        }

        return $locations;
    }
}
