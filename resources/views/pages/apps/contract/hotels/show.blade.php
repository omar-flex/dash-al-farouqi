<x-default-layout>

    @section('title')
        Hotel Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('contract.hotels.show', $hotel) }}
    @endsection

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold my-4"
                    role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                           href="#information_tab" aria-selected="true" role="tab">Information</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#rates_seasonality_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Rates and Seasonality</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#higher_categories_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Higher Categories</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                           href="#supplements_tab" aria-selected="true" role="tab">Supplements</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#promotion_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Promotion</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#black_out_dates_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Black Out Dates</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#stop_sale_lines_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Stop Sale</a>
                    </li>
                </ul>
            </div>

        </div>
        <div class="card-body py-4">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade active show" id="information_tab" role="tabpanel">
                    @include('pages.apps.contract.hotels.tab._information')
                </div>
                <div class="tab-pane fade" id="rates_seasonality_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$rateLinesDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="higher_categories_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$higherCategoriesDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="supplements_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$supplementsDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="promotion_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$promotionsDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="black_out_dates_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$blackOutDatesDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="stop_sale_lines_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$stopSaleLinesDataTable->table()}}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
            {{$rateLinesDataTable->scripts()}}
            {{$higherCategoriesDataTable->scripts()}}
            {{$supplementsDataTable->scripts()}}
            {{$promotionsDataTable->scripts()}}
            {{$blackOutDatesDataTable->scripts()}}
            {{$stopSaleLinesDataTable->scripts()}}

    @endpush
</x-default-layout>
