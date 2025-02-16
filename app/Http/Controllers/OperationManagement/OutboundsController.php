<?php

namespace App\Http\Controllers\OperationManagement;


use AllowDynamicProperties;
use App\DataTables\OperationManagement\OutboundsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\OutboundRequest;
use App\Models\Country;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\LocationLine;
use App\Models\ManifestFile;
use App\Models\Outbound;
use App\Models\Product;
use App\Models\UnitMeasure;
use App\Models\Warehouse;
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
        ];

        return $dataTable->render('pages.apps.operation-management.outbounds.list', compact('payload'));
    }

    public function show(EnterRequest $enterRequest)
    {
        return '';
        $locations = [];

        $locationLines = LocationLine::with('location', 'location.warehouse')->get();
        foreach ($locationLines as $key => $locationLine) {
            $locations [$key]['id'] = $locationLine->id;
            $locations [$key]['code'] = $locationLine?->location?->warehouse?->code . ' - ' . $locationLine?->location?->code . ' - ' . $locationLine?->code;
        }

        $payload = (object)[
            'title' => 'Enter Request',
            'resource' => $this->resource,
            'products' => Product::all(['id', 'name']),
            'unitMeasures' => UnitMeasure::all(['id', 'name']),
            'locations' => $locations,
        ];

        return view('pages.apps.operation-management.outbounds.view', compact('enterRequest', 'payload'));
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

        if ($request->hasFile('files')) {
            $this->filesCreate($outbound);
        }

        return response()->json(['message' => $message, 'status' => 200]);
    }

    public function edit(Outbound $outbound)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Enter Request Edit',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => $this->tableId,
            'bound_numbers' => EnterRequest::get(['id', 'bound_number as name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code'])
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

        $data['cpm_result'] = ceil(($outbound->EnterRequest->cpm / $outbound->EnterRequest->gross_weight) * $outbound->gross_weight);;

        $data = Arr::except($data, 'files');
        $outbound->update($data);

        if ($request->hasFile('files')) {
            $this->filesCreate($outbound);
        }

        return response()->json(['message' => 'Update Successfully', 'status' => 200]);
    }

    public function destroy(Outbound $outbound)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $files = ManifestFile::where('manifest_id', $outbound->id)
            ->where('type', 7)
            ->get();
        foreach ($files as $file) {
            Storage::delete($file->path);
            $file->delete();
        }
        $outbound->delete();
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
                'type' => 4
            ]);
        }
    }

    public function fileDelete($file_id)
    {
        $file = ManifestFile::where('id', $file_id)->where('type', 4)->first();
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

}
