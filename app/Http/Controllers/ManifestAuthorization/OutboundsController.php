<?php

namespace App\Http\Controllers\ManifestAuthorization;


use AllowDynamicProperties;
use App\DataTables\ManifestAuthorization\OutboundsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\ManifestAuthorizationsRequest;
use App\Models\ClearanceCompany;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestFile;
use App\Models\EnterRequestStatus;
use App\Models\Outbound;
use App\Models\OutboundFile;
use App\Models\OutboundStatus;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


#[AllowDynamicProperties] class OutboundsController extends Controller
{

    public function __construct()
    {
        $this->formId = 'manifestAuthorizationOutbound';
        $this->resource = 'outbounds';
    }

    public function index(OutboundsDataTable $dataTable)
    {
        $payload = (object)[
            'title' => 'Manifest Authorizations Outbounds',
            'sub_title' => 'Manifest Authorization Outbound',
            'tableId' => 'manifest_authorizations_outbounds_table',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
        ];

        return $dataTable->render('pages.apps.manifest-authorizations.outbounds.list', compact('payload'));
    }

    public function edit(Outbound $outbound)
    {
        $payload = (object)[
            'title' => 'Manifest Authorizations Outbound',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'tableId' => 'manifest_authorizations_outbounds_table',
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name']),
            'warehouses' => Warehouse::all(['id', 'code']),
            'companies' => ClearanceCompany::get(['id', 'name']),
        ];

        return view('pages.apps.manifest-authorizations.outbounds.create', compact('payload', 'outbound'));
    }

    public function update(Request $request, Outbound $outbound)
    {
        if ($request->button_clicked == 'btn-delete') {
            $files = OutboundFile::where('outbound_id', $outbound->id)->get();
            foreach ($files as $file) {
                if (Storage::path($file->path)) {
                    Storage::delete($file->path);
                }
                $file->delete();
            }
            $outbound->delete();
        } elseif ($request->button_clicked == 'btn-revision')
            $outbound->update(['status_id' => OutboundStatus::NEED_REVISION]);
        else {
            $outbound->update(['status_id' => OutboundStatus::APPROVED]);
        }

        return response()->json(['message' => 'Modified successfully', 'status' => 200]);
    }

}
