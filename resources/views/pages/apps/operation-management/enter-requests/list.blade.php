<x-default-layout>
    @section('title')
        {{$payload->title}}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('operation-management.'.$payload->resource.'.index') }}
    @endsection

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                           class="form-control form-control-solid w-250px ps-13"
                           placeholder="Search {{$payload->sub_title}} Or Products"
                           id="mySearchInput"/>
                </div>
            </div>
            @if(!Auth::user()->hasRole('customer'))
                <div class="card-toolbar min-w-900px">
                    <div class="d-flex justify-content-end gap-3 w-100" data-kt-user-table-toolbar="base">
                        <div class="w-25">
                            <select class="form-select form-select-solid form-select-sm mb-2 " id="company_filter"
                                    data-control="select2" data-placeholder="Select an Company" data-allow-clear="true">
                                <option></option>
                                @foreach($payload->companies as $company)
                                    <option value="{{$company->id}}">{{$company->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-25">
                            <select class="form-select form-select-solid form-select-sm mb-2 " id="customer_filter"
                                    data-control="select2" data-placeholder="Select an Customer"
                                    data-allow-clear="true">
                                <option></option>
                                @foreach($payload->customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-25">
                            <select class="form-select form-select-solid form-select-sm mb-2 " id="status_filter"
                                    data-control="select2" data-placeholder="Select an Status" data-allow-clear="true">
                                <option></option>
                                @foreach($payload->statuses as $status)
                                    <option value="{{$status->id}}">{{$status->name}}</option>
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
            @endif
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
            $('#add').on('click', function () {
                $.ajax({
                    url: '{{ route('operation-management.'.$payload->resource.'.create')}}',
                    method: 'get',
                    success: function (data) {
                        $('#modal-body').html(data);
                        $('#modal-title').text('Add {{$payload->title}}');
                        $('#modal').modal('show');
                    }
                });
            });
            @endcan

            @can('edit_'.$payload->resource)
            $(document).on('click', '.edit_btn', function () {
                let id = $(this).attr('id');
                let url = '/operation-management/{{$payload->resource}}/' + id + '/edit'
                $.ajax({
                    url: url,
                    method: 'get',
                    success: function (data) {
                        $('#modal-body').html(data);
                        $('#modal-title').text('Edit {{$payload->title}}');
                        $('#modal').modal('show');
                    }
                });
            });
            @endcan

            @can('delete_'.$payload->resource)
            remove('remove_btn', 'operation-management/{{$payload->resource}}', '{{$payload->tableId}}', '{{ csrf_token() }}')
            @endcan

            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['{{$payload->tableId}}'].search(this.value).draw();
            });

            $('#status_filter,#customer_filter,#company_filter').select2().on('change', function () {
                let query = '?';
                let status_id = $('#status_filter').val();
                let customer_id = $('#customer_filter').val();
                let company_id = $('#company_filter').val();
                if (status_id)
                    query += 'status_id=' + status_id;
                if (customer_id)
                    query += '&customer_id=' + customer_id;
                if (company_id)
                    query += '&company_id=' + company_id;
                window.LaravelDataTables['{{$payload->tableId}}'].ajax.url(query).load();
            });
        </script>
    @endpush

</x-default-layout>
