<?php

namespace App\Console\Commands;

use App\Models\EnterRequest;
use App\Models\EnterRequestFile;
use App\Models\ManifestFile;
use App\Models\ManifestType;
use App\Models\Outbound;
use App\Models\OutboundFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:file-transfer';

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
        /* ManifestFile::where(['type' => ManifestType::OUTBOUND])->get()
             ->each(function ($manifestFile) {
                 $outbound = Outbound::where(['id' => $manifestFile->manifest_id])->first();
                 if ($outbound) {
                     $outbound_number = Str::replace('/', '-', $outbound->outbound_number);
                     $oldPath = $manifestFile->path;
                     $newFileName = $outbound_number . '_' . uniqid() . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                     $newPath = 'Outbounds/' . $newFileName;
                     $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                     if (Storage::exists($oldPath)) {
                         Storage::copy($oldPath, $newPath);
                         OutboundFile::create([
                             'filename' => $outbound_number,
                             'path' => $newPath,
                             'extension' => $extension,
                             'outbound_id' => $outbound->id,
                             'user_id' => $manifestFile->user_id,
                         ]);
                     }
                 }

             });*/

        ManifestFile::where(['type' => ManifestType::INBOUND, 'id' => 44])->get()
            ->each(function ($manifestFile) {
                $enterRequest = EnterRequest::where(['id' => $manifestFile->manifest_id])->first();
                dd($enterRequest);
                if ($enterRequest) {
                    $bound_number = Str::replace('/', '-', $enterRequest->bound_number);
                    $oldPath = $manifestFile->path;
                    $newFileName = $bound_number . '_' . uniqid() . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                    $newPath = 'Inbounds/' . $newFileName;
                    $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                    if (Storage::exists($oldPath)) {
                        Storage::copy($oldPath, $newPath);
                        EnterRequestFile::create([
                            'filename' => Str::replace('/', '-', $enterRequest->bound_number),
                            'path' => $newPath,
                            'extension' => $extension,
                            'enter_request_id' => $enterRequest->id,
                            'user_id' => $manifestFile->user_id,
                        ]);
                    }
                }

            });
    }
}
