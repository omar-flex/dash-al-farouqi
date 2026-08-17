@foreach($cars as $car )
    <input type="hidden" name="car_ids[]" value="{{$car->id}}"/>
    <div class="row px-3">
        <div class="col-md-4 mb-2">
            <input class="form-control form-control-solid-bg form-control-sm"
                   placeholder="Number" type="text" value="{{$car->number}}" name="numbers[]"
                   @disabled(!$canEditCars)/>
        </div>
        <div class="col-md-4 mb-2">
            <input class="form-control form-control-solid-bg form-control-sm"
                   placeholder="Seal Numbers" type="text" value="{{$car->seal_number}}" name="seal_numbers[]"
                   @disabled(!$canEditCars)/>
        </div>
    </div>
@endforeach
