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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTraceabilityController extends Controller
{
    public function __invoke(Request $request, ProductTraceabilityReport $report): View|StreamedResponse
    {
        abort_unless($request->user()->can('warehouses_report'), 403);

        $query = $request->query() + ['to_date' => now()->toDateString()];
        $validator = Validator::make($query, [
            'product_id' => ['nullable', 'required_if:format,csv', 'integer'],
            'customer_id' => ['nullable', 'required_with:product_id,format', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:to_date'],
            'to_date' => ['required', 'date_format:Y-m-d'],
            'format' => ['nullable', 'in:csv'],
        ], [
            'customer_id.required_with' => __('product_traceability.customer_required'),
            'product_id.required_if' => __('product_traceability.product_required'),
        ]);
        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(route('warehouses.traceability'));
        }
        $filters = $validator->validated();

        $product = isset($filters['product_id'])
            ? $report->productsForCustomer((int) $filters['customer_id'])->with('UnitMeasure:id,name')->find($filters['product_id'])
            : null;

        if (isset($filters['product_id']) && ! $product) {
            throw ValidationException::withMessages([
                'product_id' => __('product_traceability.product_customer_mismatch'),
            ])->redirectTo(route('warehouses.traceability'));
        }

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

    public function products(Request $request, ProductTraceabilityReport $report): JsonResponse
    {
        abort_unless($request->user()->can('warehouses_report'), 403);

        $filters = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'q' => ['nullable', 'string', 'max:255'],
        ], ['customer_id.required' => __('product_traceability.customer_required')]);

        $term = $filters['q'] ?? '';
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $products = $report->productsForCustomer((int) $filters['customer_id'])
            ->with('UnitMeasure:id,name')
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
            $columns = [
                'product', 'barcode', 'unit', 'date', 'movement', 'inbound_reference', 'outbound_reference',
                'status', 'customer', 'warehouse', 'location', 'batch', 'qty', 'other_qty',
            ];
            fputcsv($output, array_map(fn (string $column) => __('product_traceability.'.$column), $columns), escape: '');

            $productFields = array_map($this->csvText(...), [$product->name, $product->barcode, $product->UnitMeasure?->name]);
            foreach ($period->cursor() as $row) {
                $movementFields = array_map($this->csvText(...), [
                    $row->movement_date,
                    __((int) $row->direction === 1 ? 'product_traceability.received' : 'product_traceability.released'),
                    $row->inbound_reference,
                    $row->outbound_reference,
                    __('product_traceability.statuses.'.$row->status_id),
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
