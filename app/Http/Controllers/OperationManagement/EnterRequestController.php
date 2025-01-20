<?php

namespace App\Http\Controllers\OperationManagement;


use App\DataTables\OperationManagement\EnterRequestsDataTable;
use App\DataTables\WarehouseManagement\WarehousesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationManagement\EnterCreateRequest;
use App\Http\Requests\WarehouseManagement\WarehouseRequest;
use App\Models\Country;
use App\Models\Customer;
use App\Models\EnterRequest;
use App\Models\EnterRequestStatus;
use App\Models\Warehouse;


class EnterRequestController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formPartner';
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

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Enter Request Create',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name'])
        ];

        return view('pages.apps.operation-management.enter-requests.create', compact('payload'));
    }

    public function store(EnterCreateRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year . '/' . $request->manifest_type_number . '/' . $request->manifest_bound_number;

        if ($request->button_clicked == 'btn-draft') {
            $data['status_id'] = EnterRequestStatus::DRAFT;
            $message = 'Added Draft Successfully';
        } elseif ($request->button_clicked == 'btn-submit') {
            $data['status_id'] = EnterRequestStatus::CONFIRMING;
            $message = 'Added Successfully';
        }

        $data['cpm_result'] = $request->cpm;

        $cpm_calculated = $this->cpmCalculate($request->gross_weight);
        $data['cpm_calculated'] = $cpm_calculated;
        if ($cpm_calculated > $request->cpm) {
            $data['cpm_result'] = $cpm_calculated;
        }

        $enterRequest = EnterRequest::create($data);

        return response()->json(['message' => $message, 'enter_request_id' => $enterRequest->id, 'status' => 200]);
    }

    public function edit(EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Enter Request Edit',
            'formId' => $this->formId,
            'resource' => $this->resource,
            'customers' => Customer::get(['id', 'name']),
            'countries' => Country::all(['id', 'name'])
        ];

        return view('pages.apps.operation-management.enter-requests.create', compact('payload', 'enterRequest'));
    }

    public function update(EnterCreateRequest $request, EnterRequest $enterRequest)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data = $request->validated();

        $data['bound_number'] = $request->manifest_year . '/' . $request->manifest_type_number . '/' . $request->manifest_bound_number;

        if ($request->button_clicked == 'btn-draft') {
            $data['status_id'] = EnterRequestStatus::DRAFT;
            $message = 'Update Draft Successfully';
        } elseif ($request->button_clicked == 'btn-submit') {
            $data['status_id'] = EnterRequestStatus::CONFIRMING;
            $message = 'Update Successfully';
        }

        $data['cpm_result'] = $request->cpm;
        $data['country_id'] = $request->country_id;

        $cpm_calculated = $this->cpmCalculate($request->gross_weight);
        $data['cpm_calculated'] = $cpm_calculated;
        if ($cpm_calculated > $request->cpm) {
            $data['cpm_result'] = $cpm_calculated;
        }

        $enterRequest->update($data);

        return response()->json(['message' => $message ?? null, 'enter_request_id' => $enterRequest->id, 'status' => 200]);
    }

    public function destroy(Warehouse $warehouse)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        $warehouse->delete();
    }

}
