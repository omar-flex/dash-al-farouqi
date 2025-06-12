<x-default-layout>
    @section('title')
        {{$payload->title}} - {{$outbound->outbound_number}}
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('operation-management.'.$payload->resource.'.show',$outbound) }}
    @endsection

    <div class="d-flex flex-column flex-lg-row">
        <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-300px mb-10">
            @include('pages.apps.operation-management.outbounds.sections.details')
        </div>


        <div class="flex-lg-row-fluid ms-lg-5">

            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8"
                role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 @if($outbound->status_id == \App\Models\OutboundStatus::CAR_CHECK) active @endif"
                       data-bs-toggle="tab" href="#car_check_tab" aria-selected="true" role="tab">
                        <i class="fa-sharp-duotone fa-solid fa-truck-container fa-lg fa-flip-horizontal "></i>
                        <strong>({{$outbound->quantity_car}})</strong> Upcoming Cars
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 @if($outbound->status_id == \App\Models\OutboundStatus::WH_RELEASE_PRODUCT) active @endif"
                       data-kt-countup-tabs="true" data-bs-toggle="tab"
                       href="#products_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                       tabindex="-1">
                        <i class="fa-sharp-duotone fa-solid fa-container-storage fa-lg"></i>
                        <strong>({{number_format($outbound->quantity_packages)}})</strong> Release Packages
                    </a>
                </li>
                @if( in_array($outbound->status_id,[ \App\Models\OutboundStatus::VALIDATION,\App\Models\OutboundStatus::AUTHORIZATION,\App\Models\OutboundStatus::NEED_REVISION,\App\Models\OutboundStatus::APPROVED]))
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-active-primary pb-4 @if( in_array($outbound->status_id,[ \App\Models\OutboundStatus::VALIDATION,\App\Models\OutboundStatus::AUTHORIZATION,\App\Models\OutboundStatus::NEED_REVISION,\App\Models\OutboundStatus::APPROVED])) active @endif"
                           data-kt-countup-tabs="true" data-bs-toggle="tab"
                           href="#validation_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                           tabindex="-1">
                            <i class="fa-sharp-duotone fa-solid fa-ballot-check fa-lg"></i>
                            <strong>( {{number_format($outbound?->OutboundWarehouseItems?->count() )}} )</strong>
                            Manifest Validation
                        </a>
                    </li>
                @endif

            </ul>


            <div class="tab-content" id="myTabContent">
                <div
                    class="tab-pane fade @if($outbound->status_id == \App\Models\OutboundStatus::CAR_CHECK) active show @endif "
                    id="car_check_tab" role="tabpanel">
                    @include('pages.apps.operation-management.outbounds.sections.cares')
                </div>
                <div
                    class="tab-pane fade @if($outbound->status_id == \App\Models\OutboundStatus::WH_RELEASE_PRODUCT) active show @endif"
                    id="products_tab" role="tabpanel">
                    @include('pages.apps.operation-management.outbounds.sections.packages')
                </div>

                @if( in_array($outbound->status_id,[ \App\Models\OutboundStatus::VALIDATION,\App\Models\OutboundStatus::AUTHORIZATION,\App\Models\OutboundStatus::NEED_REVISION,\App\Models\OutboundStatus::APPROVED]) )
                    <div
                        class="tab-pane fade @if( in_array($outbound->status_id,[ \App\Models\OutboundStatus::VALIDATION,\App\Models\OutboundStatus::AUTHORIZATION,\App\Models\OutboundStatus::NEED_REVISION,\App\Models\OutboundStatus::APPROVED])) active show @endif "
                        id="validation_tab" role="tabpanel">
                        @include('pages.apps.operation-management.outbounds.sections.validations')
                    </div>
                @endif
            </div>

        </div>
    </div>


</x-default-layout>
