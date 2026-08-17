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
use App\Models\Outbound;
use App\Models\OutboundCar;
use App\Models\OutboundFile;
use App\Models\OutboundStatus;
use App\Models\OutboundWarehouseItems;
use App\Models\Warehouse;
use App\Services\Inventory\OutboundInventoryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[AllowDynamicProperties]
class OutboundsController extends Controller
{
    public function __construct(private readonly OutboundInventoryService $outboundInventoryService)
    {
        $this->formId = 'outboundForm';
        $this->resource = 'outbounds';
        $this->tableId = 'outbounds_table';
    }

    public function index(OutboundsDataTable $dataTable)
    {
        if (! auth()->user()->can('list_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
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
        if (! auth()->user()->can('list_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
            'title' => 'Outbound',
            'resource' => $this->resource,
        ];

        $cars = OutboundCar::where('outbound_id', $outbound->id)->get();

        $warehouseItems = $outbound->OutboundWarehouseItems()
            ->with(
                'WarehouseItem.Product.UnitMeasure',
                'WarehouseItem.LocationLine.Location.Warehouse',
            )
            ->get();

        $selectableWarehouseItems = $this->outboundInventoryService
            ->selectableWarehouseItems($outbound);

        return view('pages.apps.operation-management.outbounds.view', compact(
            'outbound',
            'payload',
            'warehouseItems',
            'selectableWarehouseItems',
            'cars',
        ));
    }

    public function create()
    {
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
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
        if (! auth()->user()->can('add_'.$this->resource)) {
            abort(403);
        }

        $data = $request->validated();
        $data['outbound_number'] = $request->manifest_year.'/'.$request->customs_entry_center.'/'.$request->manifest_type_number.'/'.$request->manifest_outbound_number;

        if ($request->button_clicked == 'btn-draft') {
            $data['status_id'] = OutboundStatus::DRAFT;
            $message = 'Added Draft Successfully';
        } elseif ($request->button_clicked == 'btn-submit') {
            $data['status_id'] = OutboundStatus::CAR_CHECK;
            $message = 'Added Successfully';
        } else {
            throw ValidationException::withMessages([
                'button_clicked' => 'A draft or submit action is required.',
            ]);
        }

        $storedPaths = [];
        try {
            DB::transaction(function () use ($data, $request, &$storedPaths): void {
                $enterRequest = EnterRequest::query()
                    ->whereKey($data['enter_request_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $outboundData = Arr::except($data, 'files');
                $outboundData['cpm_result'] = $this->calculateCpmResult(
                    $enterRequest,
                    (float) $outboundData['gross_weight'],
                );

                $outbound = Outbound::query()->create($outboundData);

                if ($request->hasFile('files')) {
                    $this->filesCreate($outbound, $request, $storedPaths);
                }
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return response()->json(['message' => $message, 'status' => 200]);
    }

    public function edit(Outbound $outbound)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = (object) [
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
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $data = $request->validated();
        $data['outbound_number'] = $request->manifest_year.'/'.$request->customs_entry_center.'/'.$request->manifest_type_number.'/'.$request->manifest_outbound_number;
        $data = Arr::except($data, 'files');
        $data['clearance_company_representative'] = request('clearance_company_representative', 0);
        $data['scanning_archiving'] = request('scanning_archiving', 0);
        $data['customs_department_representative'] = request('customs_department_representative', 0);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($outbound, $data, $request, &$storedPaths): void {
                $lockedOutbound = Outbound::query()
                    ->whereKey($outbound->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertNotApproved($lockedOutbound);

                $movements = OutboundWarehouseItems::query()
                    ->where('outbound_id', $lockedOutbound->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $cars = OutboundCar::query()
                    ->where('outbound_id', $lockedOutbound->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($cars->isNotEmpty()
                    && (int) $data['quantity_car'] !== (int) $lockedOutbound->quantity_car) {
                    throw ValidationException::withMessages([
                        'quantity_car' => 'The car quantity cannot change after car records exist.',
                    ]);
                }

                if ($movements->isNotEmpty()) {
                    if ((int) $data['enter_request_id'] !== (int) $lockedOutbound->enter_request_id) {
                        throw ValidationException::withMessages([
                            'enter_request_id' => 'The inbound manifest cannot change after release lines exist.',
                        ]);
                    }

                    $newPackageQuantity = (float) $data['quantity_packages'];
                    $oldPackageQuantity = (float) $lockedOutbound->quantity_packages;
                    $movementQuantity = (float) $movements->sum('quantity');
                    if (! $this->sameQuantity($newPackageQuantity, $oldPackageQuantity)
                        && ! $this->sameQuantity($newPackageQuantity, $movementQuantity)) {
                        throw ValidationException::withMessages([
                            'quantity_packages' => "Package quantity must equal the release-line total ($movementQuantity).",
                        ]);
                    }
                }

                if ((int) $lockedOutbound->status_id === OutboundStatus::DRAFT) {
                    if ($request->button_clicked === 'btn-draft') {
                        $data['status_id'] = OutboundStatus::DRAFT;
                    } elseif ($request->button_clicked === 'btn-submit') {
                        $data['status_id'] = OutboundStatus::CAR_CHECK;
                    } else {
                        throw ValidationException::withMessages([
                            'button_clicked' => 'A draft or submit action is required.',
                        ]);
                    }
                }

                $enterRequest = EnterRequest::query()
                    ->whereKey($data['enter_request_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $data['cpm_result'] = $this->calculateCpmResult(
                    $enterRequest,
                    (float) $data['gross_weight'],
                );

                $lockedOutbound->update($data);

                if ($request->hasFile('files')) {
                    $this->filesCreate($lockedOutbound, $request, $storedPaths);
                }
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Outbound $outbound)
    {
        if (! auth()->user()->can('delete_'.$this->resource)) {
            abort(403);
        }

        $paths = $this->outboundInventoryService->deleteOutbound($outbound);
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        return response()->json(['message' => 'Deleted successfully', 'status' => 200]);
    }

    public function filesCreate(Outbound $outbound, OutboundRequest $request, array &$storedPaths = []): void
    {
        foreach ($request->file('files', []) as $file) {
            $extension = $file->getClientOriginalExtension();
            $cleanName = preg_replace('/[^A-Za-z0-9\.\-_]/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $uniqueName = $cleanName.'-'.uniqid().'.'.$extension;
            $path = Storage::disk('public')->putFileAs('Outbounds', $file, $uniqueName);

            if ($path === false) {
                throw new \RuntimeException('Failed to store outbound file.');
            }

            $storedPaths[] = $path;

            OutboundFile::create([
                'filename' => Str::replace('/', '-', $outbound->outbound_number),
                'path' => $path,
                'extension' => $extension,
                'outbound_id' => $outbound->id,
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function fileDelete($file_id)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $fileReference = OutboundFile::query()->findOrFail($file_id);
        $path = DB::transaction(function () use ($fileReference): ?string {
            $outbound = Outbound::query()
                ->whereKey($fileReference->outbound_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertNotApproved($outbound);

            $file = OutboundFile::query()
                ->whereKey($fileReference->id)
                ->where('outbound_id', $outbound->id)
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
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = $request->validated();
        $numbers = array_values($payload['numbers']);
        $sealNumbers = array_values($payload['seal_numbers'] ?? array_fill(0, count($numbers), null));
        $carIds = array_values($payload['car_ids'] ?? []);

        if (count($sealNumbers) !== count($numbers)) {
            throw ValidationException::withMessages([
                'seal_numbers' => 'A seal-number value is required for every car row, even when blank.',
            ]);
        }
        if ($carIds !== [] && count($carIds) !== count($numbers)) {
            throw ValidationException::withMessages([
                'car_ids' => 'The submitted car identifiers do not align with the car rows.',
            ]);
        }

        $outbound = DB::transaction(function () use ($outbound_id, $numbers, $sealNumbers, $carIds): Outbound {
            $lockedOutbound = Outbound::query()
                ->whereKey($outbound_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertNotApproved($lockedOutbound);

            if ((int) $lockedOutbound->status_id !== OutboundStatus::CAR_CHECK) {
                throw ValidationException::withMessages([
                    'outbound' => 'Cars can only be edited during the car-check step.',
                ]);
            }

            if (is_numeric($lockedOutbound->quantity_car)
                && (int) $lockedOutbound->quantity_car !== count($numbers)) {
                throw ValidationException::withMessages([
                    'numbers' => 'The number of car rows must equal the outbound car quantity.',
                ]);
            }

            $existingCars = OutboundCar::query()
                ->where('outbound_id', $lockedOutbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($existingCars->isNotEmpty() && $carIds === []) {
                throw ValidationException::withMessages([
                    'car_ids' => 'Existing car identifiers are required when editing cars.',
                ]);
            }

            if ($existingCars->isNotEmpty() && count($carIds) !== $existingCars->count()) {
                throw ValidationException::withMessages([
                    'car_ids' => 'Every existing car must be included exactly once.',
                ]);
            }

            foreach ($carIds as $index => $carId) {
                if (! $existingCars->has((int) $carId)) {
                    throw ValidationException::withMessages([
                        "car_ids.$index" => 'The selected car does not belong to this outbound.',
                    ]);
                }
            }

            foreach ($numbers as $index => $number) {
                $carId = $carIds[$index] ?? null;
                if ($carId) {
                    $existingCars->get((int) $carId)->update([
                        'number' => $number,
                        'seal_number' => $sealNumbers[$index],
                    ]);
                } else {
                    OutboundCar::query()->create([
                        'number' => $number,
                        'seal_number' => $sealNumbers[$index],
                        'outbound_id' => $lockedOutbound->id,
                    ]);
                }
            }

            $lockedOutbound->update(['status_id' => OutboundStatus::WH_RELEASE_PRODUCT]);

            return $lockedOutbound->fresh('Cars');
        });

        // The client reloads the page after a successful save. Rendering the
        // partial here used variables that only exist in the full page and
        // could turn a committed car save into a 500 response, leaving the
        // browser on the stale car-check screen.
        return response()->json(['message' => 'Added Cars Successfully', 'status' => 200]);
    }

    public function products($outbound_id, OutboundProductsRequest $request)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $outbound = Outbound::query()->findOrFail($outbound_id);
        $payload = $request->validated();

        $this->outboundInventoryService->sync(
            $outbound,
            $payload['items'],
            $payload['action'],
        );

        return response()->json(['message' => 'Added or Update Release Product item Successfully', 'status' => 200]);
    }

    public function validations($outbound_id, OutboundValidationsRequest $request)
    {
        if (! auth()->user()->can('edit_'.$this->resource)) {
            abort(403);
        }

        $payload = $request->validated();
        $itemIds = array_values($payload['items_id']);
        $customValues = array_values($payload['custom_values']);
        $grossWeights = array_values($payload['gross_weights']);
        $netWeights = array_values($payload['net_weights']);

        if (count($itemIds) !== count($customValues)
            || count($itemIds) !== count($grossWeights)
            || count($itemIds) !== count($netWeights)) {
            throw ValidationException::withMessages([
                'items_id' => 'Validation values must align with every outbound item row.',
            ]);
        }

        DB::transaction(function () use (
            $outbound_id,
            $request,
            $itemIds,
            $customValues,
            $grossWeights,
            $netWeights,
        ): void {
            $outbound = Outbound::query()
                ->whereKey($outbound_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertNotApproved($outbound);

            if (! in_array((int) $outbound->status_id, [
                OutboundStatus::VALIDATION,
                OutboundStatus::NEED_REVISION,
            ], true)) {
                throw ValidationException::withMessages([
                    'outbound' => 'Validation values cannot be edited at the current outbound status.',
                ]);
            }

            $movements = OutboundWarehouseItems::query()
                ->where('outbound_id', $outbound->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($movements->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'items_id' => 'Every outbound item must be included exactly once.',
                ]);
            }

            foreach ($itemIds as $index => $itemId) {
                $movement = $movements->get((int) $itemId);
                if (! $movement) {
                    throw ValidationException::withMessages([
                        "items_id.$index" => 'The selected item does not belong to this outbound.',
                    ]);
                }

                $grossWeight = (float) $grossWeights[$index];
                $movement->update([
                    'custom_value' => $customValues[$index],
                    'gross_weight' => $grossWeight,
                    'net_weight' => $netWeights[$index],
                    'cpm' => $grossWeight * $outbound->EnterRequest->cpm_weight_ration,
                    'cpm_capacity' => $grossWeight * $outbound->EnterRequest->cpm_weight_ration_wh,
                ]);
            }

            if ($request->button_clicked === 'btn-submit') {
                $this->assertValidationTotal($customValues, (float) $outbound->total_cost, 'custom_values', 'custom value');
                $this->assertValidationTotal($grossWeights, (float) $outbound->gross_weight, 'gross_weights', 'gross weight');
                $this->assertValidationTotal($netWeights, (float) $outbound->net_weight, 'net_weights', 'net weight');
                $outbound->update(['status_id' => OutboundStatus::AUTHORIZATION]);
            } elseif ($request->button_clicked !== 'btn-draft') {
                throw ValidationException::withMessages([
                    'button_clicked' => 'A draft or submit action is required.',
                ]);
            }
        });

        return response()->json(['message' => 'Added or Update Validations item Successfully', 'status' => 200]);
    }

    private function calculateCpmResult(EnterRequest $enterRequest, float $outboundGrossWeight): float
    {
        $inboundGrossWeight = (float) $enterRequest->gross_weight;
        if (abs($inboundGrossWeight) < 0.0005) {
            throw ValidationException::withMessages([
                'enter_request_id' => 'The selected inbound manifest has no valid gross weight for CPM calculation.',
            ]);
        }

        return ceil(((float) $enterRequest->cpm / $inboundGrossWeight) * $outboundGrossWeight);
    }

    private function sameQuantity(float $first, float $second): bool
    {
        return abs(round($first, 3) - round($second, 3)) <= 0.0005;
    }

    private function assertValidationTotal(array $values, float $expected, string $key, string $label): void
    {
        $actual = round(array_sum(array_map('floatval', $values)), 3);
        if (! $this->sameQuantity($actual, $expected)) {
            throw ValidationException::withMessages([
                $key => "Total $label ($actual) must equal the outbound $label (".round($expected, 3).').',
            ]);
        }
    }

    private function assertNotApproved(Outbound $outbound): void
    {
        if ((int) $outbound->status_id === OutboundStatus::APPROVED) {
            throw ValidationException::withMessages([
                'outbound' => 'Approved outbounds cannot be modified or deleted.',
            ]);
        }
    }
}
