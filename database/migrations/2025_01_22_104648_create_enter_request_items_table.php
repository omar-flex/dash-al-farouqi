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
        Schema::create('enter_request_items', function (Blueprint $table) {
            $table->id();
            $table->float('quantity')->default(1);
            $table->float('fixed_cost')->nullable();
            $table->float('gross_weight')->nullable();
            $table->float('net_weight')->nullable();
            $table->foreignId('location_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enter_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enter_request_items');
    }
};
