<style>
    .custom-search-class {
        background-color: var(--bs-gray-200) !important;
        padding: 6px 12px;
        font-size: 14px;
        width: 250px;
    }
</style>
<div class="card card-flush h-xl-100 ">
    <div class="card-header pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-800">Retention and Revenue Performance Analysis</span>
            <span class="text-gray-500 mt-1 fw-semibold fs-6"></span>
        </h3>

        <div class="card-toolbar">
            <a href="#" class="btn btn-sm btn-flex btn-secondary fw-bold  menu-dropdown" data-kt-menu-trigger="click"
               data-kt-menu-placement="bottom-end">
                <i class="ki-duotone ki-filter fs-6 text-muted me-1"><span class="path1"></span><span
                        class="path2"></span></i>
                Filter
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                 style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-108px, 132px);"
                 data-popper-placement="bottom-end">
                <div class="px-7 py-5">
                    <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                </div>
                <div class="separator border-gray-200"></div>
                <form class="form px-7 py-5">
                    <div class="mb-10">
                        <div class="row">
                            <div class="col-sm-12">
                                <label for="kt_datepicker_1" class="form-label">From</label>
                                <input id="kt_datepicker_1" type="month" name="month_from" class="form-control"
                                       autocomplete="off" value="{{$firstMonth}}"/>
                            </div>
                            <div class="col-sm-12 mt-2">
                                <label for="kt_datepicker_2" class="form-label">To</label>
                                <input id="kt_datepicker_2" type="month" name="month_to" class="form-control"
                                       autocomplete="off" value="{{$listMonth}}"/>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a  class="btn btn-sm btn-light btn-active-light-primary me-2" href="{{route('dashboard')}}">Reset</a>
                        <button type="submit" class="btn btn-sm btn-primary" id="filter">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table id="dataTable" class="table table-striped table-row-bordered gy-5 gs-7">
                <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Customer Name</th>
                    @foreach($monthsArray as $month)
                        <th>{{ $month }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($data as $name => $rows)
                    <tr>
                        <td>{{ $name }}</td>
                        @php $previousAmount = null; @endphp
                        @foreach($monthsArray as $key => $month)
                            @php
                                $currentRow = $rows->firstWhere('data', $key);
                            @endphp
                            @if ($currentRow)
                                @php
                                    $color = $previousAmount === null ? 'badge-light-success' : ($currentRow->total_amount >= $previousAmount ? 'badge-light-success' : 'badge-light-warning');
                                    $previousAmount = $currentRow->total_amount;
                                @endphp
                                <td><span class="badge {{ $color }}">
                                        {{ number_format($currentRow->total_amount, 2) }}</span> /
                                    <span
                                        class="badge badge-circle badge-secondary">{{ $currentRow->count_move }}</span>
                                </td>
                            @else
                                @if($previousAmount)
                                    <td><span class="badge badge-light-danger">0</span></td>
                                @else
                                    <td><span class="badge ">NA</span></td>
                                @endif

                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>


        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                scrollX: true,
                dom: 'frtip',
                pageLength: 100,
                lengthMenu: [[200, 500, -1], [200, 500, "All"]],
                language: {
                    lengthMenu: "Show _MENU_ records per page"
                },
                initComplete: function () {
                    $('#dataTable_wrapper input[type="search"]').addClass('custom-search-class');
                },
                order: [[1, 'asc']]
            });
        });


    </script>
@endpush
