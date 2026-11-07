<?php

namespace App\Http\Controllers\WarehouseManagement;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\ProductTraceabilityReport;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTraceabilityController extends Controller
{
    public function __invoke(Request $request, ProductTraceabilityReport $report): View|StreamedResponse
    {
        abort_unless($request->user()->can('warehouses_report'), 403);

        $request->mergeIfMissing(['to_date' => now()->toDateString()]);
        $filters = $request->validate([
            'product_id' => ['nullable', 'required_if:format,csv', 'integer', 'exists:products,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:to_date'],
            'to_date' => ['required', 'date_format:Y-m-d'],
            'format' => ['nullable', 'in:csv'],
        ]);

        $product = isset($filters['product_id'])
            ? Product::with('UnitMeasure:id,name')->findOrFail($filters['product_id'])
            : null;
        $summary = null;
        $rows = null;

        if ($product) {
            $movements = $report->movements($filters);
            $fromDate = $filters['from_date'] ?? null;
            $period = $report->inPeriod($movements, $fromDate);

            if (($filters['format'] ?? null) === 'csv') {
                return $this->export($period, $product, $filters['to_date']);
            }

            $summary = $report->summary($movements, $fromDate);
            $rows = $period->paginate(25)->appends($filters);
        }

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $warehouses = Warehouse::orderBy('code')->get(['id', 'code']);

        return view('pages.reports.product-traceability', compact('filters', 'product', 'summary', 'rows', 'customers', 'warehouses'));
    }

    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('warehouses_report'), 403);

        $term = $request->validate(['q' => ['nullable', 'string', 'max:255']])['q'] ?? '';
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $products = Product::with('UnitMeasure:id,name')
            ->where(function ($query) use ($pattern): void {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("barcode LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 ELSE 1 END', [$term])
            ->orderBy('name')
            ->orderBy('id')
            ->simplePaginate(25, ['id', 'name', 'barcode', 'unit_measure_id']);

        return response()->json([
            'results' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'text' => implode(' · ', array_filter([
                    $product->name,
                    filled($product->barcode) ? $product->barcode : '#'.$product->id,
                    $product->UnitMeasure?->name,
                ])),
            ])->values(),
            'pagination' => ['more' => $products->hasMorePages()],
        ]);
    }

    private function export(Builder $period, Product $product, string $toDate): StreamedResponse
    {
        return response()->streamDownload(function () use ($period, $product): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'المنتج', 'الباركود', 'وحدة القياس', 'التاريخ', 'الحركة', 'مرجع الوارد', 'مرجع الصادر',
                'الحالة', 'العميل', 'المستودع', 'الموقع', 'الدفعة', 'Qty', 'Other Qty',
            ], escape: '');

            $productFields = array_map($this->csvText(...), [$product->name, $product->barcode, $product->UnitMeasure?->name]);
            foreach ($period->cursor() as $row) {
                $movementFields = array_map($this->csvText(...), [
                    $row->movement_date,
                    (int) $row->direction === 1 ? 'وارد' : 'صادر',
                    $row->inbound_reference,
                    $row->outbound_reference,
                    $row->status_name,
                    $row->customer_name,
                    $row->warehouse_code,
                    ProductTraceabilityReport::location($row),
                    $row->batch_number,
                ]);
                fputcsv($output, [...$productFields, ...$movementFields, $row->quantity, $row->other_quantity], escape: '');
            }
            fclose($output);
        }, "product-traceability-{$product->id}-{$toDate}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvText(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\x00-\x20]*[=+\-@＝＋－＠]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
