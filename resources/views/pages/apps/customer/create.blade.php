@if(isset($customer))
    <form action="{{ route('customers.update', $customer) }}" id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('customers.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Customer name</label>
                        <input type="text" name="customer_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Customer name"
                               @isset($customer) value="{{ $customer->name }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" name="email" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Email"
                               @isset($customer) value="{{ $customer->email }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Phone"
                               @isset($customer) value="{{ $customer->phone }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="fw-semibold fs-6 mb-2">Company name</label>
                        <input type="text" name="company_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Company name"
                               @isset($customer) value="{{ $customer->company_name }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class=" fw-semibold fs-6 mb-2">  National CR number</label>
                        <input type="text" name="national_number" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="National CR number"
                               @isset($customer) value="{{ $customer->national_number }}" @endisset>
                    </div>

                    <div class="col-md-4 mb-7">
                        <label class=" fw-semibold fs-6 mb-2">  Tax Number </label>
                        <input type="text" name="tax_number" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Tax Number"
                               @isset($customer) value="{{ $customer->tax_number }}" @endisset>
                    </div>


                </div>

                <div class="col-md-12 form-group">
                    <input type="submit" class="btn btn-light-success btn-sm float-end" value="Submit"
                           id="btn-submit">
                </div>

            </form>
    </form>

    <script>
        $(document).ready(function () {
            $('#statuses').select2({
                dropdownParent: $('#modal'),
            });
        });
    </script>
