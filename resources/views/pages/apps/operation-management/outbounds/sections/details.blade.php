@php use App\Actions\GetThemeType; @endphp
<div class="card mb-5 mb-xl-8">
    <div class="card-body">
        <div class="d-flex flex-center flex-column py-3 text-center">
            <span class="fs-3 text-gray-800 fw-bold mb-3"> {{$outbound->enterRequest?->Customer?->name}}  </span>

            @if($outbound->enterRequest?->Company)
                <span class="fs-3 text-gray-800 fw-bold mb-3"> {{$outbound->enterRequest?->Company?->name}}  </span>
            @endif
            <span class="fs-3 text-gray-800 fw-bold mb-3">{{$outbound->outbound_number}} </span>
            <span class="text-gray-600 mb-3 "> From Inbound </span>
            <a href="{{route('operation-management.enter_requests.show',$outbound->enterRequest?->id )}}"
               target="_blank"
               class="fs-3 text-primary fw-bold mb-3">{{$outbound->enterRequest?->bound_number}} </a>
            <div class="mb-4">
                @php
                    $class = app(GetThemeType::class)->handle('badge-light-?', $outbound->Status?->name);
                @endphp
                <div class="badge badge-lg {{$class}} d-inline">{{$outbound->Status?->name}}</div>
            </div>
        </div>
        @can('edit_'.$payload->resource)
            <div class="d-flex flex-center flex-column py-3">
                <div class="mb-4 fw-bolder">Action</div>

                <a class="btn btn-sm btn-light btn-light-google output_products_pdf_btn @if($outbound->status_id < \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) pe-none @endif"
                   @if($outbound->status_id < \App\Models\EnterRequestStatus::WH_ENTER_PRODUCT) disabled
                   @endif  title="pdf" target="_blank">
                    <i class="fa-sharp-duotone fa-solid fa-truck-container fa-flip-horizontal fa-xl ms-2"></i> Output
                    Products Report
                </a>
            </div>
            <hr class="text-gray-500">
        @endcan
        <div class="d-flex flex-stack fs-4 py-3">
            <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_user_view_details"
                 role="button" aria-expanded="false" aria-controls="kt_user_view_details">
                Details
                <span class="ms-2 rotate-180"><i class="ki-duotone ki-down fs-3"></i></span>
            </div>
        </div>


        <div class="separator"></div>

        <div id="kt_user_view_details" class="collapse show">
            <div class="pb-5 fs-6">
                <div class="mt-5">
                    <span class="fw-bold">Outbound Number:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->manifest_outbound_number}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Manifest Type Number:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->manifest_type_number}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Custom Entry Center:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->customs_entry_center}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Customs Value:</span>
                    <span
                        class="mx-1  text-danger fw-bold">{{ $outbound->total_cost ? number_format($outbound->total_cost,3) : ''}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Gross weight:</span>
                    <span
                        class="mx-1 text-danger fw-bold">{{ $outbound->gross_weight ? number_format($outbound->gross_weight,3) : ''}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Net weight:</span>
                    <span
                        class="mx-1 text-danger fw-bold">{{ $outbound->net_weight ? number_format($outbound->net_weight,3) : ''}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Manifest Year:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->manifest_year}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Manifest Date:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->manifest_date}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Quantity of Car:</span>
                    <span class="mx-1 text-gray-600">{{$outbound->quantity_car}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Quantity of Packages:</span>
                    <span class="mx-1 text-gray-600">{{number_format($outbound->quantity_packages)}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Country:</span>
                    <span class="mx-1 text-gray-600">
                                    {{$outbound->enterRequest->country_id ? $outbound->enterRequest?->Country?->name : 'Multiple Countries'}}
                                </span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">  CPM per BL:</span>
                    <span
                        class="mx-1 text-gray-600">{{ $outbound->cpm_result ? number_format($outbound->cpm_result,3) : ''}}</span>
                </div>

                <div class="fw-bold mt-5"> Description</div>
                <div class="text-gray-600">{{$outbound->general_description_goods}}</div>

                <div class="fw-bold mt-5"> Files</div>

                @foreach($outbound->files as $file)
                    <div class="d-flex align-items-center mb-7">
                        <div class="symbol symbol-30px me-5">
                            <img alt="Icon" src="{{$file->getIcon()}}">
                        </div>
                        <div class="fw-semibold">
                            <a class="fs-6 fw-bold text-gray-900 text-hover-primary filename" target="_blank"
                               href="{{$file->getUrl()}}" id="filename_{{$file->id}}">{{$file->filename}}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        @can('edit_'.$payload->resource)
        $(document).on('click', '.output_products_pdf_btn', function () {
            let id = "{{$outbound->id}}";
            let url = '/operation-management/outbounds/' + id + '/output-products'
            $.ajax({
                url: url,
                method: 'get',
                success: function (data) {
                    $('#modal-body').html(data);
                    $('#modal-title').text('Output Products Report');
                    $('#modal').modal('show');
                }
            });
        });
        @endcan
    </script>
@endpush
