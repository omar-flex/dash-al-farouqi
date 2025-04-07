<?php

namespace App\Http\Controllers;

use AllowDynamicProperties;
use App\DataTables\CustomersDataTable;
use App\Http\Requests\CompanyRequest;
use App\Http\Requests\CustomerRequest;
use App\Models\ClearanceCompany;
use App\Models\Customer;

#[AllowDynamicProperties] class CompanyController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formCompany';
        $this->resource = 'companies';
    }

    public function index(CustomersDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Clearance Companies',
            'sub_title' => 'Clearance Company',
            'tableId' => 'customers-table',
            'formId' => $this->formId,
            'resource' => $this->resource,
        ];

        return $dataTable->render('pages.apps.customers.list', compact('payload'));
    }

    public function create()
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $payload = (object)[
            'formId' => $this->formId,
            'enter_request' => request('enter_request', 0),
        ];

        return view('pages.apps.companies.create', compact('payload'));
    }

    public function store(CompanyRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->only('phone', 'number');

        $data['name'] = $request->company_name;

        $company = ClearanceCompany::create($data);

        if (request('enter_request')) {
            $companies = ClearanceCompany::get(['id', 'name']);
            return response()->json(['companies' => $companies, 'company_id' => $company->id, 'status' => 200]);
        }

        return response()->json(['message' => 'Stored Successfully', 'status' => 200]);

    }

    public function edit(Customer $customer)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $payload = (object)['formId' => $this->formId];

        return view('pages.apps.customers.create', compact('payload', 'customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        if (!auth()->user()->can('edit_' . $this->resource))
            abort(403);

        $data = $request->only('email', 'company_name', 'phone', 'national_number', 'tax_number');

        $data['name'] = $request->customer_name;

        $customer->update($data);

        return response()->json(['message' => 'Updated Successfully', 'status' => 200]);
    }

    public function destroy(Customer $customer)
    {
        if (!auth()->user()->can('delete_' . $this->resource))
            abort(403);

        if (($customer->Inbounds->count() + $customer->Outbounds->count()) > 0)
            return response()->json([
                'exception' => 'Cannot Delete This Customer'
            ], 500);
        else
            $customer->delete();
    }

}
