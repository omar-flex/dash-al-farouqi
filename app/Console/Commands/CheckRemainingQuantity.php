<?php

namespace App\Console\Commands;

use App\Services\Inventory\InventoryAuditor;
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
    protected $description = 'Deprecated read-only alias for inventory:audit';

    /**
     * Execute the console command.
     */
    public function handle(InventoryAuditor $auditor): int
    {
        $this->components->warn(
            'This command is deprecated and read-only. Use inventory:audit --format=json instead.',
        );

        $report = $auditor->run();
        $this->line(json_encode([
            'read_only' => true,
            'summary' => $report['summary'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
