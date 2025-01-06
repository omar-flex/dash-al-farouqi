<x-default-layout>

    @section('title')
        Transportation Details
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('contract.transportations.show', $transportation) }}
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
                           href="#contract_services_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Contract By Services</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#contract_routes_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Contract By Routes</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#promotion_lines_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">Promotion</a>
                    </li>

                </ul>
            </div>

        </div>
        <div class="card-body py-4">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade active show" id="information_tab" role="tabpanel">
                    @include('pages.apps.contract.transportations.tab._information')
                </div>
                <div class="tab-pane fade" id="contract_services_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$contractServicesDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="contract_routes_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$contractRoutesDataTable->table()}}
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="promotion_lines_tab" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table table-responsive">
                            {{$promotionsDataTable->table()}}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>$contractRoutesDataTable

    @push('scripts')
        {{$contractServicesDataTable->scripts()}}
        {{$contractRoutesDataTable->scripts()}}
        {{$promotionsDataTable->scripts()}}
    @endpush
</x-default-layout>
