<form action="{{ route('manifest-authorizations.inbounds.update', $inbound) }}"
      id="{{$payload->formId}}"
      method="POST"
      enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Bound Number</label>
            <a class="input-group input-group-sm mb-5 fw-bold text-primary" target="_blank"
               href="{{route('operation-management.enter_requests.show',$inbound->id)}}">{{$inbound->bound_number }} </a>
        </div>
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Customer</label>
            <div
                class="input-group input-group-sm mb-5 fw-bold ">{{$inbound->Customer->name }} </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Company</label>
            <div
                class="input-group input-group-sm mb-5 fw-bold ">{{$inbound->Company->name }} </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Manifest Date</label>
            <div
                class="input-group input-group-sm mb-5 fw-bold ">{{$inbound->manifest_date ? \Illuminate\Support\Carbon::parse($inbound->manifest_date)->format('d M Y') : '----'}} </div>
        </div>

    </div>
    <label class="fw-bolder mb-2 fs-3 text-info">Manifest Info</label>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Quantity of Packages</label>
            <input disabled class="form-control form-control-solid-bg form-control-sm mb-2"
                   value="{{number_format($inbound->quantity_packages) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Total Cost</label>
            <input disabled class="form-control form-control-solid-bg form-control-sm mb-2"
                   value="{{number_format($inbound->total_cost) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class=" fw-semibold  mb-2">Gross weight</label>
            <div class="input-group input-group-sm  mb-5">
                <input class="form-control form-control-solid-bg form-control-sm" disabled
                       value="{{number_format($inbound->gross_weight)  }}">
                <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class=" fw-semibold  mb-2">Net weight</label>
            <div class="input-group input-group-sm  mb-5">
                <input class="form-control form-control-solid-bg form-control-sm" disabled
                       value="{{number_format($inbound->net_weight)  }}">
                <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
            </div>
        </div>
    </div>

    <label class="fw-bolder mb-2 fs-3 text-danger">System Manifest Info</label>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Quantity of Packages</label>
            <input disabled class="form-control form-control-solid-bg form-control-sm mb-2"
                   value="{{number_format($inbound->WarehouseItems()->sum('quantity')) }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class="fw-semibold mb-2">Total Cost</label>
            <input disabled class="form-control form-control-solid-bg form-control-sm mb-2"
                   value="{{number_format($inbound->WarehouseItems->sum('custom_value'))  }}">
        </div>
        <div class="col-md-3 mb-3">
            <label class=" fw-semibold  mb-2">Gross weight</label>
            <div class="input-group input-group-sm  mb-5">
                <input class="form-control form-control-solid-bg form-control-sm" disabled
                       value="{{number_format($inbound->WarehouseItems->sum('gross_weight'))  }}">
                <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class=" fw-semibold  mb-2">Net weight</label>
            <div class="input-group input-group-sm  mb-5">
                <input class="form-control form-control-solid-bg form-control-sm" disabled
                       value="{{number_format($inbound->WarehouseItems->sum('net_weight'))  }}">
                <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="required fw-semibold mb-2"> Invoicing Date </label>
            <input type="date" name="invoicing_date"
                   class="form-control form-control-solid-bg mb-2"
                   placeholder="Date">
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 form-group">
            <input type="submit" class="btn btn-light-success btn-sm float-end" value="Approval" id="btn-approval">
            <input type="submit" class="btn btn-light-info btn-sm float-end mx-2" value="Needs Revision"
                   id="btn-revision">
            <input type="submit" class="btn btn-light-danger btn-sm float-end mx-2" value="Delete" id="btn-delete">
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        let clickedButton = null;

        $('input[type="submit"]').click(function () {
            clickedButton = $(this).attr('id');
        });

        $('#manifestAuthorizationInbound').submit(function (e) {
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
                        $('#modal').modal('hide');
                        $('#modal-body').empty()
                        window.LaravelDataTables['{{$payload->tableId}}'].ajax.reload();
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    $("#btn-submit,#btn-save").prop("disabled", false)
                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                }
            });

        });
    });
</script>


