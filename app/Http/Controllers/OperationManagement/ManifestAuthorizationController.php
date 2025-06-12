<?php

namespace App\Http\Controllers\OperationManagement;


use AllowDynamicProperties;
use App\DataTables\OperationManagement\EnterRequestsDataTable;
use App\DataTables\OperationManagement\ManifestAuthorizationsInboundsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\CarsRequest;
use App\Http\Requests\OperationManagement\EnterCreateRequest;
use App\Http\Requests\OperationManagement\ManifestAuthorizationsRequest;
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


#[AllowDynamicProperties] class ManifestAuthorizationController extends Controller
{

    public function __construct()
    {
        $this->formId = 'Inbound';
        $this->resource = 'inbounds';
    }

    public function index(ManifestAuthorizationsInboundsDataTable $dataTable)
    {
        $payload = (object)[
            'title' => 'Manifest Authorizations Inbounds',
            'sub_title' => 'Manifest Authorization Inbound',
            'tableId' => 'manifest_authorizations_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'statuses' => EnterRequestStatus::get(['id', 'name']),
            'customers' => Customer::get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.operation-management.manifest-authorizations.list', compact('payload'));
    }

    public function edit(EnterRequest $inbound)
    {
        $payload = (object)[
            'title' => 'Manifest Authorizations Inbound',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => 'manifest_authorizations_inbounds_table',
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code']),
            'companies' => ClearanceCompany::get(['id', 'name']),
        ];

        return view('pages.apps.operation-management.manifest-authorizations.create', compact('payload', 'inbound'));
    }

    public function update(ManifestAuthorizationsRequest $request, EnterRequest $manifest_authorization)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);
        if ($request->button_clicked == 'btn-delete') {
            if (!auth()->user()->can('delete_' . $this->resource))
                abort(403);
            $count = $manifest_authorization->Outbounds->count();
            if ($manifest_authorization->Outbounds->count() > 0)
                return response()->json([
                    'exception' => "Cannot Delete This Inbound Because It Has Outbound ($count)",
                ], 403);
            else {
                $files = EnterRequestFile::where('enter_request_id', $manifest_authorization->id)->get();
                foreach ($files as $file) {
                    if (Storage::path($file->path)) {
                        Storage::delete($file->path);
                    }
                    $file->delete();
                }
                $manifest_authorization->delete();
            }
        } elseif ($request->button_clicked == 'btn-revision')
            $manifest_authorization->update(['status_id' => EnterRequestStatus::NEED_REVISION]);
        else {
            $manifest_authorization->update([
                'status_id' => EnterRequestStatus::APPROVED,
                'invoicing_date' => $request->invoicing_date,
            ]);

        }

        return response()->json(['message' => 'Modified successfully', 'status' => 200]);
    }

}
