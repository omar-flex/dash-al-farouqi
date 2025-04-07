@if(isset($customer))
    <form action="{{ route('companies.update', $customer) }}" id="{{$payload->formId}}"
          method="POST"
          enctype="multipart/form-data">
        @method('PUT')
        @else
            <form action="{{ route('companies.store') }}" method="POST"
                  id="{{$payload->formId}}"
                  enctype="multipart/form-data">
                @endif
                @csrf
                <input type="hidden" name="enter_request" value="{{ $payload->enter_request ?? 0 }}">
                <div class="row">
                    <div class="col-md-4 mb-7">
                        <label class="fw-semibold fs-6 mb-2">Clearance Company Name</label>
                        <input type="text" name="company_name" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Clearance Company Name"
                               @isset($customer) value="{{ $customer->company_name }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Company Number</label>
                        <input type="text" name="number" class="form-control form-control-solid-bg mb-2" placeholder="Company Number"
                               @isset($customer) value="{{ $customer->email }}" @endisset>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="fw-semibold fs-6 mb-2">Phone</label>
                        <input type="hidden" name="phone" @isset($customer) value="{{ $customer->phone }}" @endisset>
                        <input type="tel" class="form-control form-control-solid-bg mb-2"
                               autocomplete="off"
                               placeholder="Phone"
                               id="phone" @isset($customer) value="{{ $customer->phone }}" @endisset>
                    </div>
                </div>

                <div class="col-md-12 form-group">
                    <input type="submit" class="btn btn-light-success btn-sm float-end" value="Submit"
                           id="btn-submit">
                </div>

            </form>
    </form>

    <script>
        $(document).ready(function () {
            const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"]
            let phone = document.querySelector("#phone")
            let iti = window.intlTelInput(phone, {
                separateDialCode: true,
                autoFormat: true,
                nationalMode: true,
                initialCountry: 'auto',
                geoIpLookup: callback => {
                    fetch("https://ipapi.co/json")
                        .then(res => res.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback("us"));
                },
                loadUtilsOnInit: "{{asset('intl-tel-input/utils.js')}}",
            });

            phone.addEventListener('change', () => {
                $(".iti_error").each(function () {
                    $(this).remove()
                });
                if (iti.isValidNumber()) {
                    $(".iti_error").remove()
                    $('[name="phone"]').val(iti.getNumber(intlTelInput.utils.numberFormat.E164))
                } else {
                    const errorCode = iti.getValidationError();
                    const msg = errorMap[errorCode] || "Invalid number";
                    const error = '<span class="text-danger iti_error"> ' + msg + '</span>'
                    $('#phone').parent().parent().last().append(error)
                }
            });

            phone.addEventListener('countrychange', () => {
                $('[name="phone"],#phone').val('')
            });
        });
    </script>
