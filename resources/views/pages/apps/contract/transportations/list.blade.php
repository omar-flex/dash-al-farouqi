<x-default-layout>

    @section('title')
        Transportation
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('contract.transportations.index') }}
    @endsection
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Search Hotel" id="mySearchInput"/>
                </div>
            </div>

            <div class="card-toolbar">

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
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['transportations-table'].search(this.value).draw();
            });
        </script>
    @endpush

</x-default-layout>
