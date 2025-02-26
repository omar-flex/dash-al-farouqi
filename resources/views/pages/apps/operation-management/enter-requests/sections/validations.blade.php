<style>
    #suggestions {
        position: sticky;
        top: calc(100% + 5px);
        z-index: 1000;
        width: 100%;
    }

    .span_error {
        font-size: 10px;
    }
</style>
<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.enter_requests.validations.store',$enterRequest->id) }}" method="POST"
          id="formValidations"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Validation Product Item</h2>
            </div>
            <div class="card-toolbar">
                @if($enterRequest->status_id != \App\Models\EnterRequestStatus::AUTHORIZATION)
                    <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                           id="btn-submit">
                    <input type="submit" class="btn btn-light-warning btn-sm float-end" value="Save as Draft"
                           id="btn-draft">
                @endif
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-2 mb-3">
                    <label class="fw-semibold fs-7 mb-3" title="Product"> Product </label>
                </div>
                <div class="col-2 mb-3">
                    <label class="fw-semibold fs-7 mb-2 " title="Lot number"> Barcode </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Batch Number"> BN </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Unit of Measure"> UoM </label>
                </div>
                <div class="col-1 mb-3">
                    <label class="fw-semibold fs-7 mb-2" title="Quantity">Quantity</label>
                </div>

                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 required" title="Fixed Cost">Fixed Cost</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 required" title="Gross Weight">Gross Weight</label>
                </div>
                <div class="col mb-3">
                    <label class="fw-semibold fs-7 mb-2 required" title="Net Weight">Net Weight</label>
                </div>

            </div>
            @include('pages.apps.operation-management.enter-requests.sections.validation_items')
        </div>
    </form>
</div>
@push('scripts')
    <script>
        $(document).ready(function () {

            let clickedButton = null;

            $('input[type="submit"]').click(function () {
                clickedButton = $(this).attr('id');
            });

            $('#formValidations').submit(function (e) {
                e.preventDefault();
                $(".span_error").each(function () {
                    $(this).remove()
                });
                $("#btn-submit,#btn-draft").prop("disabled", true)
                if (clickedButton === 'btn-submit') {
                    Swal.fire({
                        title: 'Are you sure?',
                        html: "<span> You won't be able to <span class='text-danger'> Manifest Authorization! </span> </span>",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then(function (result) {
                        if (result.value) {
                            {{-- if (sumQuantities() !== {{$enterRequest->quantity_packages}}){
                                toastr.error('Quantity Product (' + sumQuantities() + ') Must equal packages Count ({{$enterRequest->quantity_packages}})')
                                $("#btn-submit,#btn-draft").prop("disabled", false)
                                return false;
                            } --}}
                            let form = $("#formValidations");
                            let formData = new FormData(form[0]);
                            if (clickedButton) {
                                formData.append('button_clicked', clickedButton);
                            }
                            let url = form.attr('action');
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
                                        $("#btn-submit,#btn-draft").prop("disabled", false)
                                        $.each(data.errors, function (index, value) {
                                            let error = '<span class="text-danger span_error"> ' + value + '</span>'
                                            let repeaterList = $("[data-repeater-validations-list]");
                                            if (index.split('.').length > 1) {
                                                const parts = index.split('.');
                                                let line = parts[1];
                                                let name = parts[0] + '[]';
                                                repeaterList.children().eq(line).find('[name="' + name + '"]').parent().last().append(error)
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
                                        toastr.success(data.message);
                                        location.reload(true);

                                    }
                                },
                                error: function (xhr, ajaxOptions, thrownError) {
                                    $("#btn-submit,#btn-draft").prop("disabled", false)
                                    toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                                }
                            });
                        } else {
                            $("#btn-submit,#btn-draft").prop("disabled", false)
                        }
                    });
                } else {
                    let form = $(this);
                    let formData = new FormData(this);
                    if (clickedButton) {
                        formData.append('button_clicked', clickedButton);
                    }
                    let url = form.attr('action');

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
                                $("#btn-submit,#btn-draft").prop("disabled", false)
                                $.each(data.errors, function (index, value) {
                                    let error = '<span class="text-danger span_error"> ' + value + '</span>'
                                    let repeaterList = $("[data-repeater-validations-list]");
                                    if (index.split('.').length > 1) {
                                        const parts = index.split('.');
                                        let line = parts[1];
                                        let name = parts[0] + '[]';
                                        repeaterList.children().eq(line).find('[name="' + name + '"]').parent().last().append(error)
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
                                toastr.success(data.message);
                                location.reload(true);

                            }
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            $("#btn-submit,#btn-draft").prop("disabled", false)
                            toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                        }
                    });
                }


            });
        });
    </script>
@endpush
