<x-default-layout>
    @section('title')
        Warehouse Report
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('warehouse.report') }}
    @endsection

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h1> Warehouses Report Products </h1></div>
        </div>

        <div class="card-body py-4">

            <div class="row">
                <div class="col-4">
                    <label class="required fw-semibold fs-6 mb-2">From Date</label>
                    <input type="date" id="from_date" class="form-control form-control-solid-bg form-control-sm mb-2"
                           autocomplete="off"
                           placeholder="From Date" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                </div>

                <div class="col-4">
                    <label class="required fw-semibold fs-6 mb-2">To Date</label>
                    <input type="date" id="to_date" class="form-control form-control-solid-bg form-control-sm mb-2"
                           autocomplete="off"
                           placeholder="To Date" value="{{ date('Y-m-d') }}">
                </div>

                <div class="col-4">
                    <a class="btn btn-light-primary btn-sm mt-8" id="submit"> Submit </a>
                </div>

            </div>
        </div>
    </div>


    @push('scripts')
            <script>
                document.getElementById('submit').addEventListener('click', function () {
                    let fromDate = document.getElementById('from_date').value;
                    let toDate = document.getElementById('to_date').value;

                    if (!fromDate || !toDate) {
                        toastr.error('Please enter both dates.');
                        return;
                    }
                    let url = `warehouses-report/products?from_date=${fromDate}&to_date=${toDate}`;
                    window.open(url, '_blank');
                });
            </script>
    @endpush

</x-default-layout>
