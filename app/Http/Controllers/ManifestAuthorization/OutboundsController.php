<?php

namespace App\Http\Controllers\ManifestAuthorization;

use AllowDynamicProperties;
use App\DataTables\ManifestAuthorization\OutboundsDataTable;
use App\Http\Controllers\Controller;
use App\Models\ClearanceCompany;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Outbound;
use App\Models\OutboundStatus;
use App\Models\Warehouse;
use App\Services\Inventory\OutboundInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

#[AllowDynamicProperties] class OutboundsController extends Controller
{
    public function __construct(private readonly OutboundInventoryService $outboundInventoryService)
    {
        $this->formId = 'manifestAuthorizationOutbound';
        $this->resource = 'outbounds';
    }

    public function index(OutboundsDataTable $dataTable)
    {
        $payload = (object) [
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
        $payload = (object) [
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
        $action = $request->validate([
            'button_clicked' => ['required', 'in:btn-approval,btn-revision,btn-delete'],
        ])['button_clicked'];

        $paths = DB::transaction(function () use ($action, $outbound): array {
            $lockedOutbound = Outbound::query()
                ->whereKey($outbound->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedOutbound->status_id !== OutboundStatus::AUTHORIZATION) {
                throw ValidationException::withMessages([
                    'outbound' => 'Only an outbound awaiting manifest authorization can be changed here.',
                ]);
            }

            if ($action === 'btn-delete') {
                return $this->outboundInventoryService->deleteOutbound($lockedOutbound);
            }

            $lockedOutbound->update([
                'status_id' => $action === 'btn-revision'
                    ? OutboundStatus::NEED_REVISION
                    : OutboundStatus::APPROVED,
            ]);

            return [];
        });

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        return response()->json(['message' => 'Modified successfully', 'status' => 200]);
    }
}
