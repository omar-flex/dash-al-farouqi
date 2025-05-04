<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outbound_warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->float('quantity')->nullable();
            $table->string('location')->nullable();
            $table->float('custom_value')->nullable();
            $table->float('gross_weight')->nullable();
            $table->float('net_weight')->nullable();
            $table->float('cpm')->nullable();
            $table->float('cpm_capacity')->nullable();
            $table->boolean('is_status')->default(0)->nullable();
            $table->foreignId('warehouse_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outbound_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_warehouse_items');
    }
};
