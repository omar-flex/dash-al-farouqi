<x-default-layout>
    @section('title')
        {{$payload->title}}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('warehouse-management.'.$payload->resource.'.index') }}
    @endsection

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                           class="form-control form-control-solid w-250px ps-13"
                           placeholder="Search {{$payload->sub_title}}"
                           id="mySearchInput"/>
                </div>
            </div>
            <div class="card-toolbar min-w-900px">
                <div class="d-flex justify-content-end gap-3 w-100" data-kt-user-table-toolbar="base">
                    <div class="w-25">
                        <select class="form-select form-select-solid form-select-sm mb-2 " id="warehouses-filter"
                                data-control="select2" data-placeholder="Select an Warehouse" data-allow-clear="true">
                            <option></option>
                            @foreach($payload->warehouses as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    @can('add_'.$payload->resource)
                        <div class="w-15">
                            <a class="btn btn-light-primary btn-sm" id="add">
                                {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                Add {{$payload->sub_title}}
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
        </div>
    </div>


    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            @can('add_'.$payload->resource)
            addModal('add', '{{ route('warehouse-management.'.$payload->resource.'.create')}}', 'Add {{$payload->sub_title}}', '{{$payload->formId}}', '{{$payload->tableId}}');
            @endcan
            @can('edit_'.$payload->resource)
            editModal('edit_btn', 'warehouse-management/{{$payload->resource}}', 'Edit {{$payload->sub_title}}', '{{$payload->formId}}', '{{$payload->tableId}}')
            @endcan
            @can('delete_'.$payload->resource)
            remove('remove_btn', 'warehouse-management/{{$payload->resource}}', '{{$payload->tableId}}', '{{ csrf_token() }}')
            @endcan
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['{{$payload->tableId}}'].search(this.value).draw();
            });

            $(document).ready(function () {
                $(document).on('click', '.line_btn', function () {
                    let id = $(this).attr('id');
                    let url = '/warehouse-management/locations-line/' + id + '/edit'
                    $.ajax({
                        url: url,
                        method: 'get',
                        success: function (data) {
                            $('#modal-body').html(data);
                            $('#modal-title').text('Lines {{$payload->sub_title}}');
                            $('#modal').modal('show');
                            $('#{{$payload->formId}}').submit(function (e) {
                                $(".span_error").each(function () {
                                    $(this).remove()
                                });
                                e.preventDefault();
                                $("#btn-submit").prop("disabled", false)
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
                                            let repeaterList = $("[data-repeater-list]");
                                            $.each(data.errors, function (index, value) {
                                                var error = '<div class="text-danger span_error"> ' + value + '</div>'
                                                if (index.split('.').length > 1) {
                                                    const parts = index.split('.');
                                                    let line = parts[1];
                                                    let name = parts[0] + '[]';
                                                     repeaterList.children().eq(line).find('[name="' + name + '"]').parent().last().append(error)
                                                    //$('#error').last().append(error)
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
                                            $('#modal').modal('hide');
                                            $('#modal-body').empty()
                                            window.LaravelDataTables['{{$payload->tableId}}'].ajax.reload();
                                        }
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        $("#btn-submit").prop("disabled", false)
                                        toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                                    }
                                });

                            });
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            toastr.error(xhr.status + ' : ' + xhr.responseJSON.exception);
                        }
                    });
                });

                $('#warehouses-filter').select2().on('change', function () {
                    let warehouse_id = $(this).val();
                    let query = '?warehouse_id=' + warehouse_id;
                    window.LaravelDataTables['{{$payload->tableId}}'].ajax.url(query).load();
                });
            });
        </script>
    @endpush

</x-default-layout>
