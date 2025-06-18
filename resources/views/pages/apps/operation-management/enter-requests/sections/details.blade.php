@php use App\Actions\GetThemeType; @endphp
<div class="card mb-5 mb-xl-8">
    <div class="card-body">
        <div class="d-flex flex-center flex-column py-3 text-center">
            <span class="fs-3 text-gray-800 fw-bold mb-3"> {{$enterRequest?->Customer?->name}}  </span>
            <span class="fs-3 text-gray-800 fw-bold mb-3">{{$enterRequest->bound_number}} </span>
            <div class="mb-4">
                @php
                    $class = app(GetThemeType::class)->handle('badge-light-?', $enterRequest->Status?->name);
                @endphp
                <div class="badge badge-lg {{$class}} d-inline">{{$enterRequest->Status?->name}}</div>
            </div>
        </div>

        <div class="d-flex flex-stack fs-4 py-3">
            <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_user_view_details"
                 role="button" aria-expanded="false" aria-controls="kt_user_view_details">
                Details
                <span class="ms-2 rotate-180"><i class="ki-duotone ki-down fs-3"></i></span>
            </div>

            <span data-bs-toggle="tooltip" data-bs-trigger="hover"
                  data-bs-original-title="Edit customer details" data-kt-initialized="1">
                               {{-- <a href="#" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_update_details">
                                            Edit
                                              </a>--}}
                            </span>
        </div>


        <div class="separator"></div>

        <div id="kt_user_view_details" class="collapse show">
            <div class="pb-5 fs-6">
                <div class="mt-5">
                    <span class="fw-bold">Manifest In Bound Number:</span>
                    <span class="mx-1 text-gray-600">{{$enterRequest->manifest_bound_number}}</span>
                </div>
                <div class="mt-5">
                    <span class="fw-bold">Manifest Type Number:</span>
                    <span class="mx-1 text-gray-600">{{$enterRequest->manifest_type_number}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Custom Entry Center:</span>
                    <span class="mx-1 text-gray-600">{{$enterRequest->customs_entry_center}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Customs Value:</span>
                    <span
                        class="mx-1  text-danger fw-bold">{{ $enterRequest->total_cost ? number_format($enterRequest->total_cost,4) : ''}}</span>
                </div>
                <div class="mt-5">
                    <span class="fw-bold"> Gross weight:</span>
                    <span
                        class="mx-1  text-danger fw-bold">{{ $enterRequest->gross_weight ? number_format($enterRequest->gross_weight,4) : ''}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Net weight:</span>
                    <span
                        class="mx-1 text-danger fw-bold">{{ $enterRequest->net_weight ? number_format($enterRequest->net_weight,4) : ''}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Manifest Year:</span>
                    <span class="mx-1 text-gray-600">{{$enterRequest->manifest_year}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">Manifest Date:</span>
                    <span class="mx-1 text-gray-600">
                        {{$enterRequest->manifest_date ? \Illuminate\Support\Carbon::parse($enterRequest->manifest_date)->format('d M Y') : '----'}}
                    </span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Date:</span>
                    <span class="mx-1 text-gray-600">
                        {{$enterRequest->date ? \Illuminate\Support\Carbon::parse($enterRequest->date)->format('d M Y') : '----'}}
                    </span>
                </div>

                @if(auth()->user()->hasRole('administrator'))
                    <div class="mt-5">
                        <span class="fw-bold">Invoicing Date:</span>
                        <span class="mx-1 text-gray-600">
                        {{$enterRequest->invoicing_date ? \Illuminate\Support\Carbon::parse($enterRequest->invoicing_date)->format('d M Y') : '----'}}
                    </span>
                    </div>
                @endif

                <div class="mt-5">
                    <span class="fw-bold"> Quantity of Car:</span>
                    <span class="mx-1 text-gray-600">{{$enterRequest->quantity_car}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Quantity of Packages:</span>
                    <span class="mx-1 text-gray-600">{{number_format($enterRequest->quantity_packages)}}</span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold"> Country:</span>
                    <span class="mx-1 text-gray-600">
                                    {{$enterRequest->country_id ? $enterRequest->Country?->name : 'Multiple Countries'}}
                                </span>
                </div>

                <div class="mt-5">
                    <span class="fw-bold">  CPM per BL:</span>
                    <span
                        class="mx-1 text-gray-600">{{ $enterRequest->cpm_result ? number_format($enterRequest->cpm_result,3) : ''}}</span>
                </div>

                <div class="fw-bold mt-5"> Description</div>
                <div class="text-gray-600">{{$enterRequest->general_description_goods}}</div>

                <div class="fw-bold mt-5"> Files</div>

                @foreach($enterRequest->files as $file)
                    <div class="d-flex align-items-center mb-7">
                        <div class="symbol symbol-30px me-5">
                            <img alt="Icon" src="{{$file->getIcon()}}">
                        </div>
                        <div class="fw-semibold">
                            <a class="fs-6 fw-bold text-gray-900 text-hover-primary filename" target="_blank"
                               href="{{$file->getUrl()}}" id="filename_{{$file->id}}">{{$file->filename}}</a>
                        </div>

                        {{--<a class="btn btn-clean btn-sm btn-icon btn-icon-danger btn-active-light-danger ms-auto file_remove_btn"
                           id="{{$file->id}}" title="File Delete">
                            <i class="fas fa-trash-alt fa-xl"></i>
                        </a>--}}
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</div>
