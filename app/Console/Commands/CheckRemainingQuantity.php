<?php

namespace App\Console\Commands;

use App\Models\OutboundWarehouseItems;
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
        OutboundWarehouseItems::whereNull('outbound_id')->delete();
        WarehouseItems::get()->each(function ($items) {
            $sum_remaining_quantity = $items->quantity - $items->OutboundWarehouseItems->sum('quantity');
            $sum_remaining_quantity_other_quantity = $items->other_quantity - $items->OutboundWarehouseItems->sum('other_quantity');
            $items->update([
                'remaining_quantity' => max($sum_remaining_quantity, 0),
                'remaining_other_quantity' => max($sum_remaining_quantity_other_quantity, 0),
                'is_status' => max($sum_remaining_quantity, 0) > 0 ? 1 : 0,
            ]);
        });

        $this->info('Done');
    }
}
