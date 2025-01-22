@foreach($enterRequest->cars as $car )
    <div class="row px-3">
        <div class="col-md-4 mb-2">
            <input class="form-control form-control-solid-bg form-control-sm disabled"
                   placeholder="Number" type="text" disabled value="{{$car->number}}"/>
        </div>
        <div class="col-md-4 mb-2">
            <input class="form-control form-control-solid-bg form-control-sm"
                   placeholder="Seal Numbers" type="text" disabled value="{{$car->seal_number}}"/>
        </div>
        <div class="col-md-4 mb-2">
            <input class="form-control form-control-solid-bg form-control-sm" type="text" disabled value="{{$car->is_status == 1 ? 'Valid' :'Invalid'}}"/>
        </div>
    </div>
@endforeach
