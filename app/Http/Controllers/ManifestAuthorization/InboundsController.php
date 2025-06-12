<?php

namespace App\Http\Controllers\ManifestAuthorization;


use AllowDynamicProperties;
use App\DataTables\ManifestAuthorization\InboundsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\ManifestAuthorizationsRequest;
use App\Models\ClearanceCompany;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestFile;
use App\Models\EnterRequestStatus;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Storage;


#[AllowDynamicProperties] class InboundsController extends Controller
{

    public function __construct()
    {
        $this->formId = 'manifestAuthorizationInbound';
        $this->resource = 'inbounds';
    }

    public function index(InboundsDataTable $dataTable)
    {
        $payload = (object)[
            'title' => 'Manifest Authorizations Inbounds',
            'sub_title' => 'Manifest Authorization Inbound',
            'tableId' => 'manifest_authorizations_inbounds_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'statuses' => EnterRequestStatus::get(['id', 'name']),
            'customers' => Customer::get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.manifest-authorizations.inbounds.list', compact('payload'));
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

        return view('pages.apps.manifest-authorizations.inbounds.create', compact('payload', 'inbound'));
    }

    public function update(ManifestAuthorizationsRequest $request, EnterRequest $inbound)
    {
        if ($request->button_clicked == 'btn-delete') {
            $count = $inbound->Outbounds->count();
            if ($inbound->Outbounds->count() > 0)
                return response()->json([
                    'exception' => "Cannot Delete This Inbound Because It Has Outbound ($count)",
                ], 403);
            else {
                $files = EnterRequestFile::where('enter_request_id', $inbound->id)->get();
                foreach ($files as $file) {
                    if (Storage::path($file->path)) {
                        Storage::delete($file->path);
                    }
                    $file->delete();
                }
                $inbound->delete();
            }
        } elseif ($request->button_clicked == 'btn-revision')
            $inbound->update(['status_id' => EnterRequestStatus::NEED_REVISION]);
        else {
            $inbound->update([
                'status_id' => EnterRequestStatus::APPROVED,
                'invoicing_date' => $request->invoicing_date,
            ]);

        }

        return response()->json(['message' => 'Modified successfully', 'status' => 200]);
    }

}
