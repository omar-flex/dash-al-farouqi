<?php

namespace App\Console\Commands;

use App\Models\WarehouseItems;
use Illuminate\Console\Command;

class CheckRemainingQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-remaining-quantity';

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
        WarehouseItems::get()->each(function ($items) {
            $sum_remaining_quantity = abs($items->OutboundWarehouseItems->sum('quantity') - $items->quantity);
            $sum_remaining_quantity_other_quantity = abs($items->OutboundWarehouseItems->sum('other_quantity') - $items->other_quantity);
            $items->update([
                'remaining_quantity' => $sum_remaining_quantity,
                'remaining_other_quantity' => $sum_remaining_quantity_other_quantity,
            ]);
        });
    }
}
