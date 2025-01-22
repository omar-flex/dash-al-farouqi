<x-default-layout>
    @section('title')
        {{$payload->title}}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('operation-management.'.$payload->resource.'.create') }}
    @endsection
    <div class="card">
        <div class="card-body">
            @if(isset($enterRequest))
                <form action="{{ route('operation-management.enter_requests.update', $enterRequest) }}"
                      id="{{$payload->formId}}"
                      method="POST"
                      enctype="multipart/form-data">
                    @method('PUT')
                    @else
                        <form action="{{ route('operation-management.enter_requests.store') }}" method="POST"
                              id="{{$payload->formId}}"
                              enctype="multipart/form-data">
                            @endif
                            @csrf
                            <div class="row">
                                <div class="col-md-2 mb-7">
                                    <div class="d-flex justify-content-between">
                                        <label class="required fw-semibold fs-6 mb-2">Customer</label>
                                        @can('add_customers')
                                            <a class="cursor-pointer" id="add_customer">
                                                <i class="fa-sharp-duotone fa-solid fa-user-plus fa-sm"></i>
                                                Add Customer
                                            </a>
                                        @endcanany
                                    </div>
                                    <select name="customer_id" class="form-select form-select-solid-bg mb-2"
                                            id="customers"
                                            data-control="select2" data-placeholder="Select an Customer">
                                        <option></option>
                                        @foreach($payload->customers as $customer )
                                            <option value="{{$customer->id}}"
                                                    @if(isset($enterRequest) && $enterRequest->customer_id == $customer->id) selected @endif>
                                                {{$customer->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Manifest In Bound Number</label>
                                    <input type="text" name="manifest_bound_number"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Manifest In Bound Number"
                                           @isset($enterRequest) value="{{ $enterRequest->manifest_bound_number }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Manifest Type Number</label>
                                    <input type="text" name="manifest_type_number"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Manifest type number"
                                           @isset($enterRequest) value="{{ $enterRequest->manifest_type_number }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2"> Custom Entry Center</label>
                                    <input type="text" name="customs_entry_center"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Custom entry center"
                                           @isset($enterRequest) value="{{ $enterRequest->customs_entry_center }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Manifest Year</label>
                                    <input type="text" name="manifest_year"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Manifest Year"
                                           @isset($enterRequest) value="{{ $enterRequest->manifest_year }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Manifest Date</label>
                                    <input type="date" name="manifest_date" id="manifest_date"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Manifest Date"
                                           @isset($enterRequest) value="{{ $enterRequest->manifest_date }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Organize Center</label>
                                    <input type="text" name="organize_center"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Organize Center"
                                           @isset($enterRequest) value="{{ $enterRequest->organize_center }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Quantity of Car</label>
                                    <input type="number" step="any" name="quantity_car" min="0"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Quantity of Car"
                                           @isset($enterRequest) value="{{ $enterRequest->quantity_car }}" @endisset>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2"> General description Goods</label>
                                    <textarea class="form-control form-control-solid-bg mb-2"
                                              name="general_description_goods" style="min-height: 30px"
                                              placeholder="General description Goods">@isset($enterRequest)
                                            {{ $enterRequest->general_description_goods }}
                                        @endisset</textarea>
                                </div>
                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Quantity of Packages</label>
                                    <input type="number" step="any" name="quantity_packages" min="0"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Quantity of Packages"
                                           @isset($enterRequest) value="{{ $enterRequest->quantity_packages }}" @endisset>
                                </div>

                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Total cost</label>
                                    <input type="number" step="any" name="total_cost" min="0"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="Total cost"
                                           @isset($enterRequest) value="{{ $enterRequest->total_cost }}" @endisset>
                                </div>

                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Country</label>
                                    <div class="form-check form-check-custom form-check-solid me-10">
                                        <input class="form-check-input mx-2" type="checkbox" value="1"
                                               id="countryCheckBox"
                                               @if(!(isset($enterRequest) && $enterRequest->country_id)) checked @endif />
                                        <select name="country_id" class="form-select form-select-solid-bg mb-2"
                                                id="countries"
                                                @if(!(isset($enterRequest) && $enterRequest->country_id)) disabled
                                                @endif
                                                data-control="select2" data-placeholder="Multiple Countries">
                                            <option></option>
                                            @foreach($payload->countries as $country )
                                                <option value="{{$country->id}}"
                                                        @if(isset($enterRequest) && $enterRequest->country_id == $country->id) selected @endif>
                                                    {{$country->name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Gross weight</label>
                                    <div class="input-group  mb-5">
                                        <input type="number" step="any" name="gross_weight" min="0"
                                               class="form-control form-control-solid-bg "
                                               placeholder="Gross weight"
                                               @isset($enterRequest) value="{{ $enterRequest->gross_weight }}" @endisset>
                                        <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                                    </div>
                                </div>


                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Net weight</label>
                                    <div class="input-group  mb-5">
                                        <input type="number" step="any" name="net_weight" min="0"
                                               class="form-control form-control-solid-bg"
                                               placeholder="Net weight"
                                               @isset($enterRequest) value="{{ $enterRequest->net_weight }}" @endisset>
                                        <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                                    </div>

                                </div>

                                <div class="col-md-2 mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">CPM per BL (bill of loading)</label>
                                    <input type="number" step="any" name="cpm" min="0"
                                           class="form-control form-control-solid-bg mb-2"
                                           placeholder="CPM per BL(bill of loading)"
                                           @isset($enterRequest) value="{{ $enterRequest->cpm }}" @endisset>
                                </div>
                                @isset($enterRequest)
                                    <div class="col-md-2 mb-7">
                                        <label class="fw-semibold fs-6 mb-2">Cpm Calculated</label>
                                        <input type="number" step="any" min="0" disabled
                                               class="form-control form-control-solid-bg mb-2"
                                               placeholder="Cpm Calculated" value="{{ $enterRequest->cpm_calculated }}">
                                    </div>
                                    <div class="col-md-2 mb-7">
                                        <label class="fw-semibold fs-6 mb-2">Cpm Result</label>
                                        <input type="number" step="any" min="0" disabled
                                               class="form-control form-control-solid-bg mb-2"
                                               placeholder="Cpm Result" value="{{ $enterRequest->cpm_result }}">
                                    </div>
                                @endisset
                            </div>


                            <div class="col-md-12 form-group">
                                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                       value="Submitted"
                                       id="btn-submit">
                                <input type="submit" class="btn btn-light-warning btn-sm float-end"
                                       value="Save as Draft"
                                       id="btn-draft">
                            </div>

                        </form>
                </form>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function () {
                    let clickedButton = null;

                    $('input[type="submit"]').click(function () {
                        clickedButton = $(this).attr('id');
                    });


                    $('#formPartner').submit(function (e) {
                        $(".span_error").each(function () {
                            $(this).remove()
                        });
                        e.preventDefault();
                        $("#btn-submit,#btn-save").prop("disabled", false)
                        var form = $(this);
                        let formData = new FormData(this);
                        if (clickedButton) {
                            formData.append('button_clicked', clickedButton);
                        }
                        var url = form.attr('action');
                        $.ajax({
                            type: "POST",
                            url: url,
                            data: formData,
                            dataType: "json",
                            contentType: false,
                            cache: false,
                            processData: false,
                            success: function (data) {
                                if (data.status === 422) {
                                    $("#btn-submit,#btn-save").prop("disabled", false)
                                    $.each(data.errors, function (index, value) {
                                        var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                        var parentWithColMd = $('[name="' + index + '"]').closest('[class*="col-md-"]');
                                        if (parentWithColMd.length) {
                                            parentWithColMd.append(error);
                                        } else {
                                            $('[name="' + index + '"]').parent().append(error);
                                        }
                                    });
                                    toastr.error('Oops,there were an errors...');
                                } else {
                                    toastr.success(data.message);
                                    window.location.href = '/operation-management/enter_requests/' + data.enter_request_id + '/edit';
                                }
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                $("#btn-submit,#btn-save").prop("disabled", false)
                                toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                            }
                        });

                    });
                    $('#customers,#countries').select2();
                    @can('add_customers')
                    $('#add_customer').on('click', function () {
                        $.ajax({
                            url: '{{route('customers.create')}}',
                            method: 'get',
                            success: function (data) {
                                $('#modal-body').html(data);
                                $('#modal-title').text('Add Customer');
                                $('#modal').modal('show');
                                $('#formCustomer').submit(function (e) {
                                    e.preventDefault();
                                    $(".span_error").each(function () {
                                        $(this).remove()
                                    });
                                    $('#error').empty()
                                    $("#btn-submit").prop("disabled", true)
                                    var form = $(this);
                                    var url = form.attr('action');
                                    $.ajax({
                                        type: "POST",
                                        url: url,
                                        data: new FormData(this),
                                        dataType: "json",
                                        contentType: false,
                                        cache: false,
                                        processData: false,
                                        success: function (data) {
                                            if (data.status === 422) {
                                                $("#btn-submit").prop("disabled", false)
                                                $.each(data.errors, function (index, value) {
                                                    var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                                    if (index.split('.').length > 1) {
                                                        $('#error').last().append(error)
                                                    } else {
                                                        let input = $('[name="' + index + '"]').parent().last()
                                                        if (input.length > 0) {
                                                            input.append(error)
                                                        } else {
                                                            $('#error').append(error)
                                                        }
                                                    }
                                                });
                                                toastr.error('Oops,there were an errors...');
                                            } else {
                                                toastr.success('add Successfully');
                                                $('#customers option').remove();
                                                $('#customers').append("<option> </option>");
                                                $.each(data, function (index, value) {
                                                    $('#customers').append("<option value='" + value.id + "'>" + value.name + "</option>");
                                                });
                                                $('#modal').modal('hide');
                                                $('#modal-body').empty()

                                            }
                                        },
                                        error: function (xhr, ajaxOptions, thrownError) {
                                            $("#btn-submit").prop("disabled", false)
                                            toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                                        }
                                    });

                                });
                            },
                            error: function (xhr) {
                                $("#btn-submit").prop("disabled", false)
                                toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                            }
                        });
                    });
                    @endcan
                    $('#countryCheckBox').change(function () {
                        let countries = $('#countries')
                        if ($(this).is(':checked')) {
                            countries.prop("disabled", true)
                            countries.val('').trigger('change');
                        } else {
                            countries.prop("disabled", false)
                        }
                    });
                });
            </script>
        @endpush
    </div>
</x-default-layout>
