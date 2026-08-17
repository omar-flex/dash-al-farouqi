<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reconciliation_audits', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id')->index();
            $table->string('idempotency_key', 64)->unique();
            $table->string('source_hash', 64);
            $table->string('source_file');
            $table->string('reference');
            $table->string('operation_id');
            $table->string('action', 20);
            $table->string('actor')->nullable();
            $table->unsignedBigInteger('source_ledger_id')->nullable()->index();
            $table->json('resulting_ledger_ids')->nullable();
            $table->json('affected_warehouse_item_ids');
            $table->text('reason');
            $table->json('before');
            $table->json('after');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reference', 'operation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reconciliation_audits');
    }
};
