@php
    $is_cars = $enterRequest->cars()->count() > 0
@endphp
<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.enter_requests.cars.store',$enterRequest->id) }}" method="POST"
          id="formCares"
          enctype="multipart/form-data">

        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Cares Plate Numbers</h2>
            </div>
            <div class="card-toolbar">
                @if(!$is_cars)
                    <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                           id="btn-submit">
                @endif
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-md-4 mb-3">
                    <label class="fw-semibold fs-6 mb-2  @if(!$is_cars) required @endif">Numbers</label>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-semibold fs-6 mb-2 @if(!$is_cars) required @endif">Seal Numbers</label>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-semibold fs-6 mb-2 @if(!$is_cars) required @endif"> Status </label>
                </div>
            </div>

            @csrf
            <div data-repeater-list id="div_cars">
                @if($enterRequest->cars()->count() > 0 )
                    @include('pages.apps.operation-management.enter-requests.sections._cares-list')
                @else
                    @for($i = 1; $i <= $enterRequest->quantity_car; $i++)
                        <div class="row px-3">
                            <div class="col-md-4 mb-2">
                                <input class="form-control form-control-solid-bg form-control-sm"
                                       placeholder="Number" type="text" name="numbers[]"/>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input class="form-control form-control-solid-bg form-control-sm"
                                       placeholder="Seal Numbers" type="text" name="seal_numbers[]"/>
                            </div>
                            <div class="col-md-4 mb-2">
                                <select name="statuses[]"
                                        class="form-select form-select-solid form-select-sm mb-2 statuses"
                                        data-control="select2" data-placeholder="Select an Status">
                                    <option value="1">Valid</option>
                                    <option value="0">Invalid</option>
                                </select>
                            </div>
                        </div>
                    @endfor
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#formCares').submit(function (e) {
                e.preventDefault();
                $(".span_error").each(function () {
                    $(this).remove()
                });
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

            });
        });
    </script>
@endpush
