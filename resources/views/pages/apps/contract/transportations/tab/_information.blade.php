<div class="card border-0 shadow-none">
    <div class="card-body py-4">
        <div class="row">
            <div class="col-md-6">
                <h6>Company Name</h6>
                <p>{{$transportation?->partner_id?->name}}</p>


                <h6>Curr Expense Type</h6>
                <p>
                    <span class="badge bg-light-success">---------</span>
                </p>

                <h6>Date From</h6>
                <p>{{$transportation?->date_from}}</p>

                <h6>Currency</h6>
                <p>{{$transportation?->currency_id?->name}}</p>

                <h6>Contract Type</h6>
                <div
                    class="mb-2 badge {{app(\App\Actions\GetThemeType::class)->handle('badge-?', $transportation?->type)}}">
                    {{$transportation?->type}}
                </div>
            </div>
            <div class="col-md-6">
                <h6>Contract Name</h6>
                <p>{{$transportation?->name}}</p>

                <h6>Date To</h6>
                <p>{{$transportation?->date_to}}</p>

                <h6>Exchange Rate</h6>
                <p>------------</p>

                <h6>Sales Tax</h6>
                <p>---------</p>
            </div>
        </div>
    </div>
</div>
