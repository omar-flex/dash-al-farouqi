<?php

namespace App\Console\Commands;

use App\Models\EnterRequest;
use Illuminate\Console\Command;

class UpdateCpmWeightRation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cpm-weight-ration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        EnterRequest::get()->each(function ($request) {
            $data['cpm_result'] = ceil($request->cpm_result);
            $request->update($data);
        });

        EnterRequest::get()->each(function ($request) {
            $data['cpm_weight_ration'] = $request->cpm / $request->gross_weight;
            $data['cpm_weight_ration_wh'] = $request->cpm_result / $request->gross_weight;
            $request->update($data);
        });
    }
}
