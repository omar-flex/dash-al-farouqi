<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->float('quantity')->default(1);
            $table->float('fixed_cost')->nullable();
            $table->float('gross_weight')->nullable();
            $table->float('net_weight')->nullable();
            $table->float('cpm')->nullable();
            $table->boolean('is_status')->default(1)->nullable();
            $table->foreignId('location_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level')->nullable();
            $table->string('pallet')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enter_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_items');
    }
};
