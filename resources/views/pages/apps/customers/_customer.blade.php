<!--begin:: Avatar -->
<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
    <a href="{{ route('customers.show', $customer) }}">
        @if($customer->profile_photo_url)
            <div class="symbol-label">
                <img src="{{ $customer->profile_photo_url }}" class="w-100"/>
            </div>
        @else
            <div class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $customer->name) }}">
                {{ substr($customer->name, 0, 1) }}
            </div>
        @endif
    </a>
</div>
<!--end::Avatar-->
<!--begin::User details-->
<div class="d-flex flex-column">
    <a href="{{ route('customers.show', $customer) }}" class="text-gray-800 text-hover-primary mb-1">
        {{ $customer->name }}
    </a>
    <span>{{ $customer->phone }}</span>
</div>
<!--begin::User details-->
