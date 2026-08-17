<?php

namespace App\Console\Commands;

use App\Services\Inventory\InventoryAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class InventoryAudit extends Command
{
    protected $signature = 'inventory:audit
        {--format=json : Output format: json or csv}
        {--output= : Write the report to a new file instead of stdout}
        {--force : Allow replacing an existing output file}
        {--warehouse-item=* : Limit checks to one or more warehouse item IDs}
        {--fail-on-issues : Return a non-zero exit code if any issue is found}';

    protected $description = 'Read-only audit of cached inventory balances, ledger links, and constraint prerequisites';

    public function handle(InventoryAuditor $auditor): int
    {
        try {
            $format = strtolower((string) $this->option('format'));
            if (! in_array($format, ['json', 'csv'], true)) {
                throw new InvalidArgumentException('The --format option must be json or csv.');
            }

            $ids = collect($this->option('warehouse-item'))
                ->map(function ($id): int {
                    if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                        throw new InvalidArgumentException('Every --warehouse-item value must be a positive integer.');
                    }

                    return (int) $id;
                })
                ->unique()
                ->values()
                ->all();

            $report = $auditor->run($ids === [] ? null : $ids);
            $contents = $format === 'json'
                ? $this->toJson($report)
                : $this->toCsv($report);

            $outputPath = $this->option('output');
            if ($outputPath !== null && $outputPath !== '') {
                $this->writeNewReport((string) $outputPath, $contents);
                $this->components->info(sprintf(
                    'Read-only inventory audit written to %s (%d issue(s)).',
                    $outputPath,
                    Arr::get($report, 'summary.issue_count', 0),
                ));
            } else {
                $this->output->write($contents);
                if (! str_ends_with($contents, PHP_EOL)) {
                    $this->newLine();
                }
            }

            if ($this->option('fail-on-issues') && Arr::get($report, 'summary.issue_count', 0) > 0) {
                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::INVALID;
        }
    }

    /** @param array<string, mixed> $report */
    private function toJson(array $report): string
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $json.PHP_EOL;
    }

    /** @param array<string, mixed> $report */
    private function toCsv(array $report): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new InvalidArgumentException('Could not create the temporary CSV stream.');
        }

        fputcsv($stream, [
            'code',
            'severity',
            'warehouse_item_id',
            'ledger_id',
            'outbound_id',
            'outbound_car_id',
            'expected',
            'actual',
            'message',
            'context',
        ]);

        foreach ($report['issues'] as $issue) {
            fputcsv($stream, [
                $issue['code'],
                $issue['severity'],
                $issue['warehouse_item_id'],
                $issue['ledger_id'],
                $issue['outbound_id'],
                $issue['outbound_car_id'],
                $this->csvValue($issue['expected']),
                $this->csvValue($issue['actual']),
                $issue['message'],
                $this->csvValue($issue['context']),
            ]);
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        if ($contents === false) {
            throw new InvalidArgumentException('Could not read the temporary CSV stream.');
        }

        return $contents;
    }

    private function csvValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value === null ? '' : (string) $value;
    }

    private function writeNewReport(string $path, string $contents): void
    {
        if (is_dir($path)) {
            throw new InvalidArgumentException('The --output path must be a file, not a directory.');
        }

        if (file_exists($path) && ! $this->option('force')) {
            throw new InvalidArgumentException('The output file already exists; pass --force to replace it.');
        }

        $directory = dirname($path);
        if (! is_dir($directory)) {
            throw new InvalidArgumentException('The output directory does not exist.');
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new InvalidArgumentException('Could not write the audit report.');
        }
    }
}
