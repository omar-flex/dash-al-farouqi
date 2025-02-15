<style>
    .dz-progress {
        display: none;
    }
</style>
@if(isset($outbound))
    <form action="{{ route('operation-management.outbounds.update', $outbound) }}"
          id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('operation-management.outbounds.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Bound Number / Enter Request</label>
                        <select name="enter_request_id" class="form-select form-select-solid-bg mb-2"
                                id="outbounds"
                                data-control="select2" data-placeholder="Select an Bound Number">
                            <option></option>
                            @foreach($payload->bound_numbers as $bound_number )
                                <option value="{{$bound_number->id}}"
                                        @if(isset($outbound) && $outbound->enter_request_id == $bound_number->id) selected @endif>
                                    {{$bound_number->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Manifest Out Bound Number</label>
                        <input type="text" name="manifest_outbound_number"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Manifest Out bound Number"
                               @isset($outbound) value="{{ $outbound->manifest_outbound_number }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Manifest Type Number</label>
                        <input type="text" name="manifest_type_number"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Manifest type number"
                               @isset($outbound) value="{{ $outbound->manifest_type_number }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2"> Custom Entry Center</label>
                        <input type="text" name="customs_entry_center"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Custom Entry Center"
                               @isset($outbound) value="{{ $outbound->customs_entry_center }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Manifest Year</label>
                        <input type="text" name="manifest_year"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Manifest Year"
                               @isset($outbound) value="{{ $outbound->manifest_year }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Manifest Date</label>
                        <input type="date" name="manifest_date" id="manifest_date"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Manifest Date"
                               @isset($outbound) value="{{ $outbound->manifest_date }}" @endisset>
                    </div>

                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Quantity of Car</label>
                        <input type="number" step="any" min="0"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Quantity of Car"
                               name="quantity_car"
                               @isset($outbound) value="{{ $outbound->quantity_car }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Quantity of Packages</label>
                        <input type="number" step="any" name="quantity_packages" min="0"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Quantity of Packages"
                               @isset($outbound) value="{{ $outbound->quantity_packages }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Total cost</label>
                        <input type="number" step="any" name="total_cost" min="0"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Total cost"
                               @isset($outbound) value="{{ $outbound->total_cost }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Gross weight</label>
                        <div class="input-group  mb-5">
                            <input type="number" step="any" name="gross_weight" min="0"
                                   class="form-control form-control-solid-bg "
                                   placeholder="Gross weight"
                                   @isset($outbound) value="{{ $outbound->gross_weight }}" @endisset>
                            <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Net weight</label>
                        <div class="input-group  mb-5">
                            <input type="number" step="any" name="net_weight" min="0"
                                   class="form-control form-control-solid-bg"
                                   placeholder="Net weight"
                                   @isset($outbound) value="{{ $outbound->net_weight }}" @endisset>
                            <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                        </div>

                    </div>

                    <div class="col-md-3 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Country</label>
                        <div class="form-check form-check-custom form-check-solid ">
                            <select name="country_id" class="form-select form-select-solid-bg mb-2"
                                    id="countries"
                                    @if(!(isset($outbound) && $outbound->country_id)) disabled
                                    @endif
                                    data-control="select2" data-placeholder="Multiple Countries">
                                <option></option>
                                @foreach($payload->countries as $country )
                                    <option value="{{$country->id}}"
                                            @if(isset($outbound) && $outbound->country_id == $country->id) selected @endif>
                                        {{$country->name}}
                                    </option>
                                @endforeach
                            </select>
                            <input class="form-check-input mx-2" type="checkbox" value="1"
                                   id="countryCheckBox"
                                   @if(!(isset($outbound) && $outbound->country_id)) checked @endif />
                        </div>

                    </div>

                    <div class="col-md-2 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">CPM per BL</label>
                        <input type="number" step="any" name="cpm" min="0"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="CPM"
                               @isset($outbound) value="{{ $outbound->cpm }}" @endisset>
                    </div>


                    @isset($outbound)
                        <div class="col-md-3 mb-7">
                            <label class="fw-semibold fs-6 mb-2">Cpm Calculated</label>
                            <input type="number" step="any" min="0" disabled
                                   class="form-control form-control-solid-bg mb-2"
                                   placeholder="Cpm Calculated" value="{{ $outbound->cpm_calculated }}">
                        </div>
                        <div class="col-md-3 mb-7">
                            <label class="fw-semibold fs-6 mb-2">Cpm Result</label>
                            <input type="number" step="any" min="0" disabled
                                   class="form-control form-control-solid-bg mb-2"
                                   placeholder="Cpm Result" value="{{ $outbound->cpm_result }}">
                        </div>
                    @endisset

                    <div class="col-md-2 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Date</label>
                        <input type="date" name="date" id="date"
                               class="form-control form-control-solid-bg mb-2"
                               placeholder="Date"
                               @isset($outbound) value="{{ $outbound->date }}" @endisset>
                    </div>
                    <div class="col-md-2 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">WH</label>
                        <select name="warehouse_id" class="form-select form-select-solid-bg mb-2"
                                id="warehouses"
                                data-control="select2" data-placeholder="WH">
                            <option></option>
                            @foreach($payload->warehouses as $warehouse)
                                <option value="{{$warehouse->id}}"
                                        @if(isset($outbound) && $outbound->warehouse_id == $warehouse->id) selected @endif>
                                    {{$warehouse->code}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(!isset($outbound))
                        <div class="col-md-8">

                        </div>
                    @endisset
                    <div class="col-md-6 mb-7">
                        <label class="required fw-semibold fs-6 mb-2"> General description Goods</label>
                        <textarea class="form-control form-control-solid-bg mb-2"
                                  name="general_description_goods" style="min-height: 30px"
                                  placeholder="General description Goods">@isset($outbound){{ $outbound->general_description_goods }}@endisset</textarea>
                    </div>
                    <div class="col-md-6 mb-7">
                        <label class="fw-semibold fs-6 mb-2"> Notes</label>
                        <textarea class="form-control form-control-solid-bg mb-2"
                                  name="notes" style="min-height: 30px"
                                  placeholder="Notes">@isset($outbound){{ $outbound->notes }}@endisset</textarea>
                    </div>

                    <div class="col-md-8 mb-7">
                        <label class="fw-semibold fs-6 mb-2 required">Attached</label>
                        @if(isset($outbound))
                            @foreach($outbound->files as $file)
                                <div class="d-flex align-items-center col-md-4 mb-7 border-1 border-dashed p-2"
                                     id="file_{{$file->id}}">
                                    <div class="symbol symbol-30px me-5">
                                        <img alt="Icon" src="{{$file->getIcon()}}">
                                    </div>
                                    <div class="fw-semibold">
                                        <a class="fs-6 fw-bold text-gray-900 text-hover-primary filename"
                                           target="_blank"
                                           href="{{$file->getUrl()}}" id="filename_{{$file->id}}"
                                           title="{{$file->filename}}">{{\Illuminate\Support\Str::limit($file->filename,20)}}</a>
                                    </div>

                                    <a class="btn btn-clean btn-sm btn-icon btn-icon-danger btn-active-light-danger ms-auto file_remove_btn"
                                       id="{{$file->id}}" title="File Delete">
                                        <i class="fas fa-trash-alt fa-xl"></i>
                                    </a>
                                </div>
                            @endforeach
                        @endif
                        <div class="fv-row mb-2">
                            <input type="hidden" name="files">
                            <div class="dropzone" id="dropzone">
                                <div class="dz-message needsclick">
                                     <i class="ki-duotone ki-file-up text-primary fs-3x">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="ms-4">
                                        <h3 class="fs-5 fw-bold text-gray-900 mb-1">
                                            Drop files here or click to upload.
                                        </h3>
                                        <span class="fs-7 fw-semi bold text-gray-500">Upload files</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted fs-7">Set Manifest.</div>

                    </div>

                    <div class="col-md-12 form-group">

                        @if(isset($outbound))
                            @if($outbound->status_id == \App\Models\EnterRequestStatus::DRAFT)
                                <input type="submit" class="btn btn-light-warning btn-sm float-end"
                                       value="Save as Draft"
                                       id="btn-draft">
                                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                       value="Submitted"
                                       id="btn-submit">
                            @else
                                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                       value="Save"
                                       id="btn-submit">
                            @endif
                        @else
                            <input type="submit" class="btn btn-light-warning btn-sm float-end"
                                   value="Save as Draft"
                                   id="btn-draft">
                            <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                   value="Submitted"
                                   id="btn-submit">
                        @endif
                    </div>
                </div>


            </form>

    </form>

    <script>
        $(document).ready(function () {
            let clickedButton = null;

            $('input[type="submit"]').click(function () {
                clickedButton = $(this).attr('id');
            });

            $('#outboundForm').submit(function (e) {
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
                myDropzone.files.forEach(function (file, index) {
                    formData.append('files[' + index + ']', file);
                });

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

            $('#outbounds,#countries,#warehouses').select2({
                dropdownParent: $('#modal'),
            });

            $('#countryCheckBox').change(function () {
                let countries = $('#countries')
                if ($(this).is(':checked')) {
                    countries.prop("disabled", true)
                    countries.val('').trigger('change');
                } else {
                    countries.prop("disabled", false)
                }
            });


            let myDropzone = new Dropzone("#dropzone", {
                url: "#",
                acceptedFiles: "application/pdf,image/*",
                autoProcessQueue: false,
                uploadMultiple: true,
                paramName: "images",
                maxFiles: 10,
                maxFilesize: 10,
                addRemoveLinks: true,
            });

            $(document).on('click', '.file_remove_btn', function () {
                var id = $(this).attr('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function (result) {
                    if (result.value) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            url: '/operation-management/outbounds/files/' + id,
                            method: 'delete',
                            success: function (data) {
                                $('#file_' + id).fadeOut('slow', function () {
                                    $('#file_' + id).remove();
                                });
                                toastr.success('Your File has been removed');
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                            }
                        });
                    }
                });

            });
        });
    </script>


