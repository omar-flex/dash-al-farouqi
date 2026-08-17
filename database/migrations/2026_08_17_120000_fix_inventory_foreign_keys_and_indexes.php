<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalidOutboundStatuses = DB::table('outbounds as outbound')
            ->leftJoin('outbound_statuses as status', 'status.id', '=', 'outbound.status_id')
            ->where(function ($query): void {
                $query->whereNull('outbound.status_id')->orWhereNull('status.id');
            })
            ->count();

        if ($invalidOutboundStatuses > 0) {
            throw new RuntimeException(
                "Cannot repair the outbound status foreign key: {$invalidOutboundStatuses} outbound row(s) "
                .'have a null or invalid status. Run inventory:audit, reconcile the documented rows, '
                .'and retry after the invalid_outbound_status issue count is zero.',
            );
        }

        Schema::table('outbounds', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->foreign('status_id')
                ->references('id')
                ->on('outbound_statuses')
                ->restrictOnDelete();
            $table->index(['status_id', 'date'], 'outbounds_inventory_status_date_index');
        });

        Schema::table('outbound_warehouse_items', function (Blueprint $table) {
            $table->index(
                ['warehouse_item_id', 'outbound_id'],
                'outbound_items_inventory_ledger_index'
            );
        });

        Schema::table('enter_requests', function (Blueprint $table) {
            $table->index(
                ['status_id', 'date', 'customer_id'],
                'enter_requests_inventory_report_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('enter_requests', function (Blueprint $table) {
            $table->dropIndex('enter_requests_inventory_report_index');
        });

        Schema::table('outbound_warehouse_items', function (Blueprint $table) {
            $table->dropIndex('outbound_items_inventory_ledger_index');
        });

        Schema::table('outbounds', function (Blueprint $table) {
            $table->dropIndex('outbounds_inventory_status_date_index');
            $table->dropForeign(['status_id']);
            $table->foreign('status_id')
                ->references('id')
                ->on('enter_request_statuses')
                ->nullOnDelete();
        });
    }
};
