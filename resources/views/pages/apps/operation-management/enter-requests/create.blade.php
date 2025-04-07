<style>
    .dz-progress {
        display: none;
    }
</style>
@if(isset($enterRequest))
    <form action="{{ route('operation-management.enter_requests.update', $enterRequest) }}"
          id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('operation-management.enter_requests.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="required fw-semibold  mb-2">Customer</label>
                            @can('add_customers')
                                <a class="cursor-pointer" id="add_customer">
                                    <i class="fa-sharp-duotone fa-solid fa-user-plus fa-sm"></i>
                                    Add Customer
                                </a>
                            @endcanany
                        </div>
                        <select name="customer_id" class="form-select form-select-solid-bg mb-2 form-select-sm"
                                id="customers"
                                data-control="select2" data-placeholder="Select an Customer">
                            <option></option>
                            @foreach($payload->customers as $customer )
                                <option value="{{$customer->id}}"
                                        @if(isset($enterRequest) && $enterRequest->customer_id == $customer->id) selected @endif>
                                    {{$customer->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="fw-semibold  mb-2">Clearance Company</label>
                            @can('add_companies')
                                <a class="cursor-pointer" id="add_company">
                                    <i class="fa-sharp-duotone fa-solid fa-user-plus fa-sm"></i>
                                    Add Company
                                </a>
                            @endcanany
                        </div>
                        <select name="clearance_company_id" class="form-select form-select-solid-bg mb-2 form-select-sm" id="companies"
                                data-control="select2" data-placeholder="Select an Clearance Company">
                            <option></option>
                            @foreach($payload->companies as $company )
                                <option value="{{$company->id}}"
                                        @if(isset($enterRequest) && $enterRequest->clearance_company_id == $company->id) selected @endif>
                                    {{$company->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2">In Bound Number</label>
                        <input type="text" name="manifest_bound_number"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="In Bound Number"
                               @isset($enterRequest) value="{{ $enterRequest->manifest_bound_number }}" @endisset>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2">Manifest Type Number</label>
                        <input type="text" name="manifest_type_number"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Manifest Type Number"
                               @isset($enterRequest) value="{{ $enterRequest->manifest_type_number }}" @endisset>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2"> Custom Entry Center</label>
                        <input type="text" name="customs_entry_center"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Custom Entry Center"
                               @isset($enterRequest) value="{{ $enterRequest->customs_entry_center }}" @endisset>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Manifest Year</label>
                        <input type="text" name="manifest_year"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Manifest Year"
                               @isset($enterRequest) value="{{ $enterRequest->manifest_year }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Manifest Date</label>
                        <input type="date" name="manifest_date" id="manifest_date"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Manifest Date"
                               @isset($enterRequest) value="{{ $enterRequest->manifest_date }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Quantity of Car</label>
                        <input type="number" step="any" min="0"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Quantity of Car"
                               @if(isset($enterRequest) && $enterRequest->cars()->count() > 0) disabled
                               @else name="quantity_car" @endif
                               @isset($enterRequest) value="{{ $enterRequest->quantity_car }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Quantity of Packages</label>
                        <input type="number" step="any" name="quantity_packages" min="0"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Quantity of Packages"
                               @isset($enterRequest) value="{{ $enterRequest->quantity_packages }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Total cost</label>
                        <input type="number" step="any" name="total_cost" min="0"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Total cost"
                               @isset($enterRequest) value="{{ $enterRequest->total_cost }}" @endisset>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Gross weight</label>
                        <div class="input-group  mb-5">
                            <input type="number" step="any" name="gross_weight" min="0"
                                   class="form-control form-control-solid-bg form-control-sm "
                                   placeholder="Gross weight"
                                   @isset($enterRequest) value="{{ $enterRequest->gross_weight }}" @endisset>
                            <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Net weight</label>
                        <div class="input-group  mb-5">
                            <input type="number" step="any" name="net_weight" min="0"
                                   class="form-control form-control-solid-bg" form-control-sm
                                   placeholder="Net weight"
                                   @isset($enterRequest) value="{{ $enterRequest->net_weight }}" @endisset>
                            <span class="input-group-text" id="inputGroup-sizing-default">kg</span>
                        </div>

                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="required fw-semibold  mb-2">Country</label>
                        <div class="form-check form-check-custom form-check-solid ">
                            <select name="country_id" class="form-select form-select-solid-bg mb-2"
                                    id="countries"
                                    @if(!(isset($enterRequest) && $enterRequest->country_id)) disabled
                                    @endif
                                    data-control="select2" data-placeholder="Multiple Countries">
                                <option></option>
                                @foreach($payload->countries as $country )
                                    <option value="{{$country->id}}"
                                            @if(isset($enterRequest) && $enterRequest->country_id == $country->id) selected @endif>
                                        {{$country->name}}
                                    </option>
                                @endforeach
                            </select>
                            <input class="form-check-input mx-2" type="checkbox" value="1"
                                   id="countryCheckBox"
                                   @if(!(isset($enterRequest) && $enterRequest->country_id)) checked @endif />
                        </div>

                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2">CPM per BL</label>
                        <input type="number" step="any" name="cpm" min="0"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="CPM"
                               @isset($enterRequest) value="{{ $enterRequest->cpm }}" @endisset>
                    </div>


                    @isset($enterRequest)
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold  mb-2">Cpm Calculated</label>
                            <input type="number" step="any" min="0" disabled
                                   class="form-control form-control-solid-bg form-control-sm mb-2"
                                   placeholder="Cpm Calculated" value="{{ $enterRequest->cpm_calculated }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold  mb-2">Cpm Result</label>
                            <input type="number" step="any" min="0" disabled
                                   class="form-control form-control-solid-bg form-control-sm mb-2"
                                   placeholder="Cpm Result" value="{{ $enterRequest->cpm_result }}">
                        </div>
                    @endisset

                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2">Date</label>
                        <input type="date" name="date" id="date"
                               class="form-control form-control-solid-bg form-control-sm mb-2"
                               placeholder="Date"
                               @isset($enterRequest) value="{{ $enterRequest->date }}" @endisset>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="required fw-semibold  mb-2">WH</label>
                        <select name="warehouse_id" class="form-select form-select-solid-bg form-select-sm mb-2"
                                id="warehouses"
                                data-control="select2" data-placeholder="WH">
                            <option></option>
                            @foreach($payload->warehouses as $warehouse)
                                <option value="{{$warehouse->id}}"
                                        @if(isset($enterRequest) && $enterRequest->warehouse_id == $warehouse->id) selected @endif>
                                    {{$warehouse->code}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(!isset($enterRequest))
                        <div class="col-md-8">

                        </div>
                    @endisset
                    <div class="col-md-6 mb-3">
                        <label class="required fw-semibold  mb-2"> General description Goods</label>
                        <textarea class="form-control form-control-solid-bg form-control-sm mb-2"
                                  name="general_description_goods" style="min-height: 30px"
                                  placeholder="General description Goods">@isset($enterRequest)
                                {{ $enterRequest->general_description_goods }}
                            @endisset</textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="required fw-semibold  mb-2"> Notes</label>
                        <textarea class="form-control form-control-solid-bg form-control-sm mb-2" name="notes"
                                  style="min-height: 30px" placeholder="Notes">@isset($enterRequest)
                                {{ $enterRequest->notes }}
                            @endisset</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="fw-semibold  mb-2 required">Attached</label>
                        @if(isset($enterRequest))
                            <div class="row">
                                @foreach($enterRequest->files as $file)
                                    <div class="d-flex align-items-center col-md-4 mb-3 border-1 border-dashed p-2 mx-2"
                                         id="file_{{$file->id}}">
                                        <div class="symbol symbol-30px me-5">
                                            <img alt="Icon" src="{{$file->getIcon()}}">
                                        </div>
                                        <div class="fw-semibold">
                                            <a class=" fw-bold text-gray-900 text-hover-primary filename"
                                               target="_blank"
                                               href="{{$file->getUrl()}}" id="filename_{{$file->id}}"
                                               title="{{$file->filename}}">{{\Illuminate\Support\Str::limit($file->filename,30)}}</a>
                                        </div>

                                        <a class="btn btn-clean btn-sm btn-icon btn-icon-danger btn-active-light-danger ms-auto file_remove_btn"
                                           id="{{$file->id}}" title="File Delete">
                                            <i class="fas fa-trash-alt fa-xl"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>

                        @endif
                        <div class="fv-row mb-2">
                            <input type="hidden" name="files">
                            <div class="dropzone" id="dropzone">
                                <div class="dz-message needsclick">
                                    <i class="ki-duotone ki-file-up text-primary fs-3x">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="ms-4">
                                        <h3 class="fs-5 fw-bold text-gray-900 mb-1">
                                            Drop files here or click to upload.
                                        </h3>
                                        <span class="fs-7 fw-semi bold text-gray-500">Upload files</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted fs-7">Set Manifest.</div>

                    </div>

                    <div class="col-md-12 form-group">

                        @if(isset($enterRequest))
                            @if($enterRequest->status_id == \App\Models\EnterRequestStatus::DRAFT)
                                <input type="submit" class="btn btn-light-warning btn-sm float-end"
                                       value="Save as Draft"
                                       id="btn-draft">
                                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                       value="Submitted"
                                       id="btn-submit">
                            @else
                                <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                       value="Save"
                                       id="btn-submit">
                            @endif
                        @else
                            <input type="submit" class="btn btn-light-warning btn-sm float-end"
                                   value="Save as Draft"
                                   id="btn-draft">
                            <input type="submit" class="btn btn-light-success btn-sm float-end mx-2"
                                   value="Submitted"
                                   id="btn-submit">
                        @endif
                    </div>
                </div>


            </form>

    </form>

    @include('pages.apps.operation-management.enter-requests.createJs')


