<!--begin:: Avatar -->
<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
    <a href="{{ route('partner-management.partners.show', $model) }}">
        @if($model->Partner->logo)
            <div class="symbol-label">
                <img src="{{ $model->Partner->getLogo() }}" class="w-100"/>
            </div>
        @else
            <div class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', $model->Partner->name) }}">
                {{ substr($model->Partner->name, 0, 1) }}
            </div>
        @endif
    </a>
</div>

<div class="d-flex flex-column">
    <a href="{{ route('partner-management.partners.show', $model->Partner->id) }}" class="text-gray-800 text-hover-primary mb-1">
        {{ $model->Partner->name }}
    </a>
    <span>{{ $model->Partner?->Type?->name }}</span>
</div>

