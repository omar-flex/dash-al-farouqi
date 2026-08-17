<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReconciliationAudit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'affected_warehouse_item_ids' => 'array',
            'resulting_ledger_ids' => 'array',
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
        ];
    }
}
