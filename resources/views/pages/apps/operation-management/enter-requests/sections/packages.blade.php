@php
    $is_cars = $enterRequest->cars()->count() > 0
@endphp
<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.enter_requests.cars.store',$enterRequest->id) }}" method="POST"
          id="formCaresddd"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Product item with Locations</h2>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-plus fs-2"></i>
                    Add Item
                </button>
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required ">Product</label>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-6 mb-2"> Lot number </label>
                </div>
                <div class="col-md-1 mb-3">
                    <label class="fw-semibold fs-6 mb-2"> UoM </label>
                </div>
                <div class="col-md-1 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required">Qty</label>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required">Wh</label>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required">Location</label>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="fw-semibold fs-6 mb-2 required">Line</label>
                </div>

            </div>

        </div>
    </form>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
           /* $('#formCares').submit(function (e) {
                $(".span_error").each(function () {
                    $(this).remove()
                });
                e.preventDefault();
                $("#btn-submit").prop("disabled", false)
                var form = $(this);
                let formData = new FormData(this);
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
                            $("#btn-submit").prop("disabled", false)
                            $.each(data.errors, function (index, value) {
                                var error = '<span class="text-danger span_error"> ' + value + '</span>'
                                let repeaterList = $("[data-repeater-list]");
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
                            $('#div_cars').empty().append(data.html)
                            $('#btn-submit').addClass('d-none')
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        $("#btn-submit").prop("disabled", false)
                        toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                    }
                });

            });*/
        });
    </script>
@endpush
