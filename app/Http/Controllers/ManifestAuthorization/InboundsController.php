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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

#[AllowDynamicProperties] class InboundsController extends Controller
{
    public function __construct()
    {
        $this->formId = 'manifestAuthorizationInbound';
        $this->resource = 'inbounds';
    }

    public function index(InboundsDataTable $dataTable)
    {
        $payload = (object) [
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
        $payload = (object) [
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
        if (! in_array($request->button_clicked, ['btn-approval', 'btn-revision', 'btn-delete'], true)) {
            throw ValidationException::withMessages([
                'button_clicked' => 'An approval, revision, or delete action is required.',
            ]);
        }

        $paths = DB::transaction(function () use ($request, $inbound): array {
            $lockedInbound = EnterRequest::query()
                ->whereKey($inbound->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedInbound->status_id !== EnterRequestStatus::AUTHORIZATION) {
                throw ValidationException::withMessages([
                    'inbound' => 'Only an inbound awaiting manifest authorization can be changed here.',
                ]);
            }

            if ($request->button_clicked === 'btn-delete') {
                $outboundCount = $lockedInbound->Outbounds()->count();
                if ($outboundCount > 0) {
                    throw ValidationException::withMessages([
                        'inbound' => "Cannot delete this inbound because it has $outboundCount outbound declaration(s).",
                    ]);
                }

                if ($lockedInbound->WarehouseItems()->exists()) {
                    throw ValidationException::withMessages([
                        'inbound' => 'Cannot delete this inbound while warehouse stock rows exist.',
                    ]);
                }

                $files = EnterRequestFile::query()
                    ->where('enter_request_id', $lockedInbound->id)
                    ->lockForUpdate()
                    ->get();
                $paths = $files->pluck('path')->filter()->values()->all();
                $files->each->delete();
                $lockedInbound->delete();

                return $paths;
            }

            $lockedInbound->update([
                'status_id' => $request->button_clicked === 'btn-revision'
                    ? EnterRequestStatus::NEED_REVISION
                    : EnterRequestStatus::APPROVED,
                'invoicing_date' => $request->button_clicked === 'btn-approval'
                    ? $request->invoicing_date
                    : $lockedInbound->invoicing_date,
            ]);

            return [];
        });

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        return response()->json(['message' => 'Modified successfully', 'status' => 200]);
    }
}
