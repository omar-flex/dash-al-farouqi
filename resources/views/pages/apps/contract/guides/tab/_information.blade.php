<div class="card border-0 shadow-none">
    <div class="card-body py-4">
        <div class="row">
            <div class="col-md-6">
                <h6>Guide Name</h6>
                <p>{{$guide?->name ?? '--------'}}</p>

                <h6>Date From</h6>
                <p>{{$guide?->date_from}}</p>

                <h6>Currency</h6>
                <p>{{$guide?->currency_id}}</p>

                <h6>Guide Fees Per Day</h6>
                <p>{{$guide?->guide_fees_per_day}}</p>

            </div>
            <div class="col-md-6">
                <h6>Language</h6>
                <p>{{$guide?->language}}</p>

                <h6>Date To</h6>
                <p>{{$guide?->date_to}}</p>

                <h6>Overnights Per Day	</h6>
                <p>{{$guide?->overnights_per_day}}</p>

            </div>
        </div>
    </div>
</div>
