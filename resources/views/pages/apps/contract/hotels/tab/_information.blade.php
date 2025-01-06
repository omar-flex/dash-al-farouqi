<div class="card border-0 shadow-none">
    <div class="card-body py-4">
        <div class="row">
            <div class="col-md-6">
                <h6>Contract Name</h6>
                <p>{{$hotel->name}}</p>

                <h6>Curr Expense Type</h6>
                <p>
                    <span class="badge bg-light-success">---------</span>
                    <span class="badge bg-light-success">--------</span>
                </p>

                <h6>Location</h6>
                <p>{{$hotel->Location?->name}}</p>

                <h6>Date From</h6>
                <p>{{$hotel?->date_from}}</p>

                <h6>Currency</h6>
                <p>{{$hotel?->currency_id?->name}}</p>

                <h6>Contract Attachment</h6>
                <p>---</p>

                <h6>Service Charge</h6>
                <p>---------</p>

                <h6>Contract Type</h6>
                <div class="mb-2 badge {{app(\App\Actions\GetThemeType::class)->handle('badge-?', $hotel?->contract_type)}}">
                    {{$hotel?->contract_type}}
                </div>
                <h6>There Is Foc</h6>
                <p>------------</p>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <h6>Hotel Name</h6>
                <p>{{$hotel?->partner_id?->name}}</p>

                <h6>Add Expense Type</h6>
                <p><span class="badge badge-success">---------</span></p>

                <h6>Date To</h6>
                <p>{{$hotel?->date_to}}</p>

                <h6>Exchange Rate</h6>
                <p>------------</p>

                <h6>Sales Tax</h6>
                <p>---------</p>

                <h6>Year</h6>
                <p>-----------</p>

                <h6>Specific For</h6>
                <p><span class="badge bg-secondary">----------</span></p>
            </div>
        </div>
    </div>
</div>
