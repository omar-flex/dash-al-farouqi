<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutboundProductsRequest extends FormRequest
{
    use DefaultRequest;

    protected function prepareForValidation(): void
    {
        $action = $this->input('action');

        if (! $action) {
            $action = $this->input('button_clicked') === 'btn-submit' ? 'submit' : 'draft';
        }

        if (! $this->has('items')) {
            $warehouseItemIds = $this->legacyArray('warehouse_item_ids');
            $movementIds = $this->legacyArray('items_id');
            $quantities = $this->legacyArray('quantities');
            $otherQuantities = $this->legacyArray('other_quantities');
            $carIds = $this->legacyArray('cars_id');

            $itemCount = max(
                count($warehouseItemIds),
                count($movementIds),
                count($quantities),
                count($otherQuantities),
                count($carIds),
            );

            $items = [];
            for ($index = 0; $index < $itemCount; $index++) {
                $items[] = [
                    'id' => $movementIds[$index] ?? null,
                    'warehouse_item_id' => $warehouseItemIds[$index] ?? null,
                    'outbound_car_id' => $carIds[$index] ?? null,
                    'quantity' => $quantities[$index] ?? null,
                    'other_quantity' => $otherQuantities[$index] ?? null,
                ];
            }

            $this->merge(['items' => $items]);
        }

        $this->merge(['action' => $action]);
    }

    private function legacyArray(string $key): array
    {
        $value = $this->input($key, []);

        return is_array($value) ? $value : [];
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.warehouse_item_id' => ['required', 'integer', 'exists:warehouse_items,id'],
            'items.*.outbound_car_id' => ['required', 'integer', 'exists:outbound_cars,id'],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'gte:0.001'],
            'items.*.other_quantity' => ['nullable', 'numeric', 'decimal:0,3', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.warehouse_item_id.required' => 'Warehouse item is required.',
            'items.*.outbound_car_id.required' => 'Car is required.',
            'items.*.quantity.required' => 'Quantity is required.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $pairs = [];
            $movementIds = [];

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $movementId = $item['id'] ?? null;
                if ($movementId && in_array((string) $movementId, $movementIds, true)) {
                    $validator->errors()->add(
                        "items.$index.id",
                        'The same outbound item cannot be submitted more than once.'
                    );
                } elseif ($movementId) {
                    $movementIds[] = (string) $movementId;
                }

                $warehouseItemId = $item['warehouse_item_id'] ?? null;
                $carId = $item['outbound_car_id'] ?? null;
                if (! $warehouseItemId || ! $carId) {
                    continue;
                }

                $pair = $warehouseItemId.'-'.$carId;
                if (in_array($pair, $pairs, true)) {
                    $validator->errors()->add(
                        "items.$index.warehouse_item_id",
                        'Duplicate warehouse item-car pairs are not allowed.'
                    );
                } else {
                    $pairs[] = $pair;
                }
            }
        });
    }
}
