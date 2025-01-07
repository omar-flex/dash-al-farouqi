<!--begin:: Avatar -->
<div class="d-flex align-items-center">
    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
        <a href="{{-- route('partner-management.warehouse.show', $model) --}}">
            @if($model->avatar)
                <div class="symbol-label">
                    <img src="{{ $model->getAvatar() }}" class="w-100"/>
                </div>
            @else
                <div
                    class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $model->full_name) }}">
                    {{ substr($model->full_name, 0, 1) }}
                </div>
            @endif
        </a>
    </div>
    <div class="d-flex flex-column">
        <a href="{{-- route('partner-management.warehouse.show', $model) --}}" class="text-gray-800 text-hover-primary mb-1">
           {{$model->courtesy_title}}  {{ $model->full_name }}
        </a>
    </div>
</div>


