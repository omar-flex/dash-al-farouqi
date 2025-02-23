<?php

namespace App\Console\Commands;

use App\Models\ManifestFile;
use App\Models\ManifestType;
use App\Models\Outbound;
use Illuminate\Console\Command;

class FixFileIssuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-file-issues-command';

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
        $manifestFiles = ManifestFile::where(['type' => ManifestType::OUTBOUND])->get();
        foreach ($manifestFiles as $manifestFile) {
            $outbound = Outbound::where(['id' => $manifestFile->id])->first();
            if (!$outbound) {
                $manifestFile->update(['type' => ManifestType::INBOUND]);
            }
        }

        $this->info('Done');
    }
}
