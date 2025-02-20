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
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 @if($enterRequest->status_id == \App\Models\EnterRequestStatus::CAR_CHECK) active @endif"
                       data-bs-toggle="tab"
                       href="#cares_plate_numbers_tab" aria-selected="true" role="tab">
                        <i class="fa-sharp-duotone fa-solid fa-truck-container fa-lg"></i>
                        <strong>({{$enterRequest->quantity_car}})</strong> Upcoming Cars
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 @if($enterRequest->status_id == \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) active @endif"
                       data-kt-countup-tabs="true" data-bs-toggle="tab"
                       href="#products_tab" data-kt-initialized="1" aria-selected="false" role="tab"
                       tabindex="-1">
                        <i class="fa-sharp-duotone fa-solid fa-container-storage fa-lg"></i>
                        <strong>({{number_format($enterRequest->quantity_packages)}})</strong> Packages
                    </a>
                </li>
                {{--   <li class="nav-item ms-auto">
                       <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click" data-kt-menu-attach="parent"
                          data-kt-menu-placement="bottom-end">
                           Actions
                           <i class="ki-duotone ki-down fs-2 me-0"></i>
                       </a>

                       <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                           data-kt-menu="true">

                           <div class="menu-item px-5">
                               <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">
                                   Payments
                               </div>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5">
                               <a href="#" class="menu-link px-5">
                                   Create invoice
                               </a>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5">
                               <a href="#" class="menu-link flex-stack px-5">
                                   Create payments

                                   <span class="ms-2" data-bs-toggle="tooltip"
                                         aria-label="Specify a target name for future usage and reference"
                                         data-bs-original-title="Specify a target name for future usage and reference"
                                         data-kt-initialized="1">
                   <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span
                           class="path3"></span></i>            </span>
                               </a>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5" data-kt-menu-trigger="hover" data-kt-menu-placement="left-start">
                               <a href="#" class="menu-link px-5">
                                   <span class="menu-title">Subscription</span>
                                   <span class="menu-arrow"></span>
                               </a>

                               <!--begin::Menu sub-->
                               <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                   <!--begin::Menu item-->
                                   <div class="menu-item px-3">
                                       <a href="#" class="menu-link px-5">
                                           Apps
                                       </a>
                                   </div>
                                   <!--end::Menu item-->

                                   <!--begin::Menu item-->
                                   <div class="menu-item px-3">
                                       <a href="#" class="menu-link px-5">
                                           Billing
                                       </a>
                                   </div>
                                   <!--end::Menu item-->

                                   <!--begin::Menu item-->
                                   <div class="menu-item px-3">
                                       <a href="#" class="menu-link px-5">
                                           Statements
                                       </a>
                                   </div>
                                   <!--end::Menu item-->

                                   <!--begin::Menu separator-->
                                   <div class="separator my-2"></div>
                                   <!--end::Menu separator-->

                                   <!--begin::Menu item-->
                                   <div class="menu-item px-3">
                                       <div class="menu-content px-3">
                                           <label class="form-check form-switch form-check-custom form-check-solid">
                                               <input class="form-check-input w-30px h-20px" type="checkbox" value=""
                                                      name="notifications" checked="" id="kt_user_menu_notifications">
                                               <span class="form-check-label text-muted fs-6"
                                                     for="kt_user_menu_notifications">
                           Notifications
                           </span>
                                           </label>
                                       </div>
                                   </div>
                                   <!--end::Menu item-->
                               </div>
                               <!--end::Menu sub-->
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu separator-->
                           <div class="separator my-3"></div>
                           <!--end::Menu separator-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5">
                               <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">
                                   Account
                               </div>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5">
                               <a href="#" class="menu-link px-5">
                                   Reports
                               </a>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5 my-1">
                               <a href="#" class="menu-link px-5">
                                   Account Settings
                               </a>
                           </div>
                           <!--end::Menu item-->

                           <!--begin::Menu item-->
                           <div class="menu-item px-5">
                               <a href="#" class="menu-link text-danger px-5">
                                   Delete customer
                               </a>
                           </div>
                           <!--end::Menu item-->
                       </div>
                       <!--end::Menu-->
                       <!--end::Menu-->
                   </li>--}}
            </ul>


            <div class="tab-content" id="myTabContent">
                <div
                    class="tab-pane fade @if($enterRequest->status_id == \App\Models\EnterRequestStatus::CAR_CHECK) active show @endif "
                    id="cares_plate_numbers_tab" role="tabpanel">
                    @include('pages.apps.operation-management.enter-requests.sections.cares')
                </div>
                <div
                    class="tab-pane fade @if($enterRequest->status_id == \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) active show @endif"
                    id="products_tab" role="tabpanel">
                    @include('pages.apps.operation-management.enter-requests.sections.packages')
                </div>
            </div>

        </div>

    </div>
</x-default-layout>
