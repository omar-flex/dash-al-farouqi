<div class="card card-flush mb-6 mb-xl-9">
    <form action="{{ route('operation-management.outbounds.cars.store',$outbound->id) }}" method="POST"
          id="formCares"
          enctype="multipart/form-data">
        @csrf
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h2 class="mb-1"> Cares Plate Numbers</h2>
            </div>
            <div class="card-toolbar">
                    <input type="submit" class="btn btn-light-success btn-sm float-end mx-2" value="save"
                           id="btn-submit">
            </div>
        </div>
        <div class="card-body p-9 pt-4">
            <div class="row px-3">
                <div class="col-md-4 mb-3">
                    <label class="fw-semibold fs-6 mb-2  required">Numbers</label>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-semibold fs-6 mb-2 ">Seal Numbers</label>
                </div>
            </div>


            <div data-repeater-list id="div_cars">
                @if($outbound->cars()->count() > 0 )
                    @include('pages.apps.operation-management.outbounds.sections._cares-list')
                @else
                    @for($i = 1; $i <= $outbound->quantity_car; $i++)
                        <div class="row px-3">
                            <div class="col-md-4 mb-2">
                                <input class="form-control form-control-solid-bg form-control-sm"
                                       placeholder="Number" type="text" name="numbers[]"/>
                            </div>
                            <div class="col-md-4 mb-2">
                                <input class="form-control form-control-solid-bg form-control-sm"
                                       placeholder="Seal Numbers" type="text" name="seal_numbers[]"/>
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
                            location.reload(true);
                            //$('#div_cars').empty().append(data.html)
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
