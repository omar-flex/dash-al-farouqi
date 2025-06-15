<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CheckBarcodeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-barcode-products';

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
        Product::whereColumn('name', 'barcode')
            ->orWhereIn('barcode', ['1', '2', '3', '4', '5', '6'])
            ->orWhere('barcode', '=', '123')
            ->orWhereIntegerInRaw('id', [1493, 1744, 2037,55])
            ->each(function ($product) {
                $uniqueId = uniqid(null);
                $product->update(['barcode' => $uniqueId]);
            });

        $this->info('Done');
    }
}
