<?php

namespace App\Console\Commands;

use App\Services\Inventory\InventoryReconciler;
use App\Services\Inventory\InventoryReconciliationException;
use Illuminate\Console\Command;
use JsonException;

class InventoryReconcile extends Command
{
    protected $signature = 'inventory:reconcile
        {file : Path to the reviewed schema-version-1 JSON reconciliation file}
        {--apply : Apply atomically; without this flag the command is read-only}
        {--actor= : Operator identifier stored in the immutable audit log}
        {--output= : Write the JSON result to a new file instead of stdout}
        {--force : Allow replacing an existing output file}';

    protected $description = 'Dry-run or atomically apply documented inventory ledger reconciliation operations';

    public function handle(InventoryReconciler $reconciler): int
    {
        $apply = (bool) $this->option('apply');
        $actor = $this->normaliseActor($this->option('actor'));

        try {
            $result = $apply
                ? $reconciler->apply((string) $this->argument('file'), $actor)
                : $reconciler->preview((string) $this->argument('file'), $actor);

            $json = json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ).PHP_EOL;
            $this->writeResult($json);

            return self::SUCCESS;
        } catch (InventoryReconciliationException $exception) {
            $payload = [
                'mode' => $apply ? 'apply' : 'dry-run',
                'valid' => false,
                'applied' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ];

            try {
                $json = json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ).PHP_EOL;
                // Always emit failures to stderr/stdout. In particular, do not
                // retry a bad --output path while handling that same failure.
                $this->output->write($json);
            } catch (JsonException $jsonException) {
                $this->components->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }

    private function normaliseActor(mixed $actor): string
    {
        if (is_string($actor) && trim($actor) !== '') {
            return trim($actor);
        }

        $environmentActor = getenv('USER');

        return is_string($environmentActor) && $environmentActor !== ''
            ? $environmentActor
            : 'console';
    }

    private function writeResult(string $json): void
    {
        $path = $this->option('output');
        if ($path === null || $path === '') {
            $this->output->write($json);

            return;
        }

        $path = (string) $path;
        if (is_dir($path)) {
            throw new InventoryReconciliationException('The --output path must be a file, not a directory.');
        }
        if (file_exists($path) && ! $this->option('force')) {
            throw new InventoryReconciliationException(
                'The output file already exists; pass --force to replace it.',
            );
        }
        if (! is_dir(dirname($path))) {
            throw new InventoryReconciliationException('The output directory does not exist.');
        }
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new InventoryReconciliationException('Could not write the reconciliation result.');
        }
    }
}
