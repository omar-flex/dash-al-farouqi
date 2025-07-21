<x-default-layout>
    @section('title')
        {{$payload->title}} - {{$enterRequest->bound_number}}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('operation-management.'.$payload->resource.'.show',$enterRequest) }}
    @endsection

    <div class="d-flex flex-column flex-lg-row">
        <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-300px mb-10">
            @include('pages.apps.operation-management.enter-requests.sections.details')
        </div>


        <div class="flex-lg-row-fluid ms-lg-5">

            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8"
                role="tablist">
                @if(!Auth::user()->hasRole('customer'))
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4 @if($enterRequest->status_id == \App\Models\EnterRequestStatus::CAR_CHECK) active @endif"
                           data-bs-toggle="tab"
                           href="#cares_plate_numbers_tab" aria-selected="true" role="tab">
                            <i class="fa-sharp-duotone fa-solid fa-truck-container fa-lg"></i>
                            <strong>({{$enterRequest->quantity_car}})</strong> Upcoming Cars
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4 @if($enterRequest->status_id == \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT ) active @endif"
                           data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#product_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">
                            <i class="fa-sharp-duotone fa-solid fa-container-storage fa-lg"></i>
                            <strong>({{number_format($enterRequest->quantity_packages,3)}})</strong> Packages
                        </a>
                    </li>
                    @if( in_array($enterRequest->status_id,[ \App\Models\EnterRequestStatus::VALIDATION,\App\Models\EnterRequestStatus::AUTHORIZATION,\App\Models\EnterRequestStatus::NEED_REVISION,\App\Models\EnterRequestStatus::APPROVED]))
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary pb-4 @if( in_array($enterRequest->status_id,[ \App\Models\EnterRequestStatus::VALIDATION,\App\Models\EnterRequestStatus::AUTHORIZATION,\App\Models\EnterRequestStatus::NEED_REVISION,\App\Models\EnterRequestStatus::APPROVED])) active @endif"
                               data-kt-countup-tabs="true" data-bs-toggle="tab"
                               href="#validation_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                               tabindex="-1">
                                <i class="fa-sharp-duotone fa-solid fa-ballot-check fa-lg"></i>
                                <strong>( {{number_format($enterRequest?->WarehouseItems?->count() )}} )</strong>
                                Manifest
                                Validation
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4  active"
                           data-bs-toggle="tab"
                           href="#customer_product_items" aria-selected="true" role="tab">
                            <i class="fa-sharp-duotone fa-solid fa-sitemap fa-lg"></i> Product Items
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                           href="#customer_outbounds" aria-selected="true" role="tab">
                            <i class="fa-sharp-duotone fa-solid fa-truck-arrow-right fa-flip-horizontal fa-lg"></i> Outbounds
                        </a>
                    </li>
                @endif

            </ul>


            <div class="tab-content" id="myTabContent">
                @if(!Auth::user()->hasRole('customer'))
                    <div
                        class="tab-pane fade @if($enterRequest->status_id == \App\Models\EnterRequestStatus::CAR_CHECK) active show @endif "
                        id="cares_plate_numbers_tab" role="tabpanel">
                        @include('pages.apps.operation-management.enter-requests.sections.cares')
                    </div>
                    <div
                        class="tab-pane fade @if($enterRequest->status_id == \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT ) active show @endif"
                        id="product_tab" role="tabpanel">
                        @include('pages.apps.operation-management.enter-requests.sections.packages')
                    </div>
                    @if( in_array($enterRequest->status_id,[ \App\Models\EnterRequestStatus::VALIDATION,\App\Models\EnterRequestStatus::AUTHORIZATION,\App\Models\EnterRequestStatus::NEED_REVISION,\App\Models\EnterRequestStatus::APPROVED]) )
                        <div
                            class="tab-pane fade @if( in_array($enterRequest->status_id,[ \App\Models\EnterRequestStatus::VALIDATION,\App\Models\EnterRequestStatus::AUTHORIZATION,\App\Models\EnterRequestStatus::NEED_REVISION,\App\Models\EnterRequestStatus::APPROVED])) active show @endif "
                            id="validation_tab" role="tabpanel">
                            @include('pages.apps.operation-management.enter-requests.customer.product_items')
                        </div>
                    @endif
                @else
                    <div class="tab-pane fade active show"
                        id="customer_product_items" role="tabpanel">
                        @include('pages.apps.operation-management.enter-requests.customer.product_items')
                    </div>
                    <div class="tab-pane fade"
                        id="customer_outbounds" role="tabpanel">
                        @include('pages.apps.operation-management.enter-requests.customer.outbounds')
                    </div>
                @endif
            </div>


        </div>

    </div>
</x-default-layout>
