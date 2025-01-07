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
            <div class="card-toolbar">
                <div class="d-flex justify-content-end gap-3" data-kt-user-table-toolbar="base">
                    @can('add_'.$payload->resource)
                        <a class="btn btn-light-primary btn-sm" id="add">
                            {!! getIcon('plus', 'fs-2', '', 'i') !!}
                            Add {{$payload->sub_title}}
                        </a>
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
            addModal('add', '{{ route('warehouse-management.'.$payload->resource.'.create')}}', 'Add {{$payload->title}}', '{{$payload->formId}}', '{{$payload->tableId}}');
            @endcan
            @can('edit_'.$payload->resource)
            editModal('edit_btn', 'warehouse-management/{{$payload->resource}}', 'Edit {{$payload->title}}', '{{$payload->formId}}', '{{$payload->tableId}}')
            @endcan
            @can('delete_'.$payload->resource)
            remove('remove_btn', 'warehouse-management/{{$payload->resource}}', '{{$payload->tableId}}', '{{ csrf_token() }}')
            @endcan
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['{{$payload->tableId}}'].search(this.value).draw();
            });

        </script>
    @endpush

</x-default-layout>
