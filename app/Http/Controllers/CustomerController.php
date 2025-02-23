<?php

namespace App\Http\Controllers;

use App\DataTables\CustomersDataTable;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;

class CustomerController extends Controller
{

    public function __construct()
    {
        $this->formId = 'formCustomer';
        $this->resource = 'customers';
    }

    public function index(CustomersDataTable $dataTable)
    {
        if (!auth()->user()->can('list_' . $this->resource))
            abort(403);

        $payload = (object)[
            'title' => 'Customers',
            'sub_title' => 'Customer',
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

        return view('pages.apps.customers.create', compact('payload'));
    }

    public function store(CustomerRequest $request)
    {
        if (!auth()->user()->can('add_' . $this->resource))
            abort(403);

        $data = $request->only('email', 'company_name', 'phone', 'national_number', 'tax_number');

        $data['name'] = $request->customer_name;

        Customer::create($data);

        if (request('enter_request'))
            return Customer::get(['id', 'name']);

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

        $customer->delete();
    }

}
